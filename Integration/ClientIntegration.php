<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Digital Media Solutions, LLC
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticContactClientBundle\Integration;

use Exception;
use Mautic\CampaignBundle\Model\CampaignModel;
use Mautic\CoreBundle\Helper\UTF8Helper;
use Mautic\LeadBundle\Entity\Lead as Contact;
use Mautic\PluginBundle\Entity\IntegrationEntity;
use Mautic\PluginBundle\Exception\ApiErrorException;
use Mautic\PluginBundle\Integration\AbstractIntegration;
use MauticPlugin\MauticContactClientBundle\ContactClientEvents;
use MauticPlugin\MauticContactClientBundle\Entity\ContactClient;
use MauticPlugin\MauticContactClientBundle\Entity\ContactClientRepository;
use MauticPlugin\MauticContactClientBundle\Entity\Stat;
use MauticPlugin\MauticContactClientBundle\Event\ContactDncCheckEvent;
use MauticPlugin\MauticContactClientBundle\Event\ContactLedgerContextEvent;
use MauticPlugin\MauticContactClientBundle\Exception\ContactClientException;
use MauticPlugin\MauticContactClientBundle\Helper\FilterHelper;
use MauticPlugin\MauticContactClientBundle\Model\ApiPayload;
use MauticPlugin\MauticContactClientBundle\Model\Attribution;
use MauticPlugin\MauticContactClientBundle\Model\ContactClientModel;
use MauticPlugin\MauticContactClientBundle\Model\FilePayload;
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

    /** @var ApiPayload|FilePayload $payloadModel */
    protected $payloadModel;

    /** @var bool $valid */
    protected $valid = false;

    /** @var Container $container */
    protected $container;

    /** @var string $statType */
    protected $statType;

    /** @var ContactClientModel */
    protected $contactClientModel;

    /** @var FilePayload */
    protected $filePayloadModel;

    /** @var ApiPayload */
    protected $apiPayloadModel;

    /** @var \MauticPlugin\MauticContactClientBundle\Model\Cache */
    protected $cacheModel;

    /** @var \MauticPlugin\MauticContactClientBundle\Model\Schedule */
    protected $scheduleModel;

    /** @var \Mautic\CampaignBundle\Entity\Campaign */
    protected $campaign;

    /** @var bool */
    protected $retry;

    /** @var array */
    protected $integrationSettings;

    /** @var \DateTime */
    protected $dateSend;

    /** @var float */
    protected $attribution;

    /** @var \Mautic\LeadBundle\Model\LeadModel $model */
    protected $contactModel;

    /** @var array */
    private $dncChannels = [];

    /**
     * @return string
     */
    public function getDisplayName()
    {
        return 'Clients';
    }

    /**
     * @return array
     */
    public function getSupportedFeatures()
    {
        return ['push_lead'];
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
     * @param array   $event
     *
     * @return bool
     *
     * @throws Exception
     */
    public function pushLead($contact, $event = [])
    {
        $this->reset();
        $this->getEvent($event);

        if (empty($this->event['config']['contactclient'])) {
            return false;
        }

        /** @var Contact $contactModel */
        $clientModel = $this->getContactClientModel();
        $client      = $clientModel->getEntity($this->event['config']['contactclient']);

        $this->sendContact($client, $contact, false);

        // Returning false will typically cause a retry.
        // If an error occurred and we do not wish to retry we should return true.
        return $this->valid ? $this->valid : !$this->retry;
    }

    /**
     * Reset local class variables.
     *
     * @param array $exclusions optional array of local variables to keep current values
     *
     * @return $this
     */
    public function reset($exclusions = [])
    {
        foreach (array_diff_key(
                     get_class_vars(get_class($this)),
                     get_class_vars(get_parent_class($this)),
                     array_flip($exclusions)
                 ) as $name => $default) {
            $this->$name = $default;
        }

        return $this;
    }

    /**
     * Merges a config from integration_list with feature settings.
     *
     * @param array $event to merge configuration
     *
     * @return array|mixed
     */
    public function getEvent($event = [])
    {
        if (!$this->event) {
            $this->event = $event;
            if (isset($event['config'])
                && (empty($event['integration'])
                    || (
                        !empty($event['integration'])
                        && $event['integration'] == $this->getName()
                    )
                )
            ) {
                $this->event['config'] = array_merge($this->settings->getFeatureSettings(), $event['config']);
            }

            if (isset($this->event['campaignEvent']) && !empty($this->event['campaignEvent'])) {
                $campaignEvent             = $this->event['campaignEvent'];
                $this->event['id']         = $campaignEvent['id'];
                $this->event['campaignId'] = $campaignEvent['campaign']['id'];
            }

            // If the campaign event ID is missing, backfill it.
            if (!isset($this->event['id']) || !is_numeric($this->event['id'])) {
                try {
                    $identityMap = $this->em->getUnitOfWork()->getIdentityMap();
                    if (isset($identityMap['Mautic\CampaignBundle\Entity\Event'])) {
                        if (isset($identityMap['Mautic\CampaignBundle\Entity\Campaign']) && !empty($identityMap['Mautic\CampaignBundle\Entity\Campaign'])) {
                            $memoryCampaign = end($identityMap['Mautic\CampaignBundle\Entity\Campaign']);
                            $campaignId     = $memoryCampaign->getId();

                            /** @var \Mautic\CampaignBundle\Entity\Event $leadEvent */
                            foreach ($identityMap['Mautic\CampaignBundle\Entity\Event'] as $leadEvent) {
                                $properties = $leadEvent->getProperties();
                                $campaign   = $leadEvent->getCampaign();
                                if (
                                    $properties['_token'] === $this->event['_token']
                                    && $campaignId == $campaign->getId()
                                ) {
                                    $this->event['id']         = $leadEvent->getId();
                                    $this->event['name']       = $leadEvent->getName();
                                    $this->event['campaignId'] = $campaign->getId();
                                    break;
                                }
                            }
                        }
                    }
                } catch (\Exception $e) {
                }
            }
        }

        return $this->event;
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
     * @return ContactClientModel|object
     *
     * @throws Exception
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
     * @param ContactClient|null $client
     * @param Contact|null       $contact
     * @param bool               $test
     * @param bool               $force
     *
     * @return $this
     *
     * @throws Exception
     */
    public function sendContact(
        ContactClient $client = null,
        Contact $contact = null,
        $test = false,
        $force = false
    ) {
        if (!$this->translator) {
            $this->translator = $this->getContainer()->get('translator');
        }
        $this->contactClient = $client;
        $this->contact       = $contact;
        $this->test          = $test;

        try {
            $this->validateClient($client, $force);
            $this->addTrace('contactClientId', $this->contactClient->getId());

            $this->validateContact($contact);
            $this->addTrace('contactClientContactId', $this->contact->getId());

            // Check all rules that may preclude sending this contact, in order of performance cost.

            // Schedule - Check schedule rules to ensure we can send a contact now, do not retry if outside of window.
            $this->evaluateSchedule();

            // Filter - Check filter rules to ensure this contact is applicable.
            $this->evaluateFilter();

            // DNC - Check Do Not Contact channels for an entry for this contact that is not permitted for this client.
            $this->evaluateDnc();

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

            /* @var ApiPayload|FilePayload $model */
            $this->getPayloadModel($this->contactClient)
                ->setCampaign($this->getCampaign())
                ->setEvent($this->event);

            // Send all operations (API) or queue the contact (file).
            $this->payloadModel->run();

            $this->valid = $this->payloadModel->getValid();

            if ($this->valid) {
                $this->statType = Stat::TYPE_CONVERTED;
            }
        } catch (\Exception $e) {
            $this->handleException($e);
        }

        if ($this->payloadModel) {
            $operationLogs = $this->payloadModel->getLogs();
            if ($operationLogs) {
                $this->setLogs($operationLogs, 'operations');
            }
        }

        $this->updateContact();

        $this->createCache();

        $this->logResults();

        return $this;
    }

    /**
     * @param ContactClient|null $client
     * @param bool               $force
     *
     * @throws ContactClientException
     */
    private function validateClient(ContactClient $client = null, $force = false)
    {
        if (!$client && !$this->test) {
            throw new ContactClientException(
                $this->translator->trans('mautic.contactclient.sendcontact.error.client.load'),
                0,
                null,
                Stat::TYPE_INVALID,
                false
            );
        }
        if (!$force && !$this->test && !$client->getIsPublished()) {
            throw new ContactClientException(
                $this->translator->trans('mautic.contactclient.sendcontact.error.client.publish'),
                0,
                null,
                Stat::TYPE_UNPUBLISHED,
                false
            );
        }
    }

    /**
     * If available add a parameter to NewRelic tracing to aid in debugging.
     *
     * @param $parameter
     * @param $value
     */
    private function addTrace($parameter, $value)
    {
        if (function_exists('newrelic_add_custom_parameter')) {
            call_user_func('newrelic_add_custom_parameter', $parameter, $value);
        }
    }

    /**
     * @param Contact|null $contact
     *
     * @throws ContactClientException
     */
    private function validateContact(Contact $contact = null)
    {
        if (!$contact && !$this->test) {
            throw new ContactClientException(
                $this->translator->trans('mautic.contactclient.sendcontact.error.contact.load'),
                0,
                null,
                Stat::TYPE_INVALID,
                false
            );
        }
    }

    /**
     * Evaluates the schedule given the client type.
     *
     * @return $this
     *
     * @throws ContactClientException
     */
    private function evaluateSchedule()
    {
        /* @var \DateTime $dateSend */
        $this->dateSend = $this->getPayloadModel()->evaluateSchedule();

        return $this;
    }

    /**
     * Get the current Payload model, or the model of a particular client.
     *
     * @param ContactClient|null $contactClient
     *
     * @return ApiPayload|FilePayload|object
     *
     * @throws ContactClientException
     */
    private function getPayloadModel(ContactClient $contactClient = null)
    {
        if (!$this->payloadModel || $contactClient) {
            $contactClient = $contactClient ? $contactClient : $this->contactClient;
            $clientType    = $contactClient->getType();
            if ('api' == $clientType) {
                $model = $this->getApiPayloadModel();
            } elseif ('file' == $clientType) {
                $model = $this->getFilePayloadModel();
            } else {
                throw new \InvalidArgumentException('Client type is invalid.');
            }
            $model->reset();
            $model->setTest($this->test);
            $model->setContactClient($contactClient);
            if ($this->contact) {
                $model->setContact($this->contact);
            }
            $this->payloadModel = $model;
        }

        return $this->payloadModel;
    }

    /**
     * @return ApiPayload|object
     *
     * @throws Exception
     */
    private function getApiPayloadModel()
    {
        if (!$this->apiPayloadModel) {
            /* @var ApiPayload apiPayloadModel */
            $this->apiPayloadModel = $this->getContainer()->get('mautic.contactclient.model.apipayload');
        }

        return $this->apiPayloadModel;
    }

    /**
     * @return FilePayload|object
     *
     * @throws Exception
     */
    private function getFilePayloadModel()
    {
        if (!$this->filePayloadModel) {
            /* @var FilePayload filePayloadModel */
            $this->filePayloadModel = $this->getContainer()->get('mautic.contactclient.model.filepayload');
        }

        return $this->filePayloadModel;
    }

    /**
     * @return $this
     * @throws ContactClientException
     */
    private function evaluateFilter()
    {
        if ($this->test) {
            return $this;
        }
        $filter = $this->contactClient->getFilter();
        if ($filter) {
            $filterHelper = new FilterHelper();
            $context      = $this->getPayloadModel()->getTokenHelper()->getContext(true);
            try {
                $filterHelper->filter($filter, $context);
            } catch (\Exception $e) {
                throw new ContactClientException(
                    'Contact filtered: '.$e->getMessage(),
                    0,
                    $e,
                    Stat::TYPE_FILTER,
                    false,
                    null,
                    $filter->getErrors()
                );
            }

        }

        return $this;
    }

    /**
     * Evaluates the DNC entries for the Contact against the Client settings.
     *
     * @return $this
     *
     * @throws ContactClientException
     */
    private function evaluateDnc()
    {
        if ($this->test) {
            return $this;
        }
        $channels = explode(',', $this->contactClient->getDncChecks());
        if ($channels) {
            $dncCollection = $this->contact->getDoNotContact();
            foreach ($dncCollection as $dnc) {
                $currentChannel = $dnc->getChannel();
                foreach ($channels as $channel) {
                    if ($currentChannel == $channel) {
                        $comments = !in_array($dnc->getComments(), ['user', 'system']) ? $dnc->getComments() : '';
                        throw new ContactClientException(
                            trim(
                                $this->translator->trans(
                                    'mautic.contactclient.sendcontact.error.dnc',
                                    [
                                        '%channel%'  => $this->getDncChannelName($channel),
                                        '%date%'     => $dnc->getDateAdded()->format('Y-m-d H:i:s e'),
                                        '%comments%' => $comments,
                                    ]
                                )
                            ),
                            0,
                            null,
                            Stat::TYPE_DNC,
                            false
                        );
                    }
                }
            }
            // Support external DNC checking. Should throw ContactClientException if DNC match found.
            $event = new ContactDncCheckEvent($this->contact, $channels);
            $this->dispatcher->dispatch(ContactClientEvents::EXTERNAL_DNC_CHECK, $event);
        }

        return $this;
    }

    /**
     * Get all DNC Channels, or one by key.
     *
     * @param $key
     *
     * @return array|mixed
     */
    private function getDncChannelName($key)
    {
        if (!$this->dncChannels) {
            $this->dncChannels = $this->getContactModel()->getPreferenceChannels();
        }
        if ($key) {
            if (isset($this->dncChannels[$key])) {
                return $this->dncChannels[$key];
            } else {
                return ucwords($key);
            }
        }

        return '';
    }

    /**
     * @return \Mautic\LeadBundle\Model\LeadModel
     */
    private function getContactModel()
    {
        if (!$this->contactModel) {
            $this->contactModel = $this->dispatcher->getContainer()->get('mautic.lead.model.lead');
        }

        return $this->contactModel;
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
            $this->cacheModel->setDateSend($this->dateSend);
        }

        return $this->cacheModel;
    }

    /**
     * Attempt to discern if we are being triggered by/within a campaign.
     *
     * @return \Mautic\CampaignBundle\Entity\Campaign|mixed|null
     *
     * @throws Exception
     */
    private function getCampaign()
    {
        if (!$this->campaign && $this->event) {
            // Sometimes we have a campaignId as an integer ID.
            if (!empty($this->event['campaignId']) && is_integer($this->event['campaignId'])) {
                /** @var CampaignModel $campaignModel */
                $campaignModel  = $this->getContainer()->get('mautic.campaign.model.campaign');
                $this->campaign = $campaignModel->getEntity($this->event['campaignId']);
            }
            // Sometimes we have a campaignId as a hash.
            if (!$this->campaign) {
                try {
                    $identityMap = $this->em->getUnitOfWork()->getIdentityMap();
                    if (isset($identityMap['Mautic\CampaignBundle\Entity\Campaign']) && !empty($identityMap['Mautic\CampaignBundle\Entity\Campaign'])) {
                        $this->campaign = end($identityMap['Mautic\CampaignBundle\Entity\Campaign']);
                    }
                } catch (\Exception $e) {
                }
            }
        }

        return $this->campaign;
    }

    /**
     * @param Exception $exception
     *
     * @throws ContactClientException
     */
    private function handleException(\Exception $exception)
    {
        // Any exception means the client send has failed.
        $this->valid = false;

        if ($exception instanceof ApiErrorException) {
            // Critical issue with the API. This will be logged and retried.
            // To be deprecated.
            if ($this->contact) {
                $exception->setContact($this->contact);
            }
        } elseif ($exception instanceof ContactClientException) {
            // A known exception within the Client handling.
            if ($this->contact) {
                $exception->setContact($this->contact);
            }

            if ($exception->getStatType()) {
                $this->statType = $exception->getStatType();
                $this->setLogs($this->statType, 'status');
            }

            $errorData = $exception->getData();
            if ($errorData) {
                $this->setLogs($errorData, $exception->getStatType());
            }

            if (
                Stat::TYPE_SCHEDULE == $exception->getStatType()
                && 'api' == $this->contactClient->getType()
                && $this->contactClient->getScheduleQueue()
            ) {
                // Requeue the contact to be sent at a later time per API Schedule Queue setting,
                // and change stat type to queue
                $this->retry = true;
                $exception->setStatType(Stat::TYPE_SCHEDULE_QUEUE);
                $exception->setMessage($exception->getMessage().' Queued for a later.');
                $this->addRescheduleItemToSession();
            } elseif (
                Stat::TYPE_LIMITS == $exception->getStatType()
                && $this->contactClient->getLimitsQueue()
            ) {
                // Requeue the contact to be sent at a later time per Limits Queue setting,
                // and change stat type to queue
                $this->retry = true;
                $exception->setStatType(Stat::TYPE_LIMITS_QUEUE);
                $exception->setMessage($exception->getMessage().' Queued for a later.');
                $this->addRescheduleItemToSession(1);
            } elseif ($exception->getRetry()) {
                // Handle general exception retries.
                $this->logIntegrationError($exception, $this->contact);
                if (
                    $this->contactClient
                    && 'api' === $this->contactClient->getType()
                    && ($payloadModel = $this->getPayloadModel())
                    && ($settings = $payloadModel->getSettings())
                    && isset($settings['autoRetry'])
                ) {
                    // set to Client retry setting and IS an API payload
                    $this->retry = (bool) $settings['autoRetry'];
                } else {
                    $this->retry = true;
                }
            }
            $this->setLogs($this->retry, 'retry');
        }
        $this->setLogs($exception->getMessage(), 'error');
        $this->setLogs($this->retry, 'retry');
    }

    /**
     * @param int $startDay
     *
     * @throws Exception
     */
    public function addRescheduleItemToSession($startDay = 0)
    {
        if (isset($this->getEvent()['leadEventLog'])) {
            // add leadEventLog id instance to global session array for later processing in reschedule() dispatch.
            $contactClientRescheduleEvents = $this->getSession()->get('contact.client.reschedule.event', []);
            $range                         = $this->payloadModel->getScheduleModel()->nextOpening(1, 7, $startDay);
            if (!isset($range[0])) {
                $interval       = $this->factory->getParameter('campaign_time_wait_on_event_false');
                $rescheduleDate = new \DateTime('+'.$startDay.' day');
                try {
                    $rescheduleDate->add(new \DateInterval($interval));
                } catch (\Exception $e) {
                }
            } else {
                $rescheduleDate = $range[0];
            }

            $contactClientRescheduleEvents[$this->getEvent()['leadEventLog']->getId()] = $rescheduleDate;
            $this->getSession()->set('contact.client.reschedule.event', $contactClientRescheduleEvents);
        }
    }

    /**
     * @return object|\Symfony\Component\HttpFoundation\Session\Session|\Symfony\Component\HttpFoundation\Session\SessionInterface|null
     */
    private function getSession()
    {
        if (!$this->session) {
            // CLI will not have the session available by default.
            $this->session = $this->dispatcher->getContainer()->get('session');
        }

        return $this->session;
    }

    /**
     * Loop through the API Operation responses and find valid field mappings.
     * Set the new values to the contact and log the changes thereof.
     */
    private function updateContact()
    {
        // Assume no attribution till calculated.
        $this->attribution = 0;

        // Do not update contacts for test runs.
        if ($this->test || !$this->payloadModel) {
            return;
        }

        // Only update contacts if success definitions are met.
        if (!$this->valid) {
            return;
        }

        try {
            $this->dispatchContextCreate();

            // Only API model currently has a map to update contacts based on the response.
            $updatedFields = false;
            if (method_exists($this->payloadModel, 'applyResponseMap')) {
                /** @var bool $updatedFields */
                $updatedFields = $this->payloadModel->applyResponseMap();
                if ($updatedFields) {
                    $this->contact = $this->payloadModel->getContact();
                }
            }

            /** @var Attribution $attribution */
            $attribution = new Attribution($this->contactClient, $this->contact, $this->payloadModel);
            /** @var bool $updatedAttribution */
            $updatedAttribution = $attribution->applyAttribution();
            if ($updatedAttribution) {
                $this->attribution = $attribution->getAttributionChange();
                $this->setLogs(strval(round($this->attribution, 4)), 'attribution');
                if ($this->attribution && method_exists($this->payloadModel, 'setAttribution')) {
                    $this->payloadModel->setAttribution($this->attribution);
                }
            } else {
                $this->setLogs('0', 'attribution');
            }
            $this->setLogs(strval(round($this->contact->getAttribution(), 4)), 'attributionTotal');

            // If any fields were updated, save the Contact entity.
            if ($updatedFields || $updatedAttribution) {
                $this->getContactModel()->saveEntity($this->contact);
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
        if ($this->test || !$this->payloadModel) {
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
     * For situations where there is no entity saved, but we still need to log a conversion.
     */
    private function dispatchContextCapture()
    {
        if ($this->test || !$this->valid || !$this->payloadModel || Stat::TYPE_CONVERTED !== $this->statType) {
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
        $integrationEntityId = !empty($this->payloadModel) ? $this->payloadModel->getExternalId() : null;

        /** @var contactClientModel $clientModel */
        $clientModel = $this->getContactClientModel();

        // Stats - contactclient_stats
        $errors         = $this->getLogs('error');
        $this->statType = !empty($this->statType) ? $this->statType : Stat::TYPE_ERROR;
        $this->addTrace('contactClientStatType', $this->statType);
        $message = '';
        if ($this->valid) {
            $statLevel = 'INFO';
            switch ($this->contactClient->getType()) {
                case 'api':
                    $message = 'Contact was sent successfully.';
                    break;

                case 'file':
                    $message = 'Contact queued for the next file payload.';
                    break;
            }
        } else {
            $statLevel = 'ERROR';
            $message   = $errors ? implode(PHP_EOL, $errors) : 'An unexpected issue occurred.';
        }

        // Session storage for external plugins (should probably be dispatcher instead).
        $events   = $this->getSession()->get('mautic.contactClient.events', []);
        $events[] = [
            'id'                => isset($this->event['id']) ? $this->event['id'] : 'NA',
            'name'              => isset($this->event['name']) ? $this->event['name'] : null,
            'valid'             => $this->valid,
            'statType'          => $this->statType,
            'errors'            => $errors,
            'contactId'         => $this->contact ? $this->contact->getId() : null,
            'contactClientId'   => $this->contactClient ? $this->contactClient->getId() : null,
            'contactClientName' => $this->contactClient ? $this->contactClient->getName() : null,
        ];
        $this->getSession()->set('mautic.contactClient.events', $events);

        // get the original / first utm source code for contact
        $utmSource = null;
        if ($this->contact) {
            try {
                /** @var \MauticPlugin\MauticContactClientBundle\Helper\UtmSourceHelper $utmHelper */
                $utmHelper = $this->container->get('mautic.contactclient.helper.utmsource');
                $utmSource = $utmHelper->getFirstUtmSource($this->contact);
            } catch (\Exception $e) {
            }
        }

        // Add log entry for statistics / charts.
        $eventId    = (isset($this->event['id']) && $this->event['id']) ? $this->event['id'] : 0;
        $campaignId = (isset($this->event['campaignId']) && $this->event['campaignId']) ? $this->event['campaignId'] : 0;
        $clientModel->addStat(
            $this->contactClient,
            $this->statType,
            $this->contact,
            $this->attribution,
            $utmSource,
            $campaignId,
            $eventId
        );
        $this->em->clear('MauticPlugin\MauticContactClientBundle\Entity\Stat');

        // Add transactional event for deep dive into logs.
        if ($this->contact && $this->contactClient) {
            $clientModel->addEvent(
                $this->contactClient,
                $this->statType,
                $this->contact,
                $this->getLogsJSON(),
                $message,
                $integrationEntityId
            );
            $this->em->clear('MauticPlugin\MauticContactClientBundle\Entity\Event');
        }

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
                    $this->contactClient ? $this->contactClient->getId() : null,
                    $this->contact
                ),
            ];
            if (!empty($integrationEntities)) {
                $this->em->getRepository('MauticPluginBundle:IntegrationEntity')->saveEntities($integrationEntities);
                $this->em->clear('Mautic\PluginBundle\Entity\IntegrationEntity');
            }
        }

        // File-based logging.
        $this->getLogger()->log(
            $statLevel,
            'Contact Client '.($this->contactClient ? $this->contactClient->getId() : 'NA').': '.$message
        );
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
    public function getLogsJSON()
    {
        return json_encode(
            UTF8Helper::fixUTF8($this->getLogs()),
            JSON_PRETTY_PRINT
        );
    }

    /**
     * @param        $integrationName
     * @param        $integrationEntity
     * @param        $integrationEntityId
     * @param null   $entity
     * @param string $internalEntityType
     * @param null   $internalData
     *
     * @return IntegrationEntity
     *
     * @throws Exception
     */
    private function saveSyncedData(
        $integrationName,
        $integrationEntity,
        $integrationEntityId,
        $entity = null,
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
        if ($entity) {
            $newIntegrationEntity->setInternalEntityId($entity->getId());
        }
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
                $overrides             = [];
                foreach ($contactClientEntities as $contactClientEntity) {
                    if ($contactClientEntity->getIsPublished()) {
                        $id           = $contactClientEntity->getId();
                        $clients[$id] = $contactClientEntity->getName();

                        // Get overridable fields from the payload of the type needed.
                        try {
                            $overrides[$id] = $this->getPayloadModel($contactClientEntity)
                                ->getOverrides();
                        } catch (\Exception $e) {
                            if ($this->logger) {
                                $this->logger->error($e->getMessage());
                            }
                            $clients[$id] .= ' ('.$e->getMessage().')';
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
                        'choice_attr' => function ($val, $key, $index) use ($overrides) {
                            $results = [];
                            // adds a class like attending_yes, attending_no, etc
                            if ($val && isset($overrides[$val])) {
                                $results['class'] = 'contact-client-'.$val;
                                // Change format to match json schema.
                                $results['data-overridable-fields'] = json_encode($overrides[$val]);
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
                            'class'   => 'btn btn-default',
                            'tooltip' => 'mautic.contactclient.integration.overrides.tooltip',
                            // Shim to get our javascript over the border and into Integration land.
                            'onclick' => "if (typeof Mautic.contactclientIntegrationPre === 'undefined') {".
                                "    mQuery.getScript(mauticBasePath + '/' + mauticAssetPrefix + 'plugins/MauticContactClientBundle/Assets/build/contactclient.min.js', function(){".
                                '        Mautic.contactclientIntegrationPre();'.
                                '    });'.
                                '} else {'.
                                '    Mautic.contactclientIntegrationPre();'.
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

        if ('features' == $formArea) {
            $builder->add(
                'email_from',
                'text',
                [
                    'label'    => $this->translator->trans('mautic.contactclient.email.from'),
                    'data'     => !isset($data['email_from']) ? '' : $data['email_from'],
                    'attr'     => [
                        'tooltip' => $this->translator->trans('mautic.contactclient.email.from.tooltip'),
                    ],
                    'required' => false,
                ]
            );

            $builder->add(
                'success_message',
                'textarea',
                [
                    'label'    => $this->translator->trans('mautic.contactclient.email.success_message'),
                    'data'     => !isset($data['success_message']) ? '' : $data['success_message'],
                    'attr'     => [
                        'tooltip' => $this->translator->trans('mautic.contactclient.email.success_message.tooltip'),
                    ],
                    'required' => false,
                ]
            );

            $builder->add(
                'empty_message',
                'textarea',
                [
                    'label'    => $this->translator->trans('mautic.contactclient.email.empty_message'),
                    'data'     => !isset($data['empty_message']) ? '' : $data['empty_message'],
                    'attr'     => [
                        'tooltip' => $this->translator->trans('mautic.contactclient.email.empty_message.tooltip'),
                    ],
                    'required' => false,
                ]
            );

            $builder->add(
                'empty_message',
                'textarea',
                [
                    'label'    => $this->translator->trans('mautic.contactclient.email.empty_message'),
                    'data'     => !isset($data['empty_message']) ? '' : $data['empty_message'],
                    'attr'     => [
                        'tooltip' => $this->translator->trans('mautic.contactclient.email.empty_message.tooltip'),
                    ],
                    'required' => false,
                ]
            );

            $builder->add(
                'footer',
                'textarea',
                [
                    'label'    => $this->translator->trans('mautic.contactclient.email.footer'),
                    'data'     => !isset($data['footer']) ? '' : $data['footer'],
                    'attr'     => [
                        'tooltip' => $this->translator->trans('mautic.contactclient.email.footer.tooltip'),
                    ],
                    'required' => false,
                ]
            );
        }
    }

    /**
     * Deprecated, use getLogsJSON() instead, unless logging to CLI.
     *
     * @return string
     */
    public function getLogsYAML()
    {
        return Yaml::dump($this->getLogs(), 10, 2);
    }

    /**
     * Sends a test and updates the payload as needed.
     *
     * @param        $apiPayload
     * @param string $attributionDefault
     * @param string $attributionSettings
     * @param null   $contactClientId
     *
     * @return bool
     *
     * @throws Exception
     */
    public function sendTestApi(
        &$apiPayload,
        $attributionDefault = '',
        $attributionSettings = '',
        $contactClientId = null
    ) {
        $client = null;
        if ($contactClientId) {
            $clientModel = $this->getContainer()->get('mautic.contactclient.model.contactclient');
            /** @var ContactClient $client */
            $client = $clientModel->getEntity($contactClientId);
        }
        if (!$client) {
            $client = new ContactClient();
        }
        $client->setAPIPayload($apiPayload);
        if ($attributionSettings) {
            $client->setAttributionSettings($attributionSettings);
        }
        if ($attributionDefault) {
            $client->setAttributionDefault($attributionDefault);
        }
        $contact = new Contact();

        $this->sendContact($client, $contact, true);
        // Get the API Payload in case there were updates.
        $apiPayload = $this->contactClient->getAPIPayload();

        return $this->valid;
    }

    /**
     * Sends a test file with one row.
     *
     * @param        $filePayload
     * @param string $attributionDefault
     * @param string $attributionSettings
     *
     * @return bool
     *
     * @throws ContactClientException
     */
    public function sendTestFile(&$filePayload, $attributionDefault = '', $attributionSettings = '')
    {
        $client = new ContactClient();
        $client->setFilePayload($filePayload);
        if ($attributionSettings) {
            $client->setAttributionSettings($attributionSettings);
        }
        if ($attributionDefault) {
            $client->setAttributionDefault($attributionDefault);
        }

        $this->test    = true;
        $this->contact = new Contact();

        /** @var FilePayload $payloadModel */
        $payloadModel = $this->getContainer()->get('mautic.contactclient.model.filepayload');
        $payloadModel->reset()
            ->setTest($this->test)
            ->setContact($this->contact)
            ->setContactClient($client)
            ->run('build')
            ->run('send');

        $this->valid = $payloadModel->getValid();

        if ($payloadModel) {
            $this->logs = $payloadModel->getLogsImportant();
            $this->setLogs($payloadModel->getLogs(), 'operations');
        }

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
     * @return bool
     */
    public function getValid()
    {
        return $this->valid;
    }
}
