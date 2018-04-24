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
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\LeadBundle\Entity\Lead as Contact;
use MauticPlugin\MauticContactClientBundle\Entity\ContactClient;
use MauticPlugin\MauticContactClientBundle\Entity\File;
use MauticPlugin\MauticContactClientBundle\Entity\Stat;
use MauticPlugin\MauticContactClientBundle\Exception\ContactClientException;
use MauticPlugin\MauticContactClientBundle\Helper\JSONHelper;
use MauticPlugin\MauticContactClientBundle\Helper\TokenHelper;

// use MauticPlugin\MauticContactClientBundle\Model\ApiPayloadOperation as ApiOperation;
// use MauticPlugin\MauticContactClientBundle\Services\Transport;

/**
 * Class FilePayload.
 */
class FilePayload
{
    // const SETTING_DEF_ATTEMPTS        = 3;
    //
    // const SETTING_DEF_AUTOUPDATE      = true;
    //
    // const SETTING_DEF_CONNECT_TIMEOUT = 10;
    //
    // const SETTING_DEF_DELAY           = 15;
    //
    // const SETTING_DEF_LIMIT           = 300;
    //
    // const SETTING_DEF_TIMEOUT         = 30;

    /**
     * Simple settings for this integration instance from the payload.
     *
     * @var array
     */
    protected $settings = [
        // 'limit'           => self::SETTING_DEF_LIMIT,
        // 'timeout'         => self::SETTING_DEF_TIMEOUT,
        // 'connect_timeout' => self::SETTING_DEF_CONNECT_TIMEOUT,
        // 'attempts'        => self::SETTING_DEF_ATTEMPTS,
        // 'delay'           => self::SETTING_DEF_DELAY,
        // 'autoUpdate'      => self::SETTING_DEF_AUTOUPDATE,
    ];

    /** @var ContactClient */
    protected $contactClient;

    /** @var Contact */
    protected $contact;

    /** @var array */
    protected $payload;

    /** @var array */
    protected $operations = [];

    /** @var bool */
    protected $test = false;

    /** @var array */
    protected $logs = [];

    /** @var bool */
    protected $valid = true;

    /** @var TokenHelper */
    protected $tokenHelper;

    /** @var array */
    protected $aggregateActualResponses = [];

    /** @var CoreParametersHelper */
    protected $coreParametersHelper;

    /** @var contactClientModel */
    protected $contactClientModel;

    /** @var EntityManager */
    protected $em;

    /** @var File */
    protected $file;

    /**
     * FilePayload constructor.
     *
     * @param contactClientModel   $contactClientModel
     * @param TokenHelper          $tokenHelper
     * @param CoreParametersHelper $coreParametersHelper
     * @param EntityManager        $em
     */
    public function __construct(
        contactClientModel $contactClientModel,
        tokenHelper $tokenHelper,
        CoreParametersHelper $coreParametersHelper,
        EntityManager $em
    ) {
        $this->contactClientModel   = $contactClientModel;
        $this->tokenHelper          = $tokenHelper;
        $this->coreParametersHelper = $coreParametersHelper;
        $this->em                   = $em;
    }

    /**
     * Reset local class variables.
     *
     * @param array $exclusions optional array of local variables to keep current values
     *
     * @return $this
     */
    public function reset($exclusions = ['contactClientModel', 'tokenHelper', 'coreParametersHelper', 'em'])
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
     * @param string $payload
     *
     * @return $this
     *
     * @throws ContactClientException
     */
    private function setPayload(string $payload)
    {
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
                    $value = $settings->{$key};
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
     * Step through all operations defined.
     *
     * @return bool
     * @throws ContactClientException
     */
    public function run()
    {
        $logs = [];
        if (
            !isset($this->payload->methods)
            || !count($this->payload->methods)
            || (
                (!isset($this->payload->methods->email) || $this->payload->methods->email === false)
                && (!isset($this->payload->methods->ftp) || $this->payload->methods->ftp === false)
                && (!isset($this->payload->methods->sftp) || $this->payload->methods->sftp === false)
            )
        ) {
            // There are no file operations to run. Assume manual file queue.
            // throw new ContactClientException(
            //     'There are no file operations to run.',
            //     0,
            //     null,
            //     Stat::TYPE_INVALID,
            //     false
            // );
        }

        // @todo - Determine the next file queued to be sent (if not provided).
        $file = $this->getFile();

        // @todo - If there isn't a file queued up for the next available time, create one.

        // @todo - Add the current contact to the queue.


        // $tokenHelper = $this->getTokenHelper();
        // foreach ($this->payload->methods as $method => &$operation) {
        //     $logs          = [];
        //     $fileOperation = new ApiOperation(
        //         $id + 1, $operation, $transport, $tokenHelper, $this->test, $updatePayload
        //     );
        //     try {
        //         $this->valid = false;
        //         $fileOperation->run();
        //         $this->valid = $fileOperation->getValid();
        //     } catch (\Exception $e) {
        //         // Delay this exception throw...
        //     }
        //     $logs = array_merge($fileOperation->getLogs(), $logs);
        //     $this->setLogs($logs, $id);
        //
        //     if (!$this->valid) {
        //         // Break the chain of operations if an invalid response or exception occurs.
        //         break;
        //     }
        // }

        // Intentionally delayed exception till after logging and payload update.
        if (isset($e)) {
            throw $e;
        }

        return $this->valid;
    }

    /**
     * @param null $contactClientId
     *
     * @return File
     */
    private function getFile($contactClientId = null)
    {
        if (!$this->file && !$contactClientId) {
            $contactClientId = $this->contactClient->getId();
        }
        if (!$this->file && $contactClientId) {
            // Discern the next file entity to use.

            // Get the newest unsent file entity from the repository.
            $fileEntity = $this->getFileRepository()->findOneBy(
                ['contactClient' => $contactClientId, 'status' => File::STATUS_BUILDING],
                ['dateAdded' => 'desc']
            );

            if (!$fileEntity) {
                // There isn't currently a file being built, let's create one.
                $file = new File();

                $file->setContactClient($this->contactClient);
                $this->getFileRepository()->saveEntity($file);
                if ($file->getId()) {
                    $this->file = $file;
                }
            }
        }

        return $this->file;
    }

    /**
     * @return \MauticPlugin\MauticContactClientBundle\Entity\FileRepository
     */
    public function getFileRepository()
    {
        return $this->em->getRepository('MauticContactClientBundle:File');
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
    public function getOverridableFields()
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
     * Override the default field values, if allowed.
     *
     * @param $overrides
     *
     * @return $this
     */
    public function setOverrides($overrides)
    {
        $fieldsOverridden = [];
        if (isset($this->payload->operations)) {
            foreach ($this->payload->operations as $id => &$operation) {
                if (isset($operation->request)) {
                    foreach (['headers', 'body'] as $type) {
                        if (isset($operation->request->{$type})) {
                            foreach ($operation->request->{$type} as &$field) {
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
                    }
                }
            }
        }
        if ($fieldsOverridden) {
            $this->setLogs($fieldsOverridden, 'fieldsOverridden');
        }

        return $this;
    }

    /**
     * @todo - Provide a proof of the file on the receiving side of the most recent operation.
     */
    public function getExternalId()
    {
        return null;
    }

    /**
     * This tokenHelper will be reused throughout the File operations so that they can be context aware.
     */
    private function getTokenHelper()
    {
        // Set the timezones for date/time conversion.
        $tza = $this->coreParametersHelper->getParameter(
            'default_timezone'
        );
        $tza = !empty($tza) ? $tza : date_default_timezone_get();
        $tzb = $this->contactClient->getScheduleTimezone();
        $tzb = !empty($tzb) ? $tzb : date_default_timezone_get();
        $this->tokenHelper->setTimezones($tza, $tzb);

        // Add the Contact as context for field replacement.
        if ($this->contact) {
            $this->tokenHelper->addContextContact($this->contact);
        }

        // Include the payload as additional context.
        if ($this->payload) {
            $this->tokenHelper->addContext(['payload' => $this->payload]);
        }

        return $this->tokenHelper;
    }
}
