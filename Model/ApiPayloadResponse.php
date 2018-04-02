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
use MauticPlugin\MauticContactClientBundle\Entity\Stat;
use MauticPlugin\MauticContactClientBundle\Exception\ContactClientException;
use MauticPlugin\MauticContactClientBundle\Helper\FilterHelper;
use MauticPlugin\MauticContactClientBundle\Helper\JSONHelper;
use MauticPlugin\MauticContactClientBundle\Services\Transport;
use Symfony\Component\Yaml\Yaml;

/**
 * Class ApiPayloadResponse.
 */
class ApiPayloadResponse
{
    /** @var array */
    protected $responseExpected;

    /** @var array */
    protected $responseActual;

    /** @var Transport */
    protected $service;

    /** @var bool */
    protected $test;

    /** @var array */
    protected $successDefinition;

    /** @var bool */
    protected $valid = true;

    /** @var array */
    protected $logs = [];

    /** @var array Must be in the format of attempted decoding */
    protected $contentTypes = [
        'json',
        'xml',
        'yaml',
        'html',
        'text',
    ];

    /**
     * ApiPayloadResponse constructor.
     *
     * @param           $responseExpected
     * @param           $successDefinition
     * @param Transport $service
     * @param bool      $test
     */
    public function __construct($responseExpected, $successDefinition, Transport $service, $test = false)
    {
        $this->responseExpected  = $responseExpected;
        $this->successDefinition = $successDefinition;
        $this->service           = $service;
        $this->test              = $test;
    }

    /**
     * Parse the response and capture key=value pairs.
     *
     * @return $this
     *
     * @throws \Exception
     */
    public function parse()
    {
        $result = [];

        $result['status'] = $this->service->getStatusCode();
        $this->setLogs($result['status'], 'status');

        // Format the head response.
        $result['headers'] = $this->service->getHeaders();
        if ($result['headers']) {
            $result['headers'] = $this->getResponseArray($result['headers'], 'headers');
        }
        $result['headersRaw'] = implode('; ', $result['headers']);
        $this->setLogs($result['headers'], 'headers');

        $result['bodySize'] = $this->service->getBody()->getSize();
        $this->setLogs($result['bodySize'], 'size');

        $result['bodyRaw'] = $this->service->getBody()->getContents();
        $this->setLogs($result['bodyRaw'], 'bodyRaw');

        // Format the body response.
        $responseExpectedFormat = trim(
            strtolower(isset($this->responseExpected->format) ? $this->responseExpected->format : 'auto')
        );
        // Move the expected format to the top of the detection array.
        if ('auto' !== $responseExpectedFormat) {
            $key = array_search($responseExpectedFormat, $this->contentTypes);
            if (false !== $key) {
                $this->contentTypes = array_flip(
                    array_merge([$this->contentTypes[$key] => '-'], array_flip($this->contentTypes))
                );
            }
        }
        $this->setLogs($responseExpectedFormat, 'format');
        $result['format'] = $responseExpectedFormat;

        // If auto mode, discern content type in a very forgiving manner from the header.
        if ('auto' === $result['format']) {
            foreach ($result['headers'] as $keaderType => $header) {
                if ('contenttype' === str_replace(['-', '_', ' '], '', strtolower($keaderType))) {
                    foreach ($this->contentTypes as $key => $contentType) {
                        if (strpos(strtolower($header), $contentType)) {
                            $result['format'] = $contentType;
                            // Move this type to the top of the detection array.
                            $this->contentTypes = array_flip(
                                array_merge([$this->contentTypes[$key] => '-'], array_flip($this->contentTypes))
                            );
                            $this->setLogs($contentType, 'headerFormat');
                            break;
                        }
                    }
                    break;
                }
            }
        }

        $result['body'] = [];
        // Attempt to parse with a proffered list of types.
        foreach ($this->contentTypes as $contentType) {
            $attemptBody = $this->getResponseArray($result['bodyRaw'], $contentType);
            if ($attemptBody) {
                $result['body']   = $attemptBody;
                $result['format'] = $contentType;
                $this->setLogs($contentType, 'bodyFormat');
                break;
            }
            // If in test-mode do not cycle through all types unless we are truly in auto mode.
            if (!$this->test && 'auto' === $responseExpectedFormat) {
                break;
            }
        }
        $this->setLogs($result['body'], 'body');

        $this->valid          = (bool) $result['status'];
        $this->responseActual = $result;

        return $this;
    }

    /**
     * Given a headers array or body of text and a format, parse to a flat key=value array.
     *
     * @param mixed  $data
     * @param string $responseExpectedFormat
     *
     * @return array|bool return false if there is an error or we are unable to parse
     *
     * @throws \Exception
     */
    private function getResponseArray($data, $responseExpectedFormat = 'json')
    {
        $result = $hierarchy = [];
        try {
            switch ($responseExpectedFormat) {
                case 'headers':
                    foreach ($data as $key => $array) {
                        $result[$key] = implode('; ', $array);
                    }
                    break;

                case 'xml':
                case 'html':
                    $doc          = new DOMDocument();
                    $doc->recover = true;
                    // Ensure UTF-8 encoding is handled correctly.
                    if (1 !== preg_match('/<\??xml .*encoding=["|\']?UTF-8["|\']?.*>/iU', $data, $matches)) {
                        $data = '<?xml version="1.0" encoding="UTF-8"?>'.$data;
                    }
                    if ('html' == $responseExpectedFormat) {
                        $doc->loadHTML($data);
                    } else {
                        $doc->loadXML($data);
                    }
                    $hierarchy = $this->domDocumentArray($doc);
                    break;

                case 'json':
                    $jsonHelper = new JSONHelper();
                    $hierarchy  = $jsonHelper->decodeArray($data, 'API Response', true);
                    break;

                case 'text':
                    // Handle the most common patterns of a multi-line delimited expression.
                    foreach (explode("\n", $data) as $line) {
                        if (!empty($line)) {
                            foreach ([':', '=', ';'] as $delimiter) {
                                $elements = explode($delimiter, $line);
                                if (2 == count($elements)) {
                                    // Strip outer whitespace.
                                    foreach ($elements as &$element) {
                                        $element = trim($element);
                                    }
                                    // Strip enclosures.
                                    foreach ($elements as &$element) {
                                        foreach (['"', "'"] as $enclosure) {
                                            if (
                                                0 === strpos($element, $enclosure)
                                                && strrpos($element, $enclosure) === strlen($element) - 1
                                            ) {
                                                $element = trim($element, $enclosure);
                                                continue;
                                            }
                                        }
                                    }
                                    list($key, $value) = $elements;
                                    $result[$key]      = $value;
                                    break;
                                }
                            }
                        }
                    }
                    // Fall back to a raw string (no key-value pairs at all)
                    if (!$result) {
                        foreach (explode("\n", $data) as $l => $line) {
                            $result['line '.($l + 1)] = $line;
                        }
                    }
                    break;

                case 'yaml':
                    $hierarchy = Yaml::dump($data);
                    break;
            }
        } catch (\Exception $e) {
            // Sub-parsing may fail, but we only care about acceptable values when validating.
            // Logging will capture the full response for debugging.
        }

        // Flatten hierarchical data, if needed.
        if ($hierarchy) {
            $result = $this->flattenStructure($hierarchy);
        }

        // Stringify all values.
        foreach ($result as $key => &$value) {
            if (true === $value) {
                $value = 'true';
            } elseif (false === $value) {
                $value = 'false';
            }
            $value = (string) $value;
        }

        return $result;
    }

    /**
     * Convert a DOM Document into a nested array.
     *
     * @param $root
     *
     * @return array|mixed
     */
    private function domDocumentArray($root)
    {
        $result = [];

        if ($root->hasAttributes()) {
            foreach ($root->attributes as $attribute) {
                $result['@attributes'][$attribute->name] = $attribute->value;
            }
        }

        if ($root->hasChildNodes()) {
            if (1 == $root->childNodes->length) {
                $child = $root->childNodes->item(0);
                if (in_array($child->nodeType, [XML_TEXT_NODE, XML_CDATA_SECTION_NODE]) && !empty($child->nodeValue)) {
                    $result['_value'] = $child->nodeValue;

                    return 1 == count($result)
                        ? $result['_value']
                        : $result;
                }
            }
            $groups = [];
            foreach ($root->childNodes as $child) {
                if (!isset($result[$child->nodeName])) {
                    $result[$child->nodeName] = $this->domDocumentArray($child);
                } else {
                    if (!isset($groups[$child->nodeName])) {
                        $result[$child->nodeName] = [$result[$child->nodeName]];
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
     *
     * @param       $subject
     * @param array $result
     *
     * @return array
     */
    private function flattenStructure($subject, &$result = [])
    {
        foreach ($subject as $key => $value) {
            if (is_array($value) || is_object($value)) {
                $this->flattenStructure($value, $result);
            } else {
                // Do not nullify existing key/value pairs if already present.
                if (empty($value) && false !== $value && !isset($result[$key])) {
                    $result[$key] = null;
                } else {
                    $result[$key] = $value;
                }
            }
        }

        return $result;
    }

    /**
     * Given the recent response, evaluate it for success based on the expected success validator.
     *
     * @return bool
     *
     * @throws ContactClientException
     */
    public function validate()
    {
        if ($this->valid) {
            if (!$this->responseActual) {
                throw new ContactClientException(
                    'There was no response to parse.',
                    0,
                    null,
                    Stat::TYPE_ERROR,
                    true
                );
            }

            // If there is no success definition, than do the default test of a 200 ok status check.
            if (!$this->successDefinition) {
                if (!$this->responseActual['status'] || 200 != $this->responseActual['status']) {
                    throw new ContactClientException(
                        'Status code is not 200. Default validation failure.',
                        0,
                        null,
                        Stat::TYPE_ERROR,
                        true
                    );
                }
            }

            // Standard success definition validation.
            $e      = false;
            $filter = new FilterHelper();
            try {
                $this->valid = $filter->filter($this->successDefinition, $this->responseActual);
            } catch (\Exception $e) {
            }
            if (!$this->valid || $e) {
                throw new ContactClientException(
                    'Response did not pass validation.'.$e ? ' '.$e->getMessage() : null,
                    0,
                    $e ? $e : null,
                    Stat::TYPE_REJECT,
                    false,
                    null,
                    $filter->getErrors()
                );
            }
        }

        return $this->valid;
    }

    /**
     * Get the parsed API response array.
     *
     * @return array
     */
    public function getResponse()
    {
        return isset($this->responseActual) ? $this->responseActual : [];
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
}
