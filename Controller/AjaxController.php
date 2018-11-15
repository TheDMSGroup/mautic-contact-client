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

use Mautic\CoreBundle\Controller\AjaxController as CommonAjaxController;
use Mautic\CoreBundle\Controller\AjaxLookupControllerTrait;
use Mautic\CoreBundle\Helper\InputHelper;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\LeadBundle\Entity\Lead as Contact;
use MauticPlugin\MauticContactClientBundle\Helper\TokenHelper;
use MauticPlugin\MauticContactClientBundle\Integration\ClientIntegration;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class AjaxController.
 */
class AjaxController extends CommonAjaxController
{
    use AjaxLookupControllerTrait;
    use ContactClientAccessTrait;
    use ContactClientDetailsTrait;

    /**
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     *
     * @throws \Exception
     */
    public function transactionsAction(Request $request)
    {
        $dataArray = [
            'html'    => '',
            'success' => 0,
        ];

        $objectId = InputHelper::clean($request->request->get('objectId'));
        if (empty($objectId)) {
            return $dataArray;
        }

        $contactClient = $this->checkContactClientAccess($objectId, 'view');
        if ($contactClient instanceof Response) {
            return $dataArray;
        }

        $session = $this->get('session');

        if ($request->request->has('search')) {
            $session->set(
                'mautic.contactclient.'.$contactClient->getId().'.transactions.search',
                $request->request->get('search')
            );
        }
        $order = [
            $session->get('mautic.contactclient.'.$contactClient->getId().'.transactions.orderby')
                ? $session->get('mautic.contactclient.'.$contactClient->getId().'.transactions.orderby')
                : 'date_added',
            $session->get('mautic.contactclient.'.$contactClient->getId().'.transactions.orderbydir')
                ? $session->get('mautic.contactclient.'.$contactClient->getId().'.transactions.orderbydir')
                : 'DESC',
        ];

        if ($request->request->has('orderby')) {
            $order[0] = $request->request->get('orderby');
            $session->set('mautic.contactclient.'.$contactClient->getId().'.transactions.orderby', $order[0]);
        }
        if ($request->request->has('orderbydir')) {
            $order[1] = $request->request->get('orderbydir');
            $session->set('mautic.contactclient.'.$contactClient->getId().'.transactions.orderbydir', $order[1]);
        }

        $transactions = $this->getEngagements($contactClient, null, null, 1);

        $dataArray['html']    = $this->renderView(
            'MauticContactClientBundle:Transactions:list.html.php',
            [
                'page'          => 1,
                'contactClient' => $contactClient,
                'transactions'  => $transactions,
                'order'         => $order,
            ]
        );
        $dataArray['success'] = 1;

        return $this->sendJsonResponse($dataArray);
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
        $contactClientId = (int) InputHelper::clean($request->request->get('contactClientId'));

        $apiPayload = html_entity_decode(InputHelper::clean($request->request->get('apiPayload')));

        $attributionDefault = html_entity_decode(InputHelper::clean($request->request->get('attributionDefault')));

        $attributionSettings = html_entity_decode(InputHelper::clean($request->request->get('attributionSettings')));

        // default to empty
        $dataArray = [
            'html'    => '',
            'valid'   => false,
            'success' => 0,
        ];

        if (!empty($apiPayload)) {
            /** @var Translator $translator */
            $translator = $this->get('translator');

            /** @var ClientIntegration $clientIntegration */
            $clientIntegration = $this->get('mautic.contactclient.integration');

            $valid                = $clientIntegration->sendTestApi(
                $apiPayload,
                $attributionDefault,
                $attributionSettings,
                $contactClientId
            );
            $dataArray['valid']   = $valid;
            $dataArray['payload'] = $apiPayload;
            $dataArray['message'] = $valid ? $translator->trans(
                'mautic.contactclient.form.test_results.passed'
            ) : $translator->trans('mautic.contactclient.form.test_results.failed');
            $dataArray['error']   = $clientIntegration->getLogs('error');
            $dataArray['html']    = $clientIntegration->getLogsJSON();
            $dataArray['success'] = true;
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
    protected function getFilePayloadTestAction(Request $request)
    {
        // Get the File payload to test.
        $filePayload = html_entity_decode(InputHelper::clean($request->request->get('filePayload')));

        $attributionDefault = html_entity_decode(InputHelper::clean($request->request->get('attributionDefault')));

        $attributionSettings = html_entity_decode(InputHelper::clean($request->request->get('attributionSettings')));

        // default to empty
        $dataArray = [
            'html'    => '',
            'valid'   => false,
            'success' => 0,
        ];

        if (!empty($filePayload)) {
            /** @var Translator $translator */
            $translator = $this->get('translator');

            /** @var ClientIntegration $clientIntegration */
            $clientIntegration = $this->get('mautic.contactclient.integration');

            $valid                = $clientIntegration->sendTestFile(
                $filePayload,
                $attributionDefault,
                $attributionSettings
            );
            $dataArray['valid']   = $valid;
            $dataArray['payload'] = $filePayload;
            $dataArray['message'] = $valid ? $translator->trans(
                'mautic.contactclient.form.test_results.passed'
            ) : $translator->trans('mautic.contactclient.form.test_results.failed');
            $dataArray['error']   = $clientIntegration->getLogs('error');
            $dataArray['html']    = $clientIntegration->getLogsJSON();
            $dataArray['success'] = true;
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
            'types'   => [],
            'formats' => [],
            'success' => 0,
        ];

        // Get an array representation of the current payload (of last save) for context.
        $filePayload = [];
        // Leaving File payload out of the tokens for now, since token use is not cognizant of the type yet.
        // $filePayload = html_entity_decode(InputHelper::clean($request->request->get('filePayload')));
        // $filePayload = json_decode($filePayload, true);
        // $filePayload = is_array($filePayload) ? $filePayload : [];
        $apiPayload = html_entity_decode(InputHelper::clean($request->request->get('apiPayload')));
        $apiPayload = json_decode($apiPayload, true);
        $apiPayload = is_array($apiPayload) ? $apiPayload : [];
        $payload    = array_merge($filePayload, $apiPayload);

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
                'type'  => $field['type'],
                'label' => $field['label'],
            ];
        }
        $contact->setFields($fieldGroups);

        /** @var TokenHelper $tokenHelper */
        $tokenHelper = $this->get('mautic.contactclient.helper.token');
        $tokenHelper->newSession(null, $contact, $payload);

        $fileName = (bool) $request->request->get('fileName');
        $tokens   = $tokenHelper->getContextLabeled($fileName);
        if ($tokens) {
            $dataArray['success'] = true;
            $dataArray['tokens']  = $tokens;
            $dataArray['types']   = $tokenHelper->getContextTypes($fileName);
            $dataArray['formats'] = $tokenHelper->getFormats();
        }

        return $this->sendJsonResponse($dataArray);
    }
}
