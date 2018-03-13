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
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;
use Mautic\LeadBundle\Entity\Lead as Contact;

/**
 * Class Stat.
 *
 * Entity is used to track statistics around Contact Clients.
 */
class Stat
{
    // Used for querying stats
    const TYPE_DUPLICATE = 'duplicate';

    const TYPE_ERROR     = 'error';

    const TYPE_EXCLUSIVE = 'exclusive';

    const TYPE_FILTER    = 'filtered';

    const TYPE_LIMITS    = 'limited';

    const TYPE_QUEUED    = 'queued';

    const TYPE_REJECT    = 'rejected';

    const TYPE_SCHEDULE  = 'schedule';

    const TYPE_SUCCESS   = 'success';

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
    private $utmSource;

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

        $builder->addField('utmSource', 'string');

        $builder->addIndex(
            ['contactclient_id', 'type', 'date_added'],
            'contactclient_type_date_added'
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
     * @param Contact|integer $contact
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
}
