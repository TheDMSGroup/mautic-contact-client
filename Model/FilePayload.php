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

use Doctrine\ORM\EntityManager;
use FOS\RestBundle\Util\Codes;
use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Model\EventModel;
use Mautic\CoreBundle\Model\FormModel;
use Mautic\LeadBundle\Entity\Lead as Contact;
use Mautic\LeadBundle\Model\LeadModel as ContactModel;
use MauticPlugin\MauticContactClientBundle\Entity\ContactClient;
use MauticPlugin\MauticContactClientBundle\Entity\File;
use MauticPlugin\MauticContactClientBundle\Entity\Queue;
use MauticPlugin\MauticContactClientBundle\Entity\Stat;
use MauticPlugin\MauticContactClientBundle\Exception\ContactClientException;
use MauticPlugin\MauticContactClientBundle\Helper\JSONHelper;
use MauticPlugin\MauticContactClientBundle\Helper\TokenHelper;

/**
 * Class FilePayload.
 */
class FilePayload
{
    /**
     * Simple settings for this integration instance from the payload.
     *
     * @var array
     */
    protected $settings = [
        'name'        => 'Contacts-{{date}}-{{time}}-{{count}}{{test}}',
        'compression' => 'zip',
        'headers'     => true,
        'rate'        => 1,
        'required'    => true,
        'type'        => [
            'key' => 'csv',
        ],
    ];

    /** @var array Standard CSV settings that can be overridden by the setting 'type' if it comes in as an array. */
    protected $csvSettings = [
        'delimiter' => ',',
        'enclosure' => '"',
        'escape'    => '\\',
        'terminate' => "\n",
        'null'      => null,
    ];

    /** @var ContactClient */
    protected $contactClient;

    /** @var Contact */
    protected $contact;

    /** @var object */
    protected $payload;

    /** @var array */
    protected $operations = [];

    /** @var bool */
    protected $test = false;

    /** @var array */
    protected $logs = [];

    /** @var bool */
    protected $valid = false;

    /** @var TokenHelper */
    protected $tokenHelper;

    /** @var array */
    protected $aggregateActualResponses = [];

    /** @var contactClientModel */
    protected $contactClientModel;

    /** @var EntityManager */
    protected $em;

    /** @var File */
    protected $file;

    /** @var FormModel */
    protected $formModel;

    /** @var Queue */
    protected $queue;

    /** @var array */
    protected $event;

    /** @var Campaign */
    protected $campaign;

    /** @var EventModel */
    protected $eventModel;

    /** @var Contact */
    protected $contactModel;

    /**
     * FilePayload constructor.
     *
     * @param contactClientModel $contactClientModel
     * @param TokenHelper        $tokenHelper
     * @param EntityManager      $em
     * @param FormModel          $formModel
     * @param EventModel         $eventModel
     */
    public function __construct(
        contactClientModel $contactClientModel,
        tokenHelper $tokenHelper,
        EntityManager $em,
        FormModel $formModel,
        EventModel $eventModel,
        ContactModel $contactModel
    ) {
        $this->contactClientModel = $contactClientModel;
        $this->tokenHelper        = $tokenHelper;
        $this->em                 = $em;
        $this->formModel          = $formModel;
        $this->eventModel         = $eventModel;
        $this->contactModel       = $contactModel;
    }

    /**
     * @return bool
     */
    public function getValid()
    {
        return $this->valid;
    }

    /**
     * Reset local class variables.
     *
     * @param array $exclusions optional array of local variables to keep current values
     *
     * @return $this
     */
    public function reset(
        $exclusions = [
            'contactClientModel',
            'tokenHelper',
            'em',
            'formModel',
            'eventModel',
        ]
    ) {
        foreach (array_diff_key(
                     get_class_vars(get_class($this)),
                     array_flip($exclusions)
                 ) as $name => $default) {
            $this->$name = $default;
        }

        return $this;
    }

    /**
     * @return Contact
     */
    public function getContact()
    {
        return $this->contact;
    }

    /**
     * @param Contact $contact
     *
     * @return $this
     */
    public function setContact(Contact $contact)
    {
        $this->contact = $contact;

        return $this;
    }

    /**
     * @return ContactClient
     */
    public function getContactClient()
    {
        return $this->contactClient;
    }

    /**
     * @param ContactClient $contactClient
     *
     * @return $this
     *
     * @throws ContactClientException
     */
    public function setContactClient(ContactClient $contactClient)
    {
        $this->contactClient = $contactClient;
        $this->setPayload($this->contactClient->getFilePayload());

        return $this;
    }

    /**
     * Take the stored JSON string and parse for use.
     *
     * @param string|null $payload
     *
     * @return $this
     *
     * @throws ContactClientException
     */
    private function setPayload(string $payload = null)
    {
        if (!$payload && $this->contactClient) {
            $payload = $this->contactClient->getFilePayload();
        }
        if (!$payload) {
            throw new ContactClientException(
                'File instructions not set.',
                0,
                null,
                Stat::TYPE_INVALID,
                false,
                null,
                $this->contactClient ? $this->contactClient->toArray() : null
            );
        }

        $jsonHelper = new JSONHelper();
        try {
            $this->payload = $jsonHelper->decodeObject($payload, 'Payload');
        } catch (\Exception $e) {
            throw new ContactClientException(
                'File instructions malformed.',
                0,
                $e,
                Stat::TYPE_INVALID,
                false,
                null,
                $this->contactClient ? $this->contactClient->toArray() : null
            );
        }
        $this->setSettings(!empty($this->payload->settings) ? $this->payload->settings : null);

        return $this;
    }

    /**
     * Retrieve File settings from the payload to override our defaults.
     *
     * @param object $settings
     */
    private function setSettings($settings)
    {
        if ($settings) {
            foreach ($this->settings as $key => &$value) {
                if (!empty($settings->{$key}) && $settings->{$key}) {
                    if (is_object($settings->{$key})) {
                        $value = json_decode(json_encode($settings->{$key}, JSON_FORCE_OBJECT), true);
                    } else {
                        $value = $settings->{$key};
                    }
                }
            }
        }
    }

    /**
     * @return bool
     */
    public function getTest()
    {
        return $this->test;
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
     * Add a contact to a queue for a file, generating the file entry if needed.
     *
     * @return $this
     *
     * @throws ContactClientException
     */
    public function run()
    {
        // @todo - Discern next appropriate file time based on the schedule and file rate.
        $this->getFieldValues();
        $this->determineFileToBuild();
        $this->updateFileSettings();
        $this->saveFile();
        $this->addContactToQueue();

        return $this;
    }

    /**
     * Gets the token rendered field values (evaluating required fields in the process).
     *
     * @throws ContactClientException
     */
    private function getFieldValues()
    {
        $requestFields = [];
        if (!empty($this->payload->body) && !is_string($this->payload->body)) {
            $this->tokenHelper->newSession($this->contactClient, $this->contact, $this->payload);
            $requestFields = $this->fieldValues($this->payload->body);
        }

        return $requestFields;
    }

    /**
     * Tokenize/parse fields from the file Payload for transit.
     *
     * @todo - This method also exists in the other payload type with a minor difference
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
            $key = isset($field->key) ? trim($field->key) : null;
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
                    $value = $this->tokenHelper->render($field->{$valueSource});
                    if (!empty($value)) {
                        break;
                    }
                }
            }
            if (empty($value) && 0 !== $value) {
                // The field value is empty.
                if (true === (isset($field->required) ? $field->required : false)) {
                    // The field is required. Abort.
                    throw new ContactClientException(
                        'A required file request field is missing or empty: '.$field->key,
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
     * @param bool $create
     *
     * @return File|null|object
     *
     * @throws ContactClientException
     */
    public function determineFileToBuild($create = true)
    {
        if (!$this->file && $this->contactClient) {
            // Discern the next file entity to use.

            // Get the newest unsent file entity from the repository.
            $file = $this->getFileRepository()->findOneBy(
                ['contactClient' => $this->contactClient, 'status' => File::STATUS_QUEUEING],
                ['dateAdded' => 'desc']
            );
            if (!$file && $create) {
                // There isn't currently a file being built, let's create one.
                $file = new File();
                $file->setContactClient($this->contactClient);
                $file->setIsPublished(true);
            }

            if ($file) {
                $this->file = $file;
                $this->setLogs($this->file->getId(), 'fileId');
                $this->setLogs($this->file->getStatus(), 'fileStatus');
            }
        }

        if (!$this->file) {
            throw new ContactClientException(
                'There is not a file being built.',
                0,
                null,
                Stat::TYPE_ERROR,
                false
            );
        }

        return $this;
    }

    /**
     * @return \MauticPlugin\MauticContactClientBundle\Entity\FileRepository
     */
    public function getFileRepository()
    {
        return $this->em->getRepository('MauticContactClientBundle:File');
    }

    /**
     * Update fields that are setting-based.
     *
     * @return $this
     */
    public function updateFileSettings()
    {
        if ($this->file) {
            if (!empty($this->settings['name'])) {
                $this->file->setName($this->settings['name']);
            }
            if (!empty($this->settings['type'])) {
                if (!empty($this->settings['type']['key'])) {
                    $this->file->setType($this->settings['type']['key']);
                }
                if (!empty($this->settings['type']['delimiter'])) {
                    $this->file->setCsvDelimiter($this->settings['type']['delimiter']);
                }
                if (!empty($this->settings['type']['enclosure'])) {
                    $this->file->setCsvEnclosure($this->settings['type']['enclosure']);
                }
                if (!empty($this->settings['type']['escape'])) {
                    $this->file->setCsvEscape($this->settings['type']['escape']);
                }
                if (!empty($this->settings['type']['terminate'])) {
                    $this->file->setCsvTerminate($this->settings['type']['terminate']);
                }
                if (!empty($this->settings['type']['null'])) {
                    $this->file->setCsvNull($this->settings['type']['null']);
                }
            }
            if (!empty($this->settings['compression'])) {
                $this->file->setCompression($this->settings['compression']);
            }
            if (!empty($this->settings['headers'])) {
                $this->file->setHeaders((bool) $this->settings['headers']);
            }
        }

        return $this;
    }

    /**
     * Save any file changes using the form model.
     */
    public function saveFile()
    {
        if (!$this->file) {
            return;
        }
        if ($this->file->isNew() || $this->file->getChanges()) {
            $this->formModel->saveEntity($this->file, true);
        }
    }

    /**
     * @return Queue|null|object
     *
     * @throws ContactClientException
     */
    private function addContactToQueue()
    {
        if (
            !$this->queue
            && $this->file
            && $this->contactClient
            && $this->contact
        ) {
            // Check for a pre-existing instance of this contact queued for this file.
            $queue = $this->getQueueRepository()->findOneBy(
                [
                    'contactClient' => $this->contactClient,
                    'file'          => $this->file,
                    'contact'       => $this->contact,
                ]
            );
            if ($queue) {
                throw new ContactClientException(
                    'Skipping duplicate Contact. Already queued for file delivery.',
                    Codes::HTTP_CONFLICT,
                    null,
                    Stat::TYPE_DUPLICATE,
                    false,
                    $queue
                );
            } else {
                /** @var Queue $queue */
                $queue = new Queue();
                $queue->setContactClient($this->contactClient);
                $queue->setContact($this->contact);
                $queue->setFile($this->file);
                // @todo - Add attribution to the queue entity so that it can be reversed on requirement change.
                // $queue->setAttribution();
                if (!empty($this->event['id'])) {
                    $queue->setCampaignEvent($this->event['id']);
                }
                if ($this->campaign) {
                    $queue->setCampaign($this->campaign);
                }
            }
            if ($queue) {
                $this->getQueueRepository()->saveEntity($queue);
                $this->queue = $queue;
                $this->valid = true;
                $this->setLogs($this->queue->getId(), 'queueId');
            }
        }

        if (!$this->queue) {
            throw new ContactClientException(
                'Could not append this contact to the queue.',
                Codes::HTTP_INTERNAL_SERVER_ERROR,
                isset($e) ? $e : null,
                Stat::TYPE_ERROR,
                false
            );
        }

        return $this->queue;
    }

    /**
     * @return \MauticPlugin\MauticContactClientBundle\Entity\QueueRepository
     */
    public function getQueueRepository()
    {
        return $this->em->getRepository('MauticContactClientBundle:Queue');
    }

    /**
     * @return File
     */
    public function getFile()
    {
        return $this->file;
    }

    /**
     * @param $file
     *
     * @return $this
     */
    public function setFile($file)
    {
        $this->file = $file;

        return $this;
    }

    /**
     * @return Campaign
     */
    public function getCampaign()
    {
        return $this->campaign;
    }

    /**
     * @param Campaign|null $campaign
     *
     * @return $this
     */
    public function setCampaign(Campaign $campaign = null)
    {
        $this->setLogs($campaign->getId(), 'campaignId');
        $this->campaign = $campaign;

        return $this;
    }

    /**
     * Build out the original temp file.
     *
     * @return $this
     *
     * @throws ContactClientException
     */
    public function buildFile()
    {
        if (!$this->contactClient || !$this->file) {
            return $this;
        }
        // @todo - Get batches of queue entries.

        $filter['contactClient'] = $this->contactClient;
        $filter['file']          = $this->file;

        $queues = $this->getQueueRepository()->getEntities(
            [
                'filter'        => $filter,
                'iterator_mode' => true,
            ],
            ['id' => 'ASC']
        );

        $queuesProcessed = [];
        while (false !== ($queue = $queues->next())) {
            $queue = reset($queue);

            try {
                // Get the full Contact entity.
                $contactId     = $queue->getContact();
                $this->contact = $this->contactModel->getEntity($contactId);
                if (!$this->contact) {
                    throw new ContactClientException(
                        'This contact appears to have been deleted: '.$contactId,
                        0,
                        null,
                        Codes::HTTP_GONE
                    );
                }

                // Apply the event for configuration/overrides.
                $eventId = $queue->getCampaignEvent();
                if ($eventId) {
                    $event = $this->eventModel->getEntity((int) $eventId);
                    if ($event) {
                        $event = $event->getProperties();
                        // This will apply overrides.
                        $this->setEvent($event);
                    }
                }

                // Get tokenized field values (will include overrides).
                $fieldValues = $this->getFieldValues();

                // @todo - If not open, open the temp file for output based on the file type in question.

                // @todo - Append the open file stream with a new line based on the file type in question.
            } catch (\Exception $e) {
                // @todo - Reverse attribution for this contact.
            }

            // @todo - drop the queue event once we're done with it?

            $queuesProcessed[] = $queue->getId();

            $this->em->detach($this->contact);
            $this->em->detach($queue);
            unset($queue, $contact);
        }
        unset($queues);

        // @todo - Close any open file.

        // @todo - Drop the IDs in $queuesProcessed from the queue table with a single delete.

        return $this;
    }

    /**
     * @param array $event
     *
     * @return $this
     *
     * @throws \Exception
     */
    public function setEvent($event = [])
    {
        if (!empty($event['id'])) {
            $this->setLogs($event['id'], 'campaignEventId');
        }
        $overrides = [];
        if (!empty($event['contactclient_overrides'])) {
            // Flatten overrides to key-value pairs.
            $jsonHelper = new JSONHelper();
            $array      = $jsonHelper->decodeArray($event['contactclient_overrides'], 'Overrides');
            if ($array) {
                foreach ($array as $field) {
                    if (!empty($field->key) && !empty($field->value)) {
                        $overrides[$field->key] = $field->value;
                    }
                }
            }
            if ($overrides) {
                $this->setOverrides($overrides);
            }
        }
        $this->event = $event;

        return $this;
    }

    /**
     * Override the default field values, if allowed.
     *
     * @param $overrides
     *
     * @return $this
     */
    public function setOverrides($overrides)
    {
        $fieldsOverridden = [];
        if (isset($this->payload->body)) {
            foreach ($this->payload->body as &$field) {
                if (
                    isset($field->overridable)
                    && true === $field->overridable
                    && isset($field->key)
                    && isset($overrides[$field->key])
                    && null !== $overrides[$field->key]
                ) {
                    $field->value                  = $overrides[$field->key];
                    $fieldsOverridden[$field->key] = $overrides[$field->key];
                }
            }
        }
        if ($fieldsOverridden) {
            $this->setLogs($fieldsOverridden, 'fieldsOverridden');
        }

        return $this;
    }

    /**
     * By cron/cli send appropriate files for this time.
     */
    public function sendFile()
    {
        // @todo - Upload the file to S3 (if configured).

        // @todo - Update the file record.

        // @todo - Discern which file opperations are needed at this time.
        if (isset($this->payload->operations)) {
            foreach ($this->payload->operations as $type => $operation) {
                if ($operation) {
                    switch ($type) {
                        case 'email':
                            // @todo - File payload processing for email.
                            break;

                        case 'ftp':
                            // @todo - File payload processing for email.
                            break;

                        case 'sftp':
                            // @todo - File payload processing for email.
                            break;
                    }
                }
            }
        }

        return $this;
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

    /**
     * Retrieve from the payload all outgoing fields that are set to overridable.
     *
     * @return array
     */
    public function getOverrides()
    {
        $result = [];
        if (isset($this->payload->operations)) {
            foreach ($this->payload->operations as $id => $operation) {
                if (isset($operation->request)) {
                    foreach (['headers', 'body'] as $type) {
                        if (isset($operation->request->{$type})) {
                            foreach ($operation->request->{$type} as $field) {
                                if (isset($field->overridable) && true === $field->overridable) {
                                    // Remove irrelevant data, since this result will need to be light-weight.
                                    unset($field->default_value);
                                    unset($field->test_value);
                                    unset($field->test_only);
                                    unset($field->overridable);
                                    unset($field->required);
                                    $result[(string) $field->key] = $field;
                                }
                            }
                        }
                    }
                }
            }
        }

        return $result;
    }

    /**
     * @todo - Provide a proof of the file on the receiving side of the most recent operation.
     */
    public function getExternalId()
    {
        return null;
    }
}
