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
use Mautic\LeadBundle\Entity\LeadEventLog;
use Mautic\PluginBundle\Exception\ApiErrorException;
use Mautic\PluginBundle\Integration\AbstractIntegration;
use MauticPlugin\MauticContactClientBundle\Entity\ContactClient;
use MauticPlugin\MauticContactClientBundle\Entity\ContactClientRepository;
use MauticPlugin\MauticContactClientBundle\Entity\Stat;
use MauticPlugin\MauticContactClientBundle\Exception\ContactClientRetryException;
use MauticPlugin\MauticContactClientBundle\Model\ApiPayload;
use MauticPlugin\MauticContactClientBundle\Model\ContactClientModel;
use MauticPlugin\MauticContactClientBundle\Model\Revenue;
use MauticPlugin\MauticContactClientBundle\Model\Schedule;
use Mautic\PluginBundle\Entity\IntegrationEntity;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Yaml\Yaml;

/**
 * Class ClientIntegration
 * @package MauticPlugin\MauticContactClientBundle\Integration
 */
class ClientIntegration extends AbstractIntegration
{

    /**
     * @var ContactClient client we are about to send this Contact to.
     */
    protected $contactClient;

    /**
     * @var array Of temporary log entries.
     */
    protected $logs = [];

    /**
     * @var Contact The contact we wish to send and update.
     */
    protected $contact;

    /**
     * @var bool Test mode.
     */
    protected $test = false;

    /** @var ApiPayload */
    protected $payload;

    protected $valid = true;

    protected $container;

    protected $eventType;

    /** @var string */
    protected $statType;

    /** @var contactClientModel */
    protected $contactClientModel;

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
     * @param array $config
     * @return bool
     */
    public function pushLead($contact, $config = [])
    {

        $config = $this->mergeConfigToFeatureSettings($config);
        if (empty($config['contactclient'])) {
            return false;
        }

        /** @var Contact $contactModel */
        $clientModel = $this->getContactClientModel();

        $client = $clientModel->getEntity($config['contactclient']);
        if (!$client || $client->getIsPublished() === false) {
            return false;
        }

        // Get field overrides.
        $overrides = [];
        if (!empty($config['contactclient_overrides'])) {
            // Flatten overrides to key-value pairs.
            $obj = json_decode($config['contactclient_overrides']);
            $overrides = [];
            if ($obj) {
                foreach ($obj as $field) {
                    if (!empty($field->key) && !empty($field->value)) {
                        $overrides[$field->key] = $field->value;
                    }
                }
            }
        }

        $result = $this->sendContact($client, $contact, false, $overrides);

        return $result;
    }

    /**
     * @param $apiPayload
     * @return array
     */
    public function sendTest($apiPayload)
    {
        $client = new ContactClient();
        $client->setAPIPayload($apiPayload);
        $contact = new Contact();

        $this->sendContact($client, $contact, true);

        return [
            'valid' => $this->valid,
            'payload' => $client->getAPIPayload(),
        ];
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
     * @return contactClientModel
     */
    private function getContactClientModel()
    {
        if (!$this->contactClientModel) {
            $container = $this->dispatcher->getContainer();
            /** @var contactClientModel $contactClientModel */
            $this->contactClientModel = $container->get('mautic.contactclient.model.contactclient');
        }

        return $this->contactClientModel;
    }

    /**
     * Given the JSON API API instructions payload instruction set.
     * Send the lead/contact to the API by following the steps.
     *
     * @param ContactClient $client
     * @param Contact $contact
     * @param bool $test
     * @param array $overrides
     * @return bool
     */
    public function sendContact(
        ContactClient $client,
        Contact $contact,
        $test = false,
        $overrides = []
    ) {
        $container = $this->dispatcher->getContainer();

        // @todo - add translation layer for strings in this method.
        // $translator = $container->get('translator');


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
                /** @var Schedule $schedule */
                $schedule = new Schedule($this->contactClient, $container);
                $schedule->evaluateHours($this->contactClient);
                $schedule->evaluateExclusions($this->contactClient);
            }

            // @todo - Filtering - Check filter rules to ensure this contact is applicable.

            // @todo - Limits - Check limit rules to ensure we have not sent too many contacts in our window.

            // @todo - Exclusivity - Check exclusivity rules to ensure this contact hasn't been sent to a competitor.

            // @todo - Duplicates - Check duplicate cache to ensure we have not already sent this contact.

            $this->payload = new ApiPayload($this->contactClient, $this->contact, $container, $test);

            if ($overrides) {
                $this->payload->setOverrides($overrides);
            }

            $this->valid = $this->payload->run();

        } catch (\Exception $e) {
            $this->valid = false;
            $this->setLogs($e->getMessage(), 'error');
            if ($e instanceof ApiErrorException) {
                $e->setContact($this->contact);
            } elseif ($e instanceof ContactClientRetryException) {
                $e->setContact($this->contact);
                $this->setStatType($e->getStatType());

                // This type of exception indicates that we can requeue the contact.
                $this->logIntegrationError($e, $this->contact);
            }
        }

        // @todo - Revenue - Apply revenue (default or field based) to the Contact and Revenue stats.

        if (isset($this->payload)) {
            $this->setLogs($this->payload->getLogs(), 'operations');
        }

        $this->updateContact();

        $this->logResults();

        return $this->valid;
    }

    /**
     * Loop through the API Operation responses and find valid field mappings.
     * Set the new values to the contact and log the changes thereof.
     */
    private function updateContact()
    {
        if (!$this->test && !$this->payload) {
            return;
        }
        if ($this->valid) {
            /** @var bool $updatedFields */
            $updatedFields = $this->payload->applyResponseMap();
            if ($updatedFields) {
                $this->contact = $this->payload->getContact();
            }

            /** @var Revenue $revenue */
            $revenue = new Revenue($this->contactClient, $this->contact);
            $revenue->setPayload($this->payload);
            /** @var bool $updatedRevenue */
            $updatedRevenue = $revenue->applyRevenue();
            if ($updatedRevenue) {
                $this->contact = $revenue->getContact();
                $this->setLogs('Updating Contact cost/revenue attribution = '.$this->contact->getAttribution());
            }

            // Check the Revenue setting to see if we should apply to "attribution"
            if ($updatedFields || $updatedRevenue) {
                try {
                    /** @var Contact $contactModel */
                    $contactModel = $this->dispatcher->getContainer()->get('mautic.lead.model.lead');
                    $contactModel->saveEntity($this->contact);
                    $this->setLogs('Operation successful. The Contact was updated.', 'updated');
                } catch (Exception $e) {
                    $this->setLogs('Failure to update our Contact. '.$e->getMessage(), 'error');
                    $this->valid = false;
                    $this->logIntegrationError($e, $this->contact);
                }
            } else {
                $this->setLogs('Operation successful, but no fields on the Contact needed updating.', 'info');
            }
        }
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
    public function setStatType($statType = null)
    {
        if (!empty($statType)) {
            $this->statType = $statType;
        }
        return $this;
    }

    /**
     * Log to:
     *      contactclient_stats
     *      contactclient_events
     *      integration_entity
     *
     * Use LeadTimelineEvent
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

        // @todo - additional stat logging:
        // Stat::TYPE_QUEUED - Queued should happen before pushLead when a lead is discerned that it should go to this client.
        // Stat::TYPE_DUPLICATE
        // Stat::TYPE_EXCLUSIVE
        // Stat::TYPE_FILTER
        // Stat::TYPE_LIMITS
        // Stat::TYPE_REVENUE
        // Stat::TYPE_SCHEDULE

        if ($this->valid) {
            $statType = Stat::TYPE_SUCCESS;
            $statLevel = 'INFO';
            $message = 'Contact was sent successfully.';
        } else {
            $statType = $this->statType ?: Stat::TYPE_ERROR;
            $statLevel = 'ERROR';
            $message = $operation['error'] ?? 'An unexpected error occurred.';
            // Check for a filter-based rejection.
            if (isset($this->logs['operations'])) {
                foreach ($this->logs['operations'] as $operation) {
                    if (isset($operation['filter'])) {
                        // Contact was rejected due to success definition filters.
                        $statType = Stat::TYPE_REJECT;
                        $statLevel = 'WARNING';
                        $message = $operation['filter'];
                        break;
                    }
                }
            }
        }

        // Add log entry for statistics / charts.
        $clientModel->addStat($this->contactClient, $statType, $this->contact);

        // Add transactional event for deep dive into logs.
        $clientModel->addEvent(
            $this->contactClient,
            $statType,
            $this->contact,
            $this->getLogsYAML(),
            $message,
            $integration_entity_id
        );

        // Lead event log (lead_event_log) I've decided to leave this out for now because it's not very useful.
        //$contactModel = $container->get('mautic.lead.model.lead');
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
                    $this->contact,
                    $this->contactClient->getName(),
                    'lead',
                    $integration_entity_id
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

    public function getLogsYAML()
    {
        return Yaml::dump($this->getLogs(), 10, 2);
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

    /**
     * @param $entity
     * @param $object
     * @param $mauticObjectReference
     * @param $integrationEntityId
     *
     * @return IntegrationEntity|null|object
     */
    public function saveSyncedData($entity, $object, $mauticObjectReference, $integrationEntityId)
    {
        /** @var IntegrationEntityRepository $integrationEntityRepo */
        $integrationEntityRepo = $this->em->getRepository('MauticPluginBundle:IntegrationEntity');
        $integrationEntities = $integrationEntityRepo->getIntegrationEntities(
            $this->getName(),
            $object,
            $mauticObjectReference,
            [$entity->getId()]
        );

        if ($integrationEntities) {
            $integrationEntity = reset($integrationEntities);
        } else {
            $integrationEntity = new IntegrationEntity();
            $integrationEntity->setDateAdded(new \DateTime());
            $integrationEntity->setIntegration($this->getName());
            $integrationEntity->setIntegrationEntity($object);
            $integrationEntity->setIntegrationEntityId($integrationEntityId);
            $integrationEntity->setInternalEntity($mauticObjectReference);
            $integrationEntity->setInternalEntityId($entity->getId());
        }
        // We may not want to log here as well in future.
        $integrationEntity->setInternal($this->logs);
        $integrationEntity->setLastSyncDate(new \DateTime());

        return $integrationEntity;
    }

    /**
     * @todo - Push multiple contacts by Campaign Action.
     * @param array $params
     *
     * @return mixed
     */
    public function pushLeads($params = [])
    {
        // $limit = (isset($params['limit'])) ? $params['limit'] : 100;
        $totalUpdated = 0;
        $totalCreated = 0;
        $totalErrors = 0;
        $totalIgnored = 0;

        return [$totalUpdated, $totalCreated, $totalErrors, $totalIgnored];
    }

    public function getValid()
    {
        return $this->valid;
    }

    /**
     * @param \Mautic\PluginBundle\Integration\Form|\Symfony\Component\Form\FormBuilder $builder
     * @param array $data
     * @param string $formArea
     */
    public function appendToForm(&$builder, $data, $formArea)
    {
        if ($formArea == 'integration') {
            if ($this->isAuthorized()) {

                /** @var contactClientModel $clientModel */
                $clientModel = $this->getContactClientModel();

                /** @var contactClientRepository $contactClientRepo */
                $contactClientRepo = $clientModel->getRepository();
                $contactClientEntities = $contactClientRepo->getEntities();
                $clients = ['' => ''];
                $overridableFields = [];
                foreach ($contactClientEntities as $contactClientEntity) {
                    if ($contactClientEntity->getIsPublished()) {
                        $id = $contactClientEntity->getId();
                        $clients[$id] = $contactClientEntity->getName();

                        // Get overridable fields from the payload of the type needed.
                        if ($contactClientEntity->getType() == 'api') {
                            $payload = new ApiPayload($contactClientEntity);
                            $overridableFields[$id] = $payload->getOverridableFields();
                        } else {
                            // @todo - File based payload.
                        }
                    }
                }
                if (count($clients) === 1) {
                    $clients = ['', '-- No Clients have been created and published --'];
                }

                $builder->add(
                    'contactclient',
                    'choice',
                    [
                        'choices' => $clients,
                        'expanded' => false,
                        'label_attr' => ['class' => 'control-label'],
                        'multiple' => false,
                        'label' => 'mautic.contactclient.integration.client',
                        'attr' => [
                            'class' => 'form-control',
                            'tooltip' => 'mautic.contactclient.integration.client.tooltip',
                            // Auto-set the integration name based on the client.
                            'onchange' =>
                                "var client = mQuery('#campaignevent_properties_config_contactclient:first'),".
                                "    eventName = mQuery('#campaignevent_name');".
                                "if (client.length && client.val() && eventName.length) {".
                                "    eventName.val(client.text().trim());".
                                "}",
                        ],
                        'required' => true,
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
                        'attr' => [
                            'class' => 'btn btn-default btn-nospin',
                            'tooltip' => 'mautic.contactclient.integration.overrides.tooltip',
                            // Shim to get our javascript over the border and into Integration land.
                            'onclick' =>
                                "if (typeof Mautic.contactclientIntegration === 'undefined') {".
                                "    mQuery.getScript(mauticBasePath + '/' + mauticAssetPrefix + 'plugins/MauticContactClientBundle/Assets/build/contactclient.min.js', function(){".
                                "        Mautic.contactclientIntegration();".
                                "    });".
                                "    mQuery('head').append('<"."link rel=\'stylesheet\' href=\'' + mauticBasePath + '/' + mauticAssetPrefix + 'plugins/MauticContactClientBundle/Assets/build/contactclient.min.css\' type=\'text/css\' />');".
                                "} else {".
                                "    Mautic.contactclientIntegration();".
                                "}",
                            'icon' => 'fa fa-wrench',
                        ],
                    ]
                );

                $builder->add(
                    'contactclient_overrides',
                    'textarea',
                    [
                        'label' => 'mautic.contactclient.integration.overrides',
                        'label_attr' => ['class' => 'control-label hide'],
                        'attr' => [
                            'class' => 'form-control hide',
                            'tooltip' => 'mautic.contactclient.integration.overrides.tooltip',
                        ],
                        'required' => false,
                    ]
                );
            }
        }
    }
}
