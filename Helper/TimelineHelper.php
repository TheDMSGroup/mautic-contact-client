<?php
/**
 * Created by PhpStorm.
 * User: nbush
 * Date: 7/23/18
 * Time: 3:06 AM
 */

namespace MauticContactClientBundle\Helper;

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use MauticPlugin\MauticContactClientBundle\Model\ContactClientModel;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;

class ContactClientTimelineHelper
{

    /** @var \Symfony\Component\HttpFoundation\RequestStack */
    protected $requestStack;

    /** @var \Symfony\Component\HttpFoundation\Session\Session */
    protected $session;

    /** @var \Mautic\CoreBundle\Helper\CoreParametersHelper */
    protected $parameterHelper;

    /** @var string  */
    protected $sessionKeyBase = 'contactclient.timeline';

    /** @var string  */
    protected $defaultOrderCol = 'timestamp';

    /** @var string  */
    protected $defaultOrderDir = 'DESC';

    /**
     * ContactClientTimelineHelper constructor.
     * @param RequestStack $requestStack
     * @param Session $session
     * @param CoreParametersHelper $parametersHelper
     * @param ContactClientModel $contactClientModel
     */
    public function __construct(
        RequestStack $requestStack,
        Session $session,
        CoreParametersHelper $parametersHelper,
        ContactClientModel $contactClientModel
    )
    {
        $this->requestStack = $requestStack;
        $this->session = $session;
        $this->parameterHelper = $parametersHelper;
        $this->model = $contactClientModel;

    }

    public function setTimelineFilters($contactClientId)
    {
        $request = $this->requestStack->getCurrentRequest();

        if ($request->getMethod() == 'POST' && $request->request->has('date_from')) {
            $dateFrom = InputHelpper::clean($request->request->get('date_from'));
            $dateTo   = InputHelpper::clean($request->request->get('date_to'));

            $filters = [
                'dateFrom' => InputHelper::clean(),
                'dateTo'   => InputHelper::clean($request->request->get('dateTo', false)),
            ];
            if ($request->request->has('search') && $request->request->get('search')) {
                $filters['search'] = InputHelper::clean($request->request->get('search'));
            }
            $this->session->set('mautic.contactclient.'.$contactClientId.'.timeline.filters', $filters);
        }

        $filters = $this->session->get('mautic.contactclient'.$contactClientId.'.timeline.filters', []);

        $order = [
            $session->get('mautic.contactclient.'.$contactClientId.'.timeline.orderby'),
            $session->get('mautic.contactclient.'.$contactClientId.'.timeline.orderbydir'),
        ];




    }

    public function getFilters()
    {

    }

    public function getOrderBy()
    {
            if (!$session->has('mautic.lead.'.$lead->getId().'.timeline.orderby')) {
                $session->set('mautic.lead.'.$lead->getId().'.timeline.orderby', 'timestamp');
                $session->set('mautic.lead.'.$lead->getId().'.timeline.orderbydir', 'DESC');
            }

            $orderBy = [
                $this->session->get('mautic.lead.'.$lead->getId().'.timeline.orderby'),
                $this->get('mautic.lead.'.$lead->getId().'.timeline.orderbydir'),
            ];
        }
    }

    public function getPage()
    {

    }

    public function getLimit()
    {

    }

    public function getEngagements()
    {

    }
}