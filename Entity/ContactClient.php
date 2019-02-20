<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Digital Media Solutions, LLC
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticContactClientBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Mautic\ApiBundle\Serializer\Driver\ApiMetadataDriver;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;
use Mautic\CoreBundle\Entity\FormEntity;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Mapping\ClassMetadata;

/**
 * Class ContactClient.
 *
 * Entity is used to contain all the rules necessary to create a dynamic integration called a Contact Client.
 */
class ContactClient extends FormEntity
{
    /**
     * The relative path to a file containing the default API payload for new clients.
     * This file should only contain the minimum fields as required properties will be enforced automatically.
     */
    const API_PAYLOAD_DEFAULT_FILE = '/../Assets/json/api_payload_default.json';

    /**
     * The relative path to a file containing the default file payload for new clients.
     * This file should only contain the minimum fields as required properties will be enforced automatically.
     */
    const FILE_PAYLOAD_DEFAULT_FILE = '/../Assets/json/file_payload_default.json';

    /**
     * @var int
     */
    private $id;

    /**
     * @var string
     */
    private $description;

    /**
     * @var string
     */
    private $api_payload;

    /**
     * @var string
     */
    private $file_payload;

    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $category;

    /**
     * @var string
     */
    private $type;

    /**
     * @var string
     */
    private $website;

    /**
     * @var \DateTime
     */
    private $publishUp;

    /**
     * @var \DateTime
     */
    private $publishDown;

    /**
     * @var float
     */
    private $attribution_default;

    /**
     * @var int
     */
    private $attribution_settings;

    /**
     * @var string
     */
    private $duplicate;

    /**
     * @var string
     */
    private $exclusive;

    /**
     * @var bool
     */
    private $exclusive_ignore;

    /**
     * @var int
     */
    private $filter;

    /**
     * @var int
     */
    private $limits;

    /**
     * @var int
     */
    private $schedule_timezone;

    /**
     * @var int
     */
    private $schedule_hours;

    /**
     * @var int
     */
    private $schedule_exclusions;

    /**
     * @var int
     */
    private $schedule_queue;

    /**
     * @var int
     */
    private $limits_queue;

    /**
     * @var mixed
     */
    private $dnc_checks;

    /**
     * ContactClient constructor.
     */
    public function __construct()
    {
        if (null === $this->type) {
            $this->type = 'api';
        }

        if (null === $this->schedule_queue) {
            $this->schedule_queue = false;
        }

        if (null === $this->exclusive_ignore) {
            $this->exclusive_ignore = false;
        }

        if (null === $this->limits_queue) {
            $this->limits_queue = false;
        }

        if (!$this->api_payload) {
            $defaultFile = __DIR__.self::API_PAYLOAD_DEFAULT_FILE;
            if (file_exists($defaultFile)) {
                $this->api_payload = file_get_contents($defaultFile);
            }
        }
        if (!$this->file_payload) {
            $defaultFile = __DIR__.self::FILE_PAYLOAD_DEFAULT_FILE;
            if (file_exists($defaultFile)) {
                $this->file_payload = file_get_contents($defaultFile);
            }
        }
    }

    /**
     * @param ClassMetadata $metadata
     */
    public static function loadValidatorMetadata(ClassMetadata $metadata)
    {
        $metadata->addPropertyConstraint(
            'name',
            new NotBlank(
                [
                    'message' => 'mautic.core.name.required',
                ]
            )
        );

        $metadata->addPropertyConstraint(
            'type',
            new NotBlank(
                ['message' => 'mautic.contactclient.error.select_type']
            )
        );
    }

    /**
     * @param ORM\ClassMetadata $metadata
     */
    public static function loadMetadata(ORM\ClassMetadata $metadata)
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable('contactclient')
            ->setCustomRepositoryClass('MauticPlugin\MauticContactClientBundle\Entity\ContactClientRepository')
            ->addIndex(['contactclient_type'], 'contactclient_type');

        $builder->addIdColumns();

        $builder->addCategory();

        $builder->addNamedField('type', 'string', 'contactclient_type');

        $builder->addNullableField('website', 'string');

        $builder->addPublishDates();

        $builder->createField('attribution_default', 'decimal')
            ->precision(19)
            ->scale(4)
            ->nullable()
            ->build();

        $builder->addNullableField('attribution_settings', 'text');

        $builder->addNullableField('duplicate', 'text');

        $builder->addNullableField('exclusive', 'text');

        $builder->addNullableField('exclusive_ignore', 'boolean');

        $builder->addNullableField('filter', 'text');

        $builder->addNullableField('limits', 'text');

        $builder->addNullableField('limits_queue', 'boolean');

        $builder->addNullableField('schedule_hours', 'text');

        $builder->addNullableField('schedule_timezone', 'string');

        $builder->addNullableField('schedule_exclusions', 'text');

        $builder->addNullableField('schedule_queue', 'boolean');

        $builder->addNullableField('api_payload', 'text');

        $builder->addNullableField('file_payload', 'text');

        $builder->addNullableField('dnc_checks', 'text');
    }

    /**
     * Prepares the metadata for API usage.
     *
     * @param $metadata
     */
    public static function loadApiMetadata(ApiMetadataDriver $metadata)
    {
        $metadata
            ->addListProperties(
                [
                    'id',
                    'name',
                    'category',
                ]
            )
            ->addProperties(
                [
                    'description',
                    'type',
                    'website',
                    'publishUp',
                    'publishDown',
                    'duplicate',
                    'exclusive',
                    'exclusive_ignore',
                    'filter',
                    'limits',
                    'limits_queue',
                    'attribution_default',
                    'attribution_settings',
                    'schedule_timezone',
                    'schedule_hours',
                    'schedule_exclusions',
                    'schedule_queue',
                    'api_payload',
                    'file_payload',
                    'dnc_checks',
                ]
            )
            ->build();
    }

    /**
     * Allow these entities to be cloned like core entities.
     */
    public function __clone()
    {
        $this->id = null;

        parent::__clone();
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return get_object_vars($this);
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return mixed
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param mixed $description
     *
     * @return ContactClient
     */
    public function setDescription($description)
    {
        $this->isChanged('description', $description);

        $this->description = $description;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getAPIPayload()
    {
        return $this->api_payload;
    }

    /**
     * @param mixed $payload
     *
     * @return ContactClient
     */
    public function setAPIPayload($payload)
    {
        $this->isChanged('APIPayload', $payload);

        $this->api_payload = $payload;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getFilePayload()
    {
        return $this->file_payload;
    }

    /**
     * @param mixed $payload
     *
     * @return ContactClient
     */
    public function setFilePayload($payload)
    {
        $this->isChanged('FilePayload', $payload);

        $this->file_payload = $payload;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param mixed $name
     *
     * @return ContactClient
     */
    public function setName($name)
    {
        $this->isChanged('name', $name);

        $this->name = $name;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getCategory()
    {
        return $this->category;
    }

    /**
     * @param mixed $category
     *
     * @return ContactClient
     */
    public function setCategory($category)
    {
        $this->isChanged('category', $category);

        $this->category = $category;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getPublishUp()
    {
        return $this->publishUp;
    }

    /**
     * @param mixed $publishUp
     *
     * @return ContactClient
     */
    public function setPublishUp($publishUp)
    {
        $this->isChanged('publishUp', $publishUp);

        $this->publishUp = $publishUp;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getPublishDown()
    {
        return $this->publishDown;
    }

    /**
     * @param mixed $publishDown
     *
     * @return ContactClient
     */
    public function setPublishDown($publishDown)
    {
        $this->isChanged('publishDown', $publishDown);

        $this->publishDown = $publishDown;

        return $this;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string $type
     *
     * @return ContactClient
     */
    public function setType($type)
    {
        $this->isChanged('type', $type);

        $this->type = $type;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getWebsite()
    {
        return $this->website;
    }

    /**
     * @param mixed $website
     *
     * @return ContactClient
     */
    public function setWebsite($website)
    {
        $this->isChanged('website', $website);

        $this->website = $website;

        return $this;
    }

    /**
     * @return float
     */
    public function getAttributionDefault()
    {
        return floatval($this->attribution_default);
    }

    /**
     * @param $attribution_default
     *
     * @return $this
     */
    public function setAttributionDefault($attribution_default)
    {
        $this->isChanged('attributionDefault', $attribution_default);

        $this->attribution_default = $attribution_default;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getAttributionSettings()
    {
        return $this->attribution_settings;
    }

    /**
     * @param $attribution_settings
     *
     * @return $this
     */
    public function setAttributionSettings($attribution_settings)
    {
        $this->isChanged('attributionSettings', $attribution_settings);

        $this->attribution_settings = $attribution_settings;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getDuplicate()
    {
        return $this->duplicate;
    }

    /**
     * @param mixed $duplicate
     *
     * @return ContactClient
     */
    public function setDuplicate($duplicate)
    {
        $this->isChanged('duplicate', $duplicate);

        $this->duplicate = $duplicate;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getExclusive()
    {
        return $this->exclusive;
    }

    /**
     * @param mixed $exclusive
     *
     * @return ContactClient
     */
    public function setExclusive($exclusive)
    {
        $this->isChanged('exclusive', $exclusive);

        $this->exclusive = $exclusive;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getExclusiveIgnore()
    {
        return $this->exclusive_ignore;
    }

    /**
     * @param mixed $exclusive_ignore
     *
     * @return ContactClient
     */
    public function setExclusiveIgnore($exclusive_ignore)
    {
        $this->isChanged('exclusiveIgnore', $exclusive_ignore);

        $this->exclusive_ignore = $exclusive_ignore;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getFilter()
    {
        return $this->filter;
    }

    /**
     * @param mixed $filter
     *
     * @return ContactClient
     */
    public function setFilter($filter)
    {
        $this->isChanged('filter', $filter);

        $this->filter = $filter;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getLimits()
    {
        return $this->limits;
    }

    /**
     * @param mixed $limits
     *
     * @return ContactClient
     */
    public function setLimits($limits)
    {
        $this->isChanged('limits', $limits);

        $this->limits = $limits;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getLimitsQueue()
    {
        return $this->limits_queue;
    }

    /**
     * @param mixed $limits_queue
     *
     * @return ContactClient
     */
    public function setLimitsQueue($limits_queue)
    {
        $this->isChanged('limitsQueue', $limits_queue);

        $this->limits_queue = $limits_queue;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getScheduleHours()
    {
        return $this->schedule_hours;
    }

    /**
     * @param mixed $schedule_hours
     *
     * @return ContactClient
     */
    public function setScheduleHours($schedule_hours)
    {
        $this->isChanged('scheduleHours', $schedule_hours);

        $this->schedule_hours = $schedule_hours;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getScheduleTimezone()
    {
        return $this->schedule_timezone;
    }

    /**
     * @param mixed $schedule_timezone
     *
     * @return ContactClient
     */
    public function setScheduleTimezone($schedule_timezone)
    {
        $this->isChanged('scheduleTimezone', $schedule_timezone);

        $this->schedule_timezone = $schedule_timezone;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getScheduleExclusions()
    {
        return $this->schedule_exclusions;
    }

    /**
     * @param mixed $schedule_exclusions
     *
     * @return ContactClient
     */
    public function setScheduleExclusions($schedule_exclusions)
    {
        $this->isChanged('scheduleExclusions', $schedule_exclusions);

        $this->schedule_exclusions = $schedule_exclusions;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getScheduleQueue()
    {
        return $this->schedule_queue;
    }

    /**
     * @param mixed $schedule_queue
     *
     * @return ContactClient
     */
    public function setScheduleQueue($schedule_queue)
    {
        $this->isChanged('scheduleQueue', $schedule_queue);

        $this->schedule_queue = $schedule_queue;

        return $this;
    }

    /**
     * @return int
     */
    public function getPermissionUser()
    {
        return $this->getCreatedBy();
    }

    /**
     * @return mixed
     */
    public function getDncChecks()
    {
        return $this->dnc_checks;
    }

    /**
     * @param $dnc_checks
     *
     * @return $this
     */
    public function setDncChecks($dnc_checks)
    {
        if (is_array($dnc_checks)) {
            $dnc_checks = implode(',', $dnc_checks);
        }
        $this->isChanged('dncChecks', $dnc_checks);
        $this->dnc_checks = $dnc_checks;

        return $this;
    }
}
