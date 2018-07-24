<?php
/**
 * Created by PhpStorm.
 * User: nbush
 * Date: 7/23/18
 * Time: 3:06 AM
 */

namespace MauticPlugin\MauticContactClientBundle\Helper;

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Mautic\CoreBundle\Helper\InputHelper;
use MauticPlugin\MauticContactClientBundle\Model\ContactClientModel;

class TimelineHelper
{

    /** @var \Symfony\Component\HttpFoundation\RequestStack */
    protected $requestStack;

    /** @var \Symfony\Component\HttpFoundation\Session\Session */
    protected $session;

    /** @var \Mautic\CoreBundle\Helper\CoreParametersHelper */
    protected $parameterHelper;

    /** @var \MauticPlugin\MauticContactClientBundle\Model\ContactClientModel */
    protected $model;

    /** @var string */
    protected $defaultOrderCol = 'timestamp';

    /** @var string */
    protected $defaultOrderDir = 'DESC';

    /** @var string */
    protected $sessionKey = false;

    /** @var int */
    protected $page = 1;

    /** @var int */
    protected $limit = 25;

    /**
     * TimelineHelper constructor.
     * @param RequestStack $requestStack
     * @param Session $session
     * @param CoreParametersHelper $parametersHelper
     * @param ContactClientModel $model
     */
    public function __construct(
        RequestStack $requestStack,
        Session $session,
        CoreParametersHelper $parametersHelper,
        ContactClientModel $model
    )
    {
        $this->requestStack = $requestStack;
        $this->session = $session;
        $this->parameterHelper = $parametersHelper;
        $this->model = $model; //remove this and make an abstract model passaable to the getter
    }

    public function getTimelineParams()
    {
        $params = [
            'page' => $this->page,
            'limit' => $this->limit,
            'order' => ['orderby' => 'timestamp', 'orderbydir' => 'DESC'],
            'filters' => ['search' => ''],
        ];
        $unknown = [];

        if ($this->sessionKey()) {

            $params['ContactClient'] = $this->model->getEntity($this->getEntityId());

            foreach ($this->session->getIterator() as $key => $value) {

                if (!strstr($key, $this->sessionKey)) {
                    continue;
                }

                $name = str_replace($this->sessionKey, '', $key);
                switch ($name) {
                    case 'page':
                    case 'limit':
                        $params[$name] = $value;
                        break;
                    case 'orderby':
                    case 'orderbydir':
                        $params['order'][$name] = $value;
                        break;
                    case 'fromDate':
                    case 'toDate':
                        if (!($value instanceof \DateTime)) {
                            try {
                                $value = new \DateTime($value);
                            } catch (\Exception $e) {
                                $value = ($name == 'fromDate')
                                    ? new \DeepTime('-1 month')
                                    : new \DateTime();
                            }
                        }
                    case 'search':
                        $params['filters'][$name] = $value;
                        break;
                    default:
                        $unknown[$name] = $value;
                }

            }

        }
        // TODO: validate param set

        return $params;
    }

    public function setTimelineParams()
    {
        $request = $this->requestStack->getCurrentRequest();

        if (!$this->sessionKey()) {
            return;
        }

        //initialize the pieces if needed
        if (!$this->session->get($this->sessionKey . 'fromDate')) {
            $this->session->set($this->sessionKey . 'fromDate', $this->parameterHelper->getParameter('default_daterange_filter'));
            $this->session->set($this->sessionKey . 'toDate', 'tomorrow midnight -1 second');
        }

        if (!$this->session->get($this->sessionKey . 'orderby')) {
            $this->session->set($this->sessionKey . 'orderby', 'timestamp');
            $this->session->set($this->sessionKey . 'orderbydir', 'DESC');
        }

        if (!$this->session->get($this->sessionKey . 'limit')) {
            $this->session->set($this->sessionKey . 'limit', 25);
        }

        // Apply subbmitted changes
        $this->page = $request->attributes->get('page', 1);

        // combine 'GET' and 'POST' values for simple processing
        $vars = InputHelper::cleanArray(array_merge(
            $request->query->all(),
            $request->request->all()
        ));

        if (isset($vars['chartfilter'])) {
            $this->session->set($this->sessionKey . 'fromDate', $vars['chartfilter']['date_from']);
            $this->session->set($this->sessionKey . 'toDate', $vars['chartfilter']['date_to']);
        }

        if (isset($vars['orderby'])) {
            if ($this->session->get($this->sessionKey . 'orderby') == $vars['orderby']) {
                $dir = ($this->session->get($this->sessionKey . 'orderbydir') == 'DESC') ? 'ASC' : 'DESC';
                $this->session->set($this->sessionKey . 'orderbydir', $dir);
            } else {
                $this->session->set($this->sessionKey . 'orderbydir', 'ASC');
            }

            $this->session->set($this->sessionKey . 'orderby', $vars['orderby']);
        }

        if (isset($vars['limit'])) {
            $this->session->set($this->sessionKey . 'limit', $vars['limit']);
        }

        if (isset($vars['search'])) {
            if (empty($search)) {
                $this->session->remove($this->sessionKey . 'search');
            } else {
                $this->session->set($this->sessionKey . 'search', $vars['search']);
            }
         }
    }

    public function sessionKey() {
        $id = $this->getEntityId();
        if ($id) {
            $this->sessionKey = "mautic.contactclient.$id.timeline.";
        }
        return $this->sessionKey;
    }

    private function getEntityId()
    {
        $request = $this->requestStack->getCurrentRequest();

        return $request->attributes->has('objectId')
            ? $request->attributes->get('objectId')
            : $request->attributes->get('contactClientId', false);
    }
}