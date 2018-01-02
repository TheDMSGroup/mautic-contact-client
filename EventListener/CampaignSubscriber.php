<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticContactClientBundle\EventListener;

use Mautic\CampaignBundle\CampaignEvents;
use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Event\CampaignBuilderEvent;
use Mautic\CampaignBundle\Event\CampaignExecutionEvent;
use Mautic\CampaignBundle\Model\EventModel;
use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\PageBundle\Helper\TrackingHelper;
use MauticPlugin\MauticContactClientBundle\ContactClientEvents;
use MauticPlugin\MauticContactClientBundle\Model\ContactClientModel;
use Symfony\Component\Routing\RouterInterface;

class CampaignSubscriber extends CommonSubscriber
{
    /**
     * @var EventModel
     */
    protected $campaignEventModel;

    /**
     * @var ContactClientModel
     */
    protected $contactclientModel;

    /**
     * @var TrackingHelper
     */
    protected $trackingHelper;

    /**
     * @var RouterInterface
     */
    protected $router;

    /**
     * CampaignSubscriber constructor.
     *
     * @param EventModel      $eventModel
     * @param ContactClientModel      $contactclientModel
     * @param TrackingHelper  $trackingHelper
     * @param RouterInterface $router
     */
    public function __construct(EventModel $eventModel, ContactClientModel $contactclientModel, TrackingHelper $trackingHelper, RouterInterface $router)
    {
        $this->campaignEventModel = $eventModel;
        $this->contactclientModel         = $contactclientModel;
        $this->trackingHelper     = $trackingHelper;
        $this->router             = $router;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            CampaignEvents::CAMPAIGN_ON_BUILD       => ['onCampaignBuild', 0],
            ContactClientEvents::ON_CAMPAIGN_TRIGGER_ACTION => ['onCampaignTriggerAction', 0],
        ];
    }

    /**
     * @param CampaignBuilderEvent $event
     */
    public function onCampaignBuild(CampaignBuilderEvent $event)
    {
        $action = [
            'label'                  => 'mautic.contactclient.campaign.event.show_contactclient',
            'description'            => 'mautic.contactclient.campaign.event.show_contactclient_descr',
            'eventName'              => ContactClientEvents::ON_CAMPAIGN_TRIGGER_ACTION,
            'formType'               => 'contactclientshow_list',
            'formTheme'              => 'MauticContactClientBundle:FormTheme\ContactClientShowList',
            'formTypeOptions'        => ['update_select' => 'campaignevent_properties_contactclient'],
            'connectionRestrictions' => [
                'anchor' => [
                    'decision.inaction',
                ],
                'source' => [
                    'decision' => [
                        'page.pagehit',
                    ],
                ],
            ],
        ];
        $event->addAction('contactclient.show', $action);
    }

    /**
     * @param CampaignExecutionEvent $event
     */
    public function onCampaignTriggerAction(CampaignExecutionEvent $event)
    {
        $contactclientId = (int) $event->getConfig()['contactclient'];
        if (!$contactclientId) {
            return $event->setResult(false);
        }
        $values                 = [];
        $values['contactclient_item'][] = ['id' => $contactclientId, 'js' => $this->router->generate('mautic_contactclient_generate', ['id' => $contactclientId], true)];
        $this->trackingHelper->updateSession($values);

        return $event->setResult(true);
    }
}
