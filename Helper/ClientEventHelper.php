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
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use MauticPlugin\MauticContactClientBundle\Model\ContactClientModel;
use DateTime;
use Mautic\CoreBundle\Helper\CacheStorageHelper;
use Doctrine\ORM\EntityManager;

/**
 * Class ClientEventHelper.
 */
class ClientEventHelper
{

    /** @var CampaignRepository */
    private $campaignRepo;

    /** @var LeadEventLogRepository */
    private $eventLogRepo;

    /** @var EventRepository */
    private $campaignEventRepo;

    /** @var CoreParametersHelper */
    private $coreParametersHelper;

    public function __construct(
        CampaignRepository $campaignRepo,
        LeadEventLogRepository $eventLogRepo,
        EventRepository $campaignEventRepo,
        EntityManager $em
    ) {
        $this->campaignRepo         = $campaignRepo;
        $this->eventLogRepo         = $eventLogRepo;
        $this->campaignEventRepo    = $campaignEventRepo;
        $this->em = $em;
    }

    public function getAllClientEvents($clientId= NULL)
    {
        $cacheHelper = new CacheStorageHelper(
            CacheStorageHelper::ADAPTOR_DATABASE,
            'contactClientEventsMap',
            $this->em->getConnection(),
            null,
            600
        );
        if (empty($contactClientEventsMap = $cacheHelper->get('contactClientEventsMap', 600)))
        {
            $campaigns      = $this->campaignRepo->getPublishedCampaigns(null, null, true);
            foreach ($campaigns as $campaignId => $campaign) {

                $events = $this->getClientEventsByCampaign($campaignId);
                foreach ($events as $clientId => $event) {
                    foreach ($event as $item) {
                        $contactClientEventsMap[$clientId][$item->getId()] = $item->getName();
                    }
                }
            }
            $cacheHelper->set('contactClientEventsMap', $contactClientEventsMap, 600);
        }

        if($clientId){
            return isset($contactClientEventsMap[$clientId]) ? $contactClientEventsMap[$clientId] : [];
        } else {
            return $contactClientEventsMap;

        }
    }

    public function getClientEventsByCampaign($campaignId)
    {
        $orderedEvents = [];
        $eventAlias    = $this->campaignEventRepo->getTableAlias();
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
                $clientId                                  = $properties['config']['contactclient'];
                $orderedEvents[$clientId][$event->getId()] = $event;
            }
        }

        return $orderedEvents;
    }
}
