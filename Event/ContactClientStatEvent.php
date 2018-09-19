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
     * ContactClientStatEvent constructor.
     *
     * @param ContactClient $contactClient
     * @param int           $campaignId
     * @param int           $eventId
     */
    public function __construct(
        ContactClient $contactClient,
        $campaignId,
        $eventId
    ) {
        $this->contactClient = $contactClient;
        $this->campaignId    = $campaignId;
        $this->eventId       = $eventId;
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
}
