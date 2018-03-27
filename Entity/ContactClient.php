<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc
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
     * @var
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
     * @var int
     */
    private $attribution_default;

    /**
     * @var int
     */
    private $attribution_settings;

    /**
     * @var int
     */
    private $duplicate;

    /**
     * @var int
     */
    private $exclusive;

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
     * ContactClient constructor.
     */
    public function __construct()
    {
        if (!$this->type) {
            $this->type = 'api';
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

        $builder->addNullableField('filter', 'text');

        $builder->addNullableField('limits', 'text');

        $builder->addNullableField('schedule_hours', 'text');

        $builder->addNullableField('schedule_timezone', 'string');

        $builder->addNullableField('schedule_exclusions', 'text');

        $builder->addNullableField('api_payload', 'text');

        $builder->addNullableField('file_payload', 'text');
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
                    'filter',
                    'limits',
                    'attribution_default',
                    'attribution_settings',
                    'schedule_timezone',
                    'schedule_hours',
                    'schedule_exclusions',
                    'api_payload',
                    'file_payload',
                ]
            )
            ->build();
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
     * @return mixed
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
     * Returns the user to be used for permissions.
     *
     * @return User|int
     */
    public function getPermissionUser()
    {
        // @todo add Contact Client ownership? => $this->>getOwner() else getCreatedBy()
        return $this->getCreatedBy();
    }
}
