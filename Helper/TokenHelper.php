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

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\LeadBundle\Entity\Lead as Contact;
use MauticPlugin\MauticContactClientBundle\Entity\ContactClient;
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

    /** @var array */
    private $renderCache = [];

    /** @var CoreParametersHelper */
    private $coreParametersHelper;

    /** @var ContactClient */
    private $contactClient;

    /** @var array Used to remember context data types that are not text (text being the default) */
    private $conType = [];

    /** @var array */
    private $formatNumber = [
        'lpad.2' => 'At least 2 digits, zeros on the left',
        'lpad.4' => 'At least 4 digits, zeros on the left',
        'rpad.2' => 'At least 2 digits, zeros on the right',
        'rpad.4' => 'At least 4 digits, zeros on the right',
    ];

    /** @var array */
    private $formatBoolean = [
        'bool.YesNo'     => 'Yes or No',
        'bool.YESNO'     => 'YES or NO',
        'bool.yesno'     => 'yes or no',
        'bool.YN'        => 'Y or N',
        'bool.yn'        => 'y or n',
        'bool.10'        => '1 or ',
        'bool.TrueFalse' => 'True or False',
        'bool.TRUEFALSE' => 'TRUE or FALSE',
        'bool.truefalse' => 'true or false',
        'bool.TF'        => 'T or F',
        'bool.tf'        => 't or f',
    ];

    /** @var array */
    private $formatString = [
        'zip.short' => 'Exclude zipcode +4',
        'trim.255'  => 'Trim to 255 characters (varchar)',
    ];

    /** @var array */
    private $formatText = [
        'zip.short'  => 'Exclude zipcode +4',
        'trim.255'   => 'Trim to 255 characters (varchar)',
        'trim.65535' => 'Trim to 65535 characters (text/blog)',
    ];

    /** @var array */
    private $formatEmail = [
        'trim.255'   => 'Trim to 255 characters (varchar)',
        'trim.65535' => 'Trim to 65535 characters (text/blob)',
    ];

    /**
     * TokenHelper constructor.
     *
     * @param CoreParametersHelper $coreParametersHelper
     *
     * @throws \Exception
     */
    public function __construct(CoreParametersHelper $coreParametersHelper)
    {
        try {
            $this->engine = new Engine(['pragmas' => [Engine::PRAGMA_FILTERS]]);
            $this->addHelper('number');
            $this->addHelper('boolean');
            $this->addHelper('string');
        } catch (\Exception $e) {
            throw new \Exception('You may need to install Mustache via "composer require mustache/mustache".', 0, $e);
        }

        $this->coreParametersHelper = $coreParametersHelper;
    }

    /**
     * Add token helpers/filters to the engine.
     *
     * @param $type
     */
    private function addHelper($type)
    {
        switch ($type) {
            case 'number':
                $this->engine->addHelper(
                    'lpad',
                    [
                        '2' => function ($value) {
                            return str_pad((string) $value, 2, '0', STR_PAD_LEFT);
                        },
                        '4' => function ($value) {
                            return str_pad((string) $value, 4, '0', STR_PAD_LEFT);
                        },
                    ]
                );
                $this->engine->addHelper(
                    'rpad',
                    [
                        '2' => function ($value) {
                            return str_pad((string) $value, 2, '0', STR_PAD_RIGHT);
                        },
                        '4' => function ($value) {
                            return str_pad((string) $value, 4, '0', STR_PAD_RIGHT);
                        },
                    ]
                );
                break;

            case 'date':
                $this->engine->addHelper('date', $this->dateFormatHelper);
                break;

            case 'boolean':
                $this->engine->addHelper(
                    'bool',
                    [
                        'YesNo'     => function ($value) {
                            return $value ? 'Yes' : 'No';
                        },
                        'YESNO'     => function ($value) {
                            return $value ? 'YES' : 'NO';
                        },
                        'yesno'     => function ($value) {
                            return $value ? 'yes' : 'no';
                        },
                        'YN'        => function ($value) {
                            return $value ? 'Y' : 'N';
                        },
                        'yn'        => function ($value) {
                            return $value ? 'y' : 'n';
                        },
                        '10'        => function ($value) {
                            return $value ? '1' : '0';
                        },
                        'TrueFalse' => function ($value) {
                            return $value ? 'True' : 'False';
                        },
                        'TRUEFALSE' => function ($value) {
                            return $value ? 'TRUE' : 'FALSE';
                        },
                        'truefalse' => function ($value) {
                            return $value ? 'true' : 'false';
                        },
                        'TF'        => function ($value) {
                            return $value ? 'T' : 'F';
                        },
                        'tf'        => function ($value) {
                            return $value ? 't' : 'f';
                        },
                    ]
                );
                break;

            case 'string':
            case 'text':
                $this->engine->addHelper(
                    'zip',
                    [
                        'short' => function ($value) {
                            $dash = strpos((string) $value, '-');

                            return $dash ? substr((string) $value, 0, $dash) : $value;
                        },
                    ],
                    'trim',
                    [
                        // Currently undocumented.
                        'ws'    => function ($value) {
                            return trim((string) $value);
                        },
                        '255'   => function ($value) {
                            if (strlen((string) $value) > 255) {
                                $value = trim($value);
                            }

                            return substr((string) $value, 0, 255);
                        },
                        '65535' => function ($value) {
                            if (strlen((string) $value) > 255) {
                                $value = trim($value);
                            }

                            return substr((string) $value, 0, 255);
                        },
                    ]
                );
                break;
        }
    }

    /**
     * Outputs an array of formats by field type for the front-end tokenization.
     *
     * @return array
     */
    public function getFormats()
    {
        return [
            'date'     => $this->getDateFormatHelper()->getFormatsDate(),
            'datetime' => $this->getDateFormatHelper()->getFormatsDateTime(),
            'time'     => $this->getDateFormatHelper()->getFormatsTime(),
            'number'   => $this->formatNumber,
            'boolean'  => $this->formatBoolean,
            'string'   => $this->formatString,
            'text'     => $this->formatText,
            'email'    => $this->formatEmail,
        ];
    }

    /**
     * @return DateFormatHelper
     */
    public function getDateFormatHelper()
    {
        return $this->dateFormatHelper;
    }

    /**
     * @return array
     */
    public function getFormatString()
    {
        return $this->formatString;
    }

    /**
     * @param ContactClient|null $contactClient
     * @param Contact|null       $contact
     * @param array              $payload
     * @param null               $campaign
     * @param array              $event
     *
     * @return $this
     */
    public function newSession(
        ContactClient $contactClient = null,
        Contact $contact = null,
        $payload = [],
        $campaign = null,
        $event = []
    ) {
        $this->context     = [];
        $this->renderCache = [];
        if ($this->engine->hasHelper('date')) {
            $this->engine->removeHelper('date');
        }
        $this->setContactClient($contactClient);
        $this->addContextContact($contact);
        $this->addContextPayload($payload);
        $this->addContextCampaign($campaign);
        $this->addContextEvent($event);

        return $this;
    }

    /**
     * @param ContactClient $contactClient
     *
     * @return $this
     */
    public function setContactClient(ContactClient $contactClient = null)
    {
        if ($contactClient && $contactClient !== $this->contactClient) {
            $this->contactClient = $contactClient;

            // Set the timezones for date/time conversion.
            $tza = $this->coreParametersHelper->getParameter(
                'default_timezone'
            );
            $tza = !empty($tza) ? $tza : date_default_timezone_get();
            if ($this->contactClient) {
                $tzb = $this->contactClient->getScheduleTimezone();
            }
            $tzb = !empty($tzb) ? $tzb : date_default_timezone_get();
            $this->setTimezones($tza, $tzb);
        } else {
            // Assume UTC for all (for the sake of token list by ajax)
            $this->setTimezones();
        }

        return $this;
    }

    /**
     * @param string $timezoneSource
     * @param string $timezoneDestination
     *
     * @return $this
     */
    public function setTimezones($timezoneSource = 'UTC', $timezoneDestination = 'UTC')
    {
        if (!$this->dateFormatHelper) {
            $this->dateFormatHelper = new DateFormatHelper($timezoneSource, $timezoneDestination);
            $this->addHelper('date');
        }

        return $this;
    }

    /**
     * Given a Contact, flatten the field values a bit into a more user friendly list of token possibilities.
     *
     * @param Contact|null $contact
     *
     * @return $this
     */
    public function addContextContact(Contact $contact = null)
    {
        if (!$contact) {
            return $this;
        }
        $context = [];
        $conType = [];

        // Append contact ID.
        $contactId     = $contact->getId();
        $context['id'] = isset($contactId) ? $contactId : null;
        $conType['id'] = 'number';

        // Append contact owner data.
        $owner                         = $contact->getOwner();
        $context['owner']              = [];
        $context['owner']['id']        = $owner ? $owner->getId() : null;
        $conType['owner']['id']        = 'number';
        $context['owner']['username']  = $owner ? $owner->getUsername() : null;
        $context['owner']['firstName'] = $owner ? $owner->getFirstName() : null;
        $context['owner']['lastName']  = $owner ? $owner->getLastName() : null;
        $context['owner']['email']     = $owner ? $owner->getEmail() : null;

        // Append points value.
        $points            = $contact->getPoints();
        $context['points'] = isset($points) ? $points : null;
        $conType['points'] = 'number';

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
        $context['dateIdentified'] = $dateIdentified ? $this->dateFormatHelper->format($dateIdentified) : null;
        $conType['dateIdentified'] = 'datetime';

        // Add Modified date.
        /** @var \DateTime $dateModified */
        $dateModified            = $contact->getDateModified();
        $context['dateModified'] = $dateModified ? $this->dateFormatHelper->format($dateModified) : null;
        $conType['dateModified'] = 'datetime';

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
        $conType['doNotContact'] = 'boolean';

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
                    $type  = !empty($field['type']) ? $field['type'] : null;
                    if ($value && in_array($type, ['datetime', 'date', 'time'])) {
                        // Soft support for labels/values as dates/times.
                        @$newValue = $this->dateFormatHelper->format($value);
                        if (!empty($newValue)) {
                            $value = $newValue;
                        }
                    }
                    if ('core' == $fgKey) {
                        $context[$fkey] = $value;
                        if ($type) {
                            $conType[$fkey] = $type;
                        }
                    } else {
                        if (!isset($context[$fgKey])) {
                            $context[$fgKey] = [];
                        }
                        $context[$fgKey][$fkey] = $value;
                        if ($type) {
                            if (!isset($conType[$fgKey])) {
                                $conType[$fgKey] = [];
                            }
                            $conType[$fgKey][$fkey] = $type;
                        }
                    }
                }
            }
        }

        // Support multiple contacts in the future if needed by uncommenting a bit here.
        // $contacts = !empty($this->context['contacts']) ? $this->context['contacts'] : [];

        // Set the context to this contact.
        $this->context = array_merge($this->context, $context);
        $this->conType = array_merge($this->conType, $conType);

        // Support multiple contacts for future batch processing.
        // $this->context['contacts']                 = $contacts;
        // $this->context['contacts'][$context['id']] = $context;

        return $this;
    }

    /**
     * Simplify the payload for tokens, including actual response data when possible.
     *
     * @param array $payload
     * @param null  $operationId
     * @param array $responseActual
     *
     * @return $this
     */
    public function addContextPayload($payload = [], $operationId = null, $responseActual = [])
    {
        if (!$payload) {
            return $this;
        }
        $payload = json_decode(json_encode($payload), true);
        if (!isset($this->context['payload'])) {
            $this->context['payload'] = $payload;
        }
        if (!empty($payload['operations'])) {
            foreach ($payload['operations'] as $id => $operation) {
                foreach (['request', 'response'] as $opType) {
                    if (!empty($operation[$opType])) {
                        foreach (['headers', 'body'] as $fieldType) {
                            if (!empty($operation[$opType][$fieldType])) {
                                $fieldSet = [];
                                if ('request' === $opType) {
                                    foreach ($operation[$opType][$fieldType] as $field) {
                                        if (!empty($field['key'])) {
                                            if (!empty($field['value'])) {
                                                $fieldSet[$field['key']] = $field['value'];
                                            }
                                        }
                                    }
                                } elseif ('response' === $opType) {
                                    if (
                                        $id === $operationId
                                        && !empty($responseActual[$fieldType])
                                    ) {
                                        $fieldSet = $responseActual[$fieldType];
                                    }
                                }
                                $this->context['payload']['operations'][$id][$opType][$fieldType] = $fieldSet;
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * @param null $campaign
     */
    public function addContextCampaign($campaign = null)
    {
        $this->context['campaign']['id'] = $campaign ? $campaign->getId() : null;
    }

    /**
     * Take an event array and use it to enhance the context for later dispositional callback.
     * Campaign context should be added before this, as it is used for the token.
     *
     * @param array $event
     */
    public function addContextEvent($event = [])
    {
        $contactId                       = isset($this->context['id']) ? $this->context['id'] : 0;
        $this->context['event']['id']    = !empty($event['id']) ? (int) $event['id'] : null;
        $this->context['event']['name']  = !empty($event['name']) ? $event['name'] : null;
        $this->context['event']['token'] = null;
        if ($contactId || $this->context['event']['id'] || $this->context['campaign']['id']) {
            $this->context['event']['token'] = $this->eventTokenEncode(
                [
                    $this->context['campaign']['id'],
                    $this->context['event']['id'],
                    $contactId,
                ]
            );
        }
    }

    /**
     * Encode Campaign ID, Event ID, and Contact ID into a short base62 string.
     * Zeros are used as delimiters reducing the subsequent integers to base61.
     *
     * @param $values
     *
     * @return string
     */
    private function eventTokenEncode($values)
    {
        list($campaignId, $eventId, $contactId) = $values;
        $campaignIdString                       = $this->baseEncode((int) $campaignId);
        $eventIdString                          = $this->baseEncode((int) $eventId);
        $contactIdString                        = $this->baseEncode((int) $contactId);

        return $campaignIdString.'0'.$eventIdString.'0'.$contactIdString;
    }

    /**
     * @param     $integer
     * @param int $b
     *
     * @return string
     */
    private function baseEncode($integer)
    {
        $b      = 61;
        $base   = '123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $r      = $integer % $b;
        $result = $base[$r];
        $q      = floor($integer / $b);
        while ($q) {
            $r      = $q % $b;
            $q      = floor($q / $b);
            $result = $base[$r].$result;
        }

        return $result;
    }

    /**
     * @param array $context
     *
     * @return $this
     */
    public function addContext($context = [])
    {
        if (!$context) {
            return $this;
        }
        $this->nestContext($context);
        $this->context = array_merge($this->context, $context);

        return $this;
    }

    /**
     * Nest keys containing dots as Mustache contextual arrays.
     * ex. ['utm.source' => 'value'] becomes ['utm' => ['source' => 'value']].
     *
     * @param $context
     *
     * @return mixed
     */
    private function nestContext(&$context)
    {
        foreach ($context as $key => $value) {
            $dot = strpos($key, '.');
            if ($dot && $dot !== strlen($key) - 1) {
                $currentContext = &$context;
                foreach (explode('.', $key) as $k) {
                    if (!isset($currentContext[$k])) {
                        $currentContext[$k] = [];
                    }
                    $currentContext = &$currentContext[$k];
                }
                $currentContext = $value;
                unset($context[$key]);
            }
        }

        return $context;
    }

    /**
     * Replace Tokens in a simple string using an array for context.
     *
     * @param string|array|object $template
     *
     * @return string|array
     */
    public function render($template = '')
    {
        if (is_array($template) || is_object($template)) {
            foreach ($template as $key => &$value) {
                $value = $this->render($value);
            }
        } else {
            if (
                strlen($template) > 3
                && false !== strpos($template, self::TOKEN_KEY)
            ) {
                if (isset($this->renderCache[$template])) {
                    // Already tokenized this exact string.
                    $template = $this->renderCache[$template];
                } else {
                    $this->setTimezones();
                    $key      = $template;
                    $template = $this->engine->render($template, $this->context);
                    if (!empty($template)) {
                        $this->renderCache[$key] = $template;
                    }
                }
            }
        }

        return $template;
    }

    /**
     * @param bool $flattened
     *
     * @return array
     */
    public function getContext($flattened = false)
    {
        $result = [];
        if ($flattened) {
            $this->flattenArray($this->context, $result);
        } else {
            $result = $this->context;
        }

        return $result;
    }

    /**
     * @param array $context
     *
     * @return $this
     */
    public function setContext($context = [])
    {
        $this->context = $context;

        return $this;
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
     * Get the context array labels instead of values for use in token suggestions.
     *
     * @return array
     */
    public function getContextLabeled()
    {
        $result = [];
        $labels = $this->describe($this->context);
        $this->flattenArray($labels, $result);

        return $result;
    }

    /**
     * Given a token array, set the values to the labels for the context if possible.
     *
     * @param array  $array
     * @param string $keys
     * @param bool   $sort
     * @param bool   $payload
     *
     * @return array
     */
    private function describe($array = [], $keys = '', $sort = true, $payload = false)
    {
        foreach ($array as $key => &$value) {
            if (is_array($value)) {
                if (0 === count($value)) {
                    // Currently such groups are undocumented, so labels are not needed.
                    unset($array[$key]);
                    continue;
                } else {
                    $value = $this->describe($value, $keys.' '.$key, $sort, ($payload || 'payload' === $key));
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
                } elseif ($payload) {
                    // For payload tokens, don't label at all but express the path to the token instead.
                    // This is for advanced use.
                    $value = implode('.', explode(' ', trim($keys))).'.'.$key;
                }
            }
        }

        if ($sort) {
            ksort($array, SORT_NATURAL);
        }

        return $array;
    }

    /**
     * Get the context data types (that are not text) for use in token suggestions.
     *
     * @return array
     */
    public function getContextTypes()
    {
        $result = [];
        $types  = $this->describe($this->conType);
        $this->flattenArray($types, $result);

        return $result;
    }

    /**
     * Take a string from eventTokenEncode and reverse it to an array.
     *
     * NOTE: Not currently in use, but likely to be used in the future.
     *
     * @param $string
     *
     * @return array
     */
    private function eventTokenDecode($string)
    {
        list($campaignIdString, $eventIdString, $contactIdString) = explode('0', $string);

        return [
            $this->baseDecode($campaignIdString),
            $this->baseDecode($eventIdString),
            $this->baseDecode($contactIdString),
        ];
    }

    /**
     * @param   $string
     *
     * @return bool|float|int
     */
    private function baseDecode($string)
    {
        $b      = 61;
        $base   = '123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $limit  = strlen($string);
        $result = strpos($base, $string[0]);
        for ($i = 1; $i < $limit; ++$i) {
            $result = $b * $result + strpos($base, $string[$i]);
        }

        return $result;
    }
}
