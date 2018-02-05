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

use Mautic\LeadBundle\Entity\Lead as Contact;
use MauticPlugin\MauticContactClientBundle\Entity\ContactClient;
use MauticPlugin\MauticContactClientBundle\Model\ApiPayload;

/**
 * Class Revenue
 * @package MauticPlugin\MauticContactClientBundle\Model
 */
class Revenue
{

    /** @var ContactClient $contactClient */
    protected $contactClient;

    /** @var ApiPayload */
    protected $apiPayload;

    /** @var Contact */
    protected $contact;

    /**
     * Revenue constructor.
     * @param ContactClient $contactClient
     * @param Contact $contact
     */
    public function __construct(ContactClient $contactClient, Contact $contact)
    {
        $this->contactClient = $contactClient;
        $this->contact = $contact;
    }

    /**
     * @param $apiPayload
     */
    public function setApiPayload($apiPayload)
    {
        $this->apiPayload = $apiPayload;
    }

    /**
     * Apply revenue to the current contact based on payload and settings of the Contact Client.
     * This assumes the Payload was successfully delivered.
     */
    public function applyRevenue()
    {
        $updated = false;
        $alias = 'attribution';

        $revenueDefault = $this->contactClient->getRevenueDefault();
        $revenueSettings = $this->jsonDecodeArray($this->contactClient->getRevenueSettings());




//        // If we have a value to apply, do the math now and apply to the Contact.
//        $oldValue = $this->contact->getFieldValue($alias);
//        $this->contact->addUpdatedField($alias, $value, $oldValue);
//        $this->setLogs('Updating Contact attribution: '.$alias.' = '.$value);
//        $updated = true;

        return $updated;
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

}