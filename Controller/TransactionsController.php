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

use Mautic\CoreBundle\Controller\AbstractFormController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Yaml\Yaml;

/**
 * Class TransactionsController.
 */
class TransactionsController extends AbstractFormController
{
    use ContactClientAccessTrait;
    use ContactClientDetailsTrait;

    /**
     * @param Request $request
     * @param         $objectId
     *
     * @return array|\MauticPlugin\MauticContactClientBundle\Entity\ContactClient|\Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse|StreamedResponse
     */
    public function exportAction(Request $request, $objectId)
    {
        if (empty($objectId)) {
            return $this->accessDenied();
        }
        $contactClient = $this->checkContactClientAccess($objectId, 'view');
        if ($contactClient instanceof Response) {
            return $contactClient;
        }
        // send a stream csv file of the timeline
        $name        = 'ContactClientExport';
        $headers     = [
            'type',
            'message',
            'date_added',
            'contact_id',
            'request_format',
            'request_method',
            'request_headers',
            'request_body',
            'request_duration',
            'status',
            'response_headers',
            'response_body_raw',
            'response_format',
            'valid',
        ];
        $session     = $this->get('session');
        $chartFilter = $session->get('mautic.contactclient.'.$contactClient->getId().'.chartfilter');
        $params      = [
            'dateTo'     => new \DateTime($chartFilter['date_to']),
            'dateFrom'   => new \DateTime($chartFilter['date_from']),
            'campaignId' => $chartFilter['campaign'],
            'message'    => !empty($request->query->get('message')) ? $request->query->get('message') : null,
            'type'       => !empty($request->query->get('type')) ? $request->query->get('type') : null,
            'utm_source' => !empty($request->query->get('utm_source')) ? $request->query->get('utm_source') : null,
            'contact_id' => !empty($request->query->get('contact_id')) ? $request->query->get('contact_id') : null,
            'start'      => 0,
            'limit'      => 1000,  // is this necessary?
        ];
        /** @var EventRepository $eventRepository */
        $eventRepository = $this->getDoctrine()->getEntityManager()->getRepository(
            'MauticContactClientBundle:Event'
        );
        $count           = $eventRepository->getEventsForTimelineExport($contactClient->getId(), $params, true);
        ini_set('max_execution_time', 0);
        $response = new StreamedResponse();
        $response->setCallback(
            function () use ($params, $headers, $contactClient, $count, $eventRepository) {
                $handle = fopen('php://output', 'w+');
                fputcsv($handle, $headers);
                while ($params['start'] < $count[0]['count']) {
                    $timelineData = $eventRepository->getEventsForTimelineExport(
                        $contactClient->getId(),
                        $params,
                        false
                    );
                    foreach ($timelineData as $data) {
                        // depracating use of YAML for event logs, but need to be backward compatible
                        $csvRows = $data['logs'][0] === '{' ?
                            $this->parseLogJSONBlob(
                                $data
                            ) :
                            $this->parseLogYAMLBlob(
                                $data
                            );
                        // a single data row can be multiple operations and subsequent rows
                        foreach ($csvRows as $csvRow) {
                            fputcsv($handle, array_values($csvRow));
                        }
                    }
                    $params['start'] += $params['limit'];
                }
                fclose($handle);
            }
        );
        $fileName = $name.'.csv';
        $response->setStatusCode(200);
        $response->headers->set('Content-Type', 'application/csv; charset=utf-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="'.$fileName.'"');

        return $response;
    }

    /**
     * @param $data
     *
     * @return array
     */
    private function parseLogJSONBlob($data)
    {
        $json = json_decode($data['logs'], true);
        unset($data['logs']);
        $rows                      = [];
        $data['request_format']    = '';
        $data['request_method']    = '';
        $data['request_headers']   = '';
        $data['request_body']      = '';
        $data['request_duration']  = '';
        $data['response_status']   = '';
        $data['response_headers']  = '';
        $data['response_body_raw'] = '';
        $data['response_format']   = '';
        $data['valid']             = '';
        if (isset($json['operations'])) {
            foreach ($json['operations'] as $id => $operation) {
                if (is_numeric($id)) {
                    $row                    = $data;
                    $row['request_format']  = isset($operation['request']['format']) ? $operation['request']['format'] : '';
                    $row['request_method']  = isset($operation['request']['method']) ? $operation['request']['method'] : '';
                    $row['request_headers'] = '';
                    $row['request_body']    = '';
                    if (isset($operation['request']['options'])) {
                        if (isset($operation['request']['options']['headers'])) {
                            $row['request_headers'] = implode('; ', $operation['request']['options']['headers']);
                            unset($operation['request']['options']['headers']);
                        }

                        $string = '';
                        foreach ($operation['request']['options'] as $key => $option) {
                            $string .= "$key: ".implode(',', $option).'; ';
                        }
                        $row['request_body'] = $string;
                    }
                    $row['request_duration']  = isset($operation['request']['duration']) ? $operation['request']['duration'] : '';
                    $row['response_status']   = isset($operation['response']['status']) ? $operation['response']['status'] : '';
                    $row['response_headers']  = isset($operation['response']['headers']) ? implode(
                        '; ',
                        $operation['response']['headers']
                    ) : '';
                    $row['response_body_raw'] = isset($operation['response']['bodyRaw']) ? $operation['response']['bodyRaw'] : '';
                    $row['response_format']   = isset($operation['response']['format']) ? $operation['response']['format'] : '';
                    $row['valid']             = isset($operation['valid']) ? $operation['valid'] : '';

                    $rows[$id] = $row;
                }
            }
        } else {
            $rows[0] = $data;
        }

        return $rows;
    }

    /**
     * @param $data
     *
     * @return array
     */
    private function parseLogYAMLBlob($data)
    {
        $yaml = Yaml::parse($data['logs']);
        unset($data['logs']);
        $rows                      = [];
        $data['request_format']    = '';
        $data['request_method']    = '';
        $data['request_headers']   = '';
        $data['request_body']      = '';
        $data['request_duration']  = '';
        $data['response_status']   = '';
        $data['response_headers']  = '';
        $data['response_body_raw'] = '';
        $data['response_format']   = '';
        $data['valid']             = '';
        if (isset($yaml['operations'])) {
            foreach ($yaml['operations'] as $id => $operation) {
                if (is_numeric($id)) {
                    $row                    = $data;
                    $row['request_format']  = isset($operation['request']['format']) ? $operation['request']['format'] : '';
                    $row['request_method']  = isset($operation['request']['method']) ? $operation['request']['method'] : '';
                    $row['request_headers'] = '';
                    $row['request_body']    = '';
                    if (isset($operation['request']['options'])) {
                        if (isset($operation['request']['options']['headers'])) {
                            $row['request_headers'] = implode('; ', $operation['request']['options']['headers']);
                            unset($operation['request']['options']['headers']);
                        }

                        $string = '';
                        foreach ($operation['request']['options'] as $key => $option) {
                            $string .= $key.': '.implode(',', $option).'; ';
                        }
                        $row['request_body'] = $string;
                    }
                    $row['request_duration']  = isset($operation['request']['duration']) ? $operation['request']['duration'] : '';
                    $row['response_status']   = isset($operation['response']['status']) ? $operation['response']['status'] : '';
                    $row['response_headers']  = isset($operation['response']['headers']) ? implode(
                        '; ',
                        $operation['response']['headers']
                    ) : '';
                    $row['response_body_raw'] = isset($operation['response']['bodyRaw']) ? $operation['response']['bodyRaw'] : '';
                    $row['response_format']   = isset($operation['response']['format']) ? $operation['response']['format'] : '';
                    $row['valid']             = isset($operation['valid']) ? $operation['valid'] : '';

                    $rows[$id] = $row;
                }
            }
        } else {
            $rows[0] = $data;
        }

        return $rows;
    }
}
