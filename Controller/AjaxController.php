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

use Mautic\CoreBundle\Controller\AjaxController as CommonAjaxController;
use Mautic\CoreBundle\Controller\AjaxLookupControllerTrait;
use Mautic\CoreBundle\Helper\InputHelper;
use Mautic\CoreBundle\Helper\UTF8Helper;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\LeadBundle\Entity\Lead as Contact;
use MauticPlugin\MauticContactClientBundle\Helper\TokenHelper;
use MauticPlugin\MauticContactClientBundle\Integration\ClientIntegration;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class AjaxController.
 */
class AjaxController extends CommonAjaxController
{
    use AjaxLookupControllerTrait;

    /**
     * @param Request $request
     *
     * @return mixed
     */
    public function ajaxTimelineAction(Request $request)
    {
        $filters     = [];
        $eventsModel = $this->get('mautic.contactclient.model.contactclient');

        foreach ($request->request->get('filters') as $key => $filter) {
            $filter['name']           = str_replace(
                '[]',
                '',
                $filter['name']
            ); // the serializeArray() js method seems to add [] to the key ???
            $filters[$filter['name']] = $filter['value'];
        }
        if (isset($filters['contactClientId'])) {
            if (!$contactClient = $eventsModel->getEntity($filters['contactClientId'])) {
                throw new \InvalidArgumentException('Contact Client argument is Invalid.');
            }
        } else {
            throw new \InvalidArgumentException('Contact Client argument is Missing.');
        }
        $orderBy = isset($filters['orderBy']) ? explode(':', $filters['orderBy']) : null;
        $page    = isset($filters['page']) ? $filters['page'] : 1;
        $limit   = isset($filters['limit']) ? $filters['limit'] : 25;

        $events = $eventsModel->getEngagements($contactClient, $filters, $orderBy, $page, $limit, true);
        $view   = $this->render(
            'MauticContactClientBundle:Timeline:list.html.php',
            [
                'events'        => $events,
                'contactClient' => $contactClient,
                'tmpl'          => '',
            ]
        );

        return $view;
    }

    /**
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     *
     * @throws \Exception
     */
    protected function getApiPayloadTestAction(Request $request)
    {
        // Get the API payload to test.
        $apiPayload = html_entity_decode(InputHelper::clean($request->request->get('apiPayload')));

        $attributionDefault = html_entity_decode(InputHelper::clean($request->request->get('attributionDefault')));

        $attributionSettings = html_entity_decode(InputHelper::clean($request->request->get('attributionSettings')));

        // default to empty
        $dataArray = [
            'html'  => '',
            'valid' => false,
        ];

        if (!empty($apiPayload)) {
            /** @var Translator $translator */
            $translator = $this->get('translator');

            /** @var ClientIntegration $clientIntegration */
            $clientIntegration = $this->get('mautic.contactclient.integration');

            $valid                = $clientIntegration->sendTest(
                $apiPayload,
                $attributionDefault,
                $attributionSettings
            );
            $dataArray['valid']   = $valid;
            $dataArray['payload'] = $apiPayload;
            $dataArray['message'] = $valid ? $translator->trans(
                'mautic.contactclient.form.test_results.passed'
            ) : $translator->trans('mautic.contactclient.form.test_results.failed');
            $dataArray['error']   = $clientIntegration->getLogs('error');
            //$dataArray['html']    = UTF8Helper::fixUTF8($clientIntegration->getLogsYAML());
            $dataArray['html']    = UTF8Helper::fixUTF8($clientIntegration->getLogsJSON());
        }

        return $this->sendJsonResponse($dataArray);
    }

    /**
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     *
     * @throws \Exception
     */
    protected function getTokensAction(Request $request)
    {
        $dataArray = [
            'tokens'  => [],
            'success' => 0,
        ];

        /** @var \Mautic\LeadBundle\Model\FieldModel $fieldModel */
        $fieldModel = $this->get('mautic.lead.model.field');

        // Exclude company fields as they are not currently used by the token helper.
        $fields      = $fieldModel->getEntities(
            [
                'filter'         => [
                    'force' => [
                        [
                            'column' => 'f.isPublished',
                            'expr'   => 'eq',
                            'value'  => true,
                        ],
                        [
                            'column' => 'f.object',
                            'expr'   => 'notLike',
                            'value'  => 'company',
                        ],
                    ],
                ],
                'hydration_mode' => 'HYDRATE_ARRAY',
            ]
        );
        $contact     = new Contact();
        $fieldGroups = [];
        foreach ($fields as $field) {
            $fieldGroups[$field['group']][$field['alias']] = [
                'value' => $field['label'],
                'label' => $field['label'],
            ];
        }
        $contact->setFields($fieldGroups);

        $tokenHelper = new TokenHelper();
        $tokenHelper->addContextContact($contact);

        $tokens = $tokenHelper->getContext(true);
        if ($tokens) {
            $dataArray['tokens']  = $tokens;
            $dataArray['success'] = true;
        }

        return $this->sendJsonResponse($dataArray);
    }
}
