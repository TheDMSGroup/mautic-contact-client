<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Digital Media Solutions, LLC
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticContactClientBundle\Controller;

use MauticPlugin\MauticContactClientBundle\Entity\ContactClient;
use MauticPlugin\MauticContactClientBundle\Entity\File;
use MauticPlugin\MauticContactClientBundle\Entity\FileRepository;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Class ContactClientAccessTrait.
 */
trait ContactClientAccessTrait
{
    /**
     * Determines if the user has access to the contactClient.
     *
     * @param        $contactClientId
     * @param        $action
     * @param bool   $isPlugin
     * @param string $integration
     *
     * @return ContactClient
     */
    protected function checkContactClientAccess($contactClientId, $action, $isPlugin = false, $integration = '')
    {
        if (!$contactClientId instanceof ContactClient) {
            //make sure the user has view access to this contactClient
            $contactClientModel = $this->getModel('contactClient');
            $contactClient      = $contactClientModel->getEntity((int) $contactClientId);
        } else {
            $contactClient   = $contactClientId;
            $contactClientId = $contactClient->getId();
        }

        if (null === $contactClient || !$contactClient->getId()) {
            if (method_exists($this, 'postActionRedirect')) {
                //set the return URL
                $page      = $this->get('session')->get(
                    $isPlugin ? 'mautic.'.$integration.'.page' : 'mautic.contactClient.page',
                    1
                );
                $returnUrl = $this->generateUrl(
                    $isPlugin ? 'mautic_plugin_timeline_index' : 'mautic_contact_index',
                    ['page' => $page]
                );

                return $this->postActionRedirect(
                    [
                        'returnUrl'       => $returnUrl,
                        'viewParameters'  => ['page' => $page],
                        'contentTemplate' => $isPlugin ? 'MauticContactClientBundle:ContactClient:pluginIndex' : 'MauticContactClientBundle:ContactClient:index',
                        'passthroughVars' => [
                            'activeLink'    => $isPlugin ? '#mautic_plugin_timeline_index' : '#mautic_contact_index',
                            'mauticContent' => 'contactClientTimeline',
                        ],
                        'flashes'         => [
                            [
                                'type'    => 'error',
                                'msg'     => 'mautic.contactClient.contactClient.error.notfound',
                                'msgVars' => ['%id%' => $contactClientId],
                            ],
                        ],
                    ]
                );
            } else {
                return $this->notFound('mautic.contact.error.notfound');
            }
        } elseif (!$this->get('mautic.security')->hasEntityAccess(
            'contactclient:items:'.$action.'own',
            'contactclient:items:'.$action.'other',
            $contactClient->getPermissionUser()
        )
        ) {
            return $this->accessDenied();
        } else {
            return $contactClient;
        }
    }

    /**
     * Determines if the user has access to a File.
     *
     * @param        $fileId
     * @param        $action
     * @param bool   $isPlugin
     * @param string $integration
     *
     * @return File
     */
    protected function checkContactClientFileAccess($fileId, $action, $isPlugin = false, $integration = '')
    {
        if (!$fileId instanceof File) {
            /** @var FileRepository $fileRepository */
            $fileRepository = $this->getDoctrine()->getEntityManager()->getRepository(
                'MauticContactClientBundle:File'
            );
            /** @var File $file */
            $file = $fileRepository->getEntity((int) $fileId);
        } else {
            /** @var File $file */
            $file   = $fileId;
            $fileId = $file->getId();
        }

        if (null === $file || !$file->getId()) {
            if (method_exists($this, 'postActionRedirect')) {
                //set the return URL
                $page      = $this->get('session')->get(
                    $isPlugin ? 'mautic.'.$integration.'.page' : 'mautic.contactClient.page',
                    1
                );
                $returnUrl = $this->generateUrl(
                    $isPlugin ? 'mautic_plugin_timeline_index' : 'mautic_contact_index',
                    ['page' => $page]
                );

                return $this->postActionRedirect(
                    [
                        'returnUrl'       => $returnUrl,
                        'viewParameters'  => ['page' => $page],
                        'contentTemplate' => $isPlugin ? 'MauticContactClientBundle:ContactClient:pluginIndex' : 'MauticContactClientBundle:ContactClient:index',
                        'passthroughVars' => [
                            'activeLink'    => $isPlugin ? '#mautic_plugin_timeline_index' : '#mautic_contact_index',
                            'mauticContent' => 'contactClientTimeline',
                        ],
                        'flashes'         => [
                            [
                                'type'    => 'error',
                                'msg'     => 'mautic.contactClient.contactClient.error.notfound',
                                'msgVars' => ['%id%' => $fileId],
                            ],
                        ],
                    ]
                );
            } else {
                return $this->notFound('mautic.contact.error.notfound');
            }
        } elseif (!$this->get('mautic.security')->hasEntityAccess(
            'contactclient:files:'.$action.'own',
            'contactclient:files:'.$action.'other',
            0 // @todo - Add ownership access when ownership exists by way of a File model extending FormModel.
        )) {
            return $this->accessDenied();
        } else {
            return $file;
        }
    }

    /**
     * Returns contactClients the user has access to.
     *
     * @param $action
     *
     * @return array|RedirectResponse
     */
    protected function checkAllAccess($action, $limit)
    {
        /** @var ContactClientModel $model */
        $model = $this->getModel('contactClient');

        //make sure the user has view access to contactClients
        $repo = $model->getRepository();

        // order by lastactive, filter
        $contactClients = $repo->getEntities(
            [
                'filter'         => [],
                'oderBy'         => 'r.last_active',
                'orderByDir'     => 'DESC',
                'limit'          => $limit,
                'hydration_mode' => 'HYDRATE_ARRAY',
            ]
        );

        if (null === $contactClients) {
            return $this->accessDenied();
        }

        foreach ($contactClients as $contactClient) {
            if (!$this->get('mautic.security')->hasEntityAccess(
                'contactclient:items:'.$action.'own',
                'contactclient:items:'.$action.'other',
                $contactClient['createdBy']
            )
            ) {
                unset($contactClient);
            }
        }

        return $contactClients;
    }
}
