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

use stdClass;
use Mautic\PluginBundle\Exception\ContactClientRetryException;
use MauticPlugin\MauticContactClientBundle\Model\ApiPayloadRequest as ApiRequest;
use MauticPlugin\MauticContactClientBundle\Model\ApiPayloadResponse as ApiResponse;
use MauticPlugin\MauticContactClientBundle\Services\Transport;

/**
 * Class ApiPayloadOperation
 * @package MauticPlugin\MauticContactClientBundle\Model
 */
class ApiPayloadOperation
{

    protected $operation;

    protected $name;

    protected $request;

    protected $responseExpected;

    protected $successDefinition;

    protected $responseActual;

    protected $test;

    protected $logs = [];

    protected $service;

    protected $updatePayload;

    protected $valid = false;

    protected $filter = null;

    protected $tokenHelper;

    /**
     * Prioritized list of possible external IDs to search for.
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

    public function __construct(
        $id,
        &$operation,
        Transport $service,
        $tokenHelper,
        $test = false,
        $updatePayload = true
    ) {
        $this->operation = &$operation;
        $this->service = $service;
        $this->name = $operation->name ?? $operation->id ?? 'Unknown';
        $this->request = $operation->request ?? [];
        $this->responseExpected = $operation->response ?? [];
        $this->successDefinition = $operation->response->success->definition ?? [];
        $this->tokenHelper = $tokenHelper;
        $this->test = $test;
        $this->updatePayload = $updatePayload;
    }

    /**
     * Run this single API Operation.
     *
     * @return $this|bool
     */
    public function run()
    {
        if (empty($this->request)) {
            $this->setLogs('Skipping empty operation: '.$this->name, 'notice');

            return true;
        }
        $uri = $this->request->url ?? null;
        if ($this->test && !empty($this->request->testUrl)) {
            $uri = $this->request->testUrl;
        }
        if (!$uri) {
            $this->setLogs('Operation skipped. No URL: '.$this->name, 'notice');

            return true;
        }

        $this->setLogs($this->name, 'name');
        $this->setLogs(($this->test ? 'TEST' : 'PROD'), 'mode');

        // Send the API request.
        $apiRequest = new ApiRequest($uri, $this->request, $this->service, $this->tokenHelper, $this->test);
        $apiRequest->send();
        $this->setLogs($apiRequest->getLogs(), 'request');

        // Parse the API response.
        $apiResponse = new ApiResponse($this->responseExpected, $this->successDefinition, $this->service, $this->test);
        $this->responseActual = $apiResponse->parse()->getResponse();
        $this->setLogs($apiResponse->getLogs(), 'response');

        // Validate the API response with the given success definition.
        try {
            $this->setValid($apiResponse->validate());
            $this->setLogs($this->getValid(), 'valid');
        } catch (ContactClientRetryException $e) {
            $this->setValid(false);
            $this->setLogs($this->getValid(), 'valid');
            $this->setLogs($e->getMessage(), 'filter');
            $this->setFilter($e->getMessage());
        }

        if ($this->updatePayload) {
            $this->updatePayloadResponse();
        }

        return $this;
    }

    /**
     * Automatically updates a response with filled in data from the parsed response object from the API.
     */
    public function updatePayloadResponse()
    {
        $result = $this->responseExpected;
        $updates = false;
        foreach (['headers', 'body'] as $type) {
            if (isset($this->responseActual[$type])) {
                $fieldKeys = [];
                foreach ($result->{$type} as $id => $value) {
                    if (!empty($value->key) && is_numeric($id)) {
                        $fieldKeys[$value->key] = intval($id);
                    }
                }
                foreach ($this->responseActual[$type] as $key => $value) {
                    // Check if this type of response was not expected (headers / body).
                    if (!isset($result->{$type})) {
                        $result->{$type} = [];
                    }
                    // Check if header/body field is unexpected.
                    $fieldId = $fieldKeys[$key] ?? -1;
                    if ($fieldId == -1) {
                        // This is a new field.
                        $newField = new stdClass();
                        $newField->key = $key;
                        $newField->example = $value;
                        $result->{$type}[] = $newField;
                        $this->setLogs('New '.$type.' field "'.$key.'" added with example: '.$value, 'autoUpdate');
                        $updates = true;
                    } else {
                        if (!empty($value) && empty($result->{$type}[$fieldId]->example)) {
                            // This is an existing field, but requires an updated example.
                            $result->{$type}[$fieldId]->example = $value;
                            $updates = true;
                            $this->setLogs(
                                'Existing '.$type.' field "'.$key.'" now has an example: '.$value,
                                'autoUpdate'
                            );
                        }
                    }
                }
            }
        }
        if ($updates) {
            $this->operation->response = $result;
        }
    }

    public function getValid()
    {
        return $this->valid;
    }

    public function setValid($valid)
    {
        $this->valid = $valid;
    }

    public function getFilter()
    {
        return $this->filter;
    }

    public function setFilter($filter)
    {
        $this->filter = $filter;
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

    public function getResponseActual()
    {
        return $this->responseActual;
    }

    /**
     * Get filled responses that have Contact destinations defined.
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
     *
     * @return null
     */
    public function getExternalId()
    {
        $externalIds = array_flip($this->externalIds);
        $id = null;
        $idIndex = null;
        foreach (['headers', 'body'] as $type) {
            if (isset($this->responseActual[$type]) && isset($this->responseActual[$type])) {
                foreach ($this->responseActual[$type] as $key => $value) {
                    $key = preg_replace("/[^a-z0-9]/", '', strtolower($key));
                    if (isset($externalIds[$key]) && ($idIndex === null || $externalIds[$key] < $idIndex)) {
                        $idIndex = $externalIds[$key];
                        $id = $value;
                        if ($idIndex == 0) {
                            break;
                        }
                    }
                }
            }
        }

        return $id;
    }

}