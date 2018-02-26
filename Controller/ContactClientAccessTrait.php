<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticContactClientBundle\Controller;

use MauticPlugin\MauticContactClientBundle\Entity\ContactClient;

/**
 * Class ContactClientAccessTrait.
 */
trait ContactClientAccessTrait
{
    /**
     * Determines if the user has access to the contactClient the note is for.
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

        if ($contactClient === null || !$contactClient->getId()) {
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
            'contactClient:contactClients:'.$action.'own',
            'contactClient:contactClients:'.$action.'other',
            $contactClient->getPermissionUser()
        )
        ) {
            return $this->accessDenied();
        } else {
            return $contactClient;
        }
    }

    /**
     * Returns contactClients the user has access to.
     *
     * @param $action
     *
     * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
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
                'filter'         => [
                    'force' => [
                        [
                            'column' => 'l.date_identified',
                            'expr'   => 'isNotNull',
                        ],
                    ],
                ],
                'oderBy'         => 'r.last_active',
                'orderByDir'     => 'DESC',
                'limit'          => $limit,
                'hydration_mode' => 'HYDRATE_ARRAY',
            ]
        );

        if ($contactClients === null) {
            return $this->accessDenied();
        }

        foreach ($contactClients as $contactClient) {
            if (!$this->get('mautic.security')->hasEntityAccess(
                'contactClient:contactClients:'.$action.'own',
                'contactClient:contactClients:'.$action.'other',
                $contactClient->getOwner()
            )
            ) {
                unset($contactClient);
            }
        }

        return $contactClients;
    }
}
