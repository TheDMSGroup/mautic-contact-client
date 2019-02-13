<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Digital Media Solutions, LLC
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticContactClientBundle\Model;

use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\LeadBundle\Entity\Lead as Contact;
use MauticPlugin\MauticContactClientBundle\Entity\ContactClient;
use MauticPlugin\MauticContactClientBundle\Entity\Stat;
use MauticPlugin\MauticContactClientBundle\Exception\ContactClientException;
use MauticPlugin\MauticContactClientBundle\Helper\JSONHelper;
use MauticPlugin\MauticContactClientBundle\Helper\TokenHelper;
use MauticPlugin\MauticContactClientBundle\Model\ApiPayloadOperation as ApiOperation;
use MauticPlugin\MauticContactClientBundle\Services\Transport;

/**
 * Class ApiPayload.
 */
class ApiPayload
{
    const SETTING_DEF_ATTEMPTS        = 3;

    const SETTING_DEF_AUTORETRY       = false;

    const SETTING_DEF_AUTOUPDATE      = true;

    const SETTING_DEF_CONNECT_TIMEOUT = 10;

    const SETTING_DEF_LIMIT           = 300;

    const SETTING_DEF_TIMEOUT         = 30;

    /**
     * Simple settings for this integration instance from the payload.
     *
     * @var array
     */
    protected $settings = [
        'limit'           => self::SETTING_DEF_LIMIT,
        'timeout'         => self::SETTING_DEF_TIMEOUT,
        'connect_timeout' => self::SETTING_DEF_CONNECT_TIMEOUT,
        'attempts'        => self::SETTING_DEF_ATTEMPTS,
        'autoUpdate'      => self::SETTING_DEF_AUTOUPDATE,
        'autoRetry'       => self::SETTING_DEF_AUTORETRY,
    ];

    /** @var ContactClient */
    protected $contactClient;

    /** @var Contact */
    protected $contact;

    /** @var object */
    protected $payload;

    /** @var bool */
    protected $test = false;

    /** @var array */
    protected $logs = [];

    /** @var Transport */
    protected $transport;

    /** @var bool */
    protected $valid = false;

    /** @var TokenHelper */
    protected $tokenHelper;

    /** @var array */
    protected $responseMap = [];

    /** @var array */
    protected $aggregateActualResponses = [];

    /** @var string */
    protected $externalId = null;

    /** @var contactClientModel */
    protected $contactClientModel;

    /** @var array */
    protected $event = [];

    /** @var Campaign */
    protected $campaign;

    /** @var Schedule */
    protected $scheduleModel;

    /** @var bool */
    protected $updatedFields = false;

    /** @var bool True to allow an authentication pre-flight check. Set to false to run as normal. */
    protected $allowPreAuthAttempt = true;

    /** @var ApiPayloadAuth */
    protected $apiPayloadAuth;

    /** @var int Starting operation ID. */
    protected $start = 0;

    /**
     * ApiPayload constructor.
     *
     * @param contactClientModel $contactClientModel
     * @param Transport          $transport
     * @param TokenHelper        $tokenHelper
     * @param Schedule           $scheduleModel
     * @param ApiPayloadAuth     $apiPayloadAuth
     */
    public function __construct(
        contactClientModel $contactClientModel,
        Transport $transport,
        tokenHelper $tokenHelper,
        Schedule $scheduleModel,
        ApiPayloadAuth $apiPayloadAuth
    ) {
        $this->contactClientModel = $contactClientModel;
        $this->transport          = $transport;
        $this->tokenHelper        = $tokenHelper;
        $this->scheduleModel      = $scheduleModel;
        $this->apiPayloadAuth     = $apiPayloadAuth;
    }

    /**
     * Reset local class variables.
     *
     * @param array $exclusions optional array of local variables to keep current values
     *
     * @return $this
     */
    public function reset(
        $exclusions = ['contactClientModel', 'transport', 'tokenHelper', 'scheduleModel', 'apiPayloadAuth']
    ) {
        foreach (array_diff_key(
                     get_class_vars(get_class($this)),
                     array_flip($exclusions)
                 ) as $name => $default) {
            $this->$name = $default;
        }

        return $this;
    }

    /**
     * @return Contact
     */
    public function getContact()
    {
        return $this->contact;
    }

    /**
     * @param Contact $contact
     *
     * @return $this
     */
    public function setContact(Contact $contact)
    {
        $this->contact = $contact;

        return $this;
    }

    /**
     * @return Campaign
     */
    public function getCampaign()
    {
        return $this->campaign;
    }

    /**
     * @param Campaign|null $campaign
     *
     * @return $this
     */
    public function setCampaign(Campaign $campaign = null)
    {
        if ($campaign instanceof Campaign) {
            $this->setLogs($campaign->getId(), 'campaign');
        }
        $this->campaign = $campaign;

        return $this;
    }

    /**
     * @return ContactClient
     */
    public function getContactClient()
    {
        return $this->contactClient;
    }

    /**
     * @param ContactClient $contactClient
     *
     * @return $this
     *
     * @throws ContactClientException
     */
    public function setContactClient(ContactClient $contactClient)
    {
        $this->contactClient = $contactClient;
        $this->setPayload($this->contactClient->getApiPayload());

        return $this;
    }

    /**
     * Take the stored JSON string and parse for use.
     *
     * @param string|null $payload
     *
     * @return $this
     *
     * @throws ContactClientException
     */
    private function setPayload($payload = null)
    {
        if (!$payload && $this->contactClient) {
            $payload = $this->contactClient->getApiPayload();
        }
        if (!$payload) {
            throw new ContactClientException(
                'API instructions not set.',
                0,
                null,
                Stat::TYPE_INVALID,
                false,
                null,
                $this->contactClient ? $this->contactClient->toArray() : null
            );
        }

        $jsonHelper = new JSONHelper();
        try {
            $this->payload = $jsonHelper->decodeObject($payload, 'Payload');
        } catch (\Exception $e) {
            throw new ContactClientException(
                'API instructions malformed.',
                0,
                $e,
                Stat::TYPE_INVALID,
                false,
                null,
                $this->contactClient ? $this->contactClient->toArray() : null
            );
        }
        $this->setSettings(!empty($this->payload->settings) ? $this->payload->settings : null);

        return $this;
    }

    /**
     * Retrieve API settings from the payload to override our defaults.
     *
     * @param object $settings
     */
    private function setSettings($settings)
    {
        if ($settings) {
            foreach ($this->settings as $key => &$value) {
                if (!empty($settings->{$key}) && $settings->{$key}) {
                    $value = $settings->{$key};
                }
            }
        }
    }

    /**
     * @return bool
     */
    public function getValid()
    {
        return $this->valid;
    }

    public function getTest()
    {
        return $this->test;
    }

    public function setTest($test)
    {
        $this->test = $test;

        return $this;
    }

    /**
     * Returns the expected send time for limit evaluation.
     * Throws an exception if an open slot is not available.
     *
     * @return \DateTime
     *
     * @throws ContactClientException
     */
    public function evaluateSchedule()
    {
        $this->scheduleModel->reset()
            ->setContactClient($this->contactClient)
            ->setTimezone()
            ->evaluateDay()
            ->evaluateTime()
            ->evaluateExclusions();

        return new \DateTime();
    }

    /**
     * Step through all operations defined.
     *
     * @return $this
     *
     * @throws ContactClientException
     */
    public function run()
    {
        $this->validateOperations();
        $this->prepareTransport();
        $this->prepareTokenHelper();
        $this->preparePayloadAuth();

        try {
            $this->runApiOperations();
        } catch (\Exception $e) {
            if (
                $this->start
                && $e instanceof ContactClientException
                && Stat::TYPE_AUTH === $e->getStatType()
            ) {
                // We failed the pre-auth run due to an auth-related issue. Flush the tokenHelper.
                $this->prepareTokenHelper();
                // Try a standard run from the top assuming authentication is needed again.
                $this->start = 0;
                $this->runApiOperations();
            } else {
                throw $e;
            }
        }

        return $this;
    }

    /**
     * @throws ContactClientException
     */
    private function validateOperations()
    {
        if (!isset($this->payload->operations) || !count($this->payload->operations)) {
            throw new ContactClientException(
                'There are no API operations to run.',
                0,
                null,
                Stat::TYPE_INVALID,
                false
            );
        }
    }

    /**
     * Apply custom settings to the transport for this API operation set.
     *
     * @return Transport
     */
    private function prepareTransport()
    {
        // Set our internal settings that are pertinent.
        $this->transport->setSettings($this->settings);

        return $this->transport;
    }

    /**
     * Apply our context to create a new tokenhelper session.
     */
    private function prepareTokenHelper()
    {
        $this->tokenHelper->newSession(
            $this->contactClient,
            $this->contact,
            $this->payload,
            $this->campaign,
            $this->event
        );
        // Add any additional tokens here that are only needed for API payloads.
        $this->tokenHelper->addContext(
            [
                'api_date' => $this->tokenHelper->getDateFormatHelper()->format(new \DateTime()),
            ]
        );
    }

    /**
     * Prepare the APIPayloadAuth model and get the starting operation ID it reccomends.
     */
    private function preparePayloadAuth()
    {
        $this->apiPayloadAuth->reset()
            ->setTest($this->test)
            ->setContactClient($this->contactClient)
            ->setOperations($this->payload->operations);

        $this->start = $this->apiPayloadAuth->getStartOperation();
        if ($this->start) {
            $context = $this->apiPayloadAuth->getPreviousPayloadAuthTokens();
            $this->tokenHelper->addContext($context);
        }
    }

    /**
     * @throws \Exception
     */
    private function runApiOperations()
    {
        $updatePayload = (bool) $this->getSettings('autoUpdate');
        $opsRemaining  = count($this->payload->operations);

        foreach ($this->payload->operations as $id => &$operation) {
            if ($id < $this->start) {
                // Running a pre-auth attempt with step/s to be skipped at the beginning.
                continue;
            }
            $logs         = [];
            $apiOperation = new ApiOperation(
                $id + 1, $operation, $this->transport, $this->tokenHelper, $this->test, $updatePayload
            );
            $this->valid  = false;
            try {
                $apiOperation->run();
                $this->valid = $apiOperation->getValid();
            } catch (\Exception $e) {
                // Delay this exception throw till after we can do some important logging.
            }
            $logs = array_merge($apiOperation->getLogs(), $logs);
            $this->setLogs($logs, ($this->start ? 'preAuth'.$id : $id));

            if (!$this->valid) {
                // Break the chain of operations if an invalid response or exception occurs.
                break;
            }

            // Aggregate successful responses that are mapped to Contact fields.
            $this->responseMap = array_merge($this->responseMap, $apiOperation->getResponseMap());
            $responseActual    = $apiOperation->getResponseActual();
            $this->setAggregateActualResponses($responseActual, $id);
            $this->savePayloadAuthTokens($id);
            --$opsRemaining;
            if ($opsRemaining) {
                // Update the contextual awareness for subsequent requests if needed.
                $this->applyResponseMap(true);
                // Update context to include actual previous payload responses.
                if ($responseActual) {
                    $this->tokenHelper->addContextPayload($this->payload, $id, $responseActual);
                }
            }
        }

        // Get the remote ID after the last operation for logging purposes.
        if ($this->valid && isset($apiOperation)) {
            $this->externalId = $apiOperation->getExternalId();
        }

        // Update the payload if enabled.
        if ($updatePayload && $this->test) {
            $this->updatePayload();
        }

        // Intentionally delayed exception till after logging and payload update.
        if (isset($e)) {
            throw $e;
        }
    }

    /**
     * @param null $setting
     *
     * @return array|mixed|null
     */
    public function getSettings($setting = null)
    {
        if ($setting) {
            return isset($this->settings[$setting]) ? $this->settings[$setting] : null;
        }

        return $this->settings;
    }

    /**
     * If we just made a successful run with an auth operation, without skipping said operation,
     * preserve the applicable auth tokens for future use.
     *
     * @param $operationId
     */
    private function savePayloadAuthTokens($operationId)
    {
        if (
            $this->valid
            && $this->apiPayloadAuth->hasAuthRequest($operationId)
        ) {
            $fieldSets = $this->getAggregateActualResponses(null, $operationId);
            $this->apiPayloadAuth->savePayloadAuthTokens($operationId, $fieldSets);
        }
    }

    /**
     * Get the most recent non-empty response value by field name, ignoring validity.
     * Provide key, or operationId or both.
     *
     * @param string $key
     * @param int    $operationId
     * @param array  $types       Types to check for (header/body/etc)
     */
    public function getAggregateActualResponses($key = null, $operationId = null, $types = ['headers', 'body'])
    {
        if ($this->valid && isset($this->aggregateActualResponses)) {
            if (null !== $key) {
                foreach ($types as $type) {
                    if (null !== $operationId) {
                        if (isset($this->aggregateActualResponses[$operationId][$type][$key])) {
                            return $this->aggregateActualResponses[$operationId][$type][$key];
                        }
                    } else {
                        foreach (array_reverse($this->aggregateActualResponses) as $values) {
                            if (isset($values[$type][$key])) {
                                return $values[$type][$key];
                            }
                        }
                    }
                }
            } else {
                // Get the entire result, separated by types.
                $result = [];
                foreach ($types as $type) {
                    if (isset($this->aggregateActualResponses[$operationId][$type])) {
                        $result[$type] = $this->aggregateActualResponses[$operationId][$type];
                    }
                }

                return $result;
            }
        }

        return null;
    }

    /**
     * @param array $responseActual Actual response array, including headers and body
     * @param null  $operationId
     * @param array $types          types of data we wish to aggregate
     *
     * @return $this
     */
    public function setAggregateActualResponses($responseActual, $operationId, $types = ['headers', 'body'])
    {
        if (!isset($this->aggregateActualResponses[$operationId])) {
            $this->aggregateActualResponses[$operationId] = [];
        }
        foreach ($types as $type) {
            if (!isset($this->aggregateActualResponses[$operationId][$type])) {
                $this->aggregateActualResponses[$operationId][$type] = [];
            }
            if (isset($responseActual[$type])) {
                $this->aggregateActualResponses[$operationId][$type] = array_merge(
                    $this->aggregateActualResponses[$operationId][$type],
                    $responseActual[$type]
                );
            }
        }

        return $this;
    }

    /**
     * Apply the responsemap to update a contact entity.
     *
     * @param bool $updateTokens
     *
     * @return bool
     */
    public function applyResponseMap($updateTokens = false)
    {
        $responseMap = $this->getResponseMap();
        // Check the responseMap to discern where field values should go.
        if (count($responseMap)) {
            foreach ($responseMap as $alias => $value) {
                $oldValue = $this->contact->getFieldValue($alias);
                if ($oldValue !== $value) {
                    $this->contact->addUpdatedField($alias, $value, $oldValue);
                    if ($updateTokens) {
                        $this->tokenHelper->addContext([$alias => $value]);
                    }
                    $this->setLogs('Updating Contact: '.$alias.' = '.$value, 'fieldsUpdated');
                    $this->updatedFields = true;
                }
            }
        }

        return $this->updatedFields;
    }

    /**
     * Return the aggregate responsemap of all valid operations.
     *
     * @return array
     */
    public function getResponseMap()
    {
        return $this->responseMap;
    }

    /**
     * Update the payload with the parent ContactClient because we've updated the response expectation.
     */
    private function updatePayload()
    {
        if ($this->contactClient) {
            $this->sortPayloadFields();
            $payloadJSON = json_encode($this->payload, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
            $this->contactClient->setAPIPayload($payloadJSON);
        }
    }

    /**
     * Sort the fields by keys, so that the user doesn't have to.
     * Only applies when AutoUpdate is enabled.
     */
    private function sortPayloadFields()
    {
        if (isset($this->payload->operations)) {
            foreach ($this->payload->operations as $id => $operation) {
                foreach (['request', 'response'] as $opType) {
                    if (isset($operation->{$opType})) {
                        foreach (['headers', 'body'] as $fieldType) {
                            if (is_array($operation->{$opType}->{$fieldType})) {
                                usort(
                                    $operation->{$opType}->{$fieldType},
                                    function ($a, $b) {
                                        return strnatcmp(
                                            isset($a->key) ? $a->key : null,
                                            isset($b->key) ? $b->key : null
                                        );
                                    }
                                );
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * @return array
     */
    public function getLogs()
    {
        return $this->logs;
    }

    /**
     * @param      $value
     * @param null $type
     */
    public function setLogs($value, $type = null)
    {
        if ($type) {
            if (isset($this->logs[$type])) {
                if (is_array($this->logs[$type])) {
                    $this->logs[$type][] = $value;
                } else {
                    $this->logs[$type] = [
                        $this->logs[$type],
                        $value,
                    ];
                }
            } else {
                $this->logs[$type] = $value;
            }
        } else {
            $this->logs[] = $value;
        }
    }

    /**
     * Retrieve from the payload all outgoing fields that are set to overridable.
     *
     * @return array
     */
    public function getOverrides()
    {
        $result = [];
        if (isset($this->payload->operations)) {
            foreach ($this->payload->operations as $id => $operation) {
                if (isset($operation->request)) {
                    // API Payloads can optionally have their URLs overriden per request.
                    if (isset($operation->request->overridableUrl) && true === $operation->request->overridableUrl) {
                        $result['payload.operation.'.$id.'.request.url'] = [
                            'key'   => 'payload.operation.'.$id.'.request.url',
                            'value' => !empty($operation->request->url) ? $operation->request->url : '',
                        ];
                    }
                    foreach (['headers', 'body'] as $type) {
                        if (isset($operation->request->{$type})) {
                            foreach ($operation->request->{$type} as $field) {
                                if (isset($field->overridable) && true === $field->overridable) {
                                    // Remove irrelevant data, since this result will need to be light-weight.
                                    unset($field->default_value);
                                    unset($field->test_value);
                                    unset($field->test_only);
                                    unset($field->overridable);
                                    unset($field->required);
                                    $result[(string) $field->key] = $field;
                                }
                            }
                        }
                    }
                }
            }
        }
        ksort($result);

        return array_values($result);
    }

    /**
     * @param array $event
     *
     * @return $this
     *
     * @throws \Exception
     */
    public function setEvent($event = [])
    {
        if (!empty($event['id'])) {
            $this->setLogs($event['id'], 'campaignEvent');
        }
        $overrides = [];
        if (!empty($event['config']['contactclient_overrides'])) {
            // Flatten overrides to key-value pairs.
            $jsonHelper = new JSONHelper();
            $array      = $jsonHelper->decodeArray($event['config']['contactclient_overrides'], 'Overrides');
            if ($array) {
                foreach ($array as $field) {
                    if (!empty($field->key) && !empty($field->value) && (!isset($field->enabled) || true === $field->enabled)) {
                        $overrides[$field->key] = $field->value;
                    }
                }
            }
            if ($overrides) {
                $this->setOverrides($overrides);
            }
        }
        $this->event = $event;

        return $this;
    }

    /**
     * Override the default field values, if allowed.
     *
     * @param array $overrides
     *
     * @return $this
     */
    public function setOverrides($overrides)
    {
        $fieldsOverridden = [];
        if (isset($this->payload->operations)) {
            foreach ($this->payload->operations as $id => &$operation) {
                // API Payloads can optionally have their URLs overriden per request.
                $key = 'payload.operation.'.$id.'.request.url';
                if (
                    isset($operation->request->overridableUrl)
                    && true === $operation->request->overridableUrl
                    && !empty($overrides[$key])
                ) {
                    $fieldsOverridden[$key]  = [
                        'key'   => $key,
                        'value' => $overrides[$key],
                    ];
                    $operation->request->url = $overrides[$key];
                }
                if (isset($operation->request)) {
                    foreach (['headers', 'body'] as $type) {
                        if (isset($operation->request->{$type})) {
                            foreach ($operation->request->{$type} as &$field) {
                                if (
                                    isset($field->overridable)
                                    && true === $field->overridable
                                    && isset($field->key)
                                    && isset($overrides[$field->key])
                                    && null !== $overrides[$field->key]
                                ) {
                                    $field->value                  = $overrides[$field->key];
                                    $fieldsOverridden[$field->key] = $overrides[$field->key];
                                }
                            }
                        }
                    }
                }
            }
        }
        if ($fieldsOverridden) {
            $this->setLogs($fieldsOverridden, 'fieldsOverridden');
        }

        return $this;
    }

    /**
     * Get the external ID acquired/assumed by the last successful API operation.
     *
     * @return mixed
     */
    public function getExternalId()
    {
        return $this->externalId;
    }

    /**
     * @return Schedule
     */
    public function getScheduleModel()
    {
        return $this->scheduleModel;
    }
}
