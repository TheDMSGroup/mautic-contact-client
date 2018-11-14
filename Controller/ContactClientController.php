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

use Mautic\CoreBundle\Controller\FormController;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class ContactClientController.
 */
class ContactClientController extends FormController
{
    use ContactClientDetailsTrait;

    public function __construct()
    {
        $this->setStandardParameters(
            'contactclient',
            'plugin:contactclient:items',
            'contactclient',
            'contactclient',
            '',
            'MauticContactClientBundle:ContactClient',
            null,
            'contactclient'
        );
    }

    /**
     * @param int $page
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse|Response
     */
    public function indexAction($page = 1)
    {
        // When the user inserts a numeric value, assume they want to find the entity by ID.
        $session = $this->get('session');
        $search  = $this->request->get('search', $session->get('mautic.'.$this->getSessionBase().'.filter', ''));
        if (isset($search) && is_numeric(trim($search))) {
            $search          = trim($search).' OR id:'.trim($search);
            $query           = $this->request->query->all();
            $query['search'] = $search;
            $this->request   = $this->request->duplicate($query);
            $session->set('mautic.'.$this->getSessionBase().'.filter', $search);
        } elseif (false === strpos($search, '%')) {
            $search          = '%'.trim($search, ' \t\n\r\0\x0B"%').'%';
            $search          = strpos($search, ' ') ? '"'.$search.'"' : $search;
            $query           = $this->request->query->all();
            $query['search'] = $search;
            $this->request   = $this->request->duplicate($query);
            $session->set('mautic.'.$this->getSessionBase().'.filter', $search);
        }

        return parent::indexStandard($page);
    }

    /**
     * Generates new form and processes post data.
     *
     * @return array|\Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse|Response
     *
     * @throws \Exception
     */
    public function newAction()
    {
        return parent::newStandard();
    }

    /**
     * Generates edit form and processes post data.
     *
     * @param      $objectId
     * @param bool $ignorePost
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse|Response
     *
     * @throws \Exception
     */
    public function editAction($objectId, $ignorePost = false)
    {
        return parent::editStandard($objectId, $ignorePost);
    }

    /**
     * Displays details on a ContactClient.
     *
     * @param $objectId
     *
     * @return array|\Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse|Response
     */
    public function viewAction($objectId)
    {
        return parent::viewStandard($objectId, 'contactclient', 'plugin.contactclient');
    }

    /**
     * Clone an entity.
     *
     * @param int $objectId
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse|Response
     */
    public function cloneAction($objectId)
    {
        return parent::cloneStandard($objectId);
    }

    /**
     * Deletes the entity.
     *
     * @param int $objectId
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function deleteAction($objectId)
    {
        return parent::deleteStandard($objectId);
    }

    /**
     * Deletes a group of entities.
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function batchDeleteAction()
    {
        return parent::batchDeleteStandard();
    }

    /**
     * @param $args
     * @param $view
     *
     * @return array
     */
    public function customizeViewArguments($args, $view)
    {
        if ('view' == $view) {
            $session = $this->get('session');

            /** @var \MauticPlugin\MauticContactClientBundle\Entity\ContactClient $item */
            $item = $args['viewParameters']['item'];

            // Setup page forms in session
            $order = [
                $session->get('mautic.contactclient.'.$item->getId().'.transactions.orderby')
                    ? $session->get('mautic.contactclient.'.$item->getId().'.transactions.orderby')
                    : 'date_added',
                $session->get('mautic.contactclient.'.$item->getId().'.transactions.orderbydir')
                    ? $session->get('mautic.contactclient.'.$item->getId().'.transactions.orderbydir')
                    : 'DESC',
            ];
            if ('POST' == $this->request->getMethod()) {
                $chartFilterValues = $this->request->request->has('chartfilter')
                    ? $this->request->request->get('chartfilter')
                    : $session->get('mautic.contactclient.'.$item->getId().'.chartfilter');
                $search = $this->request->request->has('search')
                    ? $this->request->request->get('search')
                    : $session->get('mautic.contactclient.'.$item->getId().'.transactions.search', '');
                if ($this->request->request->has('orderby')) {
                    $order[0] = $this->request->request->get('orderby');
                }
                if ($this->request->request->has('orderbydir')) {
                    $order[1] = $this->request->request->get('orderbydir');
                }
            } else {
                $chartFilterValues = $session->get('mautic.contactclient.'.$item->getId().'.chartfilter')
                    ? $session->get('mautic.contactclient.'.$item->getId().'.chartfilter')
                    : [
                        'date_from' => $this->get('mautic.helper.core_parameters')->getParameter('default_daterange_filter', 'midnight -1 month'),
                        'date_to'   => 'midnight tomorrow -1 second',
                        'type'      => '',
                    ];

                $search = $session->get('mautic.contactclient.'.$item->getId().'.transactions.search')
                    ? $session->get('mautic.contactclient.'.$item->getId().'.transactions.search')
                    : '';
            }

            if ($this->request->query->has('campaign')) {
                $chartFilterValues['campaign'] = $this->request->query->get('campaign');
            }

            $session->set('mautic.contactclient.'.$item->getId().'.chartfilter', $chartFilterValues);
            $session->set('mautic.contactclient.'.$item->getId().'.transactions.search', $search);
            $session->set('mautic.contactclient.'.$item->getId().'.transactions.orderby', $order[0]);
            $session->set('mautic.contactclient.'.$item->getId().'.transactions.orderbydir', $order[1]);

            //Setup for the chart and stats datatable
            /** @var \MauticPlugin\MauticContactClientBundle\Model\ContactClientModel $model */
            $model = $this->getModel('contactclient');

            $unit = $model->getTimeUnitFromDateRange(
                new \DateTime($chartFilterValues['date_from']),
                new \DateTime($chartFilterValues['date_to'])
            );

            $auditLog    = $this->getAuditlogs($item);
            $files       = $this->getFiles($item);
            if (in_array($chartFilterValues['type'], [''])) {
                $stats = $model->getStats(
                    $item,
                    $unit,
                    new \DateTime($chartFilterValues['date_from']),
                    new \DateTime($chartFilterValues['date_to'])
                );
            } else {
                $stats = $model->getStatsBySource(
                    $item,
                    $unit,
                    $chartFilterValues['type'],
                    new \DateTime($chartFilterValues['date_from']),
                    new \DateTime($chartFilterValues['date_to'])
                );
            }
            $transactions = $this->getEngagements($item);

            $chartFilterForm = $this->get('form.factory')->create(
                'chartfilter',
                $chartFilterValues,
                [
                    'action' => $this->generateUrl(
                        'mautic_contactclient_action',
                        [
                            'objectAction' => 'view',
                            'objectId'     => $item->getId(),
                        ]
                    ),
                ]
            );

            $args['viewParameters']['auditlog']        = $auditLog;
            $args['viewParameters']['files']           = $files;
            $args['viewParameters']['stats']           = $stats;
            $args['viewParameters']['transactions']    = $transactions;
            $args['viewParameters']['chartFilterForm'] = $chartFilterForm->createView();
            // depracated datatable section
            // $args['viewParameters']['tableData']       = $this->convertChartStatsToDatatable($stats, $unit);
            $args['viewParameters']['search']          = $search;
            $args['viewParameters']['order']           = $order;

            unset($chartFilterValues['campaign']);
            $session->set('mautic.contactclient.'.$item->getId().'.chartfilter', $chartFilterValues);
        }



        return $args;
    }

    /**
     * @param $stats
     * @param $unit
     *
     * @return array
     */
    protected function convertChartStatsToDatatable($stats, $unit)
    {
        $tableData = [
            'labels' => [],
            'data'   => [],
        ];

        if (!empty($stats)) {
            $tableData['labels'][] = ['title' => 'Date (Y-m-d)'];
            $row                   = [];
            foreach ($stats['datasets'] as $column => $dataset) {
                $tableData['labels'][] = ['title' => $dataset['label']];
                foreach ($dataset['data'] as $key => $data) {
                    //utc may produce an extra result outside of the locale date labels
                    if (!isset($stats['labels'][$key])) {
                        continue;
                    }
                    $dateStr = $stats['labels'][$key];
                    $date    = null;
                    switch ($unit) {
                        case 'd': // M j, y
                            $date    = date_create_from_format('M j, y', $dateStr);
                            $dateStr = $date->format('Y-m-d');
                            break;
                        case 'H': // M j ga
                            $date                   = date_create_from_format('M j ga', $dateStr);
                            $dateStr                = $date->format('Y-m-d - H:00');
                            $tableData['labels'][0] = ['title' => 'Date/Time'];
                            break;
                        case 'm': // M j ga
                            $date                   = date_create_from_format('M Y', $dateStr);
                            $dateStr                = $date->format('Y-m');
                            $tableData['labels'][0] = ['title' => 'Date (Y-m)'];
                            break;
                        case 'Y': // Y
                            $date                   = date_create_from_format('Y', $dateStr);
                            $dateStr                = $date->format('Y');
                            $tableData['labels'][0] = ['title' => 'Year'];
                            break;
                        case 'W': // W
                            $date = new \DateTime();
                            $date->setISODate(date('Y'), str_replace('Week ', '', $dateStr));
                            $dateStr                = 'Week '.$date->format('W');
                            $tableData['labels'][0] = ['title' => 'Week #'];
                            break;
                        default:
                            break;
                    }
                    $row[$key][0]           = $dateStr;
                    $row[$key][$column + 1] = $data;
                }
            }
            $tableData['data'] = $row;
        }

        return $tableData;
    }

    /**
     * @param array $args
     * @param       $action
     *
     * @return array
     */
    protected function getPostActionRedirectArguments(array $args, $action)
    {
        $updateSelect = ('POST' == $this->request->getMethod())
            ? $this->request->request->get('contactclient[updateSelect]', false, true)
            : $this->request->get(
                'updateSelect',
                false
            );
        if ($updateSelect) {
            switch ($action) {
                case 'new':
                case 'edit':
                    $passthrough             = $args['passthroughVars'];
                    $passthrough             = array_merge(
                        $passthrough,
                        [
                            'updateSelect' => $updateSelect,
                            'id'           => $args['entity']->getId(),
                            'name'         => $args['entity']->getName(),
                        ]
                    );
                    $args['passthroughVars'] = $passthrough;
                    break;
            }
        }

        return $args;
    }

    /**
     * @return array
     */
    protected function getEntityFormOptions()
    {
        $updateSelect = ('POST' == $this->request->getMethod())
            ? $this->request->request->get('contactclient[updateSelect]', false, true)
            : $this->request->get(
                'updateSelect',
                false
            );
        if ($updateSelect) {
            return ['update_select' => $updateSelect];
        }
    }

    /**
     * Return array of options update select response.
     *
     * @param string $updateSelect HTML id of the select
     * @param object $entity
     * @param string $nameMethod   name of the entity method holding the name
     * @param string $groupMethod  name of the entity method holding the select group
     *
     * @return array
     */
    protected function getUpdateSelectParams(
        $updateSelect,
        $entity,
        $nameMethod = 'getName',
        $groupMethod = 'getLanguage'
    ) {
        $options = [
            'updateSelect' => $updateSelect,
            'id'           => $entity->getId(),
            'name'         => $entity->$nameMethod(),
        ];

        return $options;
    }
}
