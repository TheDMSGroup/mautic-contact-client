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

use Mautic\AssetBundle\Helper\TokenHelper as AssetTokenHelper;
use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\CoreBundle\Helper\IpLookupHelper;
use Mautic\CoreBundle\Model\AuditLogModel;
use Mautic\FormBundle\Helper\TokenHelper as FormTokenHelper;
use Mautic\PageBundle\Helper\TokenHelper as PageTokenHelper;
use Mautic\PageBundle\Model\PageModel;
use Mautic\PageBundle\Model\TrackableModel;
use MauticPlugin\MauticContactClientBundle\ContactClientEvents;
use MauticPlugin\MauticContactClientBundle\Entity\EventRepository;
use MauticPlugin\MauticContactClientBundle\Entity\Stat;
use MauticPlugin\MauticContactClientBundle\Event\ContactClientEvent;
use MauticPlugin\MauticContactClientBundle\Event\ContactClientTimelineEvent;
use MauticPlugin\MauticContactClientBundle\Model\ContactClientModel;
use Symfony\Component\Routing\RouterInterface;

/**
 * Class ContactClientSubscriber.
 */
class ContactClientSubscriber extends CommonSubscriber
{
    /**
     * @var RouterInterface
     */
    protected $router;

    /**
     * @var IpLookupHelper
     */
    protected $ipHelper;

    /**
     * @var AuditLogModel
     */
    protected $auditLogModel;

    /**
     * @var TrackableModel
     */
    protected $trackableModel;

    /**
     * @var PageTokenHelper
     */
    protected $pageTokenHelper;

    /**
     * @var AssetTokenHelper
     */
    protected $assetTokenHelper;

    /**
     * @var FormTokenHelper
     */
    protected $formTokenHelper;

    /**
     * @var ContactClientModel
     */
    protected $contactclientModel;

    /** @var PageModel */
    protected $pageModel;

    /**
     * ContactClientSubscriber constructor.
     *
     * @param RouterInterface    $router
     * @param IpLookupHelper     $ipLookupHelper
     * @param AuditLogModel      $auditLogModel
     * @param TrackableModel     $trackableModel
     * @param PageTokenHelper    $pageTokenHelper
     * @param AssetTokenHelper   $assetTokenHelper
     * @param FormTokenHelper    $formTokenHelper
     * @param ContactClientModel $contactclientModel
     */
    public function __construct(
        RouterInterface $router,
        IpLookupHelper $ipLookupHelper,
        AuditLogModel $auditLogModel,
        TrackableModel $trackableModel,
        PageTokenHelper $pageTokenHelper,
        AssetTokenHelper $assetTokenHelper,
        FormTokenHelper $formTokenHelper,
        ContactClientModel $contactclientModel
    ) {
        $this->router             = $router;
        $this->ipHelper           = $ipLookupHelper;
        $this->auditLogModel      = $auditLogModel;
        $this->trackableModel     = $trackableModel;
        $this->pageTokenHelper    = $pageTokenHelper;
        $this->assetTokenHelper   = $assetTokenHelper;
        $this->formTokenHelper    = $formTokenHelper;
        $this->contactclientModel = $contactclientModel;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            ContactClientEvents::POST_SAVE            => ['onContactClientPostSave', 0],
            ContactClientEvents::POST_DELETE          => ['onContactClientDelete', 0],
            ContactClientEvents::TIMELINE_ON_GENERATE => ['onTimelineGenerate', 0],
        ];
    }

    /**
     * Add an entry to the audit log.
     *
     * @param ContactClientEvent $event
     */
    public function onContactClientPostSave(ContactClientEvent $event)
    {
        $entity = $event->getContactClient();
        if ($details = $event->getChanges()) {
            $log = [
                'bundle'    => 'contactclient',
                'object'    => 'contactclient',
                'objectId'  => $entity->getId(),
                'action'    => ($event->isNew()) ? 'create' : 'update',
                'details'   => $details,
                'ipAddress' => $this->ipHelper->getIpAddressFromRequest(),
            ];
            $this->auditLogModel->writeToLog($log);
        }
    }

    /**
     * Add a delete entry to the audit log.
     *
     * @param ContactClientEvent $event
     */
    public function onContactClientDelete(ContactClientEvent $event)
    {
        $entity = $event->getContactClient();
        $log    = [
            'bundle'    => 'contactclient',
            'object'    => 'contactclient',
            'objectId'  => $entity->deletedId,
            'action'    => 'delete',
            'details'   => ['name' => $entity->getName()],
            'ipAddress' => $this->ipHelper->getIpAddressFromRequest(),
        ];
        $this->auditLogModel->writeToLog($log);
    }

    /**
     * Compile events for the lead timeline.
     *
     * @param ContactClientTimelineEvent $event
     */
    public function onTimelineGenerate(ContactClientTimelineEvent $event)
    {
        // Set available event types
        // $event->addSerializerGroup(['formList', 'submissionEventDetails']);

        /** @var EventRepository $eventRepository */
        $eventRepository = $this->em->getRepository('MauticContactClientBundle:Event');

        $stat    = new Stat();
        $types   = $stat->getAllTypes();
        $options = $event->getQueryOptions();
        foreach ($types as $eventTypeKey) {
            $eventTypeName = ucwords($eventTypeKey);
            $event->addEventType($eventTypeKey, $eventTypeName);
        }

        $rows = $eventRepository->getEventsForTimeline($event->getContactClient()->getId(), null, $options);
        foreach ($rows['results'] as $row) {
            $eventTypeKey  = $row['type'];
            $eventTypeName = ucwords($eventTypeKey);

            // Add total to counter
            $event->addToCounter($eventTypeKey, 1);

            if (!$event->isEngagementCount()) {
//                if (!$this->pageModel) {
//                    $this->pageModel = new PageModel();
//                }

                $event->addEvent(
                    [
                        'event'           => $eventTypeKey,
                        'eventId'         => $eventTypeKey.$row['id'],
                        'eventLabel'      => [
                            'label' => $eventTypeName,
                            'href'  => $this->router->generate(
                                'mautic_form_action',
                                ['objectAction' => 'view', 'objectId' => $row['id']]
                            ),
                        ],
                        'eventType'       => $eventTypeName,
                        'timestamp'       => $row['date_added'],
                        'extra'           => [
                            // 'page' => $this->pageModel->getEntity($row['page_id']),
                            'logs'                => $row['logs'],
                            'integrationEntityId' => $row['integration_entity_id'],
                        ],
                        'contentTemplate' => 'MauticContactClientBundle:SubscribedEvents\Timeline:index.html.php',
                        'icon'            => 'fa-plus-square-o',
                        'message'         => $row['message'],
                        'contactId'       => $row['contact_id'],
                    ]
                );
            }
        }
    }
}
