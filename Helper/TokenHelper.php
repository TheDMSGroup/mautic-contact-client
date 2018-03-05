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

/**
 * Class TokenHelper.
 */
class TokenHelper
{
    /** @var string To reduce overhead, fields will be searched for this before attempting token replacement. */
    const TOKEN_KEY = '{{';

    /** @var Engine */
    private $engine;

    /** @var array context of tokens for replacement */
    private $context = [];

    /** @var DateFormatHelper */
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
     *
     * @param array $array
     *
     * @return array
     */
    public function renderArray($array = [])
    {
        $result = [];
        foreach ($array as $key => $value) {
            if (is_string($value)) {
                $value = $this->render($value, true);
            } elseif (is_array($value) || is_object($value)) {
                $value = $this->renderArray($value);
            }
            $result[$key] = $value;
        }

        return $result;
    }

    /**
     * Replace Tokens in a simple string using an array for context.
     *
     * @param      $string
     * @param bool $force  skip checking for a token
     *
     * @return string
     */
    public function render($string, $force = false)
    {
        if ($force || false !== strpos($string, self::TOKEN_KEY)) {
            if (!$this->engine->hasHelper('date')) {
                $this->setTimezones();
            }
            $string = $this->engine->render($string, $this->context);
        }

        return $string;
    }

    /**
     * @param string $timzoneSource
     * @param string $timzoneDestination
     */
    public function setTimezones($timzoneSource = 'UTC', $timzoneDestination = 'UTC')
    {
        $this->dateFormatHelper = new DateFormatHelper($timzoneSource, $timzoneDestination);
        $this->engine->addHelper('date', $this->dateFormatHelper);
    }

    /**
     * @param array $context
     */
    public function addContext($context = [])
    {
        $this->context = array_merge($this->context, $context);
    }

    /**
     * @param bool $labeled
     *
     * @return array
     */
    public function getContext($labeled = false)
    {
        if ($labeled) {
            // When retrieving labels, nested contacts are not needed.
            unset($this->context['contacts']);
            $labels     = $this->labels($this->context);
            $flatLabels = [];
            $this->flattenArray($labels, $flatLabels);

            return $flatLabels;
        } else {
            return $this->context;
        }
    }

    /**
     * @param array $context
     */
    public function setContext($context = [])
    {
        $this->context = $context;
    }

    /**
     * Given a token array, set the values to the labels of the fields if possible, or generate them.
     *
     * @param array  $array
     * @param string $keys
     * @param bool   $sort
     *
     * @return array
     */
    private function labels($array = [], $keys = '', $sort = true)
    {
        foreach ($array as $key => &$value) {
            if (is_array($value)) {
                if (0 === count($value)) {
                    // Currently such groups are undocumented, so labels are not needed.
                    unset($array[$key]);
                    continue;
                } else {
                    $value = $this->labels($value, $keys.' '.$key);
                }
            } else {
                if (is_bool($value) || null === $value || 0 === $value) {
                    // Discern the "label" given the key and previous keys conjoined.
                    $totalKey = str_replace('_', ' ', $keys.' '.trim($key));
                    preg_match_all('/(?:|[A-Z])[a-z]*/', $totalKey, $words);
                    foreach ($words[0] as &$word) {
                        if (strlen($word) > 1) {
                            // Change the case of the first letter without dropping the case of the rest of the word.
                            $word = strtoupper(substr($word, 0, 1)).substr($word, 1);
                        }
                    }
                    // Combine down to one string without extra whitespace.
                    $value = trim(preg_replace('/\s+/', ' ', implode(' ', $words[0])));
                    // One exception is UTM variables.
                    $value = str_replace('Utm ', 'UTM ', $value);
                }
            }
        }

        if ($sort) {
            ksort($array, SORT_NATURAL);
        }

        return $array;
    }

    /**
     * @param        $original
     * @param array  $new
     * @param string $delimiter
     * @param string $keys
     */
    private function flattenArray($original, &$new = [], $delimiter = '.', $keys = '')
    {
        foreach ($original as $key => $value) {
            $k = strlen($keys) ? $keys.$delimiter.$key : $key;
            if (is_array($value)) {
                $this->flattenArray($value, $new, $delimiter, $k);
            } else {
                $new[$k] = $value;
            }
        }
    }

    /**
     * Given a Contact, flatten the field values a bit into a more user friendly list of token possibilities.
     *
     * @param Contact $contact
     *
     * @return mixed
     */
    public function addContextContact(Contact $contact)
    {
        $context = [];

        // Append contact ID.
        $contactId     = $contact->getId();
        $context['id'] = isset($contactId) ? $contactId : null;

        // Append contact owner data.
        $owner                         = $contact->getOwner();
        $context['owner']              = [];
        $context['owner']['id']        = $owner ? $owner->getId() : null;
        $context['owner']['username']  = $owner ? $owner->getUsername() : null;
        $context['owner']['firstName'] = $owner ? $owner->getFirstName() : null;
        $context['owner']['lastName']  = $owner ? $owner->getLastName() : null;
        $context['owner']['email']     = $owner ? $owner->getEmail() : null;

        // Append points value.
        $points            = $contact->getPoints();
        $context['points'] = isset($points) ? $points : null;

        // Append IP Addresses.
        $context['ipAddresses'] = [];
        $context['ip']          = null;
        foreach ($contact->getIpAddresses()->toArray() as $ip => $value) {
            $context['ipAddresses'][] = $ip;
            $context['ip']            = $ip;
        }

        // Add Identified date.
        /** @var \DateTime $dateIdentified */
        $dateIdentified            = $contact->getDateIdentified();
        $context['dateIdentified'] = $dateIdentified ? $this->dateFormatHelper->iso8601($dateIdentified) : null;

        // Add Modified date.
        /** @var \DateTime $dateModified */
        $dateModified            = $contact->getDateModified();
        $context['dateModified'] = $dateModified ? $this->dateFormatHelper->iso8601($dateModified) : null;

        // Add DNC status.
        $context['doNotContacts'] = [];
        $context['doNotContact']  = false;
        /** @var \Mautic\LeadBundle\Model\DoNotContact $record */
        foreach ($contact->getDoNotContact() as $record) {
            $context['doNotContacts'][$record->getChannel()] = [
                'comments' => $record->getComments(),
                'reason'   => $record->getReason(),
            ];
            $context['doNotContact']                         = true;
        }

        // Add UTM data.
        $utmTags            = $contact->getUtmTags();
        $context['utmTags'] = [];
        $context['utm']     = [
            'campaign'   => null,
            'content'    => null,
            'medium'     => null,
            'query'      => null,
            'referrer'   => null,
            'remoteHost' => null,
            'source'     => null,
            'term'       => null,
            'url'        => null,
            'userAgent'  => null,
        ];
        if ($utmTags) {
            foreach ($utmTags as $utmTag) {
                $tags                 = [
                    'campaign'   => $utmTag->getUtmCampaign(),
                    'content'    => $utmTag->getUtmContent(),
                    'medium'     => $utmTag->getUtmMedium(),
                    'query'      => $utmTag->getQuery(),
                    'referrer'   => $utmTag->getReferer(),
                    'remoteHost' => $utmTag->getRemoteHost(),
                    'source'     => $utmTag->getUtmSource(),
                    'term'       => $utmTag->getUtmTerm(),
                    'url'        => $utmTag->getUrl(),
                    'userAgent'  => $utmTag->getUserAgent(),
                ];
                $context['utmTags'][] = $tags;
                $context['utm']       = $tags;
            }
        }

        $fieldGroups = $contact->getFields();
        if ($fieldGroups) {
            foreach ($fieldGroups as $fgKey => $fieldGroup) {
                foreach ($fieldGroup as $fkey => $field) {
                    $value = !empty($field['value']) ? $field['value'] : null;
                    if ($value && isset($field['type']) && 'datetime' == $field['type']) {
                        $value = $this->dateFormatHelper->iso8601($value);
                    }
                    if ('core' == $fgKey) {
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
        $this->context['contacts']                 = $contacts;
        $this->context['contacts'][$context['id']] = $context;
    }
}
