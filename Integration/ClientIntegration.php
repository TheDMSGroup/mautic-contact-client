<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticContactClientBundle\Integration;

//use Mautic\LeadBundle\Entity\Lead;
//use Mautic\PluginBundle\Entity\Integration;
use Mautic\PluginBundle\Integration\AbstractIntegration;
use MauticPlugin\MauticContactClientBundle\Entity\ContactClient;
use MauticPlugin\MauticContactClientBundle\Services\Transport;

/**
 * Class ContactClientIntegration.
 *
 * @todo - Rename to ClientAPIIntegration to make room for ClientFileIntegration, with virtually no overlap.
 */
class ClientIntegration extends AbstractIntegration
{
    const SETTING_DEF_LIMIT = 300;
    const SETTING_DEF_TIMEOUT = 30;
    const SETTING_DEF_ATTEMPTS = 3;
    const SETTING_DEF_DELAY = 15;

    /**
     * @var bool Flag to indicate a failure, needing abort.
     */
    protected $abort = false;

    /**
     * @var array Errors encountered during processing.
     */
    protected $errors = [];

    /**
     * @var array Of temporary log entries.
     */
    protected $log = [];

    /**
     * @var object API instructions payload defining the API integration.
     */
    protected $payload;

    /**
     * @var bool Test mode.
     */
    protected $test = false;

    /**
     * @var array Simple settings for this integration instance from the payload.
     */
    protected $settings = [
        'limit' => self::SETTING_DEF_LIMIT,
        'timeout' => self::SETTING_DEF_TIMEOUT,
        'attempts' => self::SETTING_DEF_ATTEMPTS,
        'delay' => self::SETTING_DEF_DELAY,
    ];

    /**
     * @var array Operations to step through, and the data received.
     */
    protected $operations = [];

    /**
     * @var object Generic Transport service.
     */
    protected $service;

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getName()
    {
        return 'Client';
    }

    /**
     * @return array
     */
    public function getSupportedFeatures()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getAuthenticationType()
    {
        return 'none';
    }

    /**
     * Given the JSON API API instructions payload instruction set.
     * Send the lead/contact to the API by following the steps.
     *
     * @param string $payload
     * @param ContactClient $client
     * @return int
     */
    public function sendContact(string $payload, ContactClient $client)
    {
        $this->payload = $payload;
        // @todo - add translation layer for strings in this method.
        // $translator = $container->get('translator');

        // Run basic validations on the payload. Deep schema validation should be made on save only.
        if (!$this->payload) {
            $this->errors[] = 'API instructions payload is blank.';

            return false;
        }
        $this->payload = json_decode($this->payload);
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
            $this->errors[] = 'API instructions payload JSON is invalid: '.$jsonError;

            return false;
        }
        if (!$this->payload || !is_object($this->payload)) {
            $this->errors[] = 'API instructions payload is invalid.';

            return false;
        }

        if (!$client) {
            $this->errors[] = 'Unable to send an empty contact.';

            return false;
        }

        // Load the settings array.
        $this->setInstanceSettings();

        // Load the operations array.
        $this->setInstanceOperations();

        // Run operations.
        $this->runOperations();

        die(
        var_dump(
            [
                'errors' => $this->errors,
                'log' => $this->log,
            ]
        )
        );
    }

    /**
     * Retrieve API settings from the payload to override defaults.
     */
    public function setInstanceSettings()
    {
        if (isset($this->payload->settings)) {
            foreach ($this->settings as $key => &$value) {
                if (!empty($this->payload->settings->{$key}) && $this->payload->settings->{$key}) {
                    $value = $this->payload->settings->{$key};
                }
            }
        }
    }

    /**
     * Retrieve API operation set from the payload.
     */
    public function setInstanceOperations()
    {
        if (isset($this->payload->operations)) {
            foreach ($this->payload->operations as $key => $operation) {
                $this->operations[] = $operation;
            }
        }
    }

    /**
     * Run a single API Operation.
     *
     * @param $id
     * @return bool
     */
    public function runOperation($id, $operation)
    {
        $name = $operation->name ?? $id;
        if (empty($operation) || empty($operation->request)) {
            $this->errors[] = 'Skipping empty operation';

            return true;
        }
        $uri = ($this->test ? $operation->request->url_test : $operation->request->url) ?: $operation->request->url;
        if (!$uri) {
            $this->errors[] = 'Operation skipped. No URL.';

            return true;
        }

        $this->log[] = 'Running operation in '.($this->test ? 'TEST' : 'PROD').' mode: '.$name;

        // Define the options of the request.


        /** @var Transport $service */
        $service = $this->getService();

        $options = [];

        if (!empty($operation->request->headers)) {
            $headers = $this->fieldValues($operation->request->headers);
            if (!empty($headers)) {
                $options['headers'] = $headers;
            }
        }

//        $format = $operation->request->format ?? 'form';
//        switch (strtolower($format)) {
//            case 'text':
//                $options['body'] = $operation->request->body;
//                break;
//            case 'json':
//                $service->getBodyJSON();
//                break;
//            case 'xml':
//                $service->getBodyXML();
//                break;
//            case 'form':
//            default:
//                $service->getBodyForm();
//                break;
//        }

        $method = $operation->request->method ?? 'post';
        switch (strtolower($method)) {
            case 'patch':
                $service->patch($uri, $options);
                break;
            case 'put':
                $service->put($uri, $options);
                break;
            case 'get':
                $service->get($uri, $options);
                break;
            case 'head':
                $service->head($uri, $options);
                break;
            case 'delete':
                $service->delete($uri, $options);
                break;
            case 'post':
            default:
                $service->post($uri, $options);
                break;
        }
        $status = $service->getStatusCode();
        $size = $service->getBody()->getSize();
        $body = $service->getBody()->getContents();

        // Response formatting
//        $format = $operation->response->format ?? 'form';
//        switch (strtolower($format)) {
//            case 'text':
//                $service->getBodyText();
//                break;
//            case 'json':
//                $service->getBodyJSON();
//                break;
//            case 'xml':
//                $service->getBodyXML();
//                break;
//            case 'form':
//            default:
//                $service->getBodyForm();
//                break;
//        }
    }

    /**
     * Step through all operations defined.
     */
    public function runOperations()
    {
        if (!isset($this->operations) || !count($this->operations)) {
            $this->errors[] = 'API instructions payload has no operations to run.';

            return false;
        }
        foreach ($this->operations as $id => $operation) {
            $this->runOperation($id, $operation);
        }
    }

    /**
     * Set test mode on/off.
     *
     * @param bool $test
     */
    public function setTestMode($test)
    {
        $this->test = $test;
    }

    /**
     * Retrieve the transport service for API interaction.
     */
    private function getService()
    {
        if (!$this->service) {
            $this->service = $this->factory->get('mautic.contactclient.service.transport');
        }

        return $this->service;
    }

    /**
     * Tokenize/parse fields from the API Payload for transit.
     * @param $fields
     * @return array
     */
    private function fieldValues($fields) {
        $result = [];
        foreach ($fields as $field) {
            if (!$this->test && ($field->test_only ?? false)) {
                // Skip this field as it is for test mode only.
                continue;
            }
            $key = $this->tokenReplace($field->key ?? null);
            if (empty(trim($key))) {
                // Skip if we have an empty key.
                continue;
            }
            if ($this->test) {
                $value = $this->tokenReplace($field->test_value ?? $field->value ?? null);
            } else {
                $value = $this->tokenReplace($field->value ?? null);
            }
            if (empty(trim($value))) {
                // The field value is empty.
                if (($field->required ?? false) === true) {
                    // The field is required. Abort.
                    $this->abortOperation();
                    break;
                }
            }
            $result[$key] = $value;
        }
        return $result;
    }

    /**
     * @param $text
     * @return mixed
     */
    private function tokenReplace($text) {
        return $text;
    }

    private function abortOperation() {
        $this->abort = true;
    }
}
