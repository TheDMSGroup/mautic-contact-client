<?php
/**
 * Created by PhpStorm.
 * User: nbush
 * Date: 7/23/18
 * Time: 3:06 AM
 */

namespace MauticContactClientBundle\Helper;

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Mautic\CoreBundle\Helper\InputHelper;

class ContactClientTimelineHelper
{

    /** @var \Symfony\Component\HttpFoundation\RequestStack */
    protected $requestStack;

    /** @var \Symfony\Component\HttpFoundation\Session\Session */
    protected $session;

    /** @var \Mautic\CoreBundle\Helper\CoreParametersHelper */
    protected $parameterHelper;

    /** @var string */
    protected $defaultOrderCol = 'timestamp';

    /** @var string */
    protected $defaultOrderDir = 'DESC';

    /** @var string */
    protected $sessionKey;

    /**
     * ContactClientTimelineHelper constructor.
     * @param RequestStack $requestStack
     * @param Session $session
     * @param CoreParametersHelper $parametersHelper
     */
    public function __construct(
        RequestStack $requestStack,
        Session $session,
        CoreParametersHelper $parametersHelper
    )
    {
        $this->requestStack = $requestStack;
        $this->session = $session;
        $this->parameterHelper = $parametersHelper;
    }


    public function getTimelineParams()
    {
        $params = [
            'ContactClient' => $this->model->getEntity($this->request->attributes->get('contactClientId')),
            'OrderBy' => [],
            'Filters' => [],
            'page' => 1,
            'limit' => 25.
        ];

        foreach ($this->session->getIterator() as $key => $value) {

            if (!strstr($key, $this->sessionKey)) {
                continue;
            }
            $name = str_replace($this->sessionKey.'.', '', $key);

            switch ($name) {
                case 'page':
                case 'limit':
                    $params[$name] = $value;
                    break;
                case 'orderby':
                case 'orderbydir':
                    $params['OrderBy'][$name] = $value;
                    break;
                default:
                    $params['Filters'][$name] = $value;
            }

        }

        // TODO: validate param set

        return $params;
    }

    public function setTimelineParams()
    {
        $request = $this->requestStack->getCurrentRequest();
        
        $this->sessionKey = 'mautic.clientcontact.timeline.'.$request->attributes->get('contactClientId');
        $this->page = $request->attributes->get('page', 1);

        //initialize the pieces if needed
        if (!$this->session->get($this->sessionKey . '.from')) {
            $this->session->set($this->sessionKey . '.from', $this->parameterHelper->getParameter('default_daterange_filter'));
            $this->session->set($this->sessionKey . '.to', 'tomorrow -1 second');
        }

        if (!$this->session->get($this->sessionKey . '.orderby')) {
            $this->session->set($this->sessionKey . '.orderby', 'timestamp');
            $this->session->set($this->sessionKey . '.orderbydir', 'DESC');
        }

        if (!$this->session->get($this->sessionKey . '.limit')) {
            $this->session->set($this->sessionKey . '.limit', 25);
        }

        if ($request->request->has('chartfilter')) {
            $range = InputHelper::cleanArray('chartfilter');
            $this->session->set($this->sessionKey . '.from', $range['date_from']);
            $this->session->set($this->sessionKey . '.to', $range['date_to']);
        }

        if ($request->query->has('orderby')) {
            $cleanOrderBy = InputHelpper::clean($request->query->get('orderby'));

            if ($this->session->get($this->sessionKey . '.orderby') === $cleanOrderBy) {
                $dir = ($this->session->get($this->sessionKey . '.orderbydir') === 'DESC') ? 'ASC' : 'DESC';
                $this->session->set($this->sessionKey . '.orderbydir', $dir);
            } else {
                $this->session->set($this->sessionKey . '.orderbydir', 'DESC');
            }

            $this->session->set($this->sessionKey . '.orderby', $cleanOrderBy);
        }

        if ($request->request->has('limit')) {
            $this->session->set($this->sessionKey . '.limit', InputHelpper::clean($request->request->get('limit')));
        }

        $filters = [
            'datefrom' => $this->session->get($this->sessionKey . '.from'),
            'dateto' => $this->session->get($this->sessionKey . '.to'),
        ];

        if ($request->request->has('search')) {
            $search = InputHelper::clean($request->request->get('search'));
            if (empty($search)) {
                $this->session->remove($this->sessionKey . '.search');
            } else {
                $this->session->set($this->sessionKey . '.search', $search);
                $filters['search'] = $search;
            }

         }

         $this->session->set($this->sessionKey.'.filters', $filters);
    }
}