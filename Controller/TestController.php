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

use Mautic\CoreBundle\Controller\CommonController;
use Mautic\CoreBundle\Helper\InputHelper;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class TestController
 * @package MauticPlugin\MauticContactClientBundle\Controller
 */
class TestController extends CommonController
{
    use ContactClientAccessTrait;
    use ContactClientDetailsTrait;

    public function indexAction(Request $request, $contactClientId, $page = 1)
    {
        if (empty($contactClientId)) {
            return $this->accessDenied();
        }

        $contactClient = $this->checkContactClientAccess($contactClientId, 'view');
        if ($contactClient instanceof Response) {
            return $contactClient;
        }

        $session = $this->get('session');
        if ($request->getMethod() == 'POST' && $request->request->has('search')) {
            $filters = [
                'search' => InputHelper::clean($request->request->get('search')),
                'includeEvents' => InputHelper::clean($request->request->get('includeEvents', [])),
                'excludeEvents' => InputHelper::clean($request->request->get('excludeEvents', [])),
            ];
            $session->set('mautic.contactClient.'.$contactClientId.'.timeline.filters', $filters);
        } else {
            $filters = null;
        }

        $order = [
            $session->get('mautic.contactClient.'.$contactClientId.'.timeline.orderby'),
            $session->get('mautic.contactClient.'.$contactClientId.'.timeline.orderbydir'),
        ];

        $events = $this->getEngagements($contactClient, $filters, $order, $page);

        return $this->delegateView(
            [
                'viewParameters' => [
                    'contactClient' => $contactClient,
                    'page' => $page,
                    'events' => $events,
                ],
                'passthroughVars' => [
                    'route' => false,
                    'mauticContent' => 'contactClientTimeline',
                    'timelineCount' => $events['total'],
                ],
                'contentTemplate' => 'MauticContactClientBundle:Timeline:list.html.php',
            ]
        );
    }

}
