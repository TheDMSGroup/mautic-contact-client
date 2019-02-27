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

use Doctrine\ORM\EntityManager;
use Mautic\CoreBundle\Model\AbstractCommonModel;
use MauticPlugin\MauticContactClientBundle\Entity\Auth;
use MauticPlugin\MauticContactClientBundle\Entity\AuthRepository;
use MauticPlugin\MauticContactClientBundle\Entity\ContactClient;
use MauticPlugin\MauticContactClientBundle\Helper\TokenHelper;

/**
 * Class ApiPayloadAuth.
 */
class ApiPayloadAuth extends AbstractCommonModel
{
    /** @var TokenHelper */
    protected $tokenHelper;

    /** @var bool */
    protected $test;

    /** @var array */
    protected $requiredPayloadTokens;

    /** @var array */
    protected $operations;

    /** @var ContactClient */
    protected $contactClient;

    /** @var array */
    protected $authOperations = [];

    /** @var EntityManager */
    protected $em;

    /** @var array */
    protected $authRequestOperations;

    /** @var AuthRepository */
    protected $authRepository;

    /** @var array */
    protected $previousPayloadAuthTokens = [];

    /**
     * ApiPayloadAuth constructor.
     *
     * @param TokenHelper   $tokenHelper
     * @param EntityManager $em
     */
    public function __construct(TokenHelper $tokenHelper, EntityManager $em)
    {
        $this->tokenHelper = $tokenHelper;
        $this->em          = $em;

        return $this;
    }

    /**
     * @param $test
     *
     * @return $this
     */
    public function setTest($test)
    {
        $this->test = $test;

        return $this;
    }

    /**
     * @param $operations
     *
     * @return $this
     */
    public function setOperations($operations)
    {
        $this->operations = $operations;

        return $this;
    }

    /**
     * @param $contactClient
     *
     * @return $this
     */
    public function setContactClient(ContactClient $contactClient)
    {
        $this->contactClient = $contactClient;

        return $this;
    }

    /**
     * Reset local class variables.
     *
     * @param array $exclusions optional array of local variables to keep current values
     *
     * @return $this
     */
    public function reset($exclusions = ['tokenHelper', 'em'])
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
     * Step forward through operations to find the one we MUST start with.
     *
     * @return array
     */
    public function getStartOperation()
    {
        $id = 0;
        if ($this->hasAuthRequest()) {
            // Step through operations, skipping any at the beginning that can be skipped.
            foreach ($this->operations as $id => $operation) {
                if ($id == count($this->operations) - 1) {
                    // This is the last request so it cannot be skipped.
                    break;
                }

                if (!$this->hasAuthRequest($id)) {
                    // This is not an auth request so it should never be skipped.
                    break;
                }

                if ($this->operationUpdatesContact($operation)) {
                    // This operation should never be skipped because it can update the contact.
                    break;
                }

                $this->determineOperationRequirements();
                if (isset($this->requiredPayloadTokens[$id])) {
                    // This step can be skipped if we have these tokens.
                    $this->loadPreviousPayloadAuthTokens();
                    foreach ($this->requiredPayloadTokens[$id] as $token) {
                        if (!isset($this->previousPayloadAuthTokens[$token])) {
                            // We cannot skip this operation because a required token isn't in the database yet.
                            break;
                        }
                    }
                } else {
                    // This step is indicated as auth, but no tokens from it are used in the following payload.
                    // Assume the client hasn't been fully configured and do not skip this operation.
                    break;
                }
            }
        }

        return $id;
    }

    /**
     * @param null $operationId
     *
     * @return bool
     */
    public function hasAuthRequest($operationId = null)
    {
        if (null === $this->authRequestOperations) {
            $this->authRequestOperations = [];
            if (count($this->operations) > 1) {
                foreach ($this->operations as $id => $operation) {
                    if (
                        isset($operation->request)
                        && isset($operation->request->auth)
                        && $operation->request->auth
                    ) {
                        $this->authRequestOperations[$id] = $id;
                    }
                }
            }
        }

        return $operationId ? isset($this->authRequestOperations[$operationId]) : boolval($this->authRequestOperations);
    }

    /**
     * @param $operation
     *
     * @return bool
     */
    private function operationUpdatesContact($operation)
    {
        if (isset($operation->response)) {
            foreach (['headers', 'body'] as $fieldType) {
                if (is_array($operation->response->{$fieldType})) {
                    foreach ($operation->response->{$fieldType} as $field) {
                        if (
                            isset($field->destination)
                            && '' !== $field->destination
                        ) {
                            return true;
                        }
                    }
                }
            }
        }

        return false;
    }

    /**
     * Discern the tokens that are required by operation ID.
     */
    private function determineOperationRequirements()
    {
        if (null === $this->requiredPayloadTokens) {
            $this->requiredPayloadTokens = [];

            $valueSources = ['value', 'default_value'];
            if ($this->test) {
                $valueSources = ['value', 'test_value', 'default_value'];
            }

            foreach ($this->operations as $id => $operation) {
                if (isset($operation->request)) {
                    // Check the request for payload tokens dependent on previous operations.
                    foreach (['headers', 'body'] as $fieldType) {
                        if (is_array($operation->request->{$fieldType})) {
                            foreach ($operation->request->{$fieldType} as $field) {
                                foreach ($valueSources as $valueSource) {
                                    if (!empty($field->{$valueSource})) {
                                        $tokens = $this->tokenHelper->getTokens($field->{$valueSource});
                                        if ($tokens) {
                                            // Check for tokens that use the payload of a previous operation.
                                            foreach ($tokens as $token) {
                                                $parts = explode('.', $token);
                                                if (
                                                    isset($parts[0])
                                                    && 'payload' === $parts[0]
                                                    && isset($parts[1])
                                                    && 'operations' === $parts[1]
                                                    && isset($parts[2])
                                                    && is_numeric($parts[2])
                                                    && isset($parts[3])
                                                    && 'response' === $parts[3]
                                                    && in_array($parts[4], ['headers', 'body'])
                                                    && isset($parts[5])
                                                ) {
                                                    if (!isset($this->requiredPayloadTokens[$parts[2]])) {
                                                        $this->requiredPayloadTokens[$parts[2]] = [];
                                                    }
                                                    $this->requiredPayloadTokens[$parts[2]][] = $token;
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * Load up the previously stored payload tokens.
     */
    private function loadPreviousPayloadAuthTokens()
    {
        if (!$this->previousPayloadAuthTokens) {
            $this->previousPayloadAuthTokens = $this->getAuthRepository()->getPreviousPayloadAuthTokensByContactClient(
                $this->contactClient->getId(),
                null,
                $this->test
            );
        }
    }

    /**
     * @return bool|\Doctrine\ORM\EntityRepository|\Mautic\CoreBundle\Entity\CommonRepository|AuthRepository
     */
    private function getAuthRepository()
    {
        if (!$this->authRepository) {
            $this->authRepository = $this->em->getRepository('MauticContactClientBundle:Auth');
        }

        return $this->authRepository;
    }

    /**
     * @return array
     */
    public function getPreviousPayloadAuthTokens()
    {
        return $this->previousPayloadAuthTokens;
    }

    /**
     * Given an array from the response object (headers and body) save as new Auth entities.
     * Flush previous entries for this operation.
     *
     * @param $operationId
     * @param $fieldSets
     */
    public function savePayloadAuthTokens($operationId, $fieldSets)
    {
        if (!$fieldSets) {
            return;
        }
        $repo = $this->getAuthRepository();
        $repo->flushPreviousAuthTokens($this->contactClient->getId(), $operationId, $this->test);

        $entities = [];
        foreach ($fieldSets as $type => $fields) {
            foreach ($fields as $field => $val) {
                if ($field && $val) {
                    $auth = new Auth();
                    $auth->setContactClient($this->contactClient->getId());
                    $auth->setOperation($operationId);
                    $auth->setType($type);
                    $auth->setField($field);
                    $auth->setVal(substr($val, 0, 256));
                    $auth->setTest($this->test);
                    $entities[] = $auth;
                }
            }
        }
        $repo->saveEntities($entities);
    }
}
