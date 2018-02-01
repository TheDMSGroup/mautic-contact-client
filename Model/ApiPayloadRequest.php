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

use DOMDocument;
use Mautic\PluginBundle\Exception\ApiErrorException;
use MauticPlugin\MauticContactClientBundle\Services\Transport;
use MauticPlugin\MauticContactClientBundle\Helper\TokenHelper;
use Symfony\Component\Yaml\Yaml;

/**
 * Class ApiPayloadRequest
 * @package MauticPlugin\MauticContactClientBundle\Model
 */
class ApiPayloadRequest
{

    const XML_ROOT_ELEM = 'contact';

    protected $uri;

    protected $request;

    protected $service;

    protected $tokenHelper;

    protected $test = false;

    protected $logs = [];

    /**
     * ApiRequest constructor.
     * @param $uri
     * @param $request
     * @param Transport $service
     * @param TokenHelper $tokenHelper
     * @param bool $test
     */
    public function __construct($uri, $request, Transport $service, TokenHelper $tokenHelper, $test = false)
    {
        $this->uri = $uri;
        $this->request = $request;
        $this->service = $service;
        $this->tokenHelper = $tokenHelper;
        $this->test = $test;
    }

    /**
     * Given a uri and request object, formulate options and make the request.
     */
    public function send()
    {
        $uri = $this->uri;
        $this->setLogs($uri, 'uri');

        $request = $this->request;
        $service = $this->service;
        $options = [];

        // Retrieve/filter/tokenize the Request Body Field values if present.
        $requestFields = [];
        if (!empty($request->body) && !is_string($request->body)) {
            $requestFields = $this->fieldValues($request->body);
        }
        $requestFormat = strtolower($request->format ?? 'form');
        $this->setLogs($requestFormat, 'format');
        switch ($requestFormat) {
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
            $headers = $this->fieldValues($request->headers);
            if (!empty($headers)) {
                $options['headers'] = $headers;
            }
        }

        $method = trim(strtolower($request->method ?? 'post'));
        $this->setLogs($method, 'method');
        $startTime = microtime(true);
        switch ($method) {
            case 'delete':
                $this->setLogs($options, 'options');
                $service->delete($uri, $options);
                break;

            case 'get':
                if ($requestFields) {
                    $options['query'] = $requestFields;
                    // GET will not typically support form params in addition.
                    unset($options['form_params']);
                }
                $this->setLogs($options, 'options');
                $service->get($uri, $options);
                break;

            case 'head':
                $this->setLogs($options, 'options');
                $service->head($uri, $options);
                break;

            case 'patch':
                $this->setLogs($options, 'options');
                $service->patch($uri, $options);
                break;

            case 'post':
            default:
                $this->setLogs($options, 'options');
                $service->post($uri, $options);
                break;

            case 'put':
                $this->setLogs($options, 'options');
                $service->put($uri, $options);
                break;
        }
        $this->setLogs(microtime(true) - $startTime, 'duration');
    }

    /**
     * Tokenize/parse fields from the API Payload for transit.
     *
     * @param $fields
     * @return array
     * @throws ApiErrorException
     */
    private function fieldValues($fields)
    {
        $result = [];
        foreach ($fields as $field) {
            if (!$this->test && ($field->test_only ?? false)) {
                // Skip this field as it is for test mode only.
                continue;
            }
            $key = $this->renderTokens($field->key ?? null);
            if (empty($key)) {
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
                    throw new ApiErrorException(
                        'A required field is missing/empty: '.$key
                    );
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
        return $this->tokenHelper->renderString($string);
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
                        $value
                    ];
                }
            } else {
                $this->logs[$type] = $value;
            }
        } else {
            $this->logs[] = $value;
        }
    }

}