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

use Mautic\LeadBundle\Entity\Lead;
use Mautic\PluginBundle\Integration\AbstractIntegration;
use MauticPlugin\MauticContactClientBundle\Services\Transport;
use Symfony\Component\Yaml\Yaml;
use DOMDocument;

use MauticPlugin\MauticContactClientBundle\Helper\TokenHelper;

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
    const XML_ROOT_ELEM = 'contact';

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
     * @var Transport Generic Transport service.
     */
    protected $service;

    /**
     * @var TokenHelper
     */
    protected $tokenHelper;

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
     * @param string $payload
     * @param Lead $contact
     * @return bool
     */
    public function sendContact(string $payload, Lead $contact)
    {
        // @todo - add translation layer for strings in this method.
        // $translator = $container->get('translator');

        if (!$contact) {
            $this->errors[] = 'Contact appears to not exist.';

            return false;
        }

        if (!$this->setInstancePayload($payload)) {
            return false;
        };

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
     * Run basic validations on the payload. Deep schema validation should be made on save only.
     * @return bool
     */
    private function setInstancePayload($payload = '')
    {
        if (!$payload) {
            $this->errors[] = 'API instructions payload is blank.';

            return false;
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
            $this->errors[] = 'API instructions payload JSON is invalid: '.$jsonError;

            return false;
        }
        if (!$payload || !is_object($payload)) {
            $this->errors[] = 'API instructions payload is invalid.';

            return false;
        }

        return $this->payload = $payload;
    }

    /**
     * Retrieve API settings from the payload to override defaults.
     */
    private function setInstanceSettings()
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
    private function setInstanceOperations()
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
    private function runOperation($id, $operation)
    {
        $name = $operation->name ?? $id;
        if (empty($operation) || empty($operation->request)) {
            $this->errors[] = 'Skipping empty operation';

            return true;
        }
        $uri = ($this->test ? $operation->request->test_url : $operation->request->url) ?: $operation->request->url;
        if (!$uri) {
            $this->errors[] = 'Operation skipped. No URL.';

            return true;
        }

        $this->log[] = 'Running operation in '.($this->test ? 'TEST' : 'PROD').' mode: '.$name;

        $this->sendRequest($uri, $operation->request);

        $this->parseResponse($operation->response);

    }

    /**
     * Step through all operations defined.
     */
    private function runOperations()
    {
        if (!isset($this->operations) || !count($this->operations)) {
            $this->errors[] = 'API instructions payload has no operations to run.';

            return false;
        }
        foreach ($this->operations as $id => $operation) {
            $this->runOperation($id, $operation);
            if ($this->abort) {
                break;
            }
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
     * Given a uri and request object, formulate options and make the request.
     * @param string $uri
     * @param object $request
     */
    private function sendRequest($uri, $request)
    {
        /** @var Transport $service */
        $service = $this->getService();

        $options = [];

        // Retrieve/filter/tokenize the Request Body Field values if present.
        $requestFields = [];
        if (!empty($request->body) && !is_string($request->body)) {
            $requestFields = $this->requestFieldValues($request->body);
        }
        switch (strtolower($request->format ?? 'form')) {
            case 'form':
            default:
                $options['headers']['Content-Type'] = 'application/x-www-form-urlencoded; charset=utf-8';
                if ($requestFields) {
                    $options['form_params'] = $requestFields;
                }
                break;

            case 'json':
                $options['headers']['Content-Type'] = 'application/json; charset=utf-8';
                if ($requestFields) {
                    $options['json'] = $requestFields;
                }
                break;

            case 'text':
                $options['headers']['Content-Type'] = 'text/plain; charset=utf-8';
                if ($requestFields) {
                    // User specified to send raw text, but used key value pairs.
                    // We will present this as YAML, since it's the most text-like response.
                    $yaml = Yaml::dump($requestFields);
                    if ($yaml) {
                        $options['body'] = $yaml;
                    }
                }
                break;

            case 'xml':
                $options['headers']['Content-Type'] = 'application/xml; charset=utf-8';
                if ($requestFields) {
                    $doc = new DomDocument('1.0');
                    $doc->preserveWhiteSpace = false;
                    $doc->formatOutput = true;
                    $root = $doc->createElement(self::XML_ROOT_ELEM);
                    $doc->appendChild($root);
                    foreach ($requestFields as $key => $value) {
                        $element = $doc->createElement($key);
                        $element->appendChild(
                            $doc->createTextNode($value)
                        );
                        $root->appendChild($element);
                    }
                    $xml = $doc->saveXML();
                    if ($xml) {
                        $options['body'] = $xml;
                    }
                }
                break;

            case 'yaml':
                $options['headers']['Content-Type'] = 'application/x-yaml; charset=utf-8';
                if ($requestFields) {
                    $yaml = Yaml::dump($requestFields);
                    if ($yaml) {
                        $options['body'] = $yaml;
                    }
                }
                break;

        }
        // Add the raw body if provided as a string.
        if (!empty($request->body) && is_string($request->body)) {
            $options['body'] = $this->renderTokens($request->body);
        }

        // Header Field overrides.
        if (!empty($request->headers)) {
            $headers = $this->requestFieldValues($request->headers);
            if (!empty($headers)) {
                $options['headers'] = $headers;
            }
        }

        $this->log[] = 'Sending request to: '.$uri;
        $method = trim(strtolower($request->method ?? 'post'));
        $this->log[] = 'Using method: '.$method;
        switch ($method) {
            case 'delete':
                $this->log[] = 'Using options: '.json_encode($options);
                $service->delete($uri, $options);
                break;

            case 'get':
                if ($requestFields) {
                    $options['query'] = $requestFields;
                    // GET will not typically support form params in addition.
                    unset($options['form_params']);
                }
                $this->log[] = 'Using options: '.json_encode($options);
                $service->get($uri, $options);
                break;

            case 'head':
                $this->log[] = 'Using options: '.json_encode($options);
                $service->head($uri, $options);
                break;

            case 'patch':
                $this->log[] = 'Using options: '.json_encode($options);
                $service->patch($uri, $options);
                break;

            case 'post':
            default:
                $this->log[] = 'Using options: '.json_encode($options);
                $service->post($uri, $options);
                break;

            case 'put':
                $this->log[] = 'Using options: '.json_encode($options);
                $service->put($uri, $options);
                break;
        }
    }

    private function parseResponse($response)
    {
        $result = [];

        /** @var Transport $service */
        $service = $this->getService();

        $status = $service->getStatusCode();
        $this->log[] = 'Response status code: '.$status;

        $headers = $service->getHeaders();
        $this->log[] = 'Response headers: '.$headers;

        $size = $service->getBody()->getSize();
        $this->log[] = 'Response size: '.$size;

        $body = $service->getBody()->getContents();
        $this->log[] = 'Response body: '.$body;

        if ($status !== 200) {
            $this->errors[] = 'Invalid status code received.';

            return $this->abortOperation();
        }

        if (!$body) {
            $this->errors[] = 'No body was returned.';

            return $this->abortOperation();
        }

        // Response formatting.
        $responseFormat = trim(strtolower($response->format ?? 'auto'));
        switch ($responseFormat) {
            default:
            case 'auto';
                // @todo - detect format from headers/body and step through parsers till we see success.
                break;

            case 'html';
            case 'json';
            case 'text';
            case 'xml';
            case 'yaml';
                $result = $this->getResponseArray($body, $responseFormat);
                break;
        }

        return $result;
    }

    /**
     * Given a body of text and a format, parse to a flat key=value array.
     * @param $body
     * @param string $responseFormat
     * @return array|bool Return false if there is an error or we are unable to parse.
     */
    private function getResponseArray($body, $responseFormat = 'xml')
    {
        $result = false;
        $hierarchy = [];

        switch ($responseFormat) {
            case 'xml':
            case 'html':
                $doc = new DOMDocument();
                $doc->recover = true;
                // Ensure UTF-8 encoding is handled correctly.
                if (preg_match('/<\??xml .*encoding=["|\']?UTF-8["|\']?.*>/iU', $body, $matches) == true) {
                    $body = '<?xml version="1.0" encoding="UTF-8"?>'.$body;
                }
                if ($responseFormat == 'html') {
                    $doc->loadHTML($body);
                } else {
                    $doc->loadXML($body);
                }
                $hierarchy = $this->domDocumentArray($doc);
                break;

            case 'json':
                $hierarchy = json_decode($body, true);
                break;

            case 'text':
                // Handle the most common patterns of a multi-line delimited expression.
                foreach (explode("\n", $body) as $line) {
                    if (!empty($line)) {
                        foreach ([':', '=', ';'] as $delimiter) {
                            $elements = explode($delimiter, $line);
                            if (count($elements) == 2) {
                                // Strip outer whitespace.
                                foreach ($elements as &$element) {
                                    $element = trim($element);
                                }
                                // Strip enclosures.
                                foreach ($elements as &$element) {
                                    foreach (['"', "'"] as $enclosure) {
                                        if (
                                            strpos($element, $enclosure) === 0
                                            && strrpos($element, $enclosure) === strlen($element) - 1
                                        ) {
                                            $element = trim($element, $enclosure);
                                            continue;
                                        }
                                    }
                                }
                                list($key, $value) = $elements;
                                $result[$key] = $value;
                                break;
                            }
                        }
                    }
                }
                break;

            case 'yaml':
                $hierarchy = Yaml::dump($body);
                break;
        }

        // Flatten hierarchical data, if needed.
        if ($hierarchy) {
            $result = $this->flattenStructure($hierarchy);
        }

        return $result;
    }

    private function domDocumentArray($root)
    {
        $result = [];
        if ($root->hasAttributes()) {
            foreach ($root->attributes as $attribute) {
                $result['@attributes'][$attribute->name] = $attribute->value;
            }
        }

        if ($root->hasChildNodes()) {
            $children = $root->childNodes;
            if ($children->length == 1) {
                $child = $children->item(0);
                if ($child->nodeType == [XML_TEXT_NODE, XML_CDATA_SECTION_‌​NODE]) {
                    $result['_value'] = $child->nodeValue;

                    return count($result) == 1
                        ? $result['_value']
                        : $result;
                }
            }
            $groups = array();
            foreach ($children as $child) {
                if (!isset($result[$child->nodeName])) {
                    $result[$child->nodeName] = $this->domDocumentArray($child);
                } else {
                    if (!isset($groups[$child->nodeName])) {
                        $result[$child->nodeName] = array($result[$child->nodeName]);
                        $groups[$child->nodeName] = 1;
                    }
                    $result[$child->nodeName][] = $this->domDocumentArray($child);
                }
            }
        }

        return $result;
    }

    /**
     * Recursively flatten an structure to an array including only key/value pairs.
     * @param $subject
     * @param array $result
     * @return array
     */
    private function flattenStructure($subject, &$result = [])
    {
        foreach ($subject as $key => $value) {
            if (is_array($value) || is_object($value)) {
                $this->flattenStructure($value, $result);
            } else {
                // Do not nullify existing key/value pairs if already present.
                if (empty($value) && !isset($result[$key])) {
                    $result[$key] = null;
                } else {
                    $result[$key] = $value;
                }
            }
        }

        return $result;
    }

    /**
     * Tokenize/parse fields from the API Payload for transit.
     * @param $fields
     * @return array
     */
    private function requestFieldValues($fields)
    {
        $result = [];
        foreach ($fields as $field) {
            if (!$this->test && ($field->test_only ?? false)) {
                // Skip this field as it is for test mode only.
                continue;
            }
            $key = $this->renderTokens($field->key ?? null);
            if (empty(trim($key))) {
                // Skip if we have an empty key.
                continue;
            }
            // Loop through value sources till a non-empty tokenized result is found.
            $valueSources = ['value', 'default_value'];
            if ($this->test) {
                array_unshift($valueSources, 'test_value');
            }
            $value = null;
            foreach ($valueSources as $valueSource) {
                if (!empty($field->{$valueSource})) {
                    $value = $this->renderTokens($field->{$valueSource});
                    if (!empty($value)) {
                        break;
                    }
                }
            }
            if (empty($value)) {
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
     * @param $string
     * @return mixed
     */
    private function renderTokens($string = '')
    {
        if (!$this->tokenHelper) {
            $this->tokenHelper = new TokenHelper();
        }
        $this->tokenHelper->renderString($string);

        return trim($string);
    }

    private function abortOperation()
    {
        $this->abort = true;
    }
}
