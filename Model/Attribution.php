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
use MauticPlugin\MauticContactClientBundle\Helper\JSONHelper;

/**
 * Class Attribution.
 */
class Attribution
{
    /** @var ContactClient $contactClient */
    protected $contactClient;

    /** @var ApiPayload */
    protected $payload;

    /** @var Contact */
    protected $contact;

    /** @var float */
    protected $attributionChange;

    /**
     * Attribution constructor.
     *
     * @param ContactClient $contactClient
     * @param Contact       $contact
     */
    public function __construct(ContactClient $contactClient, Contact $contact)
    {
        $this->contactClient = $contactClient;
        $this->contact       = $contact;
    }

    /**
     * @param ApiPayload|FilePayload $payload
     */
    public function setPayload($payload)
    {
        $this->payload = $payload;
    }

    /**
     * Apply attribution to the current contact based on payload and settings of the Contact Client.
     * This assumes the Payload was successfully delivered (valid = true).
     *
     * @return bool
     *
     * @throws \Exception
     */
    public function applyAttribution()
    {
        $update              = false;
        $originalAttribution = $this->contact->getFieldValue('attribution');
        $originalAttribution = !empty($originalAttribution) ? $originalAttribution : 0;
        $attributionChange   = 0;

        if ($this->payload) {
            $jsonHelper          = new JSONHelper();
            $attributionSettings = $jsonHelper->decodeObject(
                $this->contactClient->getAttributionSettings(),
                'AttributionSettings'
            );

            if (
                $attributionSettings
                && isset($attributionSettings->mode)
                && is_object($attributionSettings->mode)
                && !empty($attributionSettings->mode->key)
            ) {
                // Dynamic mode.
                $key = $attributionSettings->mode->key;

                // Attempt to get this field value from the response operations.
                $responseFieldValue = $this->payload->getAggregateResponseFieldValue($key);
                if (!empty($responseFieldValue) && is_numeric($responseFieldValue)) {
                    // We have a value, apply sign.
                    $sign = isset($attributionSettings->mode->sign) ? $attributionSettings->mode->sign : '+';
                    if ('+' == $sign) {
                        $attributionChange = abs($responseFieldValue);
                    } elseif ('-' == $sign) {
                        $attributionChange = abs($responseFieldValue) * -1;
                    } else {
                        $attributionChange = $responseFieldValue;
                    }

                    // Apply maths.
                    $math = isset($attributionSettings->mode->math) ? $attributionSettings->mode->math : null;
                    if ('/100' == $math) {
                        $attributionChange = $attributionChange / 100;
                    } elseif ('*100' == $math) {
                        $attributionChange = $attributionChange * 100;
                    }
                    $update = true;
                }
            }
        }

        // If we were not able to apply a dynamic cost/attribution, then fall back to the default (if set).
        if (!$update) {
            $attributionDefault = $this->contactClient->getAttributionDefault();
            if (!empty($attributionDefault) && is_numeric($attributionDefault)) {
                $attributionChange = $attributionDefault;
                $update            = true;
            }
        }

        if ($update && $attributionChange) {
            $this->setNewAttribution($attributionChange);
            $this->contact->addUpdatedField(
                'attribution',
                $originalAttribution + $attributionChange,
                $originalAttribution
            );
            // Unsure if we should keep this next line for BC.
            $this->contact->addUpdatedField('attribution_date', (new \DateTime())->format('Y-m-d H:i:s'));
        }

        return $update;
    }

    /**
     * @param $attributionChange
     *
     * @return $this
     */
    public function setNewAttribution($attributionChange)
    {
        $this->attributionChange = $attributionChange;

        return $this;
    }

    /**
     * @return float
     */
    public function getAttributionChange()
    {
        return $this->attributionChange;
    }

    /**
     * @return Contact|null
     */
    public function getContact()
    {
        return $this->contact;
    }
}
