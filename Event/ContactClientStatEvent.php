<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Digital Media Solutions, LLC
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticContactClientBundle\Event;

use Doctrine\ORM\EntityManager;
use Exception;
use Mautic\LeadBundle\Entity\Lead as Contact;
use MauticPlugin\MauticContactClientBundle\Entity\ContactClient;
use Symfony\Component\EventDispatcher\Event;

/**
 * Class ContactClientTimelineEvent.
 */
class ContactClientStatEvent extends Event
{
    /** @var int */
    protected $eventId;

    /** @var int */
    protected $campaignId;

    /** @var ContactClient */
    protected $contactClient;

    /** @var int */
    protected $contact;

    /** @var EntityManager */
    protected $em;

    /**
     * ContactClientStatEvent constructor.
     *
     * @param ContactClient $contactClient
     * @param int           $campaignId
     * @param int           $eventId
     * @param Contact       $contact
     */
    public function __construct(
        $contactClient,
        $campaignId,
        $eventId,
        $contact,
        $em
    ) {
        $this->contactClient = $contactClient;
        $this->campaignId    = $campaignId;
        $this->eventId       = $eventId;
        $this->contact       = $contact;
        $this->em            = $em;
    }

    /**
     * @return ContactClient
     */
    public function getContactClient()
    {
        return $this->contactClient;
    }

    /**
     * @return int
     */
    public function getCampaignId()
    {
        return $this->campaignId;
    }

    /**
     * @return int|int
     */
    public function getEventId()
    {
        return $this->eventId;
    }

    /**
     * @return int
     */
    public function getContact()
    {
        return $this->contact;
    }

    /**
     * Shore up EntityManager loading, in case there is a flaw in a plugin or campaign handling.
     *
     * @return EntityManager
     */
    public function getEntityManager()
    {
        try {
            if ($this->em && !$this->em->isOpen()) {
                $this->em = $this->em->create(
                    $this->em->getConnection(),
                    $this->em->getConfiguration(),
                    $this->em->getEventManager()
                );
            }
        } catch (Exception $exception) {
        }

        return $this->em;
    }
}
