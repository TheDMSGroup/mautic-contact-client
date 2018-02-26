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
 * Class DateFormatHelper
 *
 * @package MauticPlugin\MauticContactClientBundle\Helper
 */
class DateFormatHelper
{
    const DATE_SHORT = 'd/m/Y';

    /**
     * @var string Datasource timezone (internal).
     */
    private $tza;

    /**
     * @var string Destination timezone (client).
     */
    private $tzb;

    /**
     * DateFormatHelper constructor.
     *
     * @param string $tza
     * @param string $tzb
     */
    public function __construct($tza = 'UTC', $tzb = 'UTC')
    {
        $this->tza = $tza;
        $this->tzb = $tzb;
    }

    public function __isset($key)
    {
        return method_exists($this, '_'.$key);
    }

    public function __get($key)
    {
        return [$this, '_'.$key];
    }

    public function atom($date)
    {
        return $this->parse($date)->format(DATE_ATOM);
    }

    public function cookie($date)
    {
        return $this->parse($date)->format(DATE_COOKIE);
    }

    public function iso8601($date)
    {
        return $this->parse($date)->format(DATE_ISO8601);
    }

    public function rfc822($date)
    {
        return $this->parse($date)->format(DATE_RFC822);
    }

    public function rfc850($date)
    {
        return $this->parse($date)->format(DATE_RFC850);
    }

    public function rfc1036($date)
    {
        return $this->parse($date)->format(DATE_RFC1036);
    }

    public function rfc1123($date)
    {
        return $this->parse($date)->format(DATE_RFC1123);
    }

    public function rfc2822($date)
    {
        return $this->parse($date)->format(DATE_RFC2822);
    }

    public function rfc3339($date)
    {
        return $this->parse($date)->format(DATE_RFC3339);
    }

    public function rfc3339_extended($date)
    {
        return $this->parse($date)->format(DATE_RFC3339_EXTENDED);
    }

    public function rfc7231($date)
    {
        return $this->parse($date)->format(DATE_RFC7231);
    }

    public function rss($date)
    {
        return $this->parse($date)->format(DATE_RSS);
    }

    public function w3c($date)
    {
        return $this->parse($date)->format(DATE_W3C);
    }

    public function yearsSince($date)
    {
        return min(0, $this->parseDiff($date)->y);
    }

    public function yearsTill($date)
    {
        return max(0, $this->parseDiff($date)->y);
    }

    public function daysSince($date)
    {
        return min(0, $this->parseDiff($date)->days);
    }

    public function daysTill($date)
    {
        return max(0, $this->parseDiff($date)->days);
    }

    public function hoursSince($date)
    {
        return min(0, $this->parseDiff($date)->h);
    }

    public function hoursTill($date)
    {
        return max(0, $this->parseDiff($date)->h);
    }

    public function short($date)
    {
        return $this->parse($date)->format(self::DATE_SHORT);
    }

    /**
     * Parse a string into a DateTime
     *
     * @param string $date
     * @param string $tz Timezone
     *
     * @return \DateTime
     */
    private function parse($date, $tz = null)
    {
        if (!($date instanceof \DateTime)) {
            $date = new \DateTime($date, new \DateTimeZone(!empty($tz) ? $tz : $this->tza));
        }

        return $date;
    }

    /**
     * Parse the difference between the specified date and now, based on the destination timezone.
     *
     * @param string $date
     *
     * @return bool|\DateInterval
     */
    private function parseDiff($date)
    {
        return $this->parse($date)->diff($this->parse('now', $this->tzb));
    }

    /**
     * @param $date
     * @param $format
     *
     * @return string
     */
    private function format($date, $format = '')
    {
        if (strlen($format) == 2 && strpos($format, 'u') === 0) {
            $format = substr($format, 1);
        }

        return $this->parse($date)->format($format);
    }

    /**
     * Typical single-character expressions.
     * Uppercase expressions prepended by 'u'
     *
     * @param $date
     *
     * @return string
     */
    public function a($date)
    {
        return $this->format($date, __FUNCTION__);
    }

    public function uA($date)
    {
        return $this->format($date, __FUNCTION__);
    }

    public function B($date)
    {
        return $this->format($date, __FUNCTION__);
    }

    public function c($date)
    {
        return $this->format($date, __FUNCTION__);
    }

    public function d($date)
    {
        return $this->format($date, __FUNCTION__);
    }

    public function uD($date)
    {
        return $this->format($date, __FUNCTION__);
    }

    public function e($date)
    {
        return $this->format($date, __FUNCTION__);
    }

    public function F($date)
    {
        return $this->format($date, __FUNCTION__);
    }

    public function g($date)
    {
        return $this->format($date, __FUNCTION__);
    }

    public function uG($date)
    {
        return $this->format($date, __FUNCTION__);
    }

    public function h($date)
    {
        return $this->format($date, __FUNCTION__);
    }

    public function uH($date)
    {
        return $this->format($date, __FUNCTION__);
    }

    public function i($date)
    {
        return $this->format($date, __FUNCTION__);
    }

    public function uI($date)
    {
        return $this->format($date, __FUNCTION__);
    }

    public function j($date)
    {
        return $this->format($date, __FUNCTION__);
    }

    public function l($date)
    {
        return $this->format($date, __FUNCTION__);
    }

    public function uL($date)
    {
        return $this->format($date, __FUNCTION__);
    }

    public function m($date)
    {
        return $this->format($date, __FUNCTION__);
    }

    public function n($date)
    {
        return $this->format($date, __FUNCTION__);
    }

    public function uN($date)
    {
        return $this->format($date, __FUNCTION__);
    }

    public function o($date)
    {
        return $this->format($date, __FUNCTION__);
    }

    public function uO($date)
    {
        return $this->format($date, __FUNCTION__);
    }

    public function P($date)
    {
        return $this->format($date, __FUNCTION__);
    }

    public function r($date)
    {
        return $this->format($date, __FUNCTION__);
    }

    public function s($date)
    {
        return $this->format($date, __FUNCTION__);
    }

    public function uS($date)
    {
        return $this->format($date, __FUNCTION__);
    }

    public function t($date)
    {
        return $this->format($date, __FUNCTION__);
    }

    public function uT($date)
    {
        return $this->format($date, __FUNCTION__);
    }

    public function u($date)
    {
        return $this->format($date, __FUNCTION__);
    }

    public function uU($date)
    {
        return $this->format($date, __FUNCTION__);
    }

    public function v($date)
    {
        return $this->format($date, __FUNCTION__);
    }

    public function w($date)
    {
        return $this->format($date, __FUNCTION__);
    }

    public function uW($date)
    {
        return $this->format($date, __FUNCTION__);
    }

    public function y($date)
    {
        return $this->format($date, __FUNCTION__);
    }

    public function uY($date)
    {
        return $this->format($date, __FUNCTION__);
    }

    public function z($date)
    {
        return $this->format($date, __FUNCTION__);
    }

    public function uZ($date)
    {
        return $this->format($date, __FUNCTION__);
    }

}