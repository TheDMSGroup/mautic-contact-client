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

use Aws\S3\S3Client;
use Doctrine\ORM\EntityManager;
use Exporter\Writer\CsvWriter;
use Exporter\Writer\XlsWriter;
use FOS\RestBundle\Util\Codes;
use League\Flysystem\Adapter\Ftp as FtpAdapter;
use League\Flysystem\AwsS3v2\AwsS3Adapter;
use League\Flysystem\Filesystem;
use League\Flysystem\Sftp\SftpAdapter;
use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Model\EventModel;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\PathsHelper;
use Mautic\CoreBundle\Model\FormModel;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Helper\MailHelper;
use Mautic\LeadBundle\Entity\Lead as Contact;
use Mautic\LeadBundle\Model\LeadModel as ContactModel;
use Mautic\PluginBundle\Entity\Integration;
use Mautic\PluginBundle\Entity\IntegrationRepository;
use MauticPlugin\MauticContactClientBundle\Entity\ContactClient;
use MauticPlugin\MauticContactClientBundle\Entity\File;
use MauticPlugin\MauticContactClientBundle\Entity\Queue;
use MauticPlugin\MauticContactClientBundle\Entity\Stat;
use MauticPlugin\MauticContactClientBundle\Exception\ContactClientException;
use MauticPlugin\MauticContactClientBundle\Helper\JSONHelper;
use MauticPlugin\MauticContactClientBundle\Helper\TokenHelper;
use Symfony\Component\Filesystem\Filesystem as Files;
use Symfony\Component\Yaml\Yaml;

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
        'name'        => 'Contacts-{{date}}-{{time}}-{{count}}{{test}}.{{extension}}',
        'compression' => 'zip',
        'headers'     => true,
        'rate'        => 1,
        'required'    => true,
        'type'        => [
            'key'       => 'csv',
            'delimiter' => ',',
            'enclosure' => '"',
            'escape'    => '\\',
            'terminate' => "\n",
            'null'      => '',
        ],
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

    /** @var CsvWriter|XlsWriter */
    protected $fileWriter;

    /** @var int */
    protected $count;

    /** @var PathsHelper */
    protected $pathsHelper;

    /** @var CoreParametersHelper */
    protected $coreParametersHelper;

    /** @var Files */
    protected $filesystem;

    /** @var array */
    protected $integrationSettings;

    /** @var MailHelper */
    protected $mailHelper;

    /**
     * FilePayload constructor.
     *
     * @param contactClientModel   $contactClientModel
     * @param TokenHelper          $tokenHelper
     * @param EntityManager        $em
     * @param FormModel            $formModel
     * @param EventModel           $eventModel
     * @param ContactModel         $contactModel
     * @param PathsHelper          $pathsHelper
     * @param CoreParametersHelper $coreParametersHelper
     * @param Files                $files
     */
    public function __construct(
        contactClientModel $contactClientModel,
        tokenHelper $tokenHelper,
        EntityManager $em,
        FormModel $formModel,
        EventModel $eventModel,
        ContactModel $contactModel,
        PathsHelper $pathsHelper,
        CoreParametersHelper $coreParametersHelper,
        Files $files,
        MailHelper $mailHelper
    ) {
        $this->contactClientModel   = $contactClientModel;
        $this->tokenHelper          = $tokenHelper;
        $this->em                   = $em;
        $this->formModel            = $formModel;
        $this->eventModel           = $eventModel;
        $this->contactModel         = $contactModel;
        $this->pathsHelper          = $pathsHelper;
        $this->coreParametersHelper = $coreParametersHelper;
        $this->files                = $files;
        $this->mailHelper           = $mailHelper;
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
            'contactModel',
            'pathsHelper',
            'coreParametersHelper',
            'files',
            'mailHelper',
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
                        $value = array_merge(
                            $value,
                            json_decode(json_encode($settings->{$key}, JSON_FORCE_OBJECT), true)
                        );
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
        $this->fileEntitySelect();
        $this->fileEntityRefreshSettings();
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
    public function fileEntitySelect($create = true)
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
                $this->em->persist($file);
            }

            if ($file) {
                $this->file = $file;
                $this->setLogs($this->file->getId(), 'fileId');
                $this->setLogs($this->file->getStatus(), 'fileStatus');
            }
        }

        if (!$this->file && $create) {
            throw new ContactClientException(
                'There is not a file being built for this client.',
                0,
                null,
                Stat::TYPE_INVALID,
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
     * Update fields on the file entity based on latest data.
     *
     * @return $this
     */
    public function fileEntityRefreshSettings()
    {
        if ($this->file) {
            // Update settings from the Contact Client entity.
            if (!empty($this->settings['name']) && !$this->file->getLocation()) {
                // Preliminary name containing tokens.
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
            if ($this->count) {
                $this->file->setCount($this->count);
                $this->setLogs($this->count, 'count');
            }
        }

        return $this;
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
                    'contact'       => (int) $this->contact->getId(),
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
                if ($queue) {
                    $this->getQueueRepository()->saveEntity($queue);
                    $this->queue = $queue;
                    $this->valid = true;
                    $this->setLogs($this->queue->getId(), 'queueId');
                }
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
        if ($campaign) {
            $this->setLogs($campaign->getId(), 'campaignId');
            $this->campaign = $campaign;
        }

        return $this;
    }

    /**
     * Build out the original temp file.
     *
     * @return $this
     *
     * @throws ContactClientException
     */
    public function fileBuild()
    {
        if (!$this->contactClient || !$this->file) {
            return $this;
        }

        $filter['contactClient'] = $this->contactClient;
        $filter['file']          = $this->file;

        $queueEntries = $this->getQueueRepository()->getEntities(
            [
                'filter'        => $filter,
                'iterator_mode' => true,
            ],
            ['id' => 'ASC']
        );

        $this->count           = 0;
        $queueEntriesProcessed = [];
        while (false !== ($queueEntry = $queueEntries->next())) {
            $queueEntry    = reset($queueEntry);
            $this->contact = null;
            try {
                // Get the full Contact entity.
                $contactId = $queueEntry->getContact();
                if ($contactId) {
                    $this->contact = $this->contactModel->getEntity($contactId);
                }
                if (!$this->contact) {
                    throw new ContactClientException(
                        'This contact appears to have been deleted: '.$contactId,
                        0,
                        null,
                        Codes::HTTP_GONE
                    );
                }

                // Apply the event for configuration/overrides.
                $eventId = $queueEntry->getCampaignEvent();
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
                $this->fileAddRow($fieldValues);

                $queueEntriesProcessed[] = $queueEntry->getId();
            } catch (\Exception $e) {
                // @todo - Reverse attribution for this contact.
                // @todo - Log a stat of some kind as well.
                $this->setLogs($e->getMessage(), 'notice');
            }

            if ($this->contact) {
                $this->em->detach($this->contact);
            }
            $this->em->detach($queueEntry);
            unset($queueEntry, $contact);
        }
        unset($queueEntries);

        $this->fileClose();
        if ($this->count) {
            $this->fileCompress();
            $this->fileMove();
            $this->fileEntityRefreshSettings();
            $this->fileEntityAddLogs();
            $this->file->setStatus(File::STATUS_READY);
            $this->fileEntitySave();
            $this->getQueueRepository()->deleteEntitiesById($queueEntriesProcessed);
        } else {
            $this->setLogs('No applicable contacts were found, so no file was generated.', 'notice');
        }

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
                    if (!empty($field->key) && !empty($field->value) && (empty($field->enabled) || true === $field->enabled)) {
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
     * @param array $fieldValues
     *
     * @return $this
     */
    private function fileAddRow($fieldValues = [])
    {
        if ($fieldValues) {
            $this->getFileWriter()->write($fieldValues);
            if (0 === $this->count) {
                // Indicate to other processes that this file is being compiled.
                $this->file->setStatus(File::STATUS_BUILDING);
                $this->fileEntitySave();
            }
            ++$this->count;
        }

        return $this;
    }

    /**
     * @return CsvWriter|XlsWriter
     */
    private function getFileWriter()
    {
        if (!$this->fileWriter) {
            switch ($this->settings['type']['key']) {
                case 'csv':
                case 'csvCustom':
                    // This writer doesn't always support Null/Terminate
                    try {
                        $paramCount = null;
                        $reflection = new \ReflectionMethod('CsvWriter::__construct');
                        $paramCount = $reflection->getNumberOfParameters();
                    } catch (\ReflectionException $e) {
                    }
                    switch ($paramCount) {
                        // Future-proofing support for custom terminators.
                        // https://github.com/sonata-project/exporter/pull/220
                        case 6:
                            /* @var \Exporter\Writer\CsvWriter fileWriter */
                            $this->fileWriter = new CsvWriter(
                                $this->fileGenerateTmp(),
                                $this->settings['type']['delimiter'],
                                $this->settings['type']['enclosure'],
                                $this->settings['type']['escape'],
                                $this->settings['headers'],
                                $this->settings['type']['terminate']
                            );
                            break;

                        // All previous versions.
                        default:
                            /* @var \Exporter\Writer\CsvWriter fileWriter */
                            $this->fileWriter = new CsvWriter(
                                $this->fileGenerateTmp(),
                                $this->settings['type']['delimiter'],
                                $this->settings['type']['enclosure'],
                                $this->settings['type']['escape'],
                                $this->settings['headers']
                            );
                            break;
                    }
                    break;

                case 'Excel2007':
                    /* @var \Exporter\Writer\XlsWriter fileWriter */
                    $this->fileWriter = new XlsWriter(
                        $this->fileGenerateTmp(),
                        $this->settings['headers']
                    );
                    break;
            }
            // Go ahead and open the file for writing.
            $this->fileWriter->open();
        }

        return $this->fileWriter;
    }

    /**
     * Generates a temporary path for file generation.
     *
     * @param string $compression
     *
     * @return null|string
     */
    private function fileGenerateTmp($compression = null)
    {
        $fileTmp = null;
        while (true) {
            $fileTmpName = uniqid($this->getFileName($compression), true);
            $fileTmp     = sys_get_temp_dir().'/'.$fileTmpName;
            if (!file_exists($fileTmp)) {
                if (!$compression) {
                    $this->file->setTmp($fileTmp);
                    $this->setLogs($fileTmp, 'fileTmp');
                }
                break;
            }
        }

        return $fileTmp;
    }

    /**
     * Discern the desired output file name for a new file.
     *
     * @param string $compression
     *
     * @return string
     */
    private function getFileName($compression = null)
    {
        $this->tokenHelper->newSession($this->contactClient, null, $this->payload);
        $type      = $this->file->getType();
        $extension = $type.($compression ? '.'.$compression : '');
        $this->tokenHelper->addContext(
            [
                'count'       => $this->count,
                'test'        => $this->test ? '.test' : '',
                'date'        => $this->tokenHelper->getDateFormatHelper()->format(new \DateTime(), 'Y-m-d', false),
                'time'        => $this->tokenHelper->getDateFormatHelper()->format(new \DateTime(), 'H-i-s', false),
                'type'        => $type,
                'compression' => $compression,
                'extension'   => $extension,
            ]
        );

        // Update the name of the output file to represent latest token data.
        return trim($this->tokenHelper->render($this->settings['name']));
    }

    /**
     * Save any file changes using the form model.
     */
    public function fileEntitySave()
    {
        if (!$this->file) {
            return;
        }
        if ($this->file->isNew() || $this->file->getChanges()) {
            $this->formModel->saveEntity($this->file, true);
        }
    }

    /**
     * @return $this
     */
    private function fileClose()
    {
        if ($this->fileWriter) {
            $this->fileWriter->close();
        }

        return $this;
    }

    /**
     * Perform compression on the temp file.
     *
     * @return $this
     *
     * @throws ContactClientException
     */
    private function fileCompress()
    {
        if ($this->file && $this->file->getTmp()) {
            $compression = $this->file->getCompression();
            if ($compression) {
                // Discern new tmp file name (with compression applied).
                $target   = $this->fileGenerateTmp($compression);
                $fileName = $this->getFileName();
                try {
                    switch ($compression) {
                        case 'tar.gz':
                            $phar = new \PharData($target);
                            $phar->addFile($this->file->getTmp(), $fileName);
                            $phar->compress(\Phar::GZ, $compression);
                            $target = $phar->getRealPath();
                            break;

                        case 'tar.bz2':
                            $phar = new \PharData($target);
                            $phar->addFile($this->file->getTmp(), $fileName);
                            $phar->compress(\Phar::BZ2, $compression);
                            $target = $phar->getRealPath();
                            break;

                        default:
                        case 'zip':
                            $zip = new \ZipArchive();
                            if (true !== $zip->open($target, \ZipArchive::CREATE)) {
                                throw new \Exception('Zip could not open.');
                            }
                            $zip->addFile($this->file->getTmp(), $fileName);
                            $zip->close();
                            break;
                    }
                    $this->file->setTmp($target);
                    $this->setLogs($target, 'fileCompressed');
                } catch (\Exception $e) {
                    throw new ContactClientException(
                        'Could not create compressed file '.$target,
                        Codes::HTTP_INTERNAL_SERVER_ERROR,
                        $e,
                        Stat::TYPE_ERROR,
                        false
                    );
                }
            } else {
                $this->setLogs(false, 'fileCompressed');
            }
        }

        return $this;
    }

    /**
     * Moves the file out of temp, and locks the hashes.
     *
     * @param bool $overwrite
     *
     * @return $this
     *
     * @throws ContactClientException
     */
    private function fileMove($overwrite = true)
    {
        if (!$this->file->getLocation() || $overwrite) {
            $origin = $this->file->getTmp();

            // This will typically be /media/files
            $uploadDir = realpath($this->coreParametersHelper->getParameter('upload_dir'));
            $fileName  = $this->getFileName(
                $this->file->getCompression()
            );
            $target    = $uploadDir.'/client_payloads/'.$this->contactClient->getId().'/'.$fileName;
            if ($origin && (!file_exists($target) || $overwrite)) {
                $this->files->copy($origin, $target, $overwrite);
                if (file_exists($target)) {
                    // Final file name as it will be seen by the client.
                    $this->file->setName($fileName);
                    $this->setLogs($fileName, 'fileName');

                    $this->file->setLocation($target);
                    $this->setLogs($target, 'fileLocation');

                    $crc32 = hash_file('crc32', $target);
                    $this->file->setCrc32($crc32);
                    $this->setLogs($crc32, 'crc32');

                    $md5 = hash_file('md5', $target);
                    $this->file->setMd5($md5);
                    $this->setLogs($md5, 'md5');

                    $sha1 = hash_file('sha1', $target);
                    $this->file->setSha1($sha1);
                    $this->setLogs($sha1, 'sha1');
                    $this->files->remove($origin);

                    $this->setLogs(filesize($target), 'fileSize');
                } else {
                    throw new ContactClientException(
                        'Could not copy file to local location.',
                        Codes::HTTP_INTERNAL_SERVER_ERROR,
                        null,
                        Stat::TYPE_ERROR,
                        false
                    );
                }
            }
        }

        return $this;
    }

    /**
     * @return $this
     */
    public function fileEntityAddLogs()
    {
        if ($this->logs) {
            // Add our new logs to the entity.
            $logs           = $this->file->getLogs();
            $logs           = $logs ? json_decode($logs, true) : [];
            $iso1601        = $this->tokenHelper->getDateFormatHelper()->format(new \DateTime());
            $logs[$iso1601] = $this->logs;
            $this->file->setLogs(json_encode($logs, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT));
        }

        return $this;
    }

    /**
     * By cron/cli send appropriate files for this time.
     */
    public function fileSend()
    {
        // @todo - Upload the file to S3 (if configured).

        // @todo - Update the file record.

        // @todo - Discern which file opperations are needed at this time.
        if (isset($this->payload->operations)) {
            foreach ($this->payload->operations as $type => $operation) {
                if ($operation) {
                    $now = new \DateTime();
                    $this->setLogs($now->format(\DateTime::ISO8601), $type.'OperationTime');
                    switch ($type) {
                        case 'email':
                            $this->operationEmail($operation);
                            break;

                        case 'ftp':
                            $this->operationFtp($operation);
                            break;

                        case 'sftp':
                            $this->operationSftp($operation);
                            break;

                        case 's3':
                            // $client     = S3Client::factory(
                            //     [
                            //         'credentials' => [
                            //             'key'    => 'your-key',
                            //             'secret' => 'your-secret',
                            //         ],
                            //         'region'      => 'your-region',
                            //         'version'     => 'latest|version',
                            //     ]
                            // );
                            // $adapter    = new AwsS3Adapter($client, 'your-bucket-name', 'optional/path/prefix');
                            // $filesystem = new Filesystem($adapter);
                            break;
                    }
                }
            }
        }

        return $this;
    }

    /**
     * Send an email containing the file to the current client by Email.
     *
     * @param $operation
     *
     * @return bool
     */
    private function operationEmail($operation)
    {
        if ($this->test) {
            $to = !empty($operation->test) ? $operation->test : !empty($operation->to) ? $operation->to : '';
        } else {
            $to = !empty($operation->to) ? $operation->to : '';
        }
        if (!$to) {
            $this->setLogs('Email to address is invalid. No email will be sent.', 'error');

            return false;
        }
        $from  = !empty($operation->from) ? $operation->from : $this->getIntegrationSetting(
            'email_from'
        );
        $email = new Email();
        $email->setSessionId('new_'.hash('sha1', uniqid(mt_rand())));
        $email->setReplyToAddress($from);
        $email->setFromAddress($from);
        $subject = !empty($operation->subject) ? $operation->subject : $this->file->getName();
        $email->setSubject($subject);
        if ($this->file->getCount()) {
            $body = !empty($operation->successMessage) ? $operation->successMessage : $this->getIntegrationSetting(
                'success_message'
            );
        } else {
            $body = !empty($operation->emptyMessage) ? $operation->emptyMessage : $this->getIntegrationSetting(
                'empty_message'
            );
        }
        $body .= PHP_EOL.PHP_EOL.!empty($operation->footer) ? $operation->footer : $this->getIntegrationSetting(
            'footer'
        );
        $email->setContent($body);
        $email->setCustomHtml(htmlentities($body));

        $mailer = $this->mailHelper->getMailer();
        $mailer->setLead(null, true);
        $mailer->setTokens([]);
        $mailer->setTo($to);
        $mailer->setFrom($from);
        $mailer->setEmail($email);
        $mailer->attachFile($this->file->getLocation(), $this->file->getName());

        $this->setLogs($to, 'emailTo');
        $this->setLogs($from, 'emailFrom');
        $this->setLogs($subject, 'emailSubject');
        $this->setLogs($body, 'emailbody');

        // $mailer->setBody($email);
        // $mailer->setEmail($to, false, $emailSettings[$emailId]['slots'], $assetAttachments, (!$saveStat));
        // $mailer->setCc($cc);
        // $mailer->setBcc($bcc);

        return $mailer->send(false, false);;
    }

    /**
     * @param $key
     *
     * @return mixed
     */
    public function getIntegrationSetting($key)
    {
        if (null === $this->integrationSettings) {
            $this->integrationSettings = [];
            /** @var IntegrationRepository $integrationRepo */
            $integrationRepo = $this->em->getRepository('MauticPluginBundle:Integration');
            $integrations    = $integrationRepo->getIntegrations();
            if (!empty($integrations['Client'])) {
                /** @var Integration $integration */
                $integration               = $integrations['Client'];
                $this->integrationSettings = $integration->getFeatureSettings();
            }
        }
        if (isset($this->integrationSettings[$key])) {
            return $this->integrationSettings[$key];
        }
    }

    /**
     * Upload the current client file by FTP.
     *
     * @param $operation
     *
     * @return bool
     */
    private function operationFtp($operation)
    {
        $config = $this->operationFtpConfig($operation);

        $adapter    = new FtpAdapter($config);
        $filesystem = new Filesystem($adapter);

        $written = false;
        if ($stream = fopen($this->file->getLocation(), 'r+')) {
            $this->setLogs($this->file->getLocation(), 'ftpUploading');
            $written = $filesystem->writeStream($this->file->getName(), $stream);
            if (is_resource($stream)) {
                fclose($stream);
            }
            // $written = $written ? $filesystem->has($this->file->getName()) : false;
            $this->setLogs($written, 'ftpConfirmed');
            if (!$written) {
                $this->setLogs('Could not confirm file upload via FTP', 'error');
            }
        } else {
            $this->setLogs('Unable to open file for upload via FTP.', 'error');
        }

        return $written;
    }

    /**
     * Given the operation array, construct configuration array for a FTP/SFTP adaptor.
     *
     * @param $operation
     *
     * @return array|bool
     */
    private function operationFtpConfig($operation)
    {
        $config         = [];
        $config['host'] = isset($operation->host) ? trim($operation->host) : null;
        if (!$config['host']) {
            $this->setLogs('FTP Host is needed.', 'error');

            return false;
        } else {
            $this->setLogs($config['host'], 'host');
        }
        $config['username'] = isset($operation->user) ? trim($operation->user) : null;
        if (!$config['username']) {
            $this->setLogs('FTP User is needed.', 'error');

            return false;
        } else {
            $this->setLogs($config['username'], 'user');
        }
        $config['password'] = isset($operation->pass) ? trim($operation->pass) : null;
        if (!$config['password']) {
            unset($config['password']);
            $this->setLogs('FTP Password is blank. Assuming anonymous access.', 'warning');
        } else {
            $this->setLogs(str_repeat('*', strlen($config['password'])), 'password');
        }

        $config['privateKey'] = isset($operation->privateKey) ? trim($operation->privateKey) : null;
        if (!$config['privateKey']) {
            unset($config['privateKey']);
        } else {
            $this->setLogs(md5($config['privateKey']), 'privateKey');
        }

        $config['port'] = isset($operation->port) ? (int) $operation->port : null;
        if (!$config['port']) {
            unset($config['port']);
        } else {
            $this->setLogs($config['port'], 'port');
        }

        $config['root'] = isset($operation->root) ? trim($operation->root) : null;
        if (!$config['root']) {
            unset($config['root']);
        } else {
            $this->setLogs($config['root'], 'root');
        }

        $config['passive'] = isset($operation->passive) ? (bool) $operation->passive : true;
        $this->setLogs($config['passive'], 'passive');

        $config['timeout'] = isset($operation->timeout) ? (int) $operation->timeout : 90;
        $this->setLogs($config['timeout'], 'timeout');

        $config['ssl'] = isset($operation->ssl) ? (bool) $operation->ssl : false;
        $this->setLogs($config['ssl'], 'ssl');

        return $config;
    }

    /**
     * Upload the current client file by sFTP.
     *
     * @param $operation
     *
     * @return bool
     */
    private function operationSftp($operation)
    {
        $config = $this->operationFtpConfig($operation);

        $adapter    = new SftpAdapter($config);
        $filesystem = new Filesystem($adapter);

        $written = false;
        if ($stream = fopen($this->file->getLocation(), 'r+')) {
            $this->setLogs($this->file->getLocation(), 'sftpUploading');
            $written = $filesystem->writeStream($this->file->getName(), $stream);
            if (is_resource($stream)) {
                fclose($stream);
            }
            // $written = $written ? $filesystem->has($this->file->getName()) : false;
            $this->setLogs($written, 'sftpConfirmed');
            if (!$written) {
                $this->setLogs('Could not confirm file upload via SFTP', 'error');
            }
        } else {
            $this->setLogs('Unable to open file for upload via SFTP.', 'error');
        }

        return $written;

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
