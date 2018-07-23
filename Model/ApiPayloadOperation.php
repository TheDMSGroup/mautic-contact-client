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

use MauticPlugin\MauticContactClientBundle\Helper\TokenHelper;
use MauticPlugin\MauticContactClientBundle\Model\ApiPayloadRequest as ApiRequest;
use MauticPlugin\MauticContactClientBundle\Model\ApiPayloadResponse as ApiResponse;
use MauticPlugin\MauticContactClientBundle\Services\Transport;
use stdClass;

/**
 * Class ApiPayloadOperation.
 */
class ApiPayloadOperation
{
    /** @var int */
    protected $id;

    /** @var array */
    protected $operation;

    /** @var string */
    protected $name;

    /** @var array */
    protected $request;

    /** @var array */
    protected $responseExpected;

    /** @var string */
    protected $successDefinition;

    /** @var array */
    protected $responseActual;

    /** @var bool */
    protected $test;

    /** @var array */
    protected $logs = [];

    /** @var Transport */
    protected $transport;

    /** @var bool */
    protected $updatePayload;

    /** @var bool */
    protected $valid = false;

    /** @var array */
    protected $filter;

    /** @var TokenHelper */
    protected $tokenHelper;

    /**
     * Prioritized list of possible external IDs to search for.
     *
     * @var array
     */
    protected $externalIds = [
        'id',
        'uuid',
        'leadid',
        'leaduuid',
        'contactid',
        'contactuuid',
        'uniqueid',
        'email',
    ];

    /**
     * ApiPayloadOperation constructor.
     *
     * @param           $id
     * @param           $operation
     * @param Transport $transport
     * @param           $tokenHelper
     * @param bool      $test
     * @param bool      $updatePayload
     */
    public function __construct(
        $id,
        &$operation,
        Transport $transport,
        $tokenHelper,
        $test = false,
        $updatePayload = true
    ) {
        $this->id                = $id;
        $this->operation         = &$operation;
        $this->transport         = $transport;
        $this->name              = !empty($operation->name) ? $operation->name : $this->id;
        $this->request           = isset($operation->request) ? $operation->request : [];
        $this->responseExpected  = isset($operation->response) ? $operation->response : [];
        $this->successDefinition = isset($operation->response->success->definition) ? $operation->response->success->definition : null;
        $this->tokenHelper       = $tokenHelper;
        $this->test              = $test;
        $this->updatePayload     = $updatePayload;
    }

    /**
     * Run this single API Operation.
     *
     * @return $this|bool
     *
     * @throws \Exception
     */
    public function run()
    {
        if (empty($this->request)) {
            $this->setLogs('Skipping empty operation: '.$this->name, 'notice');

            return true;
        }

        $this->setLogs($this->name, 'name');
        $this->setLogs(($this->test ? 'TEST' : 'PROD'), 'mode');

        // Send the API request.
        $apiRequest = new ApiRequest($this->request, $this->transport, $this->tokenHelper, $this->test);
        $apiRequest->send();
        $this->setLogs($apiRequest->getLogs(), 'request');

        // Parse the API response.
        $apiResponse          = new ApiResponse(
            $this->responseExpected, $this->successDefinition, $this->transport, $this->test
        );
        $this->responseActual = $apiResponse->parse()->getResponse();
        $this->setLogs($apiResponse->getLogs(), 'response');

        // Validate the API response with the given success definition.
        $valid = false;
        try {
            $valid = $apiResponse->validate();
        } catch (\Exception $e) {
        }

        $this->setValid($valid);
        $this->setLogs($valid, 'valid');
        if ($this->updatePayload && $this->test) {
            $this->updatePayloadResponse();
        }

        // Allow any exception encountered during validation to bubble upward.
        if (!empty($e)) {
            throw $e;
        }

        return $this;
    }

    /**
     * Automatically updates a response with filled in data from the parsed response object from the API.
     */
    public function updatePayloadResponse()
    {
        $result  = $this->responseExpected;
        $updates = false;
        foreach (['headers', 'body'] as $type) {
            if (isset($this->responseActual[$type])) {
                $fieldKeys = [];
                foreach ($result->{$type} as $id => $value) {
                    if (isset($value->key) && is_numeric($id)) {
                        $fieldKeys[$value->key] = intval($id);
                    }
                }
                foreach ($this->responseActual[$type] as $key => $value) {
                    // Check if this type of response was not expected (headers / body).
                    if (!isset($result->{$type})) {
                        $result->{$type} = [];
                    }
                    // Check if header/body field is unexpected.
                    $fieldId = isset($fieldKeys[$key]) ? $fieldKeys[$key] : -1;
                    if ($fieldId == -1) {
                        // This is a new field.
                        $newField          = new stdClass();
                        $newField->key     = $key;
                        $newField->example = $value;
                        $result->{$type}[] = $newField;
                        $this->setLogs('New '.$type.' field "'.$key.'" added with example: '.$value, 'autoUpdate');
                        $updates = true;
                    } else {
                        if (!empty($value)) {
                            if (empty($result->{$type}[$fieldId]->example)) {
                                // This is an existing field, but requires an updated example.
                                $result->{$type}[$fieldId]->example = $value;
                                $updates                            = true;
                                $this->setLogs(
                                    'Existing '.$type.' field '.$key.' now has an example: '.$value,
                                    'autoUpdate'
                                );
                            } else {
                                if ($this->test && (!isset($result->{$type}[$fieldId]->example) || $result->{$type}[$fieldId]->example !== $value)) {
                                    // Updating our example because a test was run.
                                    $result->{$type}[$fieldId]->example = $value;
                                    $updates                            = true;
                                    $this->setLogs(
                                        'Existing '.$type.' field '.$key.' has a new example: '.$value,
                                        'autoUpdate'
                                    );
                                }
                            }
                        }
                    }
                }
            }
        }
        // Check for auto format detection during a run.
        if (
            $this->test
            && isset($this->responseActual['format'])
            && isset($result->format)
            && 'auto' == $result->format
            && 'auto' !== $this->responseActual['format']
        ) {
            $updates        = true;
            $result->format = $this->responseActual['format'];
            $this->setLogs(
                'Response type has been automatically determined to be: '.$result->format,
                'autoUpdate'
            );
        }
        if ($updates) {
            $this->operation->response = $result;
        }
    }

    /**
     * @return bool
     */
    public function getValid()
    {
        return $this->valid;
    }

    /**
     * @param $valid
     */
    public function setValid($valid)
    {
        $this->valid = $valid;
    }

    /**
     * @return array
     */
    public function getFilter()
    {
        return $this->filter;
    }

    /**
     * @param $filter
     */
    public function setFilter($filter)
    {
        $this->filter = $filter;
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

    public function getResponseActual()
    {
        return $this->responseActual;
    }

    /**
     * Get filled responses that have Contact destinations defined.
     *
     * @return array
     */
    public function getResponseMap()
    {
        $mappedFields = [];
        foreach (['headers', 'body'] as $type) {
            if (isset($this->responseExpected->{$type}) && isset($this->responseActual[$type])) {
                foreach ($this->responseExpected->{$type} as $value) {
                    if (!empty($value->destination) && !empty($value->key) && !empty($this->responseActual[$type][$value->key])) {
                        // We have a non-empty value with a mapped destination.
                        $mappedFields[$value->destination] = $this->responseActual[$type][$value->key];
                    }
                }
            }
        }

        return $mappedFields;
    }

    /**
     * Given the response fields, attempt to assume an external ID for future correlation.
     * Find the best possible fit.
     */
    public function getExternalId()
    {
        $externalIds = array_flip($this->externalIds);
        $id          = null;
        $idIndex     = null;
        foreach (['headers', 'body'] as $type) {
            if (isset($this->responseActual[$type]) && isset($this->responseActual[$type])) {
                foreach ($this->responseActual[$type] as $key => $value) {
                    $key = preg_replace('/[^a-z0-9]/', '', strtolower($key));
                    if (isset($externalIds[$key]) && (null === $idIndex || $externalIds[$key] < $idIndex)) {
                        $idIndex = $externalIds[$key];
                        $id      = $value;
                        if (0 == $idIndex) {
                            break;
                        }
                    }
                }
            }
        }

        return $id;
    }
}
