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

use Mautic\CoreBundle\Controller\FormController;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class ContactClientController
 * @package MauticPlugin\MauticContactClientBundle\Controller
 */
class ContactClientController extends FormController
{
    use ContactClientDetailsTrait;

    public function __construct()
    {
        $this->setStandardParameters(
            'contactclient',
            'plugin:contactclient:items',
            'mautic_contactclient',
            'mautic_contactclient',
            'mautic.contactclient',
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
        return parent::indexStandard($page);
    }

    /**
     * Generates new form and processes post data.
     *
     * @return array|\Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse|Response
     * @throws \Exception
     */
    public function newAction()
    {
        return parent::newStandard();
    }

    /**
     * Generates edit form and processes post data.
     *
     * @param $objectId
     * @param bool $ignorePost
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse|Response
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
     * @return array
     */
    public function customizeViewArguments($args, $view)
    {
        if ($view == 'view') {
            /** @var \MauticPlugin\MauticContactClientBundle\Entity\ContactClient $item */
            $item = $args['viewParameters']['item'];

            // For line graphs in the view
            $dateRangeValues = $this->request->get('daterange', []);
            $dateRangeForm = $this->get('form.factory')->create(
                'daterange',
                $dateRangeValues,
                [
                    'action' => $this->generateUrl(
                        'mautic_contactclient_action',
                        [
                            'objectAction' => 'view',
                            'objectId' => $item->getId(),
                        ]
                    ),
                ]
            );

            /** @var \MauticPlugin\MauticContactClientBundle\Model\ContactClientModel $model */
            $model = $this->getModel('contactclient');
            $stats = $model->getStats(
                $item,
                null,
                new \DateTime($dateRangeForm->get('date_from')->getData()),
                new \DateTime($dateRangeForm->get('date_to')->getData())
            );

            $args['viewParameters']['auditlog'] = $this->getAuditlogs($item);
            $args['viewParameters']['stats'] = $stats;
            $args['viewParameters']['events'] = $model->getEngagements($item);
            $args['viewParameters']['dateRangeForm'] = $dateRangeForm->createView();

            if ('link' == $item->getType()) {
                $args['viewParameters']['trackables'] = $this->getModel('page.trackable')->getTrackableList(
                    'contactclient',
                    $item->getId()
                );
            }
        }

        return $args;
    }

    /**
     * @param array $args
     * @param       $action
     *
     * @return array
     */
    protected function getPostActionRedirectArguments(array $args, $action)
    {
        $updateSelect = ($this->request->getMethod() == 'POST')
            ? $this->request->request->get('contactclient[updateSelect]', false, true)
            : $this->request->get(
                'updateSelect',
                false
            );
        if ($updateSelect) {
            switch ($action) {
                case 'new':
                case 'edit':
                    $passthrough = $args['passthroughVars'];
                    $passthrough = array_merge(
                        $passthrough,
                        [
                            'updateSelect' => $updateSelect,
                            'id' => $args['entity']->getId(),
                            'name' => $args['entity']->getName(),
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
        $updateSelect = ($this->request->getMethod() == 'POST')
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
     * @param string $nameMethod name of the entity method holding the name
     * @param string $groupMethod name of the entity method holding the select group
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
            'id' => $entity->getId(),
            'name' => $entity->$nameMethod(),
        ];

        return $options;
    }
}
