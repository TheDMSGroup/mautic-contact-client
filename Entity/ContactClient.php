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
use Mautic\FormBundle\Entity\Form;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Mapping\ClassMetadata;

/**
 * Class ContactClient.
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
    private $revenue_default;

    /**
     * @var int
     */
    private $revenue_settings;

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

        $builder->createField('revenue_default', 'float')
            ->columnDefinition('double DEFAULT NULL')
            ->build();

        $builder->addNullableField('revenue_settings', 'text');

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
                    'revenue_default',
                    'revenue_settings',
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
     * @return mixed
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param mixed $type
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
    public function getRevenueDefault()
    {
        return $this->revenue_default;
    }

    /**
     * @param $revenue_default
     * @return $this
     */
    public function setRevenueDefault($revenue_default)
    {
        $this->isChanged('revenueDefault', $revenue_default);

        $this->revenue_default = $revenue_default;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getRevenueSettings()
    {
        return $this->revenue_settings;
    }

    /**
     * @param $revenue_settings
     * @return $this
     */
    public function setRevenueSettings($revenue_settings)
    {
        $this->isChanged('revenueSettings', $revenue_settings);

        $this->revenue_settings = $revenue_settings;

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

}
