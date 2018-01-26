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
use Mautic\CoreBundle\Entity\IpAddress;
use Mautic\LeadBundle\Entity\Lead as ContactEntity;

/**
 * Class ContactEventLog.
 */
class ContactEventLog
{
    /**
     * @var
     */
    private $id;

    /**
     * @var Event
     */
    private $event;

    /**
     * @var ContactEntity
     */
    private $lead;

    /**
     * @var ContactClient
     */
    private $contactclient;

    /**
     * @var \Mautic\CoreBundle\Entity\IpAddress
     */
    private $ipAddress;

    /**
     * @var \DateTime
     **/
    private $dateTriggered;

    /**
     * @var bool
     */
    private $isScheduled = false;

    /**
     * @var null|\DateTime
     */
    private $triggerDate;

    /**
     * @var bool
     */
    private $systemTriggered = false;

    /**
     * @var array
     */
    private $metadata = [];

    /**
     * @var bool
     */
    private $nonActionPathTaken = false;

    /**
     * @var string
     */
    private $channel;

    /**
     * @var
     */
    private $channelId;

    /**
     * @var
     */
    private $previousScheduledState;

    /**
     * @var int
     */
    private $rotation = 1;

    /**
     * @var FailedContactEventLog
     */
    private $failedLog;

    /**
     * @param ORM\ClassMetadata $metadata
     */
    public static function loadMetadata(ORM\ClassMetadata $metadata)
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable('contactclient_contact_event_log')
            ->setCustomRepositoryClass('Mautic\ContactClientBundle\Entity\ContactEventLogRepository')
            ->addIndex(['is_scheduled', 'lead_id'], 'contactclient_event_upcoming_search')
            ->addIndex(['date_triggered'], 'contactclient_date_triggered')
            ->addIndex(['lead_id', 'contactclient_id', 'rotation'], 'contactclient_leads')
            ->addIndex(['channel', 'channel_id', 'lead_id'], 'contactclient_log_channel')
            ->addUniqueConstraint(['event_id', 'lead_id', 'rotation'], 'contactclient_rotation');

        $builder->addId();

        $builder->createManyToOne('event', 'Event')
            ->inversedBy('log')
            ->addJoinColumn('event_id', 'id', false, false, 'CASCADE')
            ->build();

        $builder->addLead(false, 'CASCADE');

        $builder->addField('rotation', 'integer');

        $builder->createManyToOne('contactclient', 'ContactClient')
            ->addJoinColumn('contactclient_id', 'id')
            ->build();

        $builder->addIpAddress(true);

        $builder->createField('dateTriggered', 'datetime')
            ->columnName('date_triggered')
            ->nullable()
            ->build();

        $builder->createField('isScheduled', 'boolean')
            ->columnName('is_scheduled')
            ->build();

        $builder->createField('triggerDate', 'datetime')
            ->columnName('trigger_date')
            ->nullable()
            ->build();

        $builder->createField('systemTriggered', 'boolean')
            ->columnName('system_triggered')
            ->build();

        $builder->createField('metadata', 'array')
            ->nullable()
            ->build();

        $builder->createField('channel', 'string')
            ->nullable()
            ->build();

        $builder->addNamedField('channelId', 'integer', 'channel_id', true);

        $builder->addNullableField('nonActionPathTaken', 'boolean', 'non_action_path_taken');

        $builder->createOneToOne('failedLog', 'FailedContactEventLog')
            ->mappedBy('log')
            ->fetchExtraLazy()
            ->cascadeAll()
            ->build();
    }

    /**
     * Prepares the metadata for API usage.
     *
     * @param $metadata
     */
    public static function loadApiMetadata(ApiMetadataDriver $metadata)
    {
        $metadata->setGroupPrefix('contactclientEventLog')
            ->addProperties(
                [
                    'ipAddress',
                    'dateTriggered',
                    'isScheduled',
                    'triggerDate',
                    'metadata',
                    'nonActionPathTaken',
                    'channel',
                    'channelId',
                    'rotation',
                ]
            )
            // Add standalone groups
            ->setGroupPrefix('contactclientEventStandaloneLog')
            ->addProperties(
                [
                    'event',
                    'lead',
                    'contactclient',
                    'ipAddress',
                    'dateTriggered',
                    'isScheduled',
                    'triggerDate',
                    'metadata',
                    'nonActionPathTaken',
                    'channel',
                    'channelId',
                    'rotation',
                ]
            )
            ->build();
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return \DateTime
     */
    public function getDateTriggered()
    {
        return $this->dateTriggered;
    }

    /**
     * @param \DateTime|null $dateTriggered
     *
     * @return $this
     */
    public function setDateTriggered(\DateTime $dateTriggered = null)
    {
        $this->dateTriggered = $dateTriggered;
        if (null !== $dateTriggered) {
            $this->setIsScheduled(false);
        }

        return $this;
    }

    /**
     * @return IpAddress
     */
    public function getIpAddress()
    {
        return $this->ipAddress;
    }

    /**
     * @param IpAddress $ipAddress
     *
     * @return $this
     */
    public function setIpAddress(IpAddress $ipAddress)
    {
        $this->ipAddress = $ipAddress;

        return $this;
    }

    /**
     * @return ContactEntity
     */
    public function getLead()
    {
        return $this->lead;
    }

    /**
     * @param ContactEntity $lead
     *
     * @return $this
     */
    public function setLead(ContactEntity $lead)
    {
        $this->lead = $lead;

        return $this;
    }

    /**
     * @return Event
     */
    public function getEvent()
    {
        return $this->event;
    }

    /***
     * @param $event
     *
     * @return $this
     */
    public function setEvent($event)
    {
        $this->event = $event;

        if (!$this->contactclient) {
            $this->setContactClient($event->getContactClient());
        }

        return $this;
    }

    /**
     * @return bool
     */
    public function getIsScheduled()
    {
        return $this->isScheduled;
    }

    /**
     * @param $isScheduled
     *
     * @return $this
     */
    public function setIsScheduled($isScheduled)
    {
        if (null === $this->previousScheduledState) {
            $this->previousScheduledState = $this->isScheduled;
        }

        $this->isScheduled = $isScheduled;

        return $this;
    }

    /**
     * If isScheduled was changed, this will have the previous state.
     *
     * @return mixed
     */
    public function getPreviousScheduledState()
    {
        return $this->previousScheduledState;
    }

    /**
     * @return mixed
     */
    public function getTriggerDate()
    {
        return $this->triggerDate;
    }

    /**
     * @param \DateTime $triggerDate
     *
     * @return $this
     */
    public function setTriggerDate(\DateTime $triggerDate = null)
    {
        $this->triggerDate = $triggerDate;
        $this->setIsScheduled(true);

        return $this;
    }

    /**
     * @return mixed
     */
    public function getContactClient()
    {
        return $this->contactclient;
    }

    /**
     * @param ContactClient $contactclient
     *
     * @return $this
     */
    public function setContactClient(ContactClient $contactclient)
    {
        $this->contactclient = $contactclient;

        return $this;
    }

    /**
     * @return bool
     */
    public function getSystemTriggered()
    {
        return $this->systemTriggered;
    }

    /**
     * @param $systemTriggered
     *
     * @return $this
     */
    public function setSystemTriggered($systemTriggered)
    {
        $this->systemTriggered = $systemTriggered;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getNonActionPathTaken()
    {
        return $this->nonActionPathTaken;
    }

    /**
     * @param $nonActionPathTaken
     *
     * @return $this
     */
    public function setNonActionPathTaken($nonActionPathTaken)
    {
        $this->nonActionPathTaken = $nonActionPathTaken;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getMetadata()
    {
        return $this->metadata;
    }

    /**
     * @param $metadata
     *
     * @return $this
     */
    public function setMetadata($metadata)
    {
        if (!is_array($metadata)) {
            // Assumed output for timeline
            $metadata = ['timeline' => $metadata];
        }

        $this->metadata = $metadata;

        return $this;
    }

    /**
     * @return string
     */
    public function getChannel()
    {
        return $this->channel;
    }

    /**
     * @param string $channel
     *
     * @return ContactEventLog
     */
    public function setChannel($channel)
    {
        $this->channel = $channel;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getChannelId()
    {
        return $this->channelId;
    }

    /**
     * @param mixed $channelId
     *
     * @return ContactEventLog
     */
    public function setChannelId($channelId)
    {
        $this->channelId = $channelId;

        return $this;
    }

    /**
     * @return int
     */
    public function getRotation()
    {
        return $this->rotation;
    }

    /**
     * @param int $rotation
     *
     * @return ContactEventLog
     */
    public function setRotation($rotation)
    {
        $this->rotation = (int)$rotation;

        return $this;
    }

    /**
     * @return bool
     */
    public function isSuccess()
    {
        return !$this->isFailed();
    }

    /**
     * @return bool
     */
    public function isFailed()
    {
        $log = $this->getFailedLog();

        return !empty($log);
    }

    /**
     * @return FailedContactEventLog
     */
    public function getFailedLog()
    {
        return $this->failedLog;
    }

    /**
     * @param FailedContactEventLog|null $log
     * @return $this
     */
    public function setFailedLog(FailedContactEventLog $log = null)
    {
        $this->failedLog = $log;

        return $this;
    }
}
