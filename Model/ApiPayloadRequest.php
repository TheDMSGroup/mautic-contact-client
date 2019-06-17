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

use DOMDocument;
use MauticPlugin\MauticContactClientBundle\Entity\Stat;
use MauticPlugin\MauticContactClientBundle\Exception\ContactClientException;
use MauticPlugin\MauticContactClientBundle\Helper\TokenHelper;
use MauticPlugin\MauticContactClientBundle\Services\Transport;
use Symfony\Component\Yaml\Yaml;

/**
 * Class ApiPayloadRequest.
 */
class ApiPayloadRequest
{
    const XML_ROOT_ELEM = 'contact';

    /** @var array */
    protected $request;

    /** @var Transport */
    protected $transport;

    /** @var TokenHelper */
    protected $tokenHelper;

    /** @var bool */
    protected $test = false;

    /** @var array */
    protected $logs = [];

    /**
     * ApiRequest constructor.
     *
     * @param array       $request
     * @param Transport   $transport
     * @param TokenHelper $tokenHelper
     * @param bool        $test
     */
    public function __construct($request, Transport $transport, TokenHelper $tokenHelper, $test = false)
    {
        $this->request     = $request;
        $this->transport   = $transport;
        $this->tokenHelper = $tokenHelper;
        $this->test        = $test;
    }

    /**
     * Given a uri and request object, formulate options and make the request.
     *
     * @throws ContactClientException
     */
    public function send()
    {
        // If it's in test mode, add test_values to TokenHelper.
        if ($this->test && null !== $this->request && null !== $this->request->body) {
            $testValues = [];
            foreach ($this->request->body as $item) {
                $testValues[$item->key] = $item->test_value;
            }
            $this->tokenHelper->addContext($testValues);
        }

        // The URI has already been overriden (if applicable) by this point.
        $uri = isset($this->request->url) ? $this->request->url : null;
        if ($this->test && !empty($this->request->testUrl)) {
            $uri = $this->request->testUrl;
        }
        $uri = trim($this->renderTokens($uri));
        if (empty($uri)) {
            throw new ContactClientException(
                'No URL was specified for an API operation.',
                0,
                null,
                Stat::TYPE_ERROR,
                false
            );
        }

        $this->setLogs($uri, 'uri');

        $request   = $this->request;
        $transport = $this->transport;
        $options   = [];
        $manual    = isset($request->manual)
            && $request->manual
            && isset($request->template)
            && !empty(trim($request->template));

        // Retrieve/filter/tokenize the Request Body Field values if present.
        $requestFields = [];
        if (!$manual && !empty($request->body) && !is_string($request->body)) {
            $requestFields = $this->fieldValues($request->body);
        }
        $requestFormat = strtolower(isset($request->format) ? $request->format : 'form');
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
                    $doc                     = new DomDocument('1.0');
                    $doc->preserveWhiteSpace = false;
                    $doc->formatOutput       = true;
                    $root                    = $doc->createElement(self::XML_ROOT_ELEM);
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

        // Add the manual template if provided and manual mode is enabled.
        if ($manual) {
            $templateFields = $this->templateFieldValues($request->body);
            $body           = $this->renderTokens($request->template, $templateFields);
            if (!empty(trim($body))) {
                $options['body'] = $body;
                // Prevent double-encoding JSON.
                unset($options['json']);
            }
        } else {
            if (is_string($request->body) && !empty(trim($request->body))) {
                // For backward compatibility, support body as a string.
                $body = $this->renderTokens($request->body);
                if (!empty(trim($body))) {
                    $options['body'] = $body;
                }
            }
        }

        // Header Field overrides.
        if (!empty($request->headers)) {
            $headers = $this->fieldValues($request->headers);
            if (!empty($headers)) {
                if (isset($options['headers'])) {
                    $options['headers'] = array_merge($options['headers'], $headers);
                } else {
                    $options['headers'] = $headers;
                }
            }
        }

        $method = trim(strtolower(isset($request->method) ? $request->method : 'post'));
        $this->setLogs($method, 'method');
        $startTime = microtime(true);
        switch ($method) {
            case 'delete':
                $this->setLogs($options, 'options');
                $transport->delete($uri, $options);
                break;

            case 'get':
                if ($requestFields) {
                    $options['query'] = $requestFields;
                    // GET will not typically support form params in addition.
                    unset($options['form_params']);
                }
                $this->setLogs($options, 'options');
                $transport->get($uri, $options);
                break;

            case 'head':
                $this->setLogs($options, 'options');
                $transport->head($uri, $options);
                break;

            case 'patch':
                $this->setLogs($options, 'options');
                $transport->patch($uri, $options);
                break;

            case 'post':
            default:
                $this->setLogs($options, 'options');
                $transport->post($uri, $options);
                break;

            case 'put':
                $this->setLogs($options, 'options');
                $transport->put($uri, $options);
                break;
        }
        $transportRecords = $transport->getRecords();
        if ($transportRecords) {
            $debug = [];
            foreach ($transportRecords as $transportRecord) {
                if (
                    isset($transportRecord['channel'])
                    && 'guzzle.to.curl' == $transportRecord['channel']
                    && isset($transportRecord['message'])
                ) {
                    $debug[] = $transportRecord['message'];
                }
            }
            if ($debug) {
                $this->setLogs($debug, 'debug');
            }
        }
        $this->setLogs(microtime(true) - $startTime, 'duration');
        if ($this->test) {
            $this->setLogs($this->tokenHelper->getContext(true), 'availableTokens');
        }
    }

    /**
     * @param string|array|object $template
     * @param array               $context
     *
     * @return string
     */
    private function renderTokens($template = '', $context = [])
    {
        if ($context) {
            $this->tokenHelper->addContext($context);
        }

        return $this->tokenHelper->render($template);
    }

    /**
     * Tokenize/parse fields from the API Payload for transit.
     * This method also exists in the other payload type with a minor difference.
     *
     * @param $fields
     *
     * @return array
     *
     * @throws ContactClientException
     */
    private function fieldValues($fields)
    {
        $result = [];
        foreach ($fields as $field) {
            if (!$this->test && (isset($field->test_only) ? $field->test_only : false)) {
                // Skip this field as it is for test mode only.
                continue;
            }
            $key = isset($field->key) ? trim($field->key) : '';
            if ('' === $key) {
                // Skip if we have an empty key.
                continue;
            }
            // Loop through value sources till a non-empty tokenized result is found.
            $valueSources = ['value', 'default_value'];
            if ($this->test) {
                $valueSources = ['test_value', 'value', 'default_value'];
            }
            $value = null;
            foreach ($valueSources as $valueSource) {
                if (isset($field->{$valueSource}) && null !== $field->{$valueSource} && '' !== $field->{$valueSource}) {
                    $value = $this->renderTokens($field->{$valueSource});
                    if (null !== $value && '' !== $value) {
                        break;
                    }
                }
            }
            if (null === $value || '' === trim($value)) {
                // The field value is empty and not 0/false.
                if (true === (isset($field->required) ? $field->required : false)) {
                    // The field is required. Abort.
                    throw new ContactClientException(
                        'A required Client field "'.$field->key.'" is empty based on "'.$field->value.'"',
                        0,
                        null,
                        Stat::TYPE_FIELDS,
                        false,
                        $field->key
                    );
                }
            }
            $result[$key] = $value;
        }

        return $result;
    }

    /**
     * Tokenize/parse fields from the API Payload for template context.
     *
     * @param $fields
     *
     * @return array
     *
     * @throws ContactClientException
     */
    private function templateFieldValues($fields)
    {
        $result = [];
        foreach ($fields as $field) {
            if (!$this->test && (isset($field->test_only) ? $field->test_only : false)) {
                // Skip this field as it is for test mode only.
                continue;
            }
            $key = isset($field->key) ? trim($field->key) : '';
            if ('' === $key) {
                // Skip if we have an empty key.
                continue;
            }
            // Exclude default_value (may add this functionality in the future if desired).
            $valueSources = ['value', 'default_value'];
            if ($this->test) {
                $valueSources = ['test_value', 'value', 'default_value'];
            }
            $value = null;
            foreach ($valueSources as $valueSource) {
                if (isset($field->{$valueSource}) && null !== $field->{$valueSource} && '' !== $field->{$valueSource}) {
                    $value = $this->renderTokens($field->{$valueSource});
                    if (null !== $value && '' !== $value) {
                        break;
                    }
                }
            }
            if (null === $value || '' === $value) {
                // The field value is empty.
                if (true === (isset($field->required) ? $field->required : false)) {
                    // The field is required. Abort.
                    throw new ContactClientException(
                        'A required Client template field is empty: '.$field->value.' ('.$field->key.' in client configuration)',
                        0,
                        null,
                        Stat::TYPE_FIELDS,
                        false,
                        $field->key
                    );
                }
            }
            $result[$key] = $value;

            // Support pure mustache tags as keys as well (for templates only).
            if (1 === preg_match('/^{{\s*[\w\.]+\s*}}$/', trim($field->value))) {
                $mustacheTag = trim(str_replace(['{', '}'], '', $field->value));
                if (empty($result[$mustacheTag])) {
                    $result[$mustacheTag] = $value;
                }
            }
        }

        return $result;
    }

    /**
     * @return array
     */
    public function getLogs()
    {
        return $this->logs;
    }

    /**
     * @param        $value
     * @param string $type
     */
    public function setLogs($value, $type = '')
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
}
