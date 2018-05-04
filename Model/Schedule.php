<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticContactClientBundle\Model;

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

    /** @var \Datetime $now */
    protected $now;

    /** @var ContactClient $contactClient */
    protected $contactClient;

    /** @var \Symfony\Component\DependencyInjection\Container */
    protected $container;

    /** @var array */
    protected $scheduleHours;

    /** @var int */
    protected $nextOpeningDay;

    /**
     * Schedule constructor.
     *
     * We need to be container aware, but don't need all the meat of AbstractCommonModel.
     *
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->now       = new \DateTime();
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
    public function reset($exclusions = ['container'])
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
     * @param int  $fileRate Maximum number of files to build per day.
     * @param int  $seekDays Maximum number of days forward to seek for an opening.
     *
     * @return \DateTime|null
     */
    public function nextOpening($fileRate, $seekDays)
    {
        // Seek up to a year in the future for an opening date.
        if (!isset($this->nextOpeningDay)) {
            $this->nextOpeningDay = 0;
        }
        for ($day = $this->nextOpeningDay; $day < $seekDays; ++$day) {
            // Use noon to evaluate days to not worry about timezones.
            $this->now = new \DateTime('noon +'.$day.' day');
            try {
                $start = $end = $this->now;
                $hours = $this->evaluateDay();
                $this->evaluateExclusions();
                if (0 == $day) {
                    // Is *now* a good time?
                    $this->evaluateTime();
                }

                // Check if there is time left in the day.
                $timeTill = !empty($hours->timeTill) ? $hours->timeTill : '23:59';
                $end->setTimezone($this->timezone);
                $end->modify($timeTill.':59');
                // Give breathing room.
                $end->modify('+1 minute');
                if (0 == $day && new \DateTime() >= $end) {
                    // No time left today, try tomorrow.
                    continue;
                }

                $timeFrom = !empty($hours->timeFrom) ? $hours->timeFrom : '00:00';
                $start->setTimezone($this->timezone);
                $start->modify($timeFrom);

                // Check if there is an open slot today given the range (file limit).
                if ($fileRate) {
                    // Ensure we have not exceeded the amount for this day.
                    $fileCount = $this->evaluateFileRate($fileRate);

                    // Spread the rate over the day, by setting the start time to the next segment of time.
                    if ($fileRate > 1 && $fileCount > 1) {
                        $daySeconds   = $end->format('U') - $start->format('U');
                        $rangeSeconds = $daySeconds / ($fileRate - 1);
                        $addSeconds   = $rangeSeconds * ($fileCount - 1);
                        $start->modify('+'.$addSeconds.' seconds');
                    }
                }
                $this->nextOpeningDay = $day + 1;

                return $start;
                break;
            } catch (\Exception $e) {
                if ($e instanceof ContactClientException) {
                    // Expected.
                } else {
                    throw $e;
                }
            }
        }

        return null;
    }

    /**
     * @return array|mixed
     *
     * @throws ContactClientException
     */
    public function evaluateDay()
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
                } else {
                    return $hours[$day];
                }
            }
        }

        return null;
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
     * @return array|null
     *
     * @throws ContactClientException
     */
    public function evaluateTime()
    {
        $hours  = $this->getScheduleHours();
        $result = null;
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
                    $result = [$startDate, $endDate];
                }
            }
        }

        return $result;
    }

    /**
     * Test if we can send/build another file for the day in question.
     *
     * @param int $fileRate
     *
     * @return bool|string
     * @throws ContactClientException
     */
    private function evaluateFileRate($fileRate = 1)
    {
        $repo  = $this->getFileRepository();
        $fileCount = $repo->getCountByDate($this->now, $this->contactClient->getId());
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
        $em = $this->container->get('doctrine.orm.default_entity_manager');
        return $em->getRepository('MauticContactClientBundle:File');
    }

    /**
     * @return \Datetime
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
            $now = new \Datetime();
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
                $timezone = $this->container->get('mautic.helper.core_parameters')->getParameter(
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
