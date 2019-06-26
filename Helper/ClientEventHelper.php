<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Digital Media Solutions, LLC
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticContactClientBundle\Helper;

use Mautic\CampaignBundle\Entity\CampaignRepository;
use Mautic\CampaignBundle\Entity\EventRepository;
use Mautic\CampaignBundle\Entity\LeadEventLogRepository;
use MauticPlugin\MauticContactClientBundle\Model\ContactClientModel;
use DateTime;

/**
 * Class ClientEventHelper.
 */
class ClientEventHelper
{

    /** @var CampaignRepository */
    protected $campaignRepo;

    /** @var LeadEventLogRepository */
    protected $eventLogRepo;

    /** @var EventRepository */
    protected $campaignEventRepo;

    public function __construct(
        CampaignRepository $campaignRepo,
        LeadEventLogRepository $eventLogRepo,
        EventRepository $campaignEventRepo
    ) {
        $this->campaignRepo      = $campaignRepo;
        $this->eventLogRepo      = $eventLogRepo;
        $this->campaignEventRepo = $campaignEventRepo;
    }

    public function getScheduledEvents()
    {
        $eventsByClient = $this->getEventsByClient();
        

    }

    protected function getEventsByClient()
    {
        $eventsByClient = [];
        $campaigns = $this->campaignRepo->getPublishedCampaigns(null, null, true);
        foreach ($campaigns as $campaignId => $campaign) {

            $events = $this->getClientEventsByCampaign($campaignId);
            foreach($events as $clientId=>$event)
            {
                foreach($event as $item)
                {
                    $eventsByClient[$clientId][$item->getId()] = $item;
                }
            }
        }
    }

    protected function getClientEventsByCampaign($campaignId)
    {
        $orderedEvents = [];
        $eventAlias = $this->campaignEventRepo->getTableAlias();
        $filter        = [
            'where' => [
                0 =>
                    [
                        'col'  => $eventAlias.'.properties',
                        'expr' => 'like',
                        'val'  => '%s:11:"integration";s:6:"Client"%',
                    ],
            ],
        ];
        $args          = [
            'filter'           => $filter,
            'ignore_paginator' => true,
            'campaign_id'      => $campaignId,
        ];

        $events = $this->campaignEventRepo->getEntities($args);
        foreach ($events as $event) {
            $properties = $event->getProperties();
            if ($properties['integration'] == 'Client' && isset($properties['config']['contactclient'])) {
                $clientId = $properties['config']['contactclient'];
                $orderedEvents[$clientId][$event->getId()] = $event;
            }
        }

        return $orderedEvents;
    }
}
