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
use Mautic\PluginBundle\Exception\ApiErrorException;
use MauticPlugin\MauticContactClientBundle\Entity\ContactClient;
use MauticPlugin\MauticContactClientBundle\Model\ContactClientModel;
use MauticPlugin\MauticContactClientBundle\Helper\TokenHelper;
use MauticPlugin\MauticContactClientBundle\Model\ApiPayloadOperation as ApiOperation;
use Symfony\Component\DependencyInjection\Container;

/**
 * Class ApiPayload.
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

    protected $contact;

    protected $payload;

    protected $operations = [];

    protected $test = false;

    protected $logs = [];

    protected $service;

    protected $valid = true;

    protected $tokenHelper;

    protected $responseMap = [];

    /**
     * @var Container $container
     */
    protected $container;

    /**
     * ApiPayload constructor.
     * @param ContactClient $contactClient
     * @param Contact $contact
     * @param Container $container
     * @param bool $test
     * @throws ApiErrorException
     */
    public function __construct(ContactClient $contactClient, Contact $contact, Container $container, $test = false)
    {
        $this->contactClient = $contactClient;
        $this->container = $container;
        $this->contact = $contact;
        $this->test = $test;
        $this->setPayload($this->contactClient->getApiPayload());
        $this->setSettings($this->payload->settings ?? null);
    }

    /**
     * Take the stored JSON string and parse for use.
     *
     * @param string $payload
     * @return mixed
     * @throws ApiErrorException
     */
    public function setPayload(string $payload)
    {
        if (!$payload) {
            throw new ApiErrorException('API instructions payload is blank.');
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
            throw new ApiErrorException('API instructions payload JSON is invalid: '.$jsonError);
        }
        if (!$payload || !is_object($payload)) {
            throw new ApiErrorException('API instructions payload is invalid.');
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
     * @throws ApiErrorException
     * @throws \Exception
     */
    public function run()
    {
        if (!isset($this->payload->operations) || !count($this->payload->operations)) {
            throw new ApiErrorException('API instructions payload has no operations to run.');
        }
        // We will create and reuse the same Transport session throughout our operations.
        $service = $this->getService();
        $tokenHelper = $this->getTokenHelper();
        $updatePayload = (bool)$this->settings['autoUpdate'];

        foreach ($this->payload->operations as $id => &$operation) {
            $logs = [];
            $apiOperation = new ApiOperation($id, $operation, $service, $tokenHelper, $this->test, $updatePayload);
            try {
                $apiOperation->run();
                $this->valid = $apiOperation->getValid();
            } catch (Exception $e) {
                $logs[] = $e->getMessage();
                $this->valid = false;
            }
            $logs = array_merge($apiOperation->getLogs(), $logs);
            $this->logs[$id] = $logs;
            if (!$this->valid) {
                // Break the chain of operations if an invalid response or exception occurs.
                break;
            } else {
                // Aggregate succesful responses that are mapped to Contact fields.
                $this->responseMap = array_merge($this->responseMap, $apiOperation->getResponseMap());
            }
        }

        // Update the payload if enabled.
        if ($updatePayload) {
            $this->updatePayload();
        }

        return $this->valid;
    }

    /**
     * Update the payload with the parent ContactClient because we've updated the response expectation.
     */
    private function updatePayload(){
        if ($this->contactClient) {
            $payloadJSON = json_encode($this->payload, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
            if ($this->contactClient->getAPIPayload() !== $payloadJSON) {
                $this->contactClient->setAPIPayload($payloadJSON);

                try {
                    /** @var contactClientModel $contactClientModel */
                    $contactClientModel = $this->container->get('mautic.contactclient.model.contactclient');
                    $contactClientModel->saveEntity($this->contactClient);
                    $this->logs[] = 'Updated our response payload expectations.';
                } catch (Exception $e) {
                    $this->logs[] = 'Unable to save updates to the Contact Client. ' . $e->getMessage();
                }
            } else {
                $this->logs[] = 'Payload responses matched expectations, no updates necessary.';
            }
        }
    }

    /**
     * Retrieve the transport service for API interaction.
     */
    private function getService()
    {
        if (!$this->service) {
            try {
                $this->service = $this->container->get('mautic.contactclient.service.transport');
                // Set our internal settings that are pertinent.
                $this->service->setSettings($this->settings);
            } catch (\Exception $e) {
                // @todo - not sure what could go wrong here yet
            }
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
                $tza = $this->container->get('mautic.helper.core_parameters')->getParameter('default_timezone') ?: 'UTC';
                $tzb = $this->contactClient->getScheduleTimezone() ?: 'UTC';
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

    public function getLogs() {
        return $this->logs;
    }

    /**
     * Return the aggregate responsemap of all valid operations.
     * @return array
     */
    public function getResponseMap()
    {
        return $this->responseMap;
    }
}