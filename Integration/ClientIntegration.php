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

use Exception;
use Mautic\LeadBundle\Entity\Lead as Contact;
use Mautic\PluginBundle\Entity\IntegrationEntity;
use Mautic\PluginBundle\Exception\ApiErrorException;
use Mautic\PluginBundle\Integration\AbstractIntegration;
use MauticPlugin\MauticContactClientBundle\Entity\ContactClient;
use MauticPlugin\MauticContactClientBundle\Entity\ContactClientRepository;
use MauticPlugin\MauticContactClientBundle\Entity\Stat;
use MauticPlugin\MauticContactClientBundle\Event\ContactLedgerContextEvent;
use MauticPlugin\MauticContactClientBundle\Exception\ContactClientException;
use MauticPlugin\MauticContactClientBundle\Helper\JSONHelper;
use MauticPlugin\MauticContactClientBundle\Model\ApiPayload;
use MauticPlugin\MauticContactClientBundle\Model\Attribution;
use MauticPlugin\MauticContactClientBundle\Model\ContactClientModel;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Yaml\Yaml;

/**
 * Class ClientIntegration.
 */
class ClientIntegration extends AbstractIntegration
{
    /** @var ContactClient client we are about to send this Contact to. */
    protected $contactClient;

    /** @var array Of temporary log entries. */
    protected $logs = [];

    /** @var Contact $contact The contact we wish to send and update. */
    protected $contact;

    /** @var array */
    protected $event = [];

    /** @var bool $test */
    protected $test = false;

    /** @var ApiPayload $payload */
    protected $payload;

    /** @var bool $valid */
    protected $valid = true;

    /** @var Container $container */
    protected $container;

    /** @var string $statType */
    protected $statType;

    /** @var ContactClientModel */
    protected $contactClientModel;

    /** @var \MauticPlugin\MauticContactClientBundle\Model\Cache */
    protected $cacheModel;

    /** @var \MauticPlugin\MauticContactClientBundle\Model\Schedule */
    protected $scheduleModel;

    /** @var \Mautic\CampaignBundle\Entity\Campaign */
    protected $campaign;

    /** @var bool */
    protected $retry;

    public function getDisplayName()
    {
        return 'Clients';
    }

    /**
     * @return array
     */
    public function getSupportedFeatures()
    {
        return ['push_lead', 'push_leads'];
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
     * Push a contact to a preconfigured Contact Client.
     *
     * @param Contact $contact
     * @param array   $config
     *
     * @return bool
     *
     * @throws Exception
     */
    public function pushLead($contact, $config = [])
    {
        $this->event = $config;
        $config      = $this->mergeConfigToFeatureSettings($config);
        if (empty($config['contactclient'])) {
            return false;
        }

        /** @var Contact $contactModel */
        $clientModel = $this->getContactClientModel();

        $client = $clientModel->getEntity($config['contactclient']);
        if (!$client || false === $client->getIsPublished()) {
            return false;
        }

        // Get field overrides.
        $overrides = [];
        if (!empty($config['contactclient_overrides'])) {
            // Flatten overrides to key-value pairs.
            $jsonHelper = new JSONHelper();
            $array      = $jsonHelper->decodeArray($config['contactclient_overrides'], 'Overrides');
            if ($array) {
                foreach ($array as $field) {
                    if (!empty($field->key) && !empty($field->value)) {
                        $overrides[$field->key] = $field->value;
                    }
                }
            }
        }


        // Returning false will typically cause a retry.
        // If an error occurred and we do not wish to retry we should return true.
    }

    /**
     * Merges a config from integration_list with feature settings.
     *
     * @param array $config
     *
     * @return array|mixed
     */
    public function mergeConfigToFeatureSettings($config = [])
    {
        $featureSettings = $this->settings->getFeatureSettings();

        if (isset($config['config'])
            && (empty($config['integration'])
                || (!empty($config['integration'])
                    && $config['integration'] == $this->getName()))
        ) {
            $featureSettings = array_merge($featureSettings, $config['config']);
        }

        return $featureSettings;
    }

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
     * @return ContactClientModel
     */
    private function getContactClientModel()
    {
        if (!$this->contactClientModel) {
            /* @var ContactClientModel $contactClientModel */
            $this->contactClientModel = $this->getContainer()->get('mautic.contactclient.model.contactclient');
        }

        return $this->contactClientModel;
    }

    /**
     * @return Container|\Symfony\Component\DependencyInjection\ContainerInterface
     */
    private function getContainer()
    {
        if (!$this->container) {
            $this->container = $this->dispatcher->getContainer();
        }

        return $this->container;
    }

    /**
     * Given the JSON API API instructions payload instruction set.
     * Send the lead/contact to the API by following the steps.
     *
     * @param ContactClient $client
     * @param Contact       $contact
     * @param bool          $test
     * @param array         $overrides
     *
     * @return bool
     */
    public function sendContact(
        ContactClient $client,
        Contact $contact,
        $test = false,
        $overrides = []
    ) {
        // @todo - add translation layer for strings in this method.
        // $translator = $this->getContainer()->get('translator');

        $this->test = $test;

        try {
            if (!$client && !$this->test) {
                throw new \InvalidArgumentException('Contact Client appears to not exist.');
            }
            $this->contactClient = $client;

            if (!$contact && !$this->test) {
                throw new \InvalidArgumentException('Contact appears to not exist.');
            }
            $this->contact = $contact;

            // Check all rules that may preclude sending this contact, in order of performance cost.

            // Schedule - Check schedule rules to ensure we can send a contact now, retry if outside of window.
            if (!$this->test) {
                /** @var \MauticPlugin\MauticContactClientBundle\Model\Schedule $schedule */
                $schedule = $this->getScheduleModel();
                $schedule->evaluateHours($this->contactClient);
                $schedule->evaluateExclusions($this->contactClient);
            }

            // @todo - Filtering - Check filter rules to ensure this contact is applicable.

            // Limits - Check limit rules to ensure we have not sent too many contacts in our window.
            if (!$this->test) {
                $this->getCacheModel()->evaluateLimits();
            }

            // Duplicates - Check duplicate cache to ensure we have not already sent this contact.
            if (!$this->test) {
                $this->getCacheModel()->evaluateDuplicate();
            }

            // Exclusivity - Check exclusivity rules on the cache to ensure this contact hasn't been sent to a disallowed competitor.
            if (!$this->test) {
                $this->getCacheModel()->evaluateExclusive();
            }

            // Configure the payload.
            $this->getApiPayloadModel();
            $this->payload
                ->setTest($test)
                ->setContactClient($this->contactClient)
                ->setContact($this->contact)
                ->setOverrides($overrides);

            // Run the payload and all operations.
            $this->valid = $this->payload->run();

            if ($this->valid) {
                $this->statType = Stat::TYPE_CONVERTED;
            }
        } catch (\Exception $e) {
            $this->handleException($e);
        }

        if ($this->payload) {
            $operationLogs = $this->payload->getLogs();
            if ($operationLogs) {
                $this->setLogs($operationLogs, 'operations');
            }
        }

        $this->updateContact();

        $this->createCache();

        $this->logResults();

        return $this->valid;
    }

    /**
     * @return \MauticPlugin\MauticContactClientBundle\Model\Schedule|object
     *
     * @throws Exception
     */
    private function getScheduleModel()
    {
        if (!$this->scheduleModel) {
            /* @var \MauticPlugin\MauticContactClientBundle\Model\Schedule scheduleModel */
            $this->scheduleModel = $this->getContainer()->get('mautic.contactclient.model.schedule');
            $this->scheduleModel->setContactClient($this->contactClient);
        }

        return $this->scheduleModel;
    }

    /**
     * Get the Cache model for duplicate/exclusive/limit checking.
     *
     * @return \MauticPlugin\MauticContactClientBundle\Model\Cache
     *
     * @throws Exception
     */
    private function getCacheModel()
    {
        if (!$this->cacheModel) {
            /* @var \MauticPlugin\MauticContactClientBundle\Model\Cache $cacheModel */
            $this->cacheModel = $this->getContainer()->get('mautic.contactclient.model.cache');
            $this->cacheModel->setContact($this->contact);
            $this->cacheModel->setContactClient($this->contactClient);
        }

        return $this->cacheModel;
    }

    /**
     * @return ApiPayload
     */
    private function getApiPayloadModel()
    {
        if (!$this->payload) {
            $this->payload = $this->getContainer()->get('mautic.contactclient.model.apipayload');
        }

        return $this->payload;
    }

    /**
     * @param \Exception $exception
     */
    private function handleException(\Exception $exception)
    {
        // Any exception means the client send has failed.
        $this->valid = false;
        $this->setLogs($exception->getMessage(), 'error');

        $field = null;
        // $code  = $exception->getCode();
        if ($exception instanceof ApiErrorException) {
            // Critical issue with the API. This will be logged and retried.
            // To be deprecated.
            if ($this->contact) {
                $exception->setContact($this->contact);
            }
        }
        if ($exception instanceof ContactClientException) {
            // A known exception within the Client handling.
            if ($this->contact) {
                $exception->setContact($this->contact);
            }

            $statType = $exception->getStatType();
            if ($statType) {
                $this->statType = $statType;
                $this->setLogs($this->statType, 'status');
            }

            $errorData = $exception->getData();
            if ($errorData) {
                $this->setLogs($errorData, $statType);
            }

            if ($exception->getRetry()) {
                // This type of exception indicates that we can requeue the contact.
                $this->logIntegrationError($exception, $this->contact);
                $this->retry = true;
                $this->setLogs($this->retry, 'retry');
            }
        }
    }

    /**
     * Loop through the API Operation responses and find valid field mappings.
     * Set the new values to the contact and log the changes thereof.
     */
    private function updateContact()
    {
        // Do not update contacts for test runs.
        if ($this->test || !$this->payload) {
            return;
        }

        // Only update contacts if success definitions are met.
        if (!$this->valid) {
            return;
        }

        try {
            $this->dispatchContextCreate();

            // Update any fields based on the response map.
            /** @var bool $updatedFields */
            $updatedFields = $this->payload->applyResponseMap();
            if ($updatedFields) {
                $this->contact = $this->payload->getContact();
            }

            // Update attribution based on attribution settings.
            /** @var Attribution $attribution */
            $attribution = new Attribution($this->contactClient, $this->contact);
            $attribution->setPayload($this->payload);
            /** @var bool $updatedAttribution */
            $updatedAttribution = $attribution->applyAttribution();
            if ($updatedAttribution) {
                $this->contact = $attribution->getContact();
                $this->setLogs(strval(round($attribution->getAttributionChange(), 4)), 'attribution');
            } else {
                $this->setLogs('0', 'attribution');
            }
            $this->setLogs(strval(round($this->contact->getAttribution(), 4)), 'attributionTotal');

            // If any fields were updated, save the Contact entity.
            if ($updatedFields || $updatedAttribution) {
                /** @var \Mautic\LeadBundle\Model\LeadModel $model */
                $contactModel = $this->dispatcher->getContainer()->get('mautic.lead.model.lead');
                $contactModel->saveEntity($this->contact);
                $this->setLogs('Operation successful. The contact was updated.', 'updated');
            } else {
                $this->setLogs('Operation successful, but no fields on the contact needed updating.', 'info');
            }
            if (!$updatedAttribution) {
                // Fields may have updated, but not attribution, so the ledger needs an event to capture conversions.
                $this->dispatchContextCapture();
            }
        } catch (\Exception $e) {
            $this->valid = false;
            $this->setLogs('Operation completed, but we failed to update our Contact. '.$e->getMessage(), 'error');
            $this->logIntegrationError($e, $this->contact);
            $this->retry = false;
        }
    }

    /**
     * Provide context to Ledger plugin (or others) about this contact for save events.
     */
    private function dispatchContextCreate()
    {
        if ($this->test || !$this->payload) {
            return;
        }

        $campaign = $this->getCampaign();
        $event    = new ContactLedgerContextEvent(
            $campaign, $this->contactClient, $this->statType, '0 Revenue conversion', $this->contact
        );
        $this->dispatcher->dispatch(
            'mautic.contactledger.context_create',
            $event
        );
    }

    /**
     * Attempt to discern if we are being triggered by/within a campaign.
     *
     * @return \Mautic\CampaignBundle\Entity\Campaign
     */
    private function getCampaign()
    {
        if (!$this->campaign && $this->event) {
            try {
                $config      = $this->event;
                $identityMap = $this->em->getUnitOfWork()->getIdentityMap();
                if (isset($identityMap['Mautic\CampaignBundle\Entity\LeadEventLog'])) {
                    /** @var \Mautic\CampaignBundle\Entity\LeadEventLog $leadEventLog */
                    foreach ($identityMap['Mautic\CampaignBundle\Entity\LeadEventLog'] as $leadEventLog) {
                        $properties = $leadEventLog->getEvent()->getProperties();
                        if (
                            $properties['_token'] === $config['_token']
                            && $properties['campaignId'] === $config['campaignId']
                        ) {
                            $this->campaign = $leadEventLog->getCampaign();
                            break;
                        }
                    }
                }
            } catch (\Exception $e) {
            }
        }

        return $this->campaign;
    }

    /**
     * For situations where there is no entity saved, but we still need to log a conversion.
     */
    private function dispatchContextCapture()
    {
        if ($this->test || !$this->valid || !$this->payload || Stat::TYPE_CONVERTED !== $this->statType) {
            return;
        }

        $campaign = $this->getCampaign();
        $event    = new ContactLedgerContextEvent(
            $campaign, $this->contactClient, $this->statType, null, $this->contact
        );
        $this->dispatcher->dispatch(
            'mautic.contactledger.context_capture',
            $event
        );
    }

    /**
     * If all went well, and a contact was sent, create a cache entity for later correlation on exclusive/duplicate/
     * limit rules.
     */
    private function createCache()
    {
        if (!$this->test && $this->valid) {
            try {
                $this->getCacheModel()->create();
            } catch (Exception $e) {
                // Do not log this as an error, because the contact was sent successfully.
                $this->setLogs(
                    'Caching issue which may impact duplicates/exclusivity/limits: '.$e->getMessage(),
                    'warning'
                );
            }
        }
    }

    /**
     * Log to:
     *      contactclient_stats
     *      contactclient_events
     *      integration_entity.
     */
    private function logResults()
    {
        // Do not log the results of a test?
        if ($this->test) {
            return;
        }
        $integration_entity_id = !empty($this->payload) ? $this->payload->getExternalId() : null;

        /** @var contactClientModel $clientModel */
        $clientModel = $this->getContactClientModel();

        // Stats - contactclient_stats
        $errors         = $this->getLogs('error');
        $this->statType = !empty($this->statType) ? $this->statType : Stat::TYPE_ERROR;
        if ($this->valid) {
            $statLevel = 'INFO';
            $message   = 'Contact was sent successfully.';
        } else {
            $statLevel = 'ERROR';
            $message   = $errors ? implode(PHP_EOL, $errors) : 'An unexpected issue occurred.';
        }

        // Session storage for external plugins (should probably be dispatcher instead).
        $session          = $this->dispatcher->getContainer()->get('session');
        $eventId          = isset($this->event['id']) ? $this->event['id'] : 0;
        $eventName        = isset($this->event['name']) ? $this->event['name'] : null;
        $events           = $session->get('contactclient_events', []);
        $events[$eventId] = [
            'name'     => $eventName,
            'valid'    => $this->valid,
            'statType' => $this->statType,
            'error'    => $errors,
        ];
        $session->set('contactclient_events', $events);
        // Indicates that a single (or more) valid sends have been made.
        if ($this->valid) {
            $session->set('contactclient_valid', true);
        }
        // get the original / first utm source code for contact
        try {
            /** @var \MauticPlugin\MauticContactClientBundle\Helper\UtmSourceHelper $utmHelper */
            $utmHelper = $this->container->get('mautic.contactclient.helper.utmsource');
            $utmSource = $utmHelper->getFirstUtmSource($this->contact);
        } catch (\Exception $e) {
            $utmSource = null;
        }

        // Add log entry for statistics / charts.
        $clientModel->addStat($this->contactClient, $this->statType, $this->contact, $attribution, $utmSource);

        // Add transactional event for deep dive into logs.
        $clientModel->addEvent(
            $this->contactClient,
            $this->statType,
            $this->contact,
            $this->getLogsYAML(),
            $message,
            $integration_entity_id
        );

        // Lead event log (lead_event_log) I've decided to leave this out for now because it's not very useful.
        //$contactModel = $this->getContainer()->get('mautic.lead.model.lead');
        //$eventLogRepo = $contactModel->getEventLogRepository();
        //$eventLog = new LeadEventLog();
        //$eventLog
        //    ->setUserId($this->contactClient->getCreatedBy())
        //    ->setUserName($this->contactClient->getCreatedByUser())
        //    ->setBundle('lead')
        //    ->setObject('import')
        //    ->setObjectId($this->contactClient->getId())
        //    ->setLead($this->contact)
        //    ->setAction('updated')
        //    ->setProperties($this->logs);
        //$eventLogRepo->saveEntity($eventLog);

        // $this->dispatchIntegrationKeyEvent()

        // Integration entity creation (shows up under Integrations in a Contact).
        if ($this->valid) {
            $integrationEntities = [
                $this->saveSyncedData(
                    'Client',
                    'ContactClient',
                    $this->contactClient->getId(),
                    $this->contact
                ),
            ];
            if (!empty($integrationEntities)) {
                $this->em->getRepository('MauticPluginBundle:IntegrationEntity')->saveEntities($integrationEntities);
                $this->em->clear('Mautic\PluginBundle\Entity\IntegrationEntity');
            }
        }

        // File-based logging.
        $this->getLogger()->log($statLevel, 'Contact Client '.$this->contactClient->getId().': '.$message);
    }

    /**
     * @param string $key
     *
     * @return array|mixed|null
     */
    public function getLogs($key = '')
    {
        if ($key) {
            if (isset($this->logs[$key])) {
                if (!is_array($this->logs[$key])) {
                    return [$this->logs[$key]];
                } else {
                    return $this->logs[$key];
                }
            } else {
                return null;
            }
        }

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

    /**
     * @return string
     */
    public function getLogsYAML()
    {
        return Yaml::dump($this->getLogs(), 10, 2);
    }

    /**
     * Create a new integration record (we are never updating here).
     *
     * @param        $integrationName
     * @param        $integrationEntity
     * @param        $integrationEntityId
     * @param        $entity
     * @param string $internalEntityType
     * @param null   $internalData
     *
     * @return IntegrationEntity
     */
    private function saveSyncedData(
        $integrationName,
        $integrationEntity,
        $integrationEntityId,
        $entity,
        $internalEntityType = 'lead',
        $internalData = null
    ) {
        /** @var IntegrationEntity $newIntegrationEntity */
        $newIntegrationEntity = new IntegrationEntity();
        $newIntegrationEntity->setDateAdded(new \DateTime());
        $newIntegrationEntity->setIntegration($integrationName);
        $newIntegrationEntity->setIntegrationEntity($integrationEntity);
        $newIntegrationEntity->setIntegrationEntityId($integrationEntityId);
        $newIntegrationEntity->setInternalEntity($internalEntityType);
        $newIntegrationEntity->setInternalEntityId($entity->getId());
        $newIntegrationEntity->setLastSyncDate(new \DateTime());

        // This is too heavy of data to log in multiple locations.
        if ($internalData) {
            $newIntegrationEntity->setInternal($internalData);
        }

        return $newIntegrationEntity;
    }

    /**
     * @param \Symfony\Component\Form\FormBuilder $builder
     * @param array                               $data
     * @param string                              $formArea
     *
     * @throws Exception
     */
    public function appendToForm(&$builder, $data, $formArea)
    {
        if ('integration' == $formArea) {
            if ($this->isAuthorized()) {
                /** @var contactClientModel $clientModel */
                $clientModel = $this->getContactClientModel();

                /** @var contactClientRepository $contactClientRepo */
                $contactClientRepo     = $clientModel->getRepository();
                $contactClientEntities = $contactClientRepo->getEntities();
                $clients               = ['' => ''];
                $overridableFields     = [];
                foreach ($contactClientEntities as $contactClientEntity) {
                    if ($contactClientEntity->getIsPublished()) {
                        $id           = $contactClientEntity->getId();
                        $clients[$id] = $contactClientEntity->getName();

                        // Get overridable fields from the payload of the type needed.
                        if ('api' == $contactClientEntity->getType()) {
                            try {
                                $payload = $this->getApiPayloadModel();
                                $payload->setContactClient($contactClientEntity);
                                $overridableFields[$id] = $payload->getOverridableFields();
                            } catch (\Exception $e) {
                                if ($this->logger) {
                                    $this->logger->error($e->getMessage());
                                }
                                $clients[$id] .= ' ('.$e->getMessage().')';
                            }
                        } else {
                            // @todo - File based payload.
                        }
                    }
                }
                if (1 === count($clients)) {
                    $clients = ['', '-- No Clients have been created and published --'];
                }

                $builder->add(
                    'contactclient',
                    'choice',
                    [
                        'choices'     => $clients,
                        'expanded'    => false,
                        'label_attr'  => ['class' => 'control-label'],
                        'multiple'    => false,
                        'label'       => 'mautic.contactclient.integration.client',
                        'attr'        => [
                            'class'    => 'form-control',
                            'tooltip'  => 'mautic.contactclient.integration.client.tooltip',
                            // Auto-set the integration name based on the client.
                            'onchange' => "var client = mQuery('#campaignevent_properties_config_contactclient:first'),".
                                "    eventName = mQuery('#campaignevent_name');".
                                'if (client.length && client.val() && eventName.length) {'.
                                '    eventName.val(client.find("option:selected:first").text().trim());'.
                                '}',
                        ],
                        'required'    => true,
                        'constraints' => [
                            new NotBlank(
                                ['message' => 'mautic.core.value.required']
                            ),
                        ],
                        'choice_attr' => function ($val, $key, $index) use ($overridableFields) {
                            $results = [];
                            // adds a class like attending_yes, attending_no, etc
                            if ($val && isset($overridableFields[$val])) {
                                $results['class'] = 'contact-client-'.$val;
                                // Change format to match json schema.
                                $results['data-overridable-fields'] = json_encode($overridableFields[$val]);
                            }

                            return $results;
                        },
                    ]
                );

                $builder->add(
                    'contactclient_overrides_button',
                    'button',
                    [
                        'label' => 'mautic.contactclient.integration.overrides',
                        'attr'  => [
                            'class'   => 'btn btn-default btn-nospin',
                            'tooltip' => 'mautic.contactclient.integration.overrides.tooltip',
                            // Shim to get our javascript over the border and into Integration land.
                            'onclick' => "if (typeof Mautic.contactclientIntegration === 'undefined') {".
                                "    mQuery.getScript(mauticBasePath + '/' + mauticAssetPrefix + 'plugins/MauticContactClientBundle/Assets/build/contactclient.min.js', function(){".
                                '        Mautic.contactclientIntegration();'.
                                '    });'.
                                "    mQuery('head').append('<"."link rel=\'stylesheet\' href=\'' + mauticBasePath + '/' + mauticAssetPrefix + 'plugins/MauticContactClientBundle/Assets/build/contactclient.min.css\' type=\'text/css\' />');".
                                '} else {'.
                                '    Mautic.contactclientIntegration();'.
                                '}',
                            'icon'    => 'fa fa-wrench',
                        ],
                    ]
                );

                $builder->add(
                    'contactclient_overrides',
                    'textarea',
                    [
                        'label'      => 'mautic.contactclient.integration.overrides',
                        'label_attr' => ['class' => 'control-label hide'],
                        'attr'       => [
                            'class'   => 'form-control hide',
                            'tooltip' => 'mautic.contactclient.integration.overrides.tooltip',
                        ],
                        'required'   => false,
                    ]
                );
            }
        }
    }

    /**
     * Sends a test and updates the payload as needed.
     *
     * @param        $apiPayload
     * @param string $attributionDefault
     * @param string $attributionSettings
     *
     * @return bool
     */
    public function sendTest(&$apiPayload, $attributionDefault = '', $attributionSettings = '')
    {
        $client = new ContactClient();
        $client->setAPIPayload($apiPayload);
        if ($attributionSettings) {
            $client->setAttributionSettings($attributionSettings);
        }
        if ($attributionDefault) {
            $client->setAttributionDefault($attributionDefault);
        }
        $contact = new Contact();

        $this->sendContact($client, $contact, true);
        $apiPayload = $client->getAPIPayload();

        return $this->valid;
    }

    /**
     * @return string
     */
    public function getStatType()
    {
        return $this->statType;
    }

    /**
     * @param string $statType
     *
     * @return ClientIntegration
     */
    public function setStatType($statType = '')
    {
        $this->statType = $statType;

        return $this;
    }

    /**
     * @todo - Push multiple contacts by Campaign Action.
     *
     * @param array $params
     *
     * @return mixed
     */
    public function pushLeads($params = [])
    {
        // $limit = (isset($params['limit'])) ? $params['limit'] : 100;
        $totalUpdated = 0;
        $totalCreated = 0;
        $totalErrors  = 0;
        $totalIgnored = 0;

        return [$totalUpdated, $totalCreated, $totalErrors, $totalIgnored];
    }

    /**
     * @return bool
     */
    public function getValid()
    {
        return $this->valid;
    }
}
