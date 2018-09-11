<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Digital Media Solutions, LLC
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticContactClientBundle\Model;

use Doctrine\ORM\EntityManager;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use MauticPlugin\MauticContactClientBundle\Entity\ContactClient;
use MauticPlugin\MauticContactClientBundle\Entity\Stat;
use MauticPlugin\MauticContactClientBundle\Exception\ContactClientException;
use MauticPlugin\MauticContactClientBundle\Helper\JSONHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class Schedule.
 */
class Schedule
{
    /** @var \DateTimeZone */
    protected $timezone;

    /** @var \DateTime $now */
    protected $now;

    /** @var ContactClient $contactClient */
    protected $contactClient;

    /** @var \Symfony\Component\DependencyInjection\Container */
    protected $container;

    /** @var array */
    protected $scheduleHours;

    /** @var EntityManager */
    protected $em;

    /** @var CoreParametersHelper */
    protected $coreParametersHelper;

    /**
     * Schedule constructor.
     *
     * We need to be container aware, but don't need all the meat of AbstractCommonModel.
     *
     * @param ContainerInterface $container
     */
    public function __construct(EntityManager $em, CoreParametersHelper $coreParametersHelper)
    {
        $this->em                   = $em;
        $this->coreParametersHelper = $coreParametersHelper;
        $this->now                  = new \DateTime();
    }

    /**
     * @param ContactClient $contactClient
     *
     * @return $this
     */
    public function setContactClient(ContactClient $contactClient)
    {
        $this->contactClient = $contactClient;

        return $this;
    }

    /**
     * Reset local class variables.
     *
     * @param array $exclusions optional array of local variables to keep current values
     *
     * @return $this
     */
    public function reset($exclusions = ['em', 'coreParametersHelper'])
    {
        foreach (array_diff_key(
                     get_class_vars(get_class($this)),
                     array_flip($exclusions)
                 ) as $name => $default) {
            $this->$name = $default;
        }
        $this->setNow();

        return $this;
    }

    /**
     * Given the hours of operation, timezone and excluded dates of the client...
     * Find the next appropriate time to send them contacts.
     *
     * @param int $fileRate maximum number of files to build per day
     * @param int $seekDays maximum number of days forward to seek for an opening
     *
     * @return array
     *
     * @throws \Exception
     */
    public function nextOpening($fileRate, $seekDays)
    {
        // During this evaluation the $this->now property will shift forward in time till the next opening.
        $realNow = new \DateTime();

        for ($day = 0; $day < $seekDays; ++$day) {
            if (0 === $day) {
                // Current day.
                $this->now = clone $realNow;
            } else {
                // Future days. Use noon to begin to evaluate days in the future while avoiding timezone concerns.
                $this->now = new \DateTime('noon +'.$day.' day');
            }
            try {
                // Initialize the range (will expand as appropriate later).
                $start = clone $this->now;
                $end   = clone $this->now;

                // Evaluate that the client is open this day of the week.
                $hours = $this->evaluateDay(true);

                // Evaluate that the client isn't closed by an excluded date rule.
                $this->evaluateExclusions();

                // Push the end time to the correct time for this client's hours.
                $timeTill = !empty($hours->timeTill) ? $hours->timeTill : '23:59';
                $end->setTimezone($this->timezone);
                $end->modify($timeTill.':59');

                // Current day: Evaluate that there is still time today to send.
                if (0 == $day && $realNow > $end) {
                    // Continue to the next day.
                    continue;
                }

                // Pull the start time to the correct time for this day and schedule.
                $timeFrom = !empty($hours->timeFrom) ? $hours->timeFrom : '00:00';
                $start->setTimezone($this->timezone);
                $start->modify($timeFrom);

                // Evaluate if we have exceeded allowed file count for this day.
                $fileCount = $this->evaluateFileRate($fileRate);

                // If we have already built a file in this day and can send more...
                if ($fileCount > 0 && $fileRate > 1) {
                    // Push the start time to the next available slot in this day.
                    $daySeconds = $end->format('U') - $start->format('U');
                    if ('00:00' === $timeFrom && '23:59' === $timeTill) {
                        // Avoid sending 2 files at midnight.
                        $segmentSeconds = intval($daySeconds / $fileRate);
                    } else {
                        // Send at opening and closing times, spreading the rest of the day evenly.
                        $segmentSeconds = intval($daySeconds / ($fileRate - 1));
                    }
                    // Push start time to the next segment.
                    $start->modify('+'.($segmentSeconds * $fileCount).' seconds');
                }

                // Start time should not be in the past.
                if (0 === $day && $start < $realNow) {
                    // Must be done after rate has been applied.
                    $start = $realNow;
                }

                // Return the next appropriate window to send contacts.
                return [$start, $end];
                break;
            } catch (\Exception $e) {
                if ($e instanceof ContactClientException) {
                    // Expected.
                } else {
                    throw $e;
                }
            }
        }

        return [null, null];
    }

    /**
     * @param bool $returnHours
     *
     * @return $this|array
     *
     * @throws ContactClientException
     */
    public function evaluateDay($returnHours = false)
    {
        $hours = $this->getScheduleHours();

        if ($hours) {
            $day = intval($this->now->format('N')) - 1;
            if (isset($hours[$day])) {
                if (
                    isset($hours[$day]->isActive)
                    && !$hours[$day]->isActive
                ) {
                    throw new ContactClientException(
                        'This contact client does not allow contacts on a '.$this->now->format('l').'.',
                        0,
                        null,
                        Stat::TYPE_SCHEDULE
                    );
                } elseif ($returnHours) {
                    return $hours[$day];
                }
            }
        }

        return $this;
    }

    /**
     * @return array|mixed
     *
     * @throws \Exception
     */
    private function getScheduleHours()
    {
        if (!$this->scheduleHours) {
            $jsonHelper          = new JSONHelper();
            $hoursString         = $this->contactClient->getScheduleHours();
            $this->scheduleHours = $jsonHelper->decodeArray($hoursString, 'ScheduleHours');
        }

        return $this->scheduleHours;
    }

    /**
     * @return $this
     *
     * @throws ContactClientException
     */
    public function evaluateExclusions()
    {
        // Check dates of exclusion (if there are any).
        $jsonHelper       = new JSONHelper();
        $exclusionsString = $this->contactClient->getScheduleExclusions();
        $exclusions       = $jsonHelper->decodeArray($exclusionsString, 'ScheduleExclusions');
        if ($exclusions) {
            // Fastest way to compare dates is by string.
            $todaysDateString = $this->now->format('Y-m-d');
            foreach ($exclusions as $exclusion) {
                if (!empty($exclusion->value)) {
                    $dateString   = trim(str_ireplace('yyyy-', '', $exclusion->value));
                    $segments     = explode('-', $dateString);
                    $segmentCount = count($segments);
                    if (3 == $segmentCount) {
                        $year  = !empty($segments[0]) ? str_pad(
                            $segments[0],
                            4,
                            '0',
                            STR_PAD_LEFT
                        ) : $this->now->format(
                            'Y'
                        );
                        $month = !empty($segments[1]) ? str_pad(
                            $segments[1],
                            2,
                            '0',
                            STR_PAD_LEFT
                        ) : $this->now->format('m');
                        $day   = !empty($segments[2]) ? str_pad(
                            $segments[2],
                            2,
                            '0',
                            STR_PAD_LEFT
                        ) : $this->now->format('d');
                    } elseif (2 == $segmentCount) {
                        $year  = $this->now->format('Y');
                        $month = !empty($segments[0]) ? str_pad(
                            $segments[0],
                            2,
                            '0',
                            STR_PAD_LEFT
                        ) : $this->now->format('m');
                        $day   = !empty($segments[1]) ? str_pad(
                            $segments[1],
                            2,
                            '0',
                            STR_PAD_LEFT
                        ) : $this->now->format('d');
                    } else {
                        continue;
                    }
                    $dateString = $year.'-'.$month.'-'.$day;
                    if ($dateString == $todaysDateString) {
                        throw new ContactClientException(
                            'This contact client does not allow contacts on the date '.$dateString.'.',
                            0,
                            null,
                            Stat::TYPE_SCHEDULE
                        );
                    }
                }
            }
        }

        return $this;
    }

    /**
     * @param bool $returnRange
     *
     * @return $this|array
     *
     * @throws ContactClientException
     */
    public function evaluateTime($returnRange = false)
    {
        $hours = $this->getScheduleHours();
        if ($hours) {
            $day = intval($this->now->format('N')) - 1;
            if (isset($hours[$day])) {
                if (
                    isset($hours[$day]->isActive)
                    && !$hours[$day]->isActive
                ) {
                    // No need to trigger an exception because we are only evaluating the time.
                } else {
                    $timeFrom  = !empty($hours[$day]->timeFrom) ? $hours[$day]->timeFrom : '00:00';
                    $timeTill  = !empty($hours[$day]->timeTill) ? $hours[$day]->timeTill : '23:59';
                    $startDate = \DateTime::createFromFormat('H:i', $timeFrom, $this->timezone);
                    $endDate   = \DateTime::createFromFormat('H:i', $timeTill, $this->timezone);
                    if (!($this->now > $startDate && $this->now < $endDate)) {
                        throw new ContactClientException(
                            'This contact client does not allow contacts during this time of day.',
                            0,
                            null,
                            Stat::TYPE_SCHEDULE
                        );
                    }
                    if ($returnRange) {
                        return [$startDate, $endDate];
                    }
                }
            }
        }

        return $this;
    }

    /**
     * Test if we can send/build another file for the day in question.
     *
     * @param int $fileRate
     *
     * @return int
     *
     * @throws ContactClientException
     */
    private function evaluateFileRate($fileRate = 1)
    {
        $date = clone $this->now;
        $date->setTimezone($this->timezone);
        $repo      = $this->getFileRepository();
        $fileCount = $repo->getCountByDate($date, $this->contactClient->getId());

        if ($fileCount >= $fileRate) {
            throw new ContactClientException(
                'This client has reached the maximum number of files they can receive per day.',
                0,
                null,
                Stat::TYPE_SCHEDULE
            );
        }

        return $fileCount;
    }

    /**
     * @return \MauticPlugin\MauticContactClientBundle\Entity\FileRepository
     */
    public function getFileRepository()
    {
        return $this->em->getRepository('MauticContactClientBundle:File');
    }

    /**
     * @return \DateTime
     */
    public function getNow()
    {
        return $this->now;
    }

    /**
     * @param \DateTime $now
     *
     * @return $this
     */
    public function setNow(\DateTime $now = null)
    {
        if (!$now) {
            $now = new \DateTime();
        }

        $this->now = $now;

        return $this;
    }

    /**
     * @return \DateTimeZone
     */
    public function getTimezone()
    {
        return $this->timezone;
    }

    /**
     * Set Client timezone, defaulting to Mautic or System as is relevant.
     *
     * @param \DateTimeZone $timezone
     *
     * @return $this
     */
    public function setTimezone(\DateTimeZone $timezone = null)
    {
        if (!$timezone) {
            $timezone = $this->contactClient->getScheduleTimezone();
            if (!$timezone) {
                $timezone = $this->coreParametersHelper->getParameter(
                    'default_timezone'
                );
                $timezone = !empty($timezone) ? $timezone : date_default_timezone_get();
            }
            $timezone = new \DateTimeZone($timezone);
        }
        $this->timezone = $timezone;

        return $this;
    }
}
