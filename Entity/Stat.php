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
 */
class Stat
{
    // Used for querying stats
    const TYPE_QUEUED = 'queue';
    const TYPE_DUPLICATE = 'duplicate';
    const TYPE_EXCLUSIVE = 'exclusive';
    const TYPE_FILTER = 'filter';
    const TYPE_LIMITS = 'limits';
    const TYPE_REVENUE = 'revenue';
    const TYPE_SCHEDULE = 'schedule';
    const TYPE_SUCCESS = 'success';
    const TYPE_REJECT = 'reject';
    const TYPE_ERROR = 'error';

    /**
     * @var int
     */
    private $id;

    /**
     * @var ContactClient
     */
    private $contactClient;

    /**
     * @var string
     */
    private $type;

    /**
     * @var \DateTime
     */
    private $dateAdded;

    /**
     * @var
     */
    private $contact;

    /**
     * @param ORM\ClassMetadata $metadata
     */
    public static function loadMetadata(ORM\ClassMetadata $metadata)
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable('contactclient_stats')
            ->setCustomRepositoryClass('MauticPlugin\MauticContactClientBundle\Entity\StatRepository')
            ->addIndex(['type'], 'contactclient_type')
            ->addIndex(['date_added'], 'contactclient_date_added');

        $builder->addId();

        $builder->createManyToOne('contactClient', 'ContactClient')
            ->addJoinColumn('contactclient_id', 'id', false, false, 'CASCADE')
            ->build();

        $builder->addField('type', 'string');

        $builder->addNamedField('dateAdded', 'datetime', 'date_added');

        $builder->addContact(true, 'SET NULL');
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
     * @param Contact $contact
     *
     * @return Stat
     */
    public function setContact(Contact $contact)
    {
        $this->contact = $contact;

        return $this;
    }
}
