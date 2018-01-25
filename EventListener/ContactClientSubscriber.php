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
use Mautic\CoreBundle\Helper\InputHelper;
use Mautic\CoreBundle\Helper\IpLookupHelper;
use Mautic\CoreBundle\Model\AuditLogModel;
use Mautic\FormBundle\Helper\TokenHelper as FormTokenHelper;
use Mautic\PageBundle\Helper\TokenHelper as PageTokenHelper;
use Mautic\PageBundle\Model\TrackableModel;
use MauticPlugin\MauticContactClientBundle\Event\ContactClientEvent;
use MauticPlugin\MauticContactClientBundle\ContactClientEvents;
use MauticPlugin\MauticContactClientBundle\Model\ContactClientModel;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Form\FormEvents;

/**
 * Class ContactClientSubscriber
 * @package MauticPlugin\MauticContactClientBundle\EventListener
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

    /**
     * ContactClientSubscriber constructor.
     *
     * @param RouterInterface  $router
     * @param IpLookupHelper   $ipLookupHelper
     * @param AuditLogModel    $auditLogModel
     * @param TrackableModel   $trackableModel
     * @param PageTokenHelper  $pageTokenHelper
     * @param AssetTokenHelper $assetTokenHelper
     * @param FormTokenHelper  $formTokenHelper
     * @param ContactClientModel       $contactclientModel
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
        $this->router           = $router;
        $this->ipHelper         = $ipLookupHelper;
        $this->auditLogModel    = $auditLogModel;
        $this->trackableModel   = $trackableModel;
        $this->pageTokenHelper  = $pageTokenHelper;
        $this->assetTokenHelper = $assetTokenHelper;
        $this->formTokenHelper  = $formTokenHelper;
        $this->contactclientModel       = $contactclientModel;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            ContactClientEvents::POST_SAVE         => ['onContactClientPostSave', 0],
            ContactClientEvents::POST_DELETE       => ['onContactClientDelete', 0],
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

}
