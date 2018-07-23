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
use Mautic\LeadBundle\Entity\Lead as Contact;

/**
 * Class Stat.
 *
 * Entity is used to track statistics around Contact Clients.
 */
class Stat
{
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
     * Contact sent:    Yes
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
     * Indicates that the client was unpublished and so the contact was intentionally not sent and will never be sent.
     *
     * Contact sent:    No
     */
    const TYPE_UNPUBLISHED = 'unpublished';

    /** @var int $id */
    private $id;

    /** @var ContactClient $contactClient */
    private $contactClient;

    /** @var string $type */
    private $type;

    /** @var \DateTime $dateAdded */
    private $dateAdded;

    /** @var Contact $contact */
    private $contact;

    /** @var float $attribution */
    private $attribution;

    /** @var string $utmSource */
    private $utm_source;

    /**
     * @param ORM\ClassMetadata $metadata
     */
    public static function loadMetadata(ORM\ClassMetadata $metadata)
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable('contactclient_stats')
            ->setCustomRepositoryClass('MauticPlugin\MauticContactClientBundle\Entity\StatRepository');

        $builder->addId();

        $builder->createManyToOne('contactClient', 'ContactClient')
            ->addJoinColumn('contactclient_id', 'id', true, false, null)
            ->build();

        $builder->addField('type', 'string');

        $builder->addDateAdded();

        $builder->createField('attribution', 'decimal')
            ->precision(19)
            ->scale(4)
            ->nullable()
            ->build();

        $builder->addNamedField('contact', 'integer', 'contact_id', true);

        $builder->addNamedField('utm_source', 'string', 'utm_source', true);

        $builder->addIndex(
            ['contactclient_id', 'type', 'date_added'],
            'contactclient_type_date_added'
        );

        $builder->addIndex(
            ['contactclient_id', 'utm_source'],
            'contactclient_utm_source'
        );
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
    public function getContactClient()
    {
        return $this->contactClient;
    }

    /**
     * @param mixed $contactClient
     *
     * @return Stat
     */
    public function setContactClient($contactClient)
    {
        $this->contactClient = $contactClient;

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
     * @param $attribution
     *
     * @return $this
     */
    public function setAttribution($attribution)
    {
        $this->attribution = $attribution;

        return $this;
    }

    /**
     * @return array
     */
    public function getAllTypes()
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
     * @return Contact
     */
    public function getContact()
    {
        return $this->contact;
    }

    /**
     * @param Contact|int $contact
     *
     * @return Stat
     */
    public function setContact($contact)
    {
        if ($contact instanceof Contact) {
            $contact = $contact->getId();
        }
        $this->contact = $contact;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getUtmSource()
    {
        return $this->utm_source;
    }

    /**
     * @param mixed $utmSource
     *
     * @return Stat
     */
    public function setUtmSource($utmSource)
    {
        $this->utm_source = $utmSource;

        return $this;
    }
}
