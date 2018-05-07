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
use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Event\CampaignEvent;
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

    /** @var int $contact */
    private $contact;

    /** @var int $campaign */
    private $campaign;

    /** @var int $campaignEvent */
    private $campaignEvent;

    /** @var float */
    private $attribution;

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

        $builder->addNamedField('campaign', 'integer', 'campaign_id', true);

        $builder->addNamedField('campaignEvent', 'integer', 'campaign_event_id', true);

        $builder->addNamedField('contact', 'integer', 'contact_id', true);

        $builder->createField('attribution', 'decimal')
            ->precision(19)
            ->scale(4)
            ->nullable()
            ->build();

        $builder->addUniqueConstraint(['contactclient_id', 'file_id', 'contact_id'], 'contactclient_queue');
    }

    /**
     * @return float
     */
    public function getAttribution()
    {
        return floatval($this->attribution);
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
     * @return int
     */
    public function getCampaignEvent()
    {
        return $this->campaignEvent;
    }

    /**
     * @param $campaignEvent
     *
     * @return $this
     */
    public function setCampaignEvent($campaignEvent)
    {
        if ($campaignEvent instanceof CampaignEvent) {
            $campaignEvent = $campaignEvent->getId();
        }
        $this->campaignEvent = $campaignEvent;

        return $this;
    }

    /**
     * @return int
     */
    public function getCampaign()
    {
        return $this->campaign;
    }

    /**
     * @param $campaign
     *
     * @return $this
     */
    public function setCampaign($campaign)
    {
        if ($campaign instanceof Campaign) {
            $campaign = $campaign->getId();
        }
        $this->campaign = $campaign;

        return $this;
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
     * @param File $file
     *
     * @return $this
     */
    public function setFile(File $file)
    {
        $this->file = $file;

        return $this;
    }

    /**
     * @return int
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
    public function setContact($contact)
    {
        if ($contact instanceof Contact) {
            $contact = $contact->getId();
        }

        $this->contact = $contact;

        return $this;
    }
}
