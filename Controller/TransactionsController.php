<?php
/**
 * Created by PhpStorm.
 * User: nbush
 * Date: 7/25/18
 * Time: 9:10 AM.
 */

namespace MauticPlugin\MauticContactClientBundle\Controller;

use Mautic\CoreBundle\Controller\CommonController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class TransactionsController extends CommonController
{
    use ContactClientAccessTrait;
    use ContactClientDetailsTrait;

    public function indexAction(Request $request, $objectId, $page = 1)
    {
        if (empty($objectId)) {
            return $this->accessDenied();
        }

        $contactClient = $this->checkContactClientAccess($objectId, 'view');
        if ($contactClient instanceof Response) {
            return $contactClient;
        }

        $session = $this->get('session');

        if ('POST' == $request->getMethod()) {
            $search = $request->request->has('search')
                ? $request->request->get('search')
                : $session->get('mautic.contactclient.'.$contactClient->getId().'.transactions.search');
        } else {
            $search = $session->get('mautic.contactclient.'.$contactClient->getId().'.transactions.search', '');
        }
        $session->set('mautic.contactclient.'.$contactClient->getId().'.transactions.search', $search);

        $chartFilterValues = $session->get('mautic.contactclient.'.$contactClient->getId().'.chartfilter');

        $chartfilter = [
            'type'     => $chartFilterValues['type'],
            'dateFrom' => new \DateTime($chartFilterValues['date_from']),
            'dateTo'   => new \DateTime($chartFilterValues['date_to']),
        ];

        $order = [
            $session->get('mautic.contactclient.'.$contactClient->getId().'.transactions.orderby'),
            $session->get('mautic.contactclient.'.$contactClient->getId().'.transactions.orderbydir'),
        ];

        $events = $this->getModel('contactclient')->getEngagements($contactClient, $chartfilter, $search, $order, $page);

        return $this->delegateView(
            [
                'viewParameters'  => [
                    'contactClient'       => $contactClient,
                    'page'                => $page,
                    'transactions'        => $events,
                ],
                'passthroughVars' => [
                    'route'             => false,
                    'mauticContent'     => 'contactClientTransactions',
                    'transactionsCount' => $events['total'],
                ],
                'contentTemplate' => 'MauticContactClientBundle:Transactions:list.html.php',
            ]
        );
    }
}
