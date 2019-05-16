<?php

namespace MauticPlugin\MauticContactClientBundle\EventListener;

use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\CoreBundle\Templating\Helper\AssetsHelper;
use Mautic\LeadBundle\Event\LeadTimelineEvent;
use Mautic\LeadBundle\LeadEvents;

class LeadTimelineSubscriber extends CommonSubscriber
{
    /**
     * @var AssetsHelper
     */
    private $assets;

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            LeadEvents::TIMELINE_ON_GENERATE => ['onTimelineGenerate', 0],
        ];
    }

    /**
     * @param LeadTimelineEvent $event
     */
    public function onTimelineGenerate(LeadTimelineEvent $event)
    {
        $repo         = $this->em->getRepository('MauticContactClientBundle:Event');
        $clientEvents = $repo->getEventsByContactId($event->getLeadId());

        if(!is_array($clientEvents)){ 
            return;
        }

        foreach ($clientEvents as $srcEvent) {
            $srcEvent['eventLabel'] = [
                'label' => 'Contact Client: '.$srcEvent['client_name'],
                'href'  => "/s/contactclient/view/{$srcEvent['contactclient_id']}",
            ];
            $srcEvent['timestamp'] = $srcEvent['date_added'];
            $srcEvent['event']     = '';
            $srcEvent['eventType'] = ucfirst($srcEvent['type']);
            $srcEvent['extra']     = [
                'logs'    => $srcEvent['logs'],
                'message' => $srcEvent['message'],
            ];
            $srcEvent['contentTemplate'] = 'MauticContactClientBundle:Timeline:clientevent.html.php';
            $srcEvent['icon']            = 'fa-plus-square-o contact-client-button';

            $event->addEvent($srcEvent);
        }
    }
}
