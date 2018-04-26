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

    /** @var Contact $contact */
    private $contact;

    /** @var integer $campaign */
    private $campaign;

    /** @var integer $campaignEvent */
    private $campaignEvent;

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

        $builder->addContact(true, null);

        $builder->addUniqueConstraint(['file_id', 'contact_id'], 'contactclient_queue');
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
