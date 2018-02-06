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
 * Class Attribution
 * @package MauticPlugin\MauticContactClientBundle\Model
 */
class Attribution
{

    /** @var ContactClient $contactClient */
    protected $contactClient;

    /** @var ApiPayload */
    protected $payload;

    /** @var Contact */
    protected $contact;

    /** @var double */
    protected $newAttribution;

    /**
     * Attribution constructor.
     * @param ContactClient $contactClient
     * @param Contact $contact
     */
    public function __construct(ContactClient $contactClient, Contact $contact)
    {
        $this->contactClient = $contactClient;
        $this->contact = $contact;
    }

    /**
     * @param ApiPayload $payload
     */
    public function setPayload(ApiPayload $payload)
    {
        $this->payload = $payload;
    }

    /**
     * Apply attribution to the current contact based on payload and settings of the Contact Client.
     * This assumes the Payload was successfully delivered (valid = true).
     *
     * @return bool
     * @throws \Exception
     */
    public function applyAttribution()
    {
        $update = false;
        $originalAttribution = $this->contact->getFieldValue('attribution');
        $originalAttribution = !empty($originalAttribution) ? $originalAttribution : 0;
        $newAttribution = 0;

        if ($this->payload) {
            $attributionSettings = $this->jsonDecodeObject($this->contactClient->getAttributionSettings());
            if ($attributionSettings && is_object(
                    $attributionSettings->mode
                ) && !empty($attributionSettings->mode->key)) {
                // Dynamic mode.
                $key = $attributionSettings->mode->key;

                // Attempt to get this field value from the response operations.
                $responseFieldValue = $this->payload->getAggregateResponseFieldValue($key);
                if (!empty($responseFieldValue) && is_numeric($responseFieldValue)) {

                    // We have a value, apply sign.
                    $sign = isset($attributionSettings->mode->sign) ? $attributionSettings->mode->sign : '+';
                    if ($sign == '+') {
                        $newAttribution = abs($responseFieldValue);
                    } elseif ($sign == '-') {
                        $newAttribution = abs($responseFieldValue) * -1;
                    } else {
                        $newAttribution = $responseFieldValue;
                    }

                    // Apply maths.
                    $math = isset($attributionSettings->mode->math) ? $attributionSettings->mode->math : null;
                    if ($math == '/100') {
                        $newAttribution = $newAttribution / 100;
                    } elseif ($math == '*100') {
                        $newAttribution = $newAttribution * 100;
                    }
                    $update = true;
                }
            }
        }

        // If we were not able to apply a dynamic cost/attribution, then fall back to the default (if set).
        if (!$update) {
            $attributionDefault = $this->contactClient->getAttributionDefault();
            if (!empty($attributionDefault) && is_numeric($attributionDefault)) {
                $newAttribution = $attributionDefault;
                $update = true;
            }
        }

        if ($update && $newAttribution) {
            $this->setNewAttribution($newAttribution);
            $this->contact->addUpdatedField(
                'attribution',
                $originalAttribution + $newAttribution,
                $originalAttribution
            );
            // Unsure if we should keep this next line for BC.
            $this->contact->addUpdatedField('attribution_date', (new \DateTime())->format('Y-m-d H:i:s'));
        }

        return $update;
    }

    /**
     * @param $string
     * @return mixed
     * @throws \Exception
     */
    private function jsonDecodeObject($string)
    {
        $object = json_decode(!empty($string) ? $string : '{}');
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
        if (!$object || !is_object($object)) {
            throw new \Exception('Attribution is invalid.');
        }

        return $object;
    }

    /**
     * @return float
     */
    public function getNewAttribution()
    {
        return $this->newAttribution;
    }

    /**
     * @param $newAttribution
     * @return $this
     */
    public function setNewAttribution($newAttribution)
    {
        $this->newAttribution = $newAttribution;

        return $this;
    }

    /**
     * @return Contact|null
     */
    public function getContact()
    {
        return $this->contact;
    }
}