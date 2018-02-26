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

use Mautic\LeadBundle\Entity\Lead as Contact;
use Mustache_Engine as Engine;
use MauticPlugin\MauticContactClientBundle\Helper\DateFormatHelper;

/**
 * Class TokenHelper
 * @package MauticPlugin\MauticContactClientBundle\Helper
 */
class TokenHelper
{
    /**
     * To reduce overhead, fields will be searched for this before attempting token replacement.
     */
    const TOKEN_KEY = '{{';

    /**
     * @var Engine
     */
    private $engine;

    /**
     * @var array Context of tokens for replacement.
     */
    private $context = [];

    private $dateFormatHelper;

    /**
     * TokenHelper constructor.
     *
     * @throws \Exception
     */
    public function __construct()
    {
        try {
            $this->engine = new Engine();
        } catch (\Exception $e) {
            throw new \Exception('You may need to install Mustache via "composer require mustache/mustache".', 0, $e);
        }
    }

    /**
     * Recursively replaces tokens using an array for context.
     * @param array $array
     * @return array
     */
    public function renderArray($array = [])
    {
        $result = [];
        foreach ($array as $key => $value) {
            if (strpos($key, self::TOKEN_KEY) !== false) {
                $key = $this->engine->render($key, $this->context);
            }
            if (is_string($value)) {
                if (strpos($value, self::TOKEN_KEY) !== false) {
                    $value = $this->engine->render($value, $this->context);
                }
            } elseif (is_array($value) || is_object($value)) {
                $value = $this->tokenizeArray($value, $this->context);
            }
            $result[$key] = $value;
        }

        return $result;
    }

    /**
     * Replace Tokens in a simple string using an array for context.
     * @param $string
     * @return string
     */
    public function renderString($string)
    {
        if (strpos($string, self::TOKEN_KEY) !== false) {
            $string = $this->engine->render($string, $this->context);
        }

        return $string;
    }

    public function setTimezones($tza = 'UTC', $tzb = 'UTC')
    {
        $this->dateFormatHelper = new DateFormatHelper($tza, $tzb);
        $this->engine->addHelper('date', $this->dateFormatHelper);
    }

    /**
     * @param array $context
     */
    public function setContext($context = [])
    {
        $this->context = $context;
    }

    /**
     * @param array $context
     */
    public function addContext($context = [])
    {
        $this->context = array_merge($this->context, $context);
    }

    /**
     * Given a Contact, flatten the field values a bit into a more user friendly list of token possibilities.
     * @param Contact $contact
     * @return mixed
     */
    public function addContextContact(Contact $contact)
    {
        $context = [];
        
        // Append contact ID.
        $contactId = $contact->getId();
        $context['id'] = isset($contactId) ? $contactId : 0;

        // Append contact owner data.
        $owner = $contact->getOwner();
        if ($owner) {
            if (!isset($context['owner'])) {
                $context['owner'] = [];
            }
            $context['owner']['id'] = $owner->getId();
            $context['owner']['username'] = $owner->getUsername();
            $context['owner']['firstName'] = $owner->getFirstName();
            $context['owner']['lastName'] = $owner->getLastName();
            $context['owner']['email'] = $owner->getEmail();
        }

        // Append points value.
        $points = $contact->getPoints();
        $context['points'] = isset($points) ? $points : 0;

        // Append IP Addresses.
        $ips = $contact->getIpAddresses()->toArray();
        if ($ips) {
            if (!isset($context['ipAddresses'])) {
                $context['ipAddresses'] = [];
            }
            foreach ($ips as $ip => $value) {
                $context['ipAddresses'][] = $ip;
                $context['ipAddress'] = $ip;
            }
        }

        // Add Identified date.
        /** @var \DateTime $dateIdentified */
        $dateIdentified = $contact->getDateIdentified();
        if ($dateIdentified) {
            $context['dateIdentified'] = $this->dateFormatHelper->iso8601($dateIdentified);
        }

        // Add Modified date.
        /** @var \DateTime $dateModified */
        $dateModified = $contact->getDateModified();
        if ($dateModified) {
            $context['dateModified'] = $this->dateFormatHelper->iso8601($dateModified);
        }

        // Add DNC status.
        /** @var DoNotContact $record */
        foreach ($contact->getDoNotContact() as $record) {
            if (!isset($context['doNotContact'])) {
                $context['doNotContact'] = [];
            }
            $context['doNotContact'][$record->getChannel()] = [
                'comments' => $record->getComments(),
                'reason' => $record->getReason(),
            ];
        }

        // Add UTM data.
        $utmTags = $contact->getUtmTags();
        if ($utmTags) {
            foreach ($utmTags as $utmTag) {
                if (!isset($context['utmTags'])) {
                    $context['utmTags'] = [];
                }
                $tags = [
                    'query' => $utmTag->getQuery(),
                    'referrer' => $utmTag->getReferer(),
                    'remoteHost' => $utmTag->getRemoteHost(),
                    'url' => $utmTag->getUrl(),
                    'userAgent' => $utmTag->getUserAgent(),
                    'campaign' => $utmTag->getUtmCampaign(),
                    'content' => $utmTag->getUtmContent(),
                    'medium' => $utmTag->getUtmMedium(),
                    'source' => $utmTag->getUtmSource(),
                    'term' => $utmTag->getUtmTerm(),
                ];
                $context['utmTags'][] = $tags;
                $context['utmTag'] = $tags;
            }
        }

        // Add all other fields.
        $fieldGroups = $contact->getFields();
        if ($fieldGroups) {
            foreach ($fieldGroups as $fgKey => $fieldGroup) {
                foreach ($fieldGroup as $fkey => $field) {
                    $value = !empty($field['value']) ? $field['value'] : null;
                    if ($value && $field['type'] == 'datetime') {
                        $value = $this->dateFormatHelper->iso8601($value);
                    }
                    if ($fgKey == 'core') {
                        $context[$fkey] = $value;
                    } else {
                        if (!isset($context[$fgKey])) {
                            $context[$fgKey] = [];
                        }
                        $context[$fgKey][$fkey] = $value;
                    }
                }
            }
        }

        $contacts = !empty($this->context['contacts']) ? $this->context['contacts'] : [];

        // Set the context to this contact.
        $this->context = $context;

        // Support multiple contacts for future batch processing.
        $this->context['contacts'] = $contacts;
        $this->context['contacts'][$context['id']] = $context;
    }
}
