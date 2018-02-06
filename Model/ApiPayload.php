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
use MauticPlugin\MauticContactClientBundle\Services\Transport;
use MauticPlugin\MauticContactClientBundle\Helper\TokenHelper;
use MauticPlugin\MauticContactClientBundle\Model\ApiPayloadOperation as ApiOperation;
use Symfony\Component\DependencyInjection\Container;

/**
 * Class ApiPayload
 * @package MauticPlugin\MauticContactClientBundle\Model
 */
class ApiPayload
{

    const SETTING_DEF_LIMIT = 300;
    const SETTING_DEF_TIMEOUT = 30;
    const SETTING_DEF_CONNECT_TIMEOUT = 10;
    const SETTING_DEF_ATTEMPTS = 3;
    const SETTING_DEF_DELAY = 15;
    const SETTING_DEF_AUTOUPDATE = true;

    /**
     * @var array Simple settings for this integration instance from the payload.
     */
    protected $settings = [
        'limit' => self::SETTING_DEF_LIMIT,
        'timeout' => self::SETTING_DEF_TIMEOUT,
        'connect_timeout' => self::SETTING_DEF_CONNECT_TIMEOUT,
        'attempts' => self::SETTING_DEF_ATTEMPTS,
        'delay' => self::SETTING_DEF_DELAY,
        'autoUpdate' => self::SETTING_DEF_AUTOUPDATE,
    ];

    /** @var ContactClient */
    protected $contactClient;

    /** @var Contact */
    protected $contact;

    protected $payload;

    protected $operations = [];

    protected $test = false;

    protected $logs = [];

    protected $service;

    protected $valid = true;

    protected $tokenHelper;

    protected $responseMap = [];

    protected $responseAggregate = [];


    /**
     * @var Container $container
     */
    protected $container;

    protected $externalId = null;

    /**
     * ApiPayload constructor.
     * @param ContactClient $contactClient
     * @param null $contact
     * @param null $container
     * @param bool $test
     * @throws \Exception
     */
    public function __construct(ContactClient $contactClient, $contact = null, $container = null, $test = false)
    {
        $this->contactClient = $contactClient;
        $this->container = $container;
        $this->contact = $contact;
        $this->test = $test;
        $this->setPayload($this->contactClient->getApiPayload());
        $this->setSettings(!empty($this->payload->settings) ? $this->payload->settings : null);
    }

    /**
     * Take the stored JSON string and parse for use.
     *
     * @param string $payload
     * @return mixed
     * @throws \Exception
     */
    public function setPayload(string $payload)
    {
        if (!$payload) {
            throw new \Exception('API instructions payload is blank.');
        }
        $payload = json_decode($payload);
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
            throw new \Exception('API instructions payload JSON is invalid: '.$jsonError);
        }
        if (!$payload || !is_object($payload)) {
            throw new \Exception('API instructions payload is invalid.');
        }

        return $this->payload = $payload;
    }

    /**
     * Retrieve API settings from the payload to override our defaults.
     * @param object $settings
     */
    public function setSettings($settings)
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
     * Step through all operations defined.
     *
     * @return bool
     * @throws \Exception
     */
    public function run()
    {
        if (!isset($this->payload->operations) || !count($this->payload->operations)) {
            throw new \Exception('API instructions payload has no operations to run.');
        }
        // We will create and reuse the same Transport session throughout our operations.
        /** @var Transport $service */
        $service = $this->getService();
        $tokenHelper = $this->getTokenHelper();
        $updatePayload = (bool)$this->settings['autoUpdate'];

        foreach ($this->payload->operations as $id => &$operation) {
            $logs = [];
            $apiOperation = new ApiOperation($id, $operation, $service, $tokenHelper, $this->test, $updatePayload);
            try {
                $apiOperation->run();
                $this->valid = $apiOperation->getValid();
            } catch (\Exception $e) {
                $logs[] = $e->getMessage();
                $this->valid = false;
            }
            $logs = array_merge($apiOperation->getLogs(), $logs);
            $this->setLogs($logs, $id);

            if (!$this->valid) {
                // Break the chain of operations if an invalid response or exception occurs.
                break;
            } else {
                // Aggregate successful responses that are mapped to Contact fields.
                $this->responseMap = array_merge($this->responseMap, $apiOperation->getResponseMap());
                $this->responseAggregate = array_merge($this->responseAggregate, $apiOperation->getResponseActual());
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

        return $this->valid;
    }

    /**
     * Retrieve the transport service for API interaction.
     */
    private function getService()
    {
        if (!$this->service) {
            /** @var Transport service */
            $this->service = $this->container->get('mautic.contactclient.service.transport');
            // Set our internal settings that are pertinent.
            $this->service->setSettings($this->settings);
        }

        return $this->service;
    }

    /**
     * Retrieve the transport service for API interaction.
     * This tokenHelper will be reused throughout the API operations so that they can be context aware.
     */
    private function getTokenHelper()
    {
        if (!$this->tokenHelper) {
            try {
                /** @var tokenHelper $tokenHelper */
                $this->tokenHelper = $this->container->get('mautic.contactclient.helper.token');

                // Set the timezones for date/time conversion.
                $tza = $this->container->get('mautic.helper.core_parameters')->getParameter(
                    'default_timezone'
                ) ?: date_default_timezone_get();
                $tzb = $this->contactClient->getScheduleTimezone() ?: date_default_timezone_get();
                $this->tokenHelper->setTimezones($tza, $tzb);

                // Add the Contact as context for field replacement.
                $this->tokenHelper->addContextContact($this->contact);

                // Include the payload as additional context.
                $this->tokenHelper->addContext(['payload' => $this->payload]);

            } catch (\Exception $e) {
                // @todo - not sure what could go wrong here yet
            }
        }

        return $this->tokenHelper;
    }

    /**
     * Update the payload with the parent ContactClient because we've updated the response expectation.
     */
    private function updatePayload()
    {
        if ($this->contactClient) {
            $payloadJSON = json_encode($this->payload, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
            if ($this->contactClient->getAPIPayload() !== $payloadJSON) {
                $this->contactClient->setAPIPayload($payloadJSON);

                if ($this->contactClient->getId()) {
                    try {
                        /** @var ContactClientModel $contactClientModel */
                        $contactClientModel = $this->container->get('mautic.contactclient.model.contactclient');
                        $contactClientModel->saveEntity($this->contactClient);
                        $this->setLogs('Updated our response payload expectations.', 'payload');
                    } catch (Exception $e) {
                        $this->setLogs('Unable to save updates to the Contact Client. '.$e->getMessage(), 'error');
                    }
                }
            } else {
                $this->setLogs('Payload responses matched expectations, no updates necessary.', 'payload');
            }
        }
    }

    public function getLogs()
    {
        return $this->logs;
    }

    function setLogs($value, $type = null)
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
     * @return bool
     */
    public function applyResponseMap()
    {
        $updated = false;
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
     * @return array
     */
    public function getResponseMap()
    {
        return $this->responseMap;
    }

    /**
     * @return Contact|null
     */
    public function getContact()
    {
        return $this->contact;
    }

    /**
     * Retrieve from the payload all outgoing fields that are set to overridable.
     *
     * @return array
     */
    public function getOverridableFields()
    {
        $result = [];
        if (isset($this->payload->operations)) {
            foreach ($this->payload->operations as $id => $operation) {
                if (isset($operation->request)) {
                    foreach (['headers', 'body'] as $type) {
                        if (isset($operation->request->{$type})) {
                            foreach ($operation->request->{$type} as $field) {
                                if (isset($field->overridable) && $field->overridable === true) {
                                    // Remove irrelevant data, since this result will need to be light-weight.
                                    unset($field->default_value);
                                    unset($field->test_value);
                                    unset($field->test_only);
                                    unset($field->overridable);
                                    unset($field->required);
                                    $result[(string)$field->key] = $field;
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
     * @param $fieldName
     * @param array $types
     * @return null
     */
    public function getResponseFieldValue($fieldName, $types = ['headers', 'body'])
    {
        if ($this->valid) {
            if (isset($this->responseAggregate)) {
                foreach ($types as $type) {
                    if (!empty($this->responseAggregate[$type])) {
                        foreach ($this->responseAggregate[$type] as $key => $field) {
                            if ($key == $fieldName && !empty($field)) {
                                return $field;
                            }
                        }
                    }
                }
            }
        }

        return null;
    }

    /**
     * Override the default field values, if allowed.
     *
     * @param array $overrides Key value pair array.
     */
    public function setOverrides($overrides)
    {
        if (isset($this->payload->operations)) {
            foreach ($this->payload->operations as $id => &$operation) {
                if (isset($operation->request)) {
                    foreach (['headers', 'body'] as $type) {
                        if (isset($operation->request->{$type})) {
                            foreach ($operation->request->{$type} as &$field) {
                                if (
                                    isset($field->overridable)
                                    && $field->overridable === true
                                    && isset($field->key)
                                    && isset($overrideValues[$field->key])
                                ) {
                                    $field->value = $overrides[$field->key];
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * Get the external ID acquired/assumed by the last successful API operation.
     * @return mixed
     */
    public function getExternalId()
    {
        return $this->externalId;
    }
}