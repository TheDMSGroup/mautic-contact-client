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
use Mautic\CoreBundle\Helper\PhoneNumberHelper;
use Mautic\LeadBundle\Entity\Lead as Contact;
use MauticPlugin\MauticContactClientBundle\Entity\ContactClient;
use Mustache_Engine as Engine;
use Psr\Log\LoggerInterface;

/**
 * Class TokenHelper.
 */
class TokenHelper
{
    /** @var int */
    const TEMPLATE_MIN_LENGTH = 5;

    /** @var string */
    const TOKEN_KEY_END = '}}';

    /** @var string */
    const TOKEN_KEY_START = '{{';

    /** @var Engine */
    private $engine;

    /** @var array context of tokens for replacement */
    private $context = [];

    /** @var string|array Last template we attempted to parse, used for error reporting. */
    private $lastTemplate;

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

    /** @var PhoneNumberHelper */
    private $phoneHelper;

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
        'bool.10'        => '1 or 0',
        'bool.TrueFalse' => 'True or False',
        'bool.TRUEFALSE' => 'TRUE or FALSE',
        'bool.truefalse' => 'true or false',
        'bool.TF'        => 'T or F',
        'bool.tf'        => 't or f',
    ];

    /** @var array */
    private $formatString = [
        'case.lower'        => 'Lowercase',
        'case.upper'        => 'Uppercase',
        'case.first'        => 'Uppercase first letters',
        'strip.comma'       => 'Exclude commas',
        'strip.html'        => 'Exclude HTML',
        'strip.doublequote' => 'Exclude double quotes',
        'strip.singlequote' => 'Exclude single quotes',
        'strip.nonascii'    => 'Exclude non ASCII',
        'strip.nonnumeric'  => 'Exclude non numeric',
        'strip.numeric'     => 'Exclude numeric',
        'trim.ws'           => 'Trim whitespace',
        'trim.255'          => 'Trim to 255 characters (varchar)',
        'url.encode'        => 'Encode for use in a URL',
        'zip.short'         => 'Exclude zipcode +4',
    ];

    /** @var array */
    private $formatTel = [
        'tel.e164'  => 'Format as +12223334444',
        'tel.idash' => 'Format as 1-222-333-4444',
        'tel.ldash' => 'Format as 222-333-4444',
        'tel.ipar'  => 'Format as 1 (222) 333-4444',
        'tel.lpar'  => 'Format as (222) 333-4444',
        'tel.idot'  => 'Format as 1.222.333.4444',
        'tel.ldot'  => 'Format as 222.333.4444',
    ];

    /** @var array */
    private $formatText = [
        'case.lower'        => 'Lowercase',
        'case.upper'        => 'Uppercase',
        'case.first'        => 'Uppercase first letters',
        'strip.comma'       => 'Exclude commas',
        'strip.html'        => 'Exclude HTML',
        'strip.doublequote' => 'Exclude double quotes',
        'strip.singlequote' => 'Exclude single quotes',
        'strip.nonascii'    => 'Exclude non ASCII',
        'strip.nonnumeric'  => 'Exclude non numeric',
        'strip.numeric'     => 'Exclude numeric',
        'trim.ws'           => 'Trim whitespace',
        'trim.255'          => 'Trim to 255 characters (varchar)',
        'trim.65535'        => 'Trim to 65535 characters (text/blog)',
        'url.encode'        => 'Encode for use in a URL',
        'zip.short'         => 'Exclude zipcode +4',
    ];

    /** @var array */
    private $formatEmail = [
        'trim.255'   => 'Trim to 255 characters (varchar)',
        'trim.65535' => 'Trim to 65535 characters (text/blob)',
    ];

    /** @var array List of all helpers we utilize, used to sanitize context to prevent filter exceptions. */
    private $helpers = [
        'bool',
        'case',
        'date',
        'lpad',
        'rpad',
        'strip',
        'trim',
        'url',
        'zip',
        'tel',
    ];

    /** @var string */
    private $timezoneSource = 'UTC';

    /** @var string */
    private $timezoneDestination = 'UTC';

    /** @var LoggerInterface */
    private $logger;

    /**
     * TokenHelper constructor.
     *
     * @param CoreParametersHelper $coreParametersHelper
     * @param LoggerInterface      $logger
     *
     * @throws \Exception
     */
    public function __construct(CoreParametersHelper $coreParametersHelper, LoggerInterface $logger)
    {
        $this->logger               = $logger;
        $this->coreParametersHelper = $coreParametersHelper;
        try {
            $this->engine = new Engine(
                [
                    'pragmas' => [Engine::PRAGMA_FILTERS],
                    'escape'  => function ($value) {
                        if (is_array($value) || is_object($value)) {
                            $value = '';
                        }

                        return (string) $value;
                    },
                    'logger'  => $this->logger,
                    'cache'   => realpath($this->coreParametersHelper->getParameter('kernel.cache_dir')).'/mustache',
                ]
            );
            if ($cacheDir = $this->coreParametersHelper->getParameter('mautic.mustache.cache_dir')) {
                $this->engine->setCache($cacheDir);
            }
            $this->addHelper('number');
            $this->addHelper('boolean');
            $this->addHelper('string');
        } catch (\Exception $e) {
            throw new \Exception('You may need to install Mustache via "composer require mustache/mustache".', 0, $e);
        }
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
                if (!$this->engine->hasHelper('lpad')) {
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
                }
                if (!$this->engine->hasHelper('rpad')) {
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
                }
                break;

            case 'date':
                // If there are new timezones, recreate the helper.
                if (
                    !$this->engine->hasHelper('date')
                    || ($this->engine->hasHelper('date')
                        && (
                            $this->timezoneSource !== $this->dateFormatHelper->getTimezoneSource()
                            || $this->timezoneDestination !== $this->dateFormatHelper->getTimezoneDestination()
                        )
                    )
                ) {
                    $this->dateFormatHelper = new DateFormatHelper($this->timezoneSource, $this->timezoneDestination);
                    $this->engine->addHelper('date', $this->dateFormatHelper);
                }
                break;

            case 'boolean':
                if (!$this->engine->hasHelper('bool')) {
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
                }
                break;

            case 'string':
            case 'text':
                if (!$this->engine->hasHelper('zip')) {
                    $this->engine->addHelper(
                        'zip',
                        [
                            'short' => function ($value) {
                                $dash = strpos((string) $value, '-');

                                return $dash ? substr((string) $value, 0, $dash) : $value;
                            },
                        ]
                    );
                }
                if (!$this->engine->hasHelper('trim')) {
                    $this->engine->addHelper(
                        'trim',
                        [
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
                }
                if (!$this->engine->hasHelper('strip')) {
                    $this->engine->addHelper(
                        'strip',
                        [
                            'comma'       => function ($value) {
                                return str_replace(',', '', $value);
                            },
                            'doublequote' => function ($value) {
                                return str_replace('"', '', $value);
                            },
                            'singlequote' => function ($value) {
                                return str_replace("'", '', $value);
                            },
                            'html'        => function ($value) {
                                return strip_tags($value);
                            },
                            // Not currently documented (will not show up in suggestions).
                            'nonascii'    => function ($value) {
                                return utf8_strip_non_ascii($value);
                            },
                            'nonnumeric'  => function ($value) {
                                return preg_replace('/[^0-9,.]/', '', $value);
                            },
                            'numeric'     => function ($value) {
                                return preg_replace('/[0-9]+/', '', $value);
                            },
                        ]
                    );
                }
                if (!$this->engine->hasHelper('case')) {
                    $this->engine->addHelper(
                        'case',
                        [
                            'first' => function ($value) {
                                return utf8_ucfirst($value);
                            },
                            'lower' => function ($value) {
                                return utf8_strtolower($value);
                            },
                            'upper' => function ($value) {
                                return utf8_strtoupper($value);
                            },
                        ]
                    );
                }
                if (!$this->engine->hasHelper('url')) {
                    $this->engine->addHelper(
                        'url',
                        [
                            // Not currently documented (will not show up in suggestions).
                            'decode' => function ($value) {
                                return rawurldecode($value);
                            },
                            'encode' => function ($value) {
                                return rawurlencode($value);
                            },
                        ]
                    );
                }
                if (!$this->engine->hasHelper('tel')) {
                    $this->engine->addHelper(
                        'tel',
                        [
                            'e164'  => function ($value) {
                                $phone = trim($value);
                                if (!empty($value)) {
                                    if (!$this->phoneHelper) {
                                        $this->phoneHelper = new PhoneNumberHelper();
                                    }
                                    try {
                                        $phone = $this->phoneHelper->format($phone);
                                        if (!empty($phone)) {
                                            // +12223334444
                                            $value = $phone;
                                        }
                                    } catch (\Exception $e) {
                                    }
                                }

                                return $value;
                            },
                            'idash' => function ($value) {
                                $phone = trim($value);
                                if (!empty($value)) {
                                    if (!$this->phoneHelper) {
                                        $this->phoneHelper = new PhoneNumberHelper();
                                    }
                                    try {
                                        $phone = $this->phoneHelper->format($phone);
                                        if (!empty($phone)) {
                                            // 1-222-333-4444
                                            $value = substr($phone, 1, 1).'-'
                                                .substr($phone, 2, 3).'-'
                                                .substr($phone, 5, 3).'-'
                                                .substr($phone, 8, 4);
                                        }
                                    } catch (\Exception $e) {
                                    }
                                }

                                return $value;
                            },
                            'ldash' => function ($value) {
                                $phone = trim($value);
                                if (!empty($value)) {
                                    if (!$this->phoneHelper) {
                                        $this->phoneHelper = new PhoneNumberHelper();
                                    }
                                    try {
                                        $phone = $this->phoneHelper->format($phone);
                                        if (!empty($phone)) {
                                            // 222-333-4444
                                            $value = substr($phone, 2, 3).'-'
                                                .substr($phone, 5, 3).'-'
                                                .substr($phone, 8, 4);
                                        }
                                    } catch (\Exception $e) {
                                    }
                                }

                                return $value;
                            },
                            'ipar'  => function ($value) {
                                $phone = trim($value);
                                if (!empty($value)) {
                                    if (!$this->phoneHelper) {
                                        $this->phoneHelper = new PhoneNumberHelper();
                                    }
                                    try {
                                        $phone = $this->phoneHelper->format($phone);
                                        if (!empty($phone)) {
                                            // 1 (222) 333-4444
                                            $value = substr($phone, 1, 1).
                                                ' ('.substr($phone, 2, 3).') '
                                                .substr($phone, 5, 3).'-'
                                                .substr($phone, 8, 4);
                                        }
                                    } catch (\Exception $e) {
                                    }
                                }

                                return $value;
                            },
                            'lpar'  => function ($value) {
                                $phone = trim($value);
                                if (!empty($value)) {
                                    if (!$this->phoneHelper) {
                                        $this->phoneHelper = new PhoneNumberHelper();
                                    }
                                    try {
                                        $phone = $this->phoneHelper->format($phone);
                                        if (!empty($phone)) {
                                            // (222) 333-4444
                                            $value = '('.substr($phone, 2, 3).') '
                                                .substr($phone, 5, 3).'-'
                                                .substr($phone, 8, 4);
                                        }
                                    } catch (\Exception $e) {
                                    }
                                }

                                return $value;
                            },
                            'idot'  => function ($value) {
                                $phone = trim($value);
                                if (!empty($value)) {
                                    if (!$this->phoneHelper) {
                                        $this->phoneHelper = new PhoneNumberHelper();
                                    }
                                    try {
                                        $phone = $this->phoneHelper->format($phone);
                                        if (!empty($phone)) {
                                            // 1.222.333.4444
                                            $value = substr($phone, 1, 1).'.'
                                                .substr($phone, 2, 3).'.'
                                                .substr($phone, 5, 3).'.'
                                                .substr($phone, 8, 4);
                                        }
                                    } catch (\Exception $e) {
                                    }
                                }

                                return $value;
                            },
                            'ldot'  => function ($value) {
                                $phone = trim($value);
                                if (!empty($value)) {
                                    if (!$this->phoneHelper) {
                                        $this->phoneHelper = new PhoneNumberHelper();
                                    }
                                    try {
                                        $phone = $this->phoneHelper->format($phone);
                                        if (!empty($phone)) {
                                            // 222.333.4444
                                            $value = substr($phone, 2, 3).'.'
                                                .substr($phone, 5, 3).'.'
                                                .substr($phone, 8, 4);
                                        }
                                    } catch (\Exception $e) {
                                    }
                                }

                                return $value;
                            },
                        ]
                    );
                }
                break;
        }
    }

    /**
     * Scan a string for tokens using the same engine.
     *
     * @param $string
     *
     * @return array a unique list of all the raw mustache tags found in the string
     */
    public function getTokens($string)
    {
        $tokens      = [];
        $scanResults = $this->engine->getTokenizer()->scan($string);

        foreach ($scanResults as $scanResult) {
            if (
                isset($scanResult['type'])
                && isset($scanResult['name'])
                && \Mustache_Tokenizer::T_ESCAPED === $scanResult['type']
            ) {
                $tokens[$scanResult['name']] = true;
            }
        }

        return array_keys($tokens);
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
            'region'   => $this->formatString,
            'tel'      => $this->formatTel,
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
            $tza                  = $this->coreParametersHelper->getParameter(
                'default_timezone'
            );
            $this->timezoneSource = !empty($tza) ? $tza : date_default_timezone_get();
            if ($this->contactClient) {
                $tzb = $this->contactClient->getScheduleTimezone();
            }
            $this->timezoneDestination = !empty($tzb) ? $tzb : date_default_timezone_get();
        }
        $this->addHelper('date');

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

        // @todo - Get Device data here.

        $fieldGroups = $contact->getFields();
        if ($fieldGroups) {
            foreach ($fieldGroups as $fgKey => $fieldGroup) {
                foreach ($fieldGroup as $fkey => $field) {
                    $value = isset($field['value']) ? $field['value'] : '';
                    $type  = isset($field['type']) ? $field['type'] : '';
                    if ('' !== $value && in_array($type, ['datetime', 'date', 'time'])) {
                        // Soft support for labels/values as dates/times.
                        @$newValue = $this->dateFormatHelper->format($value);
                        if (null !== $newValue && '' !== $newValue) {
                            $value = $newValue;
                        }
                    }
                    $context[$fkey] = $value;
                    if ($type) {
                        $conType[$fkey] = $type;
                    }
                }
            }
        }

        // Support multiple contacts in the future if needed by uncommenting a bit here.
        // $contacts = !empty($this->context['contacts']) ? $this->context['contacts'] : [];

        // Set the context to this contact.
        $this->sanitizeContext($context);
        $this->context = array_merge($this->context, $context);
        $this->conType = array_merge($this->conType, $conType);

        // Support multiple contacts for future batch processing.
        // $this->context['contacts']                 = $contacts;
        // $this->context['contacts'][$context['id']] = $context;

        return $this;
    }

    /**
     * Contextual field values cannot exactly match filters, or an exception will occur when rendering.
     *
     * @param $context
     */
    private function sanitizeContext(&$context)
    {
        $context = array_diff_key($context, array_flip($this->helpers));
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
                                if (
                                    'response' === $opType
                                    && $id === $operationId
                                    && null !== $responseActual[$fieldType]
                                ) {
                                    // While running in realtime.
                                    $fieldSet = $responseActual[$fieldType];
                                } else {
                                    foreach ($operation[$opType][$fieldType] as $field) {
                                        if (null !== $field['key']) {
                                            $fieldSet[$field['key']] = isset($field['value']) ? $field['value'] : null;
                                        }
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
        $campaignIdString = $this->baseEncode((int) $campaignId);
        $eventIdString    = $this->baseEncode((int) $eventId);
        $contactIdString  = $this->baseEncode((int) $contactId);

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
        if (!$context || empty($context)) {
            return $this;
        }
        $this->nestContext($context);
        $this->sanitizeContext($context);
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
     * @return string|array|object
     */
    public function render($template = '')
    {
        $result = $template;
        if (is_array($template) || is_object($template)) {
            // Recursively go down into an object/array to tokenize.
            foreach ($result as &$val) {
                $val = $this->render($val);
            }
        } elseif (
            is_string($template)
            && strlen($template) >= self::TEMPLATE_MIN_LENGTH
            && false !== strpos($template, self::TOKEN_KEY_START)
            && false !== strpos($template, self::TOKEN_KEY_END)
        ) {
            if (isset($this->renderCache[$template])) {
                // Already tokenized this exact string.
                $result = $this->renderCache[$template];
            } else {
                // A new or non-tokenized string.
                $this->lastTemplate = $template;
                set_error_handler([$this, 'handleMustacheErrors'], E_WARNING | E_NOTICE);
                $result = $this->engine->render($template, $this->context);
                restore_error_handler();
                if (null !== $result && '' !== $result) {
                    // Store the result in cache for faster lookup later.
                    $this->renderCache[$template] = $result;
                }
            }
        }

        return $result;
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
     * @param bool $sort
     *
     * @return array
     */
    public function getContextLabeled($sort = true)
    {
        $result = [];
        $labels = $this->describe($this->context);
        $this->flattenArray($labels, $result);

        $result['file_count']       = 'File: Number of contacts in this file';
        $result['file_test']        = 'File: Inserts ".test" if testing';
        $result['file_date']        = 'File: Date/time of file creation';
        $result['file_type']        = 'File: Type of file, such as csv/xsl';
        $result['file_compression'] = 'File: Compression of the file, such as zip/gz';
        $result['file_extension']   = 'File: Automatic extension such as xsl/zip/csv';
        $result['api_date']         = 'API: Date/time of the API request';

        if ($sort) {
            asort($result, SORT_STRING | SORT_FLAG_CASE | SORT_NATURAL);
        }

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
                if ($payload) {
                    // $value = implode('.', explode(' ', trim($keys))).'.'.$key;
                    // Use the last key for easy finding as these get long.
                    $value = $key;
                } elseif (is_bool($value) || null === $value || 0 === $value) {
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
     * @return array
     */
    public function getContextTypes()
    {
        $result = [];
        $types  = $this->describe($this->conType);
        $this->flattenArray($types, $result);

        $result['file_count'] = 'number';
        $result['file_date']  = 'datetime';
        $result['api_date']   = 'datetime';

        return $result;
    }

    /**
     * Capture Mustache warnings to be logged for debugging.
     *
     * @param $errno
     * @param $errstr
     * @param $errfile
     * @param $errline
     *
     * @return bool
     */
    private function handleMustacheErrors($errno, $errstr, $errfile, $errline)
    {
        if (!empty($this->lastTemplate)) {
            if (function_exists('newrelic_add_custom_parameter')) {
                call_user_func(
                    'newrelic_add_custom_parameter',
                    'contactclientToken',
                    $this->lastTemplate
                );
            }
        }
        if (!empty($this->context)) {
            if (function_exists('newrelic_add_custom_parameter')) {
                call_user_func(
                    'newrelic_add_custom_parameter',
                    'contactclientContext',
                    json_encode($this->context)
                );
            }
        }
        $this->logger->error(
            'Contact Client '.$this->contactClient->getId().
            ': Warning issued with Template: '.$this->lastTemplate.' Context: '.json_encode($this->context)
        );

        return true;
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
