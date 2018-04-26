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
        $this->now = new \DateTime();
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
     * @return $this
     * @throws ContactClientException
     */
    public function evaluateHours()
    {
        $jsonHelper  = new JSONHelper();
        $hoursString = $this->contactClient->getScheduleHours();
        $hours       = $jsonHelper->decodeArray($hoursString, 'ScheduleHours');

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
                }
            }
        }

        return $this;
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

    /**
     * @return $this
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
}
