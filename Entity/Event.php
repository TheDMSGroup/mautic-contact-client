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
 * Class Event
 * @package MauticPlugin\MauticContactClientBundle\Entity
 */
class Event
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var Contact
     */
    protected $contact;

    /**
     * @ORM\Column(name="type", type="string", length=50)
     */
    protected $type;

    /**
     * @var ContactClient
     */
    protected $contactClient;

    /**
     * @var array
     */
    protected $logs;

    /**
     * @var string
     */
    protected $message;

    /**
     * @var string
     */
    protected $integration_entity_id;

    /**
     * @var \DateTime
     */
    private $dateAdded;

    public function __construct()
    {
        $this->dateAdded = new \Datetime();
        $this->type = 'undefined';
    }

    /**
     * @param ORM\ClassMetadata $metadata
     */
    public static function loadMetadata(ORM\ClassMetadata $metadata)
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable('contactclient_events')
            ->setCustomRepositoryClass('MauticPlugin\MauticContactClientBundle\Entity\EventRepository')
            // ->addIndex(['type', 'contactclient_id', 'contact_id'], 'event_contactclient_contact')
            ->addIndex(['type', 'contact_id'], 'type_contact')
            ->addIndex(['date_added'], 'date_added');

        $builder->addId();

        $builder->createManyToOne('contactClient', 'ContactClient')
            ->addJoinColumn('contactclient_id', 'id', false, false, 'CASCADE')
            ->build();

        $builder->createField('type', 'string')
            ->columnName('type')
            ->length(50)
            ->build();

        $builder->createField('message', 'string')
            ->columnName('message')
            ->length(255)
            ->nullable()
            ->build();

        $builder->createField('integration_entity_id', 'string')
            ->columnName('integration_entity_id')
            ->length(255)
            ->nullable()
            ->build();

        $builder->addNamedField('logs', 'text', 'logs');

        $builder->addDateAdded();

        $builder->addContact();
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return get_object_vars($this);
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param $type
     *
     * @return $this
     */
    public function setType($type)
    {
        $this->type = $type;

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
     * @return Event
     */
    public function setContact(Contact $contact)
    {
        $this->contact = $contact;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getContactClient()
    {
        return $this->contactClient;
    }

    /**
     * @param $contactClient
     * @return $this
     */
    public function setContactClient($contactClient)
    {
        $this->contactClient = $contactClient;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getLogs()
    {
        return $this->logs;
    }

    /**
     * @param $logs
     * @return $this
     */
    public function setLogs($logs)
    {
        $this->logs = $logs;

        return $this;
    }

    /**
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * @param $message
     * @return $this
     */
    public function setMessage($message)
    {
        $this->message = $message;

        return $this;
    }

    /**
     * @return string
     */
    public function getIntegrationEntityId()
    {
        return $this->integration_entity_id;
    }

    /**
     * @param $integration_entity_id
     * @return $this
     */
    public function setIntegrationEntityId($integration_entity_id)
    {
        $this->integration_entity_id = $integration_entity_id;

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
     * @param $dateAdded
     * @return $this
     */
    public function setDateAdded($dateAdded)
    {
        $this->dateAdded = $dateAdded;

        return $this;
    }


}
