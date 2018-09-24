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

use MauticPlugin\MauticContactClientBundle\Entity\ContactClient;
use MauticPlugin\MauticSocialBundle\Entity\Lead;
use Symfony\Component\EventDispatcher\Event;

/**
 * Class ContactClientTimelineEvent.
 */
class ContactClientStatEvent extends Event
{
    /**
     * Campaign Event Id.
     *
     * @var int
     */
    protected $eventId;

    /**
     * Container with all registered events types.
     *
     * @var int
     */
    protected $campaignId;

    /**
     * ContactClient entity for the contactClient the timeline is being generated for.
     *
     * @var ContactClient
     */
    protected $contactClient;

    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * ContactClientStatEvent constructor.
     *
     * @param ContactClient $contactClient
     * @param int           $campaignId
     * @param int           $eventId
     * @param Lead          $contact
     */
    public function __construct(
        ContactClient $contactClient,
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
     * @return Lead
     */
    public function getContact()
    {
        return $this->contact;
    }

    /**
     * @return EntityManager
     */
    public function getEntityManager()
    {
        return $this->em;
    }
}
