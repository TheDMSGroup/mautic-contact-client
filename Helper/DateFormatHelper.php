<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticContactClientBundle\Helper;

/**
 * Class DateFormatHelper.
 */
class DateFormatHelper
{
    /** @var array All standard date formats, plus any we wish to standardize. */
    protected $formats = [
        'atom'       => 'Y-m-d\TH:i:sP',
        'cookie'     => 'l, d-M-y H:i:s T',
        'iso8601'    => 'Y-m-d\TH:i:sO',
        'rfc822'     => 'D, d M y H:i:s O',
        'rfc850'     => 'l, d-M-y H:i:s T',
        'rfc1036'    => 'D, d M y H:i:s O',
        'rfc1123'    => 'D, d M Y H:i:s O',
        'rfc2822'    => 'D, d M Y H:i:s O',
        'rfc3339'    => 'Y-m-d\TH:i:sP',
        'rfc3339ext' => 'Y-m-d\TH:i:s.vP',
        'rfc7231'    => 'D, d M Y H:i:s \G\M\T',
        'rss'        => 'D, d M Y H:i:s O',
        'w3c'        => 'Y-m-d\TH:i:sP',
        'short'      => 'd/m/Y',
    ];

    /** @var string All allowed single-character date/time formats. */
    protected $formatSingle = 'dDjlNSwzWFmMntLoYyaABgGhHisuveIOPTZcrU';

    protected $formatInterval = [
        'seconds' => 'PT1S',
        'minutes' => 'PT1M',
        'hours'   => 'PT1H',
        'days'    => 'P1D',
        'months'  => 'P1M',
        'years'   => 'P1Y',
    ];

    /** @var string */
    private $timezoneSource;

    /** @var string */
    private $timezoneDestination;

    /** @var string */
    private $defaultFormat;

    // @todo - Create a magic method that handles all standard formats, returning closures for each.

    /**
     * DateFormatHelper constructor.
     *
     * @param string $timezoneSource
     * @param string $timezoneDestination
     * @param string $defaultFormat
     */
    public function __construct($timezoneSource = 'UTC', $timezoneDestination = 'UTC', $defaultFormat = 'rfc8601')
    {
        $this->timezoneSource      = $timezoneSource;
        $this->timezoneDestination = $timezoneDestination;
        $this->defaultFormat       = $defaultFormat;
    }

    /**
     * @param $key
     *
     * @return bool
     */
    public function __isset($key)
    {
        $format = $this->validateFormat($key);

        if (!$format) {
            list($op, $format) = $this->validateDiff($key);
        }

        return (bool) $format;
    }

    /**
     * @param $format
     *
     * @return mixed|string
     */
    private function validateFormat($format)
    {
        if (1 === strlen($format)) {
            if (false !== strpos($this->formatSingle, $format)) {
                return $format;
            }
        } else {
            $format = strtolower($format);
            if (isset($this->formats[$format])) {
                return $this->formats[$format];
            }
        }
    }

    /**
     * @param $format
     *
     * @return array
     */
    private function validateDiff($format)
    {
        $keywords = [
            'from',
            'since',
            'till',
            'until',
        ];
        $op       = null;
        $format   = strtolower($format);
        foreach ($keywords as $keyword) {
            $len = strlen($keyword);
            if (substr($format, -$len) === $keyword) {
                $op     = $keyword;
                $format = substr($format, 0, strlen($format) - $len);
                break;
            }
        }

        return [$op, isset($this->formatInterval[$format]) ? $this->formatInterval[$format] : null];
    }

    /**
     * @param $format
     *
     * @return \Closure
     */
    public function __get($format)
    {
        $originalFormat = $format;
        $format         = $this->validateFormat($format);
        if (!$format) {
            // Check for diff format.
            list($op, $intervalFormat) = $this->validateDiff($originalFormat);
            if ($intervalFormat) {
                $now = $this->parse('now', $this->timezoneDestination);
                switch ($op) {
                    case 'till':
                    case 'until':
                        return function ($date) use ($intervalFormat, $now) {
                            $date = $this->parse($date);

                            return $this->getIntervalUnits($now, $date, $intervalFormat);
                        };
                        break;
                    case 'since':
                    case 'from':
                        return function ($date) use ($intervalFormat, $now) {
                            $date = $this->parse($date);

                            return $this->getIntervalUnits($date, $now, $intervalFormat);
                        };
                        break;
                    // If no operation keyword is found, get the absolute difference (either direction)
                    default:
                        return function ($date) use ($intervalFormat, $now) {
                            $date   = $this->parse($date);
                            $result = $this->getIntervalUnits($now, $date, $intervalFormat);

                            return $result ? $result : $this->getIntervalUnits($date, $now, $intervalFormat);
                        };
                        break;
                }
            }
        }
        $format = $format ? $format : $originalFormat;

        // $format = $this->formatPrefix($format);

        return function ($date) use ($format) {
            return $this->parse($date)->format($format);
        };
    }

    /**
     * Parse a string into a DateTime.
     *
     * @param string $date
     * @param string $timezone
     *
     * @return \DateTime
     */
    private function parse($date, $timezone = null)
    {
        if (!($date instanceof \DateTime)) {
            if (!$timezone) {
                $timezone = $this->timezoneSource;
            }

            $date = new \DateTime($date, new \DateTimeZone($timezone));
        }

        return $date;
    }

    /**
     * @param \DateTime $date1
     * @param \DateTime $date2
     * @param string    $intervalFormat
     *
     * @return int
     */
    private function getIntervalUnits(\DateTime $date1, \DateTime $date2, string $intervalFormat)
    {
        $result = 0;
        try {
            $interval = new \DateInterval($intervalFormat);
            $periods  = new \DatePeriod($date1, $interval, $date2);

            $result = max(0, iterator_count($periods) - 1);
        } catch (\Exception $e) {
        }

        return $result;
    }

    /**
     * @param        $date
     * @param string $format
     *
     * @return string
     */
    public function format($date, $format = 'iso8601')
    {
        $result = null;
        try {
            $format = $this->validateFormat($format);
            $result = $this->parse($date)->format($format);
        } catch (\Exception $e) {
        }
        return $result;
    }
}
