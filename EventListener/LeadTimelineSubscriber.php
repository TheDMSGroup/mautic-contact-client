<?php

namespace MauticPlugin\MauticContactClientBundle\EventListener;

use Doctrine\ORM\EntityManager;
use Exception;
use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\CoreBundle\Templating\Helper\AssetsHelper;
use Mautic\LeadBundle\Event\LeadTimelineEvent;
use Mautic\LeadBundle\LeadEvents;
use MauticPlugin\MauticContactClientBundle\Entity\EventRepository;

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
        /** @var EventRepository $repo */
        $repo         = $this->getEntityManager()->getRepository('MauticContactClientBundle:Event');
        $clientEvents = $repo->getEventsByContactId($event->getLeadId());

        if (!is_array($clientEvents)) {
            return;
        }

        foreach ($clientEvents as $srcEvent) {
            $srcEvent['eventLabel']      = [
                'label' => 'Contact Client: '.$srcEvent['client_name'],
                'href'  => "/s/contactclient/view/{$srcEvent['contactclient_id']}",
            ];
            $srcEvent['timestamp']       = $srcEvent['date_added'];
            $srcEvent['event']           = '';
            $srcEvent['eventType']       = ucfirst($srcEvent['type']);
            $srcEvent['extra']           = [
                'logs'    => $srcEvent['logs'],
                'message' => $srcEvent['message'],
            ];
            $srcEvent['contentTemplate'] = 'MauticContactClientBundle:Timeline:clientevent.html.php';
            $srcEvent['icon']            = 'fa-plus-square-o contact-client-button';

            $event->addEvent($srcEvent);
        }
    }

    /**
     * Shore up EntityManager loading, in case there is a flaw in a plugin or campaign handling.
     *
     * @return EntityManager
     */
    private function getEntityManager()
    {
        try {
            if ($this->em && !$this->em->isOpen()) {
                $this->em = $this->em->create(
                    $this->em->getConnection(),
                    $this->em->getConfiguration(),
                    $this->em->getEventManager()
                );
                $this->logger->error('ContactClient: EntityManager was closed.');
            }
        } catch (Exception $exception) {
            $this->logger->error('ContactClient: EntityManager could not be reopened.');
        }

        return $this->em;
    }
}
