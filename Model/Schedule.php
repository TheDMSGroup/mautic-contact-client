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

use MauticPlugin\MauticContactClientBundle\Exception\ContactClientRetryException;
use MauticPlugin\MauticContactClientBundle\Entity\ContactClient;
use MauticPlugin\MauticContactClientBundle\Entity\Stat;
use Symfony\Component\DependencyInjection\Container;

/**
 * Class Schedule
 * @package MauticPlugin\MauticContactClientBundle\Model
 */
class Schedule
{

    /**
     * @var \DateTimeZone $timezone
     */
    protected $timezone;

    /** @var \Datetime $now */
    protected $now;

    /** @var ContactClient $contactClient */
    protected $contactClient;

    /** @var Container */
    protected $container;

    /**
     * Schedule constructor.
     * @param ContactClient $contactClient
     * @param $container
     */
    public function __construct(ContactClient $contactClient, $container)
    {
        $this->contactClient = $contactClient;
        $this->container = $container;
        $this->setTimezone();
    }

    /**
     * Set Client timezone, defaulting to Mautic or System as is relevant.
     */
    private function setTimezone()
    {
        if (!$this->timezone) {
            $timezone = $this->contactClient->getScheduleTimezone();
            if (!$timezone) {
                $timezone = $this->container->get('mautic.helper.core_parameters')->getParameter(
                    'default_timezone'
                ) ?: date_default_timezone_get();
            }
            $this->timezone = new \DateTimeZone($timezone);
        }
    }

    /**
     * @param ContactClient $contactClient
     * @throws ContactClientRetryException
     * @throws \Exception
     */
    public function evaluateHours(ContactClient $contactClient)
    {
        $hours = $this->jsonDecodeArray($contactClient->getScheduleHours() );
        if (is_array($hours) && $hours) {
            $now = $this->getNow();
            $timezone = $this->getTimezone();

            $day = intval($now->format('w'));
            if (isset($hours[$day])) {
                if (
                    isset($hours[$day]->isActive)
                    && !$hours[$day]->isActive
                ) {
                    throw new ContactClientRetryException(
                        'This contact client does not allow contacts on a '.$now->format('l').'.'
                    );
                } else {
                    $timeFrom = !empty($hours[$day]->timeFrom) ? $hours[$day]->timeFrom : '00:00';
                    $timeTill = !empty($hours[$day]->timeTill) ? $hours[$day]->timeTill : '23:59';
                    $startDate = \DateTime::createFromFormat('h:i', $timeFrom, $timezone);
                    $endDate = \DateTime::createFromFormat('h:i', $timeTill, $timezone);
                    if (!($now > $startDate && $now < $endDate)) {
                        throw new ContactClientRetryException(
                            'This contact client does not allow contacts during this time of day.',
                            0,
                            null,
                            Stat::TYPE_SCHEDULE
                        );
                    }
                }
            }
        }
    }

    /**
     * @param $string
     * @return mixed
     * @throws \Exception
     */
    private function jsonDecodeArray($string)
    {
        $array = json_decode($string ?: '[]');
        $jsonError = null;
        switch (json_last_error()) {
            case JSON_ERROR_NONE:
                break;
            case JSON_ERROR_DEPTH:
                $jsonError = 'Maximum stack depth exceeded';
                break;
            case JSON_ERROR_STATE_MISMATCH:
                $jsonError = 'Underflow or the modes mismatch';
                break;
            case JSON_ERROR_CTRL_CHAR:
                $jsonError = 'Unexpected control character found';
                break;
            case JSON_ERROR_SYNTAX:
                $jsonError = 'Syntax error, malformed JSON';
                break;
            case JSON_ERROR_UTF8:
                $jsonError = 'Malformed UTF-8 characters, possibly incorrectly encoded';
                break;
            default:
                $jsonError = 'Unknown error';
                break;
        }
        if ($jsonError) {
            throw new \Exception('Schedule JSON is invalid: '.$jsonError);
        }
        if (!$array || !is_array($array)) {
            throw new \Exception('Schedule is invalid.');
        }

        return $array;
    }

    /**
     * @return \Datetime
     */
    private function getNow()
    {
        if (!$this->now) {
            $now = new \Datetime();
            $now->setTimezone($this->timezone);
            $this->now = $now;
        }

        return $this->now;
    }

    /**
     * @return \DateTimeZone
     */
    private function getTimezone()
    {
        return $this->timezone;
    }

    /**
     * @param ContactClient $contactClient
     * @throws ContactClientRetryException
     * @throws \Exception
     */
    public function evaluateExclusions(ContactClient $contactClient)
    {
        // Check dates of exclusion (if there are any).
        $exclusions = $this->jsonDecodeArray($contactClient->getScheduleExclusions() );
        if (is_array($exclusions) && $exclusions) {
            $now = $this->getNow();

            // Fastest way to compare dates is by string.
            $todaysDateString = $now->format('Y-m-d');
            foreach ($exclusions as $exclusion) {
                if (!empty($exclusion->value)) {
                    $dateString = trim(str_ireplace('yyyy-', '', $exclusion->value));
                    $segments = explode('-', $dateString);
                    $segmentCount = count($segments);
                    if ($segmentCount == 3) {
                        $year = !empty($segments[0]) ? str_pad($segments[0], 4, '0', STR_PAD_LEFT) : $now->format('Y');
                        $month = !empty($segments[1]) ? str_pad($segments[1], 2, '0', STR_PAD_LEFT) : $now->format('m');
                        $day = !empty($segments[2]) ? str_pad($segments[2], 2, '0', STR_PAD_LEFT) : $now->format('d');
                    } elseif ($segmentCount == 2) {
                        $year = $now->format('Y');
                        $month = !empty($segments[0]) ? str_pad($segments[0], 2, '0', STR_PAD_LEFT) : $now->format('m');
                        $day = !empty($segments[1]) ? str_pad($segments[1], 2, '0', STR_PAD_LEFT) : $now->format('d');
                    } else {
                        continue;
                    }
                    $dateString = $year.'-'.$month.'-'.$day;
                    if ($dateString == $todaysDateString) {
                        throw new ContactClientRetryException(
                            'This contact client does not allow contacts on the date '.$dateString.'.',
                            0,
                            null,
                            Stat::TYPE_SCHEDULE
                        );
                    }
                }
            }
        }
    }
}