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

    const SETTING_DEF_AUTOUPDATE      = true;

    const SETTING_DEF_CONNECT_TIMEOUT = 10;

    const SETTING_DEF_DELAY           = 15;

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
        'delay'           => self::SETTING_DEF_DELAY,
        'autoUpdate'      => self::SETTING_DEF_AUTOUPDATE,
    ];

    /** @var ContactClient */
    protected $contactClient;

    /** @var Contact */
    protected $contact;

    /** @var array */
    protected $payload;

    /** @var array */
    protected $operations = [];

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
    protected $event;

    /** @var Campaign */
    protected $campaign;

    /**
     * ApiPayload constructor.
     *
     * @param contactClientModel $contactClientModel
     * @param Transport          $transport
     * @param TokenHelper        $tokenHelper
     */
    public function __construct(
        contactClientModel $contactClientModel,
        Transport $transport,
        tokenHelper $tokenHelper
    ) {
        $this->contactClientModel = $contactClientModel;
        $this->transport          = $transport;
        $this->tokenHelper        = $tokenHelper;
    }

    /**
     * Reset local class variables.
     *
     * @param array $exclusions optional array of local variables to keep current values
     *
     * @return $this
     */
    public function reset($exclusions = ['contactClientModel', 'transport', 'tokenHelper'])
    {
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
        $this->setLogs($campaign->getId(), 'campaign');
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
    private function setPayload(string $payload = null)
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
     * Step through all operations defined.
     *
     * @return $this
     *
     * @throws ContactClientException
     */
    public function run()
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
        // We will create and reuse the same Transport session throughout our operations.
        /** @var Transport $transport */
        $transport     = $this->getTransport();
        $tokenHelper   = $this->tokenHelper->newSession($this->contactClient, $this->contact, $this->payload);
        $updatePayload = (bool) $this->settings['autoUpdate'];

        foreach ($this->payload->operations as $id => &$operation) {
            $logs         = [];
            $apiOperation = new ApiOperation(
                $id + 1, $operation, $transport, $tokenHelper, $this->test, $updatePayload
            );
            $this->valid  = false;
            try {
                $apiOperation->run();
                $this->valid = $apiOperation->getValid();
            } catch (\Exception $e) {
                // Delay this exception throw...
            }
            $logs = array_merge($apiOperation->getLogs(), $logs);
            $this->setLogs($logs, $id);

            if (!$this->valid) {
                // Break the chain of operations if an invalid response or exception occurs.
                break;
            } else {
                // Aggregate successful responses that are mapped to Contact fields.
                $this->responseMap = array_merge($this->responseMap, $apiOperation->getResponseMap());
                $this->setAggregateActualResponses($apiOperation->getResponseActual());
            }
        }

        // Get the remote ID after the last operation for logging purposes.
        if ($this->valid && isset($apiOperation)) {
            $this->externalId = $apiOperation->getExternalId();
        }

        // Update the payload if enabled.
        if ($updatePayload) {
            $this->updatePayload();
        }

        // Intentionally delayed exception till after logging and payload update.
        if (isset($e)) {
            throw $e;
        }

        return $this;
    }

    /**
     * Retrieve the transport service for API interaction.
     *
     * @return Transport
     */
    private function getTransport()
    {
        // Set our internal settings that are pertinent.
        $this->transport->setSettings($this->settings);

        return $this->transport;
    }

    /**
     * @param       $responseActual
     * @param array $types
     *
     * @return $this
     */
    public function setAggregateActualResponses($responseActual, $types = ['headers', 'body'])
    {
        foreach ($types as $type) {
            if (!isset($this->aggregateActualResponses[$type])) {
                $this->aggregateActualResponses[$type] = [];
            }
            if (isset($responseActual[$type])) {
                $this->aggregateActualResponses[$type] = array_merge(
                    $this->aggregateActualResponses[$type],
                    $responseActual[$type]
                );
            }
        }

        return $this;
    }

    /**
     * Update the payload with the parent ContactClient because we've updated the response expectation.
     */
    private function updatePayload()
    {
        if ($this->contactClient) {
            $this->sortPayloadFields();
            $payloadJSON = json_encode($this->payload, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
            if ($this->contactClient->getAPIPayload() !== $payloadJSON) {
                $this->contactClient->setAPIPayload($payloadJSON);
                if (!$this->contactClient->isNew()) {
                    try {
                        $this->contactClientModel->saveEntity($this->contactClient);
                        $this->setLogs('Updated our response payload expectations.', 'payload');
                    } catch (\Exception $e) {
                        $this->setLogs('Unable to save updates to the payload. '.$e->getMessage(), 'error');
                    }
                }
            }
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
     * Apply the responsemap to update a contact entity.
     *
     * @return bool
     */
    public function applyResponseMap()
    {
        $updated     = false;
        $responseMap = $this->getResponseMap();
        // Check the responseMap to discern where field values should go.
        if (count($responseMap)) {
            foreach ($responseMap as $alias => $value) {
                $oldValue = $this->contact->getFieldValue($alias);
                if ($oldValue !== $value) {
                    $this->contact->addUpdatedField($alias, $value, $oldValue);
                    $this->setLogs('Updating Contact: '.$alias.' = '.$value);
                    $updated = true;
                }
            }
        }

        return $updated;
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

        return $result;
    }

    /**
     * Get the most recent non-empty response value by field name, ignoring validity.
     *
     * @param       $fieldName
     * @param array $types
     *
     * @return null|string
     */
    public function getAggregateResponseFieldValue($fieldName, $types = ['headers', 'body'])
    {
        if ($this->valid) {
            if (isset($this->aggregateActualResponses)) {
                foreach ($types as $type) {
                    if (
                        !empty($this->aggregateActualResponses[$type])
                        && !empty($this->aggregateActualResponses[$type][$fieldName])
                    ) {
                        return $this->aggregateActualResponses[$type][$fieldName];
                    }
                }
            }
        }

        return null;
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
        if (!empty($event['contactclient_overrides'])) {
            // Flatten overrides to key-value pairs.
            $jsonHelper = new JSONHelper();
            $array      = $jsonHelper->decodeArray($event['contactclient_overrides'], 'Overrides');
            if ($array) {
                foreach ($array as $field) {
                    if (!empty($field->key) && !empty($field->value) && (empty($field->enabled) || true === $field->enabled)) {
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
     * @param $overrides
     *
     * @return $this
     */
    public function setOverrides($overrides)
    {
        $fieldsOverridden = [];
        if (isset($this->payload->operations)) {
            foreach ($this->payload->operations as $id => &$operation) {
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
     * This tokenHelper will be reused throughout the File operations so that they can be context aware.
     *
     * @return TokenHelper
     */
    private function getTokenHelper()
    {
        $this->tokenHelper->setContactClient($this->contactClient);
        $this->tokenHelper->setContext([]);
        $this->tokenHelper->addContextContact($this->contact);
        if ($this->payload) {
            $this->tokenHelper->addContext(['payload' => $this->payload]);
        }

        return $this->tokenHelper;
    }
}
