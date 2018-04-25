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

use Mautic\CoreBundle\Controller\CommonController;
use Mautic\CoreBundle\Helper\InputHelper;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Yaml\Yaml;

/**
 * Class TimelineController.
 */
class TimelineController extends CommonController
{
    use ContactClientAccessTrait;
    use ContactClientDetailsTrait;

    public function indexAction(Request $request, $contactClientId, $page = 1)
    {
        if (empty($contactClientId)) {
            return $this->accessDenied();
        }

        $contactClient = $this->checkContactClientAccess($contactClientId, 'view');
        if ($contactClient instanceof Response) {
            return $contactClient;
        }

        $this->setListFilters();

        $session = $this->get('session');
        if ('POST' == $request->getMethod() && $request->request->has('search')) {
            $filters = [
                'search' => InputHelper::clean($request->request->get('search')),
            ];
            $session->set('mautic.contactClient.'.$contactClientId.'.timeline.filters', $filters);
        } else {
            $filters = null;
        }

        $order = [
            $session->get('mautic.contactClient.'.$contactClientId.'.timeline.orderby'),
            $session->get('mautic.contactClient.'.$contactClientId.'.timeline.orderbydir'),
        ];

        $events = $this->getEngagements($contactClient, $filters, $order, $page);

        return $this->delegateView(
            [
                'viewParameters'  => [
                    'contactClient' => $contactClient,
                    'page'          => $page,
                    'events'        => $events,
                ],
                'passthroughVars' => [
                    'route'         => false,
                    'mauticContent' => 'contactClientTimeline',
                    'timelineCount' => $events['total'],
                ],
                'contentTemplate' => 'MauticContactClientBundle:Timeline:list.html.php',
            ]
        );
    }

    public function pluginIndexAction(Request $request)
    {
        $limit          = 25;
        $contactClients = $this->checkAllAccess('view', $limit);

        if ($contactClients instanceof Response) {
            return $contactClients;
        }

        $this->setListFilters();

        $session = $this->get('session');
        if ('POST' === $request->getMethod() && $request->request->has('search')) {
            $filters = [
                'search' => InputHelper::clean($request->request->get('search')),
            ];
            $session->set('mautic.plugin.timeline.filters', $filters);
        } else {
            $filters = null;
        }

        $order = [
            $session->get('mautic.plugin.timeline.orderby'),
            $session->get('mautic.plugin.timeline.orderbydir'),
        ];

        // get all events grouped by contactClient
        $events = $this->getAllEngagements($contactClients, $filters, $order, $page, $limit);

        $str = $this->request->server->get('QUERY_STRING');
        parse_str($str, $query);

        $tmpl = 'table';
        if (array_key_exists('from', $query) && 'iframe' === $query['from']) {
            $tmpl = 'list';
        }
        if (array_key_exists('tmpl', $query)) {
            $tmpl = $query['tmpl'];
        }

        return $this->delegateView(
            [
                'viewParameters'  => [
                    'contactClients' => $contactClients,
                    'page'           => $page,
                    'events'         => $events,
                    'integration'    => $integration,
                    'tmpl'           => (!$this->request->isXmlHttpRequest()) ? 'index' : '',
                    'newCount'       => (array_key_exists('count', $query) && $query['count']) ? $query['count'] : 0,
                ],
                'passthroughVars' => [
                    'route'         => false,
                    'mauticContent' => 'pluginTimeline',
                    'timelineCount' => $events['total'],
                ],
                'contentTemplate' => sprintf('MauticContactClientBundle:Timeline:plugin_%s.html.php', $tmpl),
            ]
        );
    }

    public function pluginViewAction(Request $request, $integration, $contactClientId, $page = 1)
    {
        if (empty($contactClientId)) {
            return $this->notFound();
        }

        $contactClient = $this->checkContactClientAccess($contactClientId, 'view', true, $integration);
        if ($contactClient instanceof Response) {
            return $contactClient;
        }

        $this->setListFilters();

        $session = $this->get('session');
        if ('POST' === $request->getMethod() && $request->request->has('search')) {
            $filters = [
                'search'        => InputHelper::clean($request->request->get('search')),
                'includeEvents' => InputHelper::clean($request->request->get('includeEvents', [])),
                'excludeEvents' => InputHelper::clean($request->request->get('excludeEvents', [])),
            ];
            $session->set('mautic.plugin.timeline.'.$contactClientId.'.filters', $filters);
        } else {
            $filters = null;
        }

        $order = [
            $session->get('mautic.plugin.timeline.'.$contactClientId.'.orderby'),
            $session->get('mautic.plugin.timeline.'.$contactClientId.'.orderbydir'),
        ];

        $events = $this->getEngagements($contactClient, $filters, $order, $page);

        $str = $this->request->server->get('QUERY_STRING');
        parse_str($str, $query);

        $tmpl = 'table';
        if (array_key_exists('from', $query) && 'iframe' === $query['from']) {
            $tmpl = 'list';
        }
        if (array_key_exists('tmpl', $query)) {
            $tmpl = $query['tmpl'];
        }

        return $this->delegateView(
            [
                'viewParameters'  => [
                    'contactClient' => $contactClient,
                    'page'          => $page,
                    'integration'   => $integration,
                    'events'        => $events,
                    'newCount'      => (array_key_exists('count', $query) && $query['count']) ? $query['count'] : 0,
                ],
                'passthroughVars' => [
                    'route'         => false,
                    'mauticContent' => 'pluginTimeline',
                    'timelineCount' => $events['total'],
                ],
                'contentTemplate' => sprintf('MauticContactClientBundle:Timeline:plugin_%s.html.php', $tmpl),
            ]
        );
    }

    /**
     * @return array|\Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\StreamedResponse
     *
     * @todo - Needs refactoring to function.
     */
    public function batchExportAction(Request $request, $contactClientId)
    {
        if (empty($contactClientId)) {
            return $this->accessDenied();
        }

        $contactClient = $this->checkContactClientAccess($contactClientId, 'view');
        if ($contactClient instanceof Response) {
            return $contactClient;
        }

        $this->setListFilters();

        $session = $this->get('session');
        if ('POST' == $request->getMethod() && $request->request->has('search')) {
            $filters = [
                'search'        => InputHelper::clean($request->request->get('search')),
                'includeEvents' => InputHelper::clean($request->request->get('includeEvents', [])),
                'excludeEvents' => InputHelper::clean($request->request->get('excludeEvents', [])),
            ];
            $session->set('mautic.contactClient.'.$contactClientId.'.timeline.filters', $filters);
        } else {
            $filters = null;
        }

        $order = [
            $session->get('mautic.contactClient.'.$contactClientId.'.timeline.orderby'),
            $session->get('mautic.contactClient.'.$contactClientId.'.timeline.orderbydir'),
        ];

        $dataType = $this->request->get('filetype', 'csv');

        $resultsCallback = function ($event) {
            $eventLabel = (isset($event['eventLabel'])) ? $event['eventLabel'] : $event['eventType'];
            if (is_array($eventLabel)) {
                $eventLabel = $eventLabel['label'];
            }

            return [
                'eventName'      => $eventLabel,
                'eventType'      => isset($event['eventType']) ? $event['eventType'] : '',
                'eventTimestamp' => $this->get('mautic.helper.template.date')->toText(
                    $event['timestamp'],
                    'local',
                    'Y-m-d H:i:s',
                    true
                ),
            ];
        };

        $results    = $this->getEngagements($contactClient, $filters, $order, 1, 200);
        $count      = $results['total'];
        $items      = $results['events'];
        $iterations = ceil($count / 200);
        $loop       = 1;

        // Max of 50 iterations for 10K result export
        if ($iterations > 50) {
            $iterations = 50;
        }

        $toExport = [];

        while ($loop <= $iterations) {
            if (is_callable($resultsCallback)) {
                foreach ($items as $item) {
                    $toExport[] = $resultsCallback($item);
                }
            } else {
                foreach ($items as $item) {
                    $toExport[] = (array) $item;
                }
            }

            $items = $this->getEngagements($contactClient, $filters, $order, $loop + 1, 200);

            $this->getDoctrine()->getManager()->clear();

            ++$loop;
        }

        return $this->exportResultsAs($toExport, $dataType, 'contact_timeline');
    }

    /**
     * @param Request $request
     * @param         $contactClientId
     *
     * @return StreamedResponse
     *
     * @throws \Exception
     */
    public function exportTimelineAction(Request $request, $contactClientId)
    {
        // send a stream csv file of the timeline
        $name    = 'ContactClientExport';
        $headers = [
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
        $params  = $this->getDateParams();
        /** @var EventRepository $eventRepository */
        $eventRepository = $this->getDoctrine()->getEntityManager()->getRepository(
            'MauticContactClientBundle:Event'
        );
        $count           = $eventRepository->getEventsForTimelineExport($contactClientId, $params, true);
        $start           = 0;
        $params['limit'] = 1000;
        ini_set('max_execution_time', 0);
        $response = new StreamedResponse();
        $response->setCallback(
            function () use ($params, $headers, $contactClientId, $count, $eventRepository, $start) {
                $handle = fopen('php://output', 'w+');
                fputcsv($handle, $headers);
                while ($start < $count[0]['count']) {
                    $params['start'] = $start;
                    $timelineData    = $eventRepository->getEventsForTimelineExport($contactClientId, $params, false);
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
                    $start = $start + $params['limit'];
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
     * @return array
     *
     * @throws \Exception
     */
    private function getDateParams()
    {
        $params    = [];
        $lastMonth = new \DateTime();
        $lastMonth->sub(new \DateInterval('P30D'));
        $today = new \DateTime();

        $from = new \DateTime(
            $this->request->getSession()
                ->get('mautic.dashboard.date.from', $lastMonth->format('Y-m-d 00:00:00'))
        );

        $to = new \DateTime(
            $this->request->getSession()
                ->get('mautic.dashboard.date.to', $today->format('Y-m-d H:i:s'))
        );

        $params['fromDate'] = $from;

        $params['toDate'] = $to;

        return $params;
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
        $data['response_status']            = '';
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
                    $row['request_duration'] = isset($operation['request']['duration']) ? $operation['request']['duration'] : '';
                    $row['response_status']  = isset($operation['response']['status']) ? $operation['response']['status'] : '';
                    $row['response_headers'] = isset($operation['response']['headers']) ? implode(
                        '; ',
                        $operation['response']['headers']
                    ) : '';
                    $row['response_body_raw']         = isset($operation['response']['bodyRaw']) ? $operation['response']['bodyRaw'] : '';
                    $row['response_format']  = isset($operation['response']['format']) ? $operation['response']['format'] : '';
                    $row['valid']            = isset($operation['valid']) ? $operation['valid'] : '';

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
        $data['response_status']            = '';
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
                            $string .= "$key: ".implode(',', $option).'; ';
                        }
                        $row['request_body'] = $string;
                    }
                    $row['request_duration'] = isset($operation['request']['duration']) ? $operation['request']['duration'] : '';
                    $row['response_status']  = isset($operation['response']['status']) ? $operation['response']['status'] : '';
                    $row['response_headers'] = isset($operation['response']['headers']) ? implode(
                        '; ',
                        $operation['response']['headers']
                    ) : '';
                    $row['response_body_raw']         = isset($operation['response']['bodyRaw']) ? $operation['response']['bodyRaw'] : '';
                    $row['response_format']  = isset($operation['response']['format']) ? $operation['response']['format'] : '';
                    $row['valid']            = isset($operation['valid']) ? $operation['valid'] : '';

                    $rows[$id] = $row;
                }
            }
        } else {
            $rows[0] = $data;
        }

        return $rows;
    }
}
