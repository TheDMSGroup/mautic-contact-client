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

use Mautic\CampaignBundle\Entity\CampaignRepository;
use Mautic\CoreBundle\Controller\AjaxController as CommonAjaxController;
use Mautic\CoreBundle\Controller\AjaxLookupControllerTrait;
use Mautic\CoreBundle\Helper\CacheStorageHelper;
use Mautic\CoreBundle\Helper\InputHelper;
use Mautic\CoreBundle\Helper\UTF8Helper;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\LeadBundle\Entity\Lead as Contact;
use MauticPlugin\MauticContactClientBundle\Entity\ContactClient;
use MauticPlugin\MauticContactClientBundle\Entity\ContactClientRepository;
use MauticPlugin\MauticContactClientBundle\Helper\ClientEventHelper;
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

        $filters = null;
        // filters means the transaction table had a column sort or filter submission or pagination, otherwise its a fresh page load
        if ($request->request->has('filters')) {
            foreach ($request->request->get('filters') as $filter) {
                if (in_array($filter['name'], ['dateTo', 'dateFrom']) && !empty($filter['value'])) {
                    $filter['value']        = new \DateTime($filter['value']);
                    list($hour, $min, $sec) = 'dateTo' == $filter['name'] ? [23, 59, 59] : [00, 00, 00];
                    $filter['value']->setTime($hour, $min, $sec);
                }
                if (!empty($filter['value'])) {
                    $filters[$filter['name']] = $filter['value'];
                }
            }
        }
        $page     = isset($filters['page']) && !empty($filters['page']) ? $filters['page'] : 1;
        $objectId = InputHelper::clean($request->request->get('objectId'));
        if (empty($objectId)) {
            return $this->sendJsonResponse($dataArray);
        }

        $contactClient = $this->checkContactClientAccess($objectId, 'view');
        if ($contactClient instanceof Response) {
            return $this->sendJsonResponse($dataArray);
        }

        $order = [
            'date_added',
            'DESC',
        ];

        if (isset($filters['orderby']) && !empty($filters['orderby'])) {
            $order[0] = $filters['orderby'];
        }
        if (isset($filters['orderbydir']) && !empty($filters['orderbydir'])) {
            $order[1] = $filters['orderbydir'];
        }

        $transactions = $this->getEngagements($contactClient, $filters, $order, $page);

        $dataArray['html']    = $this->renderView(
            'MauticContactClientBundle:Transactions:list.html.php',
            [
                'page'          => $page,
                'contactClient' => $contactClient,
                'transactions'  => $transactions,
                'order'         => $order,
            ]
        );
        $dataArray['success'] = 1;
        $dataArray['total']   = $transactions['total'];

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
        $contactClientId = (int) InputHelper::clean($request->request->get('contactClientId'));

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
                $attributionSettings,
                $contactClientId
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
        // Get an array representation of the current payload (of last save) for context.
        // No longer needs filePayload.
        $payload     = html_entity_decode(InputHelper::clean($request->request->get('apiPayload')));
        $defaultFile = __DIR__.ContactClient::API_PAYLOAD_DEFAULT_FILE;
        if (
            $payload
            && file_exists($defaultFile)
            && $payload == file_get_contents($defaultFile)
        ) {
            $cacheKey = 'tokenCacheDefault';
            $cacheTtl = 3600;
        } else {
            $cacheKey = 'tokenCache'.md5($payload);
            $cacheTtl = 300;
        }
        $payload   = json_decode($payload, true);
        $payload   = is_array($payload) ? $payload : [];
        $cache     = new CacheStorageHelper(
            CacheStorageHelper::ADAPTOR_FILESYSTEM,
            'ContactClient',
            null,
            $this->coreParametersHelper->getParameter(
                'cached_data_dir',
                $this->get('mautic.helper.paths')->getSystemPath('cache', true)
            ),
            300
        );
        $dataArray = $cache->get($cacheKey, $cacheTtl);
        if (!$dataArray) {
            $dataArray = [
                'tokens'  => [],
                'types'   => [],
                'formats' => [],
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
                    'type'  => $field['type'],
                    'label' => $field['label'],
                ];
            }
            $contact->setFields($fieldGroups);

            /** @var TokenHelper $tokenHelper */
            $tokenHelper = $this->get('mautic.contactclient.helper.token');
            $tokenHelper->newSession(null, $contact, $payload);

            $tokens = $tokenHelper->getContextLabeled();
            if ($tokens) {
                $dataArray['success'] = true;
                $dataArray['tokens']  = $tokens;
                $dataArray['types']   = $tokenHelper->getContextTypes();
                $dataArray['formats'] = $tokenHelper->getFormats();
            }
            $cache->set($cacheKey, $dataArray, $cacheTtl);
        }

        return $this->sendJsonResponse($dataArray);
    }

    /**
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @throws \Exception
     */
    public function pendingEventsAction()
    {
        $objectId = $this->request->request->get('objectId') ? (int)($this->request->request->get('objectId')) : null;
        $data = [];
        $total = 0;
        $eventIds = [];

        if (!empty($objectId)){

            $em      = $this->dispatcher->getContainer()->get('doctrine.orm.default_entity_manager');
            /** @var ClientEventHelper $clientEventHelper */
            $clientEventHelper = $this->get('mautic.contactclient.helper.client_event');
            if($events = $clientEventHelper->getAllClientEvents($objectId))
            {
                $eventIds = array_keys($events);
            };


            /** @var ContactClientRepository $repo */
            $clientRepo    = $em->getRepository(\MauticPlugin\MauticContactClientBundle\Entity\ContactClient::class);

            $events       = $clientRepo->getPendingEventsData($objectId, $eventIds);
            foreach ($events as $event)
            {
                $total = $total + (int)$event['count'];
            }

            $headers    = [
                '',
                'mautic.contactclient.pending.events.campaign',
                '',
                'mautic.contactclient.pending.events.event',
                'mautic.contactclient.pending.events.count',
            ];

            foreach ($headers as $header) {
                $data['columns'][] = [
                    'title' => $this->translator->trans($header),
                ];
            }
            $values = [];
            foreach($events as $event)
            {
                $values[] = array_values($event);
            }
        }
        $data['events'] = $values;
        $data['total'] = $total;
        $data = UTF8Helper::fixUTF8($data);
        return $this->sendJsonResponse($data);
    }
}
