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
 * Class Queue.
 */
class Queue
{
    /** @var int $id */
    private $id;

    /** @var ContactClient $contactClient */
    private $contactClient;

    /** @var File $file */
    private $file;

    /** @var Contact $contact */
    private $contact;

    /**
     * @param ORM\ClassMetadata $metadata
     */
    public static function loadMetadata(ORM\ClassMetadata $metadata)
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable('contactclient_queue')
            ->setCustomRepositoryClass('MauticPlugin\MauticContactClientBundle\Entity\QueueRepository');

        $builder->addId();

        $builder->createManyToOne('contactClient', 'ContactClient')
            ->addJoinColumn('contactclient_id', 'id', true, false, null)
            ->build();

        $builder->createManyToOne('file', 'File')
            ->addJoinColumn('file_id', 'id', true, false, null)
            ->build();

        $builder->addNamedField('contact', 'integer', 'contact_id', true);

        $builder->addUniqueConstraint(['file_id', 'contact_id'], 'contactclient_queue');
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
     * @return $this
     */
    public function setContactClient(ContactClient $contactClient)
    {
        $this->contactClient = $contactClient;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getFile()
    {
        return $this->file;
    }

    /**
     * @param mixed $file
     *
     * @return $this
     */
    public function setFile($file)
    {
        $this->file = $file;

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
     * @return $this
     */
    public function setContact(Contact $contact)
    {
        $this->contact = $contact;

        return $this;
    }
}
