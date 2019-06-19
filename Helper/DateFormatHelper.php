<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Digital Media Solutions, LLC
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticContactClientBundle\Helper;

use Closure;
use DateInterval;
use DatePeriod;
use DateTime;
use DateTimeZone;
use Exception;

/**
 * Class DateFormatHelper.
 */
class DateFormatHelper
{
    /** @var array All standard date formats, plus any we wish to standardize. */
    protected $formatsTime = [
        '12hr'      => 'h:i:s a',
        '12hrshort' => 'g:i a',
        '24hr'      => 'H:i:s',
        '24hrshort' => 'G:i',
        'hhmmss'    => 'His',
        'hh-mm-ss'  => 'H-i-s',
        'a'         => 'a',
        'A'         => 'A',
        'B'         => 'B',
        'g'         => 'g',
        'G'         => 'G',
        'h'         => 'h',
        'H'         => 'H',
        'i'         => 'i',
        's'         => 's',
        'u'         => 'u',
        'v'         => 'v',
        'e'         => 'e',
        'I'         => 'I',
        'O'         => 'O',
        'P'         => 'P',
        'T'         => 'T',
        'Z'         => 'Z',
        'c'         => 'c',
        'r'         => 'r',
        'U'         => 'U',
    ];

    /** @var array */
    protected $formatsDate = [
        'us1'        => 'Y-m-d H:i:s',
        'us2'        => 'm/d/Y',
        'us3'        => 'm/d/Y H:i:s',
        'us4'        => 'Y/m/d',
        'us5'        => 'Y/m/d H:i:s',
        'iso8601'    => 'Y-m-d\TH:i:sO',
        'yyyy'       => 'Y',
        'm'          => 'n',
        'd'          => 'j',
        'yy'         => 'y',
        'mm'         => 'm',
        'dd'         => 'd',
        'day'        => 'l',
        'week'       => 'W',
        'month'      => 'F',
        'dayabr'     => 'D',
        'monthabr'   => 'M',
        'dayweek'    => 'w',
        'dayyear'    => 'z',
        'monthdays'  => 't',
        'yearleap'   => 'L',
        'atom'       => 'Y-m-d\TH:i:sP',
        'cookie'     => 'l, d-M-y H:i:s T',
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
        'dd-mm-yyyy' => 'd-m-Y',
        'mm-dd-yyyy' => 'm-d-Y',
        'yyyy-dd-mm' => 'Y-d-m',
        'yyyy-mm-dd' => 'Y-m-d',
    ];

    /** @var array */
    protected $formatInterval = [
        'age'     => 'P1Y',
        'years'   => 'P1Y',
        'months'  => 'P1M',
        'days'    => 'P1D',
        'hours'   => 'PT1H',
        'minutes' => 'PT1M',
        'seconds' => 'PT1S',
    ];

    /** @var string */
    private $timezoneSource;

    /** @var string */
    private $timezoneDestination;

    /** @var string */
    private $defaultFormat;

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
     * @return string
     */
    public function getTimezoneSource()
    {
        return $this->timezoneSource;
    }

    /**
     * @return string
     */
    public function getTimezoneDestination()
    {
        return $this->timezoneDestination;
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
        if (isset($this->formatsDate[$format])) {
            return $this->formatsDate[$format];
        }
        if (isset($this->formatsTime[$format])) {
            return $this->formatsTime[$format];
        }
        $format = strtolower($format);
        if (isset($this->formatsDate[$format])) {
            return $this->formatsDate[$format];
        }
        if (isset($this->formatsTime[$format])) {
            return $this->formatsTime[$format];
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
            'age',
            'till',
            'until',
        ];
        $op       = null;
        foreach ($keywords as $keyword) {
            $len = strlen($keyword);
            if (substr($format, -$len) === $keyword) {
                $op     = $keyword;
                $format = substr($format, 0, strlen($format) - $len);
                if (!$format && 'age' == $op) {
                    $format = 'years';
                }
                break;
            }
        }

        return [$op, isset($this->formatInterval[$format]) ? $this->formatInterval[$format] : null];
    }

    /**
     * @param $format
     *
     * @return Closure
     *
     * @throws Exception
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
                            try {
                                $date = $this->parse($date);

                                return $this->getIntervalUnits($now, $date, $intervalFormat);
                            } catch (Exception $e) {
                                return null;
                            }
                        };
                        break;
                    case 'since':
                    case 'from':
                    case 'age':
                        return function ($date) use ($intervalFormat, $now) {
                            try {
                                $date = $this->parse($date);

                                return $this->getIntervalUnits($date, $now, $intervalFormat);
                            } catch (Exception $e) {
                                return null;
                            }
                        };
                        break;
                    // If no operation keyword is found, get the absolute difference (either direction)
                    default:
                        return function ($date) use ($intervalFormat, $now) {
                            try {
                                $date   = $this->parse($date);
                                $result = $this->getIntervalUnits($now, $date, $intervalFormat);

                                return $result ? $result : $this->getIntervalUnits($date, $now, $intervalFormat);
                            } catch (Exception $e) {
                                return null;
                            }
                        };
                        break;
                }
            }
        }
        $format = $format ? $format : $originalFormat;
        if (!$format) {
            // Check for aggregate formats like "yyyymmdd" which aren't listed but supported.
            $aggregateFormats = array_merge($this->formatsTime, $this->formatsDate);
            // Put longest keys first.
            uksort(
                $aggregateFormats,
                function ($a, $b) {
                    $lenA = strlen($a);
                    $lenB = strlen($b);

                    return $lenA == $lenB ? strcmp($a, $b) : ($lenA < $lenB ? -1 : 1);
                }
            );
            $stack = [];
            foreach ($aggregateFormats as $key => $val) {
                while ($position = strpos($format, $key)) {
                    while (isset($stack[$position])) {
                        ++$position;
                    }
                    $stack[$position] = $val;
                }
            }
            if ($stack) {
                $format = implode('', $stack);
            }
        }
        $format = $format ? $format : $originalFormat;

        // $format = $this->formatPrefix($format);

        return function ($date) use ($format) {
            try {
                return $this->parse($date)->format($format);
            } catch (Exception $e) {
                return null;
            }
        };
    }

    /**
     * Parse a string into a DateTime.
     *
     * @param string $date
     * @param string $timezone
     *
     * @return DateTime
     *
     * @throws Exception
     */
    private function parse($date, $timezone = null)
    {
        if (!($date instanceof DateTime)) {
            if (false === strtotime($date)) {
                throw new Exception('Invalid date not parsed.');
            }

            if (!$timezone) {
                $timezone = $this->timezoneSource;
            }

            $date = new DateTime($date, new DateTimeZone($timezone));
        }

        return $date;
    }

    /**
     * @param DateTime $date1
     * @param DateTime $date2
     * @param string   $intervalFormat
     *
     * @return int
     */
    private function getIntervalUnits(DateTime $date1, DateTime $date2, string $intervalFormat)
    {
        $result = 0;
        try {
            $interval = new DateInterval($intervalFormat);
            $periods  = new DatePeriod($date1, $interval, $date2);

            $result = max(0, iterator_count($periods) - 1);
        } catch (Exception $e) {
        }

        return $result;
    }

    /**
     * @param        $date
     * @param string $format
     * @param bool   $validate
     *
     * @return string|null
     */
    public function format($date, $format = 'iso8601', $validate = true)
    {
        $result = null;
        try {
            if ($validate) {
                $format = $this->validateFormat($format);
            }
            $result = $this->parse($date)->format($format);
        } catch (Exception $e) {
        }

        return $result;
    }

    /**
     * @param bool $prefix
     *
     * @return array
     */
    public function getFormatsDateTime($prefix = true)
    {
        $formats = $this->formatsDate;
        // Add other common suggestions
        foreach ($this->formatInterval as $key => $value) {
            foreach (['till', 'since'] as $op) {
                if ('age' == $key) {
                    $op = '';
                }
                $formats[$key.$op] = $value;
            }
        }
        foreach ($this->formatsTime as $key => $value) {
            $formats[$key] = $value;
        }

        return $this->prefixWithHelperName($formats, $prefix);
    }

    /**
     * @param      $array
     * @param bool $prefix
     *
     * @return array
     */
    private function prefixWithHelperName($array, $prefix = true)
    {
        if ($prefix) {
            $newArray = [];
            foreach ($array as $key => $value) {
                $newArray['date.'.$key] = $value;
            }
            $array = $newArray;
        }

        return $array;
    }

    /**
     * @param bool $prefix
     *
     * @return array
     */
    public function getFormatsDate($prefix = true)
    {
        $formats = $this->formatsDate;
        // Add other common suggestions
        foreach ($this->formatInterval as $key => $value) {
            foreach (['hours', 'minutes', 'seconds'] as $exclusion) {
                if ($key == $exclusion) {
                    continue;
                }
            }
            foreach (['till', 'since'] as $op) {
                if ('age' == $key) {
                    $op = '';
                }
                $formats[$key.$op] = $value;
            }
        }

        return $this->prefixWithHelperName($formats, $prefix);
    }

    /**
     * @param bool $prefix
     *
     * @return array
     */
    public function getFormatsTime($prefix = true)
    {
        $formats = $this->formatsTime;
        // Add other common suggestions
        foreach ($this->formatInterval as $key => $value) {
            foreach (['months', 'years', 'days', 'age'] as $exclusion) {
                if ($key == $exclusion) {
                    continue;
                }
            }
            foreach (['from', 'till'] as $op) {
                $formats[$key.$op] = $value;
            }
        }

        return $this->prefixWithHelperName($formats, $prefix);
    }
}
