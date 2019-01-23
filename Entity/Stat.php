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
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;

/**
 * Class Stat.
 *
 * Entity is used to track statistics around Contact Clients.
 */
class Stat
{
    /**
     * Indicates that an error occurred on the client side. Due to an authentication/authorization failure.
     *
     * Contact sent:    Possibly
     */
    const TYPE_AUTH = 'auth';

    /**
     * Indicates that we intended to send this contact to a client by way of a file, but the requirements for the file,
     * or the contact changed in such a way that the contact is no longer applicable. Attribution change was reversed.
     *
     * Contact sent:    No
     */
    const TYPE_CANCELLED = 'cancelled';

    /**
     * Indicates that the contact was fully accepted by the client, and we passed validation on the response/s.
     *
     * Contact sent:    Yes
     */
    const TYPE_CONVERTED = 'converted';

    /**
     * Indicates that the client has recently accepted a similar contact based on "duplicate rules".
     *
     * Contact sent:    No (was sent previously)
     */
    const TYPE_DUPLICATE = 'duplicate';

    /**
     * Indicates that an error occurred on the client side. Something like a 500 error, empty body, etc.
     *
     * Contact sent:    Possibly
     */
    const TYPE_ERROR = 'error';

    /**
     * Indicates that a contact matching this one was recently accepted by a client with "exclusivity rules".
     * That other client locked a contact matching this one for a period of time, from clients that match this one.
     *
     * Contact sent:    No (was sent previously by another client)
     */
    const TYPE_EXCLUSIVE = 'exclusive';

    /**
     * Indicates required fields were not provided or empty for this contact.
     *
     * Contact sent:    No
     */
    const TYPE_FIELDS = 'fields';

    /**
     * Indicates that this contact did not match the filter rules.
     *
     * Contact sent:    No
     */
    const TYPE_FILTER = 'filtered';

    /**
     * Indicates that our client configuration is invalid. Likely it hasn't been fully configured.
     *
     * Contact sent:    No
     */
    const TYPE_INVALID = 'invalid';

    /**
     * Indicates that a budget/cap matching this contact/scenario was reached.
     *
     * Contact sent:    No
     */
    const TYPE_LIMITS = 'limited';

    /**
     * Indicates that the client did not accept the contact (based on the success definition aka. validation rules).
     *
     * Contact sent:    Yes
     */
    const TYPE_REJECT = 'rejected';

    /**
     * Indicates that the client is currently off-schedule and that this contact can not be queued to be sent later,
     * because the contact would be too old by the point in time of the next open hour.
     *
     * Contact sent:    No
     */
    const TYPE_SCHEDULE = 'schedule';

    /**
     * Indicates that the client is currently off-schedule and that this contact CAN be queued to be sent later.
     *
     * Contact sent:    No
     */
    const TYPE_QUEUE = 'queue';

    /**
     * Indicates that the client was unpublished and so the contact was intentionally not sent and will never be sent.
     *
     * Contact sent:    No
     */
    const TYPE_UNPUBLISHED = 'unpublished';

    /** @var int $id */
    private $id;

    /** @var int contactClientId */
    private $contactClientId = 0;

    /** @var string $type */
    private $type = '';

    /** @var \DateTime $dateAdded */
    private $dateAdded;

    /** @var int $contactId */
    private $contactId;

    /** @var float $attribution */
    private $attribution;

    /** @var string $utmSource */
    private $utmSource;

    /** @var int $campaignId */
    private $campaignId = 0;

    /** @var int $eventId */
    private $eventId = 0;

    /**
     * @param ORM\ClassMetadata $metadata
     */
    public static function loadMetadata(ORM\ClassMetadata $metadata)
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable('contactclient_stats')
            ->setCustomRepositoryClass('MauticPlugin\MauticContactClientBundle\Entity\StatRepository');

        $builder->addId();

        $builder->addNamedField('contactClientId', 'integer', 'contactclient_id', true);

        $builder->addField('type', 'string');

        $builder->addDateAdded();

        $builder->createField('attribution', 'decimal')
            ->precision(19)
            ->scale(4)
            ->nullable()
            ->build();

        $builder->addNamedField('contactId', 'integer', 'contact_id', true);
        $builder->addNamedField('utmSource', 'string', 'utm_source', true);
        $builder->addNamedField('campaignId', 'integer', 'campaign_id', false);
        $builder->addNamedField('eventId', 'integer', 'event_id', false);

        $builder->addIndex(
            ['contactclient_id', 'type', 'date_added'],
            'contactclient_type_date_added'
        );

        $builder->addIndex(
            ['contactclient_id', 'type', 'utm_source', 'date_added'],
            'contactclient_type_utm_source_date_added'
        );

        $builder->addIndex(
            ['contactclient_id', 'utm_source'],
            'contactclient_utm_source'
        );
        $builder->addIndex(
            ['contact_id'],
            'contact_id'
        );

        $builder->addIndex(
            ['contact_id', 'contactclient_id'],
            'contact_id_contactclient_id'
        );

        $builder->addIndex(
            ['campaign_id', 'date_added'],
            'campaign_id_date_added'
        );
    }

    /**
     * @return array
     */
    public static function getAllTypes()
    {
        $result = [];
        try {
            $reflection = new \ReflectionClass(__CLASS__);
            $result     = $reflection->getConstants();
        } catch (\ReflectionException $e) {
        }

        return $result;
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
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param mixed $type
     *
     * @return Stat
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return float
     */
    public function getAttribution()
    {
        return $this->attribution;
    }

    /**
     * @param float $attribution
     *
     * @return $this
     */
    public function setAttribution($attribution)
    {
        $this->attribution = $attribution;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getDateAdded()
    {
        return $this->dateAdded;
    }

    /**
     * @param mixed $dateAdded
     *
     * @return Stat
     */
    public function setDateAdded($dateAdded)
    {
        $this->dateAdded = $dateAdded;

        return $this;
    }

    /**
     * @return int
     */
    public function getContactId()
    {
        return $this->contactId;
    }

    /**
     * @param int $contactId
     *
     * @return Stat
     */
    public function setContactId($contactId)
    {
        $this->contactId = $contactId;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getUtmSource()
    {
        return $this->utmSource;
    }

    /**
     * @param mixed $utmSource
     *
     * @return Stat
     */
    public function setUtmSource($utmSource)
    {
        $this->utmSource = $utmSource;

        return $this;
    }

    /**
     * @return int
     */
    public function getContactClientId()
    {
        return $this->contactClientId;
    }

    /**
     * @param int $contactClientId
     *
     * @return $this
     */
    public function setContactClientId($contactClientId)
    {
        $this->contactClientId = $contactClientId;

        return $this;
    }

    /**
     * @return int
     */
    public function getCampaignId()
    {
        return $this->campaignId;
    }

    /**
     * @param int $campaignId
     *
     * @return Stat
     */
    public function setCampaignId($campaignId)
    {
        $this->campaignId = $campaignId;

        return $this;
    }

    /**
     * @return int
     */
    public function getEventId()
    {
        return $this->eventId;
    }

    /**
     * @param int $eventId
     *
     * @return Stat
     */
    public function setEventId($eventId)
    {
        $this->eventId = $eventId;

        return $this;
    }
}
