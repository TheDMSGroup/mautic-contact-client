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

use DateTime;
use InvalidArgumentException;
use Mautic\CampaignBundle\Entity\ContactClientEventLogRepository;
use Mautic\CoreBundle\Helper\Chart\ChartQuery;
use Mautic\CoreBundle\Helper\Chart\LineChart;
use Mautic\CoreBundle\Helper\InputHelper;
use Mautic\CoreBundle\Model\AuditLogModel;
use MauticPlugin\MauticContactClientBundle\Entity\ContactClient;
use MauticPlugin\MauticContactClientBundle\Entity\FileRepository;
use MauticPlugin\MauticContactClientBundle\Entity\Stat;
use MauticPlugin\MauticContactClientBundle\Event\ContactClientTimelineEvent;
use MauticPlugin\MauticContactClientBundle\Model\ContactClientModel;

/**
 * Trait ContactClientDetailsTrait.
 */
trait ContactClientDetailsTrait
{
    /**
     * Makes sure that the event filter array is in the right format.
     *
     * @param mixed $filters
     *
     * @return array
     *
     * @throws InvalidArgumentException if not an array
     */
    public function sanitizeEventFilter($filters)
    {
        if (!is_array($filters)) {
            throw new InvalidArgumentException('filters parameter must be an array');
        }

        if (!isset($filters['search'])) {
            $filters['search'] = '';
        }

        if (!isset($filters['includeEvents'])) {
            $filters['includeEvents'] = [];
        }

        if (!isset($filters['excludeEvents'])) {
            $filters['excludeEvents'] = [];
        }

        return $filters;
    }

    /**
     * @param ContactClient $contactClient
     * @param array|null    $filters
     * @param array|null    $orderBy
     * @param int           $page
     * @param int           $limit
     * @param bool          $forTimeline
     *
     * @return array
     */
    public function getEngagements(
        ContactClient $contactClient,
        array $filters = null,
        array $orderBy = null,
        $page = 1,
        $limit = 25,
        $forTimeline = true
    ) {
        $session = $this->get('session');

        $chartFilters = $session->get('mautic.contactclient.'.$contactClient->getId().'.chartfilter');

        if (!isset($filters['dateFrom'])) {
            $dateFrom = new DateTime($chartFilters['date_from']);
            $dateFrom->setTime(00, 00, 00); // set to beginning of day, Timezone should be OK.
            $filters['dateFrom'] = $dateFrom;
        }

        if (!isset($filters['dateTo'])) {
            $dateTo = new DateTime($chartFilters['date_to']);
            $dateTo->setTime(23, 59, 59);
            $filters['dateTo'] = $dateTo;
        }

        if (
            !isset($filters['type'])
            && !empty($chartFilters['type'])
        ) {
            $filters['type'] = $chartFilters['type'];
        }

        if ('revenue' == $filters['type']) {
            $filters['type'] = Stat::TYPE_CONVERTED;
        }

        if (
            !isset($filters['campaignId'])
            && !empty($chartFilters['campaign'])
        ) {
            $filters['campaignId'] = $chartFilters['campaign'];
        }

        /** @var ContactClientTimelineEvent $engagements */
        $engagements = $this->getModel('contactclient')->getEngagements(
            $contactClient,
            $filters,
            $orderBy,
            $page,
            $limit
        );

        $payload = [
            'events'      => $engagements->getEvents(),
            'chartfilter' => [
                'date_from' => $filters['dateFrom']->format('M j, Y'),
                'date_to'   => $filters['dateTo']->format('M j, Y'),
                'type'      => isset($filters['type']) ? $filters['type'] : null,
            ],
            'filters'     => $filters,
            'order'       => $orderBy,
            'types'       => $engagements->getEventTypes(),
            'total'       => $engagements->getQueryTotal(),
            'page'        => $page,
            'limit'       => $limit,
            'maxPages'    => $engagements->getMaxPage(),
        ];

        return ($forTimeline)
            ? $payload
            : [$payload, $engagements->getSerializerGroups()];
    }

    /**
     * @param array      $contactClients
     * @param array|null $filters
     * @param array|null $orderBy
     * @param int        $page
     * @param int        $limit
     *
     * @return array
     *
     * @throws InvalidArgumentException
     */
    protected function getAllEngagements(
        array $contactClients,
        array $filters = null,
        array $orderBy = null,
        $page = 1,
        $limit = 25
    ) {
        $session = $this->get('session');

        if (null === $filters) {
            $chartfilters = $session->get(
                'mautic.contactclient.plugin.transactions.chartfilter',
                [
                    'date_from' => $this->get('mautic.helper.core_parameters')->getParameter(
                        'default_daterange_filter',
                        '-1 month'
                    ),
                    'date_to'   => null,
                    'type'      => 'All Events',
                ]
            );
            $session->set('mautic.contactclient.plugin.transactions.chartfilter');
            $search = $session->get('mautic.contactclient.plugin.transactions.search', '');
            $session->set('mautic.contactclient.plugin.transactions.search', '');
        }
        $filters = InputHelper::cleanArray(array_merge($chartfilters, ['search' => $search]));

        if (null == $orderBy) {
            if (!$session->has('mautic.contactclient.plugin.transactions.orderby')) {
                $session->set('mautic.contactclient.plugin.transactions.orderby', 'date_added');
                $session->set('mautic.contactclient.plugin.transactions.orderbydir', 'DESC');
            }

            $orderBy = [
                $session->get('mautic.contactclient.plugin.transactions.orderby'),
                $session->get('mautic.contactclient.plugin.transactions.orderbydir'),
            ];
        }

        // prepare result object
        $result = [
            'events'      => [],
            'chartfilter' => $chartfilters,
            'search'      => $search,
            'order'       => $orderBy,
            'types'       => [],
            'total'       => 0,
            'page'        => $page,
            'limit'       => $limit,
            'maxPages'    => 0,
        ];

        // get events for each contact
        foreach ($contactClients as $contactClient) {
            //  if (!$contactClient->getEmail()) continue; // discard contacts without email

            /** @var ContactClientModel $model */
            $model       = $this->getModel('contactClient');
            $engagements = $model->getEngagements($contactClient, $filters, $orderBy, $page, $limit);
            $events      = $engagements['events'];
            $types       = $engagements['types'];

            // inject contactClient into events
            foreach ($events as &$event) {
                $event['contactClientId']    = $contactClient->getId();
                $event['contactClientEmail'] = $contactClient->getEmail();
                $event['contactClientName']  = $contactClient->getName() ? $contactClient->getName(
                ) : $contactClient->getEmail();
            }

            $result['events'] = array_merge($result['events'], $events);
            $result['types']  = array_merge($result['types'], $types);
            $result['total'] += $engagements['total'];
        }

        $result['maxPages'] = ($limit <= 0) ? 1 : round(ceil($result['total'] / $limit));

        usort($result['events'], [$this, 'cmp']); // sort events by

        // now all events are merged, let's limit to   $limit
        array_splice($result['events'], $limit);

        $result['total'] = count($result['events']);

        return $result;
    }

    /**
     * Get a list of places for the contactClient based on IP location.
     *
     * @param ContactClient $contactClient
     *
     * @return array
     */
    protected function getPlaces(ContactClient $contactClient)
    {
        // Get Places from IP addresses
        $places = [];
        if ($contactClient->getIpAddresses()) {
            foreach ($contactClient->getIpAddresses() as $ip) {
                if ($details = $ip->getIpDetails()) {
                    if (!empty($details['latitude']) && !empty($details['longitude'])) {
                        $name = 'N/A';
                        if (!empty($details['city'])) {
                            $name = $details['city'];
                        } elseif (!empty($details['region'])) {
                            $name = $details['region'];
                        }
                        $place    = [
                            'latLng' => [$details['latitude'], $details['longitude']],
                            'name'   => $name,
                        ];
                        $places[] = $place;
                    }
                }
            }
        }

        return $places;
    }

    /**
     * @param ContactClient $contactClient
     * @param DateTime|null $fromDate
     * @param DateTime|null $toDate
     *
     * @return mixed
     */
    protected function getEngagementData(
        ContactClient $contactClient,
        DateTime $fromDate = null,
        DateTime $toDate = null
    ) {
        $translator = $this->get('translator');

        if (null == $fromDate) {
            $fromDate = new DateTime('first day of this month 00:00:00');
            $fromDate->modify('-6 months');
        }
        if (null == $toDate) {
            $toDate = new DateTime();
        }

        $lineChart  = new LineChart(null, $fromDate, $toDate);
        $chartQuery = new ChartQuery($this->getDoctrine()->getConnection(), $fromDate, $toDate);

        /** @var ContactClientModel $model */
        $model       = $this->getModel('contactClient');
        $engagements = $model->getEngagementCount($contactClient, $fromDate, $toDate, 'm', $chartQuery);
        $lineChart->setDataset(
            $translator->trans('mautic.contactclient.graph.line.all_engagements'),
            $engagements['byUnit']
        );

        $pointStats = $chartQuery->fetchTimeData(
            'contactClient_points_change_log',
            'date_added',
            ['contactClient_id' => $contactClient->getId()]
        );
        $lineChart->setDataset($translator->trans('mautic.contactclient.graph.line.points'), $pointStats);

        return $lineChart->render();
    }

    /**
     * @param ContactClient $contactClient
     * @param array|null    $filters
     * @param array|null    $orderBy
     * @param int           $page
     * @param int           $limit
     *
     * @return array
     */
    protected function getAuditlogs(
        ContactClient $contactClient,
        array $filters = null,
        array $orderBy = null,
        $page = 1,
        $limit = 25
    ) {
        $session = $this->get('session');

        if (null == $filters) {
            $filters = $session->get(
                'mautic.contactclient.'.$contactClient->getId().'.auditlog.filters',
                [
                    'search'        => '',
                    'includeEvents' => [],
                    'excludeEvents' => [],
                ]
            );
        }

        if (null == $orderBy) {
            if (!$session->has('mautic.contactclient.'.$contactClient->getId().'.auditlog.orderby')) {
                $session->set('mautic.contactclient.'.$contactClient->getId().'.auditlog.orderby', 'al.dateAdded');
                $session->set('mautic.contactclient.'.$contactClient->getId().'.auditlog.orderbydir', 'DESC');
            }

            $orderBy = [
                $session->get('mautic.contactclient.'.$contactClient->getId().'.auditlog.orderby'),
                $session->get('mautic.contactclient.'.$contactClient->getId().'.auditlog.orderbydir'),
            ];
        }

        // Audit Log
        /** @var AuditLogModel $auditlogModel */
        $auditlogModel = $this->getModel('core.auditLog');

        $logs     = $auditlogModel->getLogForObject(
            'contactclient',
            $contactClient->getId(),
            $contactClient->getDateAdded()
        );
        $logCount = count($logs);

        $types = [
            'delete'     => $this->translator->trans('mautic.contactclient.event.delete'),
            'create'     => $this->translator->trans('mautic.contactclient.event.create'),
            'identified' => $this->translator->trans('mautic.contactclient.event.identified'),
            'ipadded'    => $this->translator->trans('mautic.contactclient.event.ipadded'),
            'merge'      => $this->translator->trans('mautic.contactclient.event.merge'),
            'update'     => $this->translator->trans('mautic.contactclient.event.update'),
        ];

        return [
            'events'   => $logs,
            'filters'  => $filters,
            'order'    => $orderBy,
            'types'    => $types,
            'total'    => $logCount,
            'page'     => $page,
            'limit'    => $limit,
            'maxPages' => ceil($logCount / $limit),
        ];
    }

    /**
     * @param ContactClient $contactClient
     * @param array|null    $filters
     * @param array|null    $orderBy
     * @param int           $page
     * @param int           $limit
     *
     * @return array
     */
    protected function getFiles(
        ContactClient $contactClient,
        array $filters = null,
        array $orderBy = null,
        $page = 1,
        $limit = 25
    ) {
        $session  = $this->get('session');
        $criteria = [];
        if (null === $filters) {
            $storedFilters = $session->get(
                'mautic.contactclient.'.$contactClient->getId().'.chartfilter',
                [
                    'date_from' => $this->get('mautic.helper.core_parameters')->getParameter(
                        'default_daterange_filter',
                        '-1 month midnight'
                    ),
                    'date_to'   => null,
                    'type'      => '',
                ]
            );
            $session->set('mautic.contactclient.'.$contactClient->getId().'.chartfilter', $storedFilters);
            // $filters['fromDate'] = new \DateTime($storedFilters['date_from']);
            // $filters['toDate']   = new \DateTime($storedFilters['date_to']);
            $filters['type'] = $storedFilters['type'];
        }

        $filters['contactClient'] = $contactClient->getId();

        foreach ($filters as $name => $value) {
            switch ($name) {
                // case 'fromDate':
                //     $criteria[] = ['col' => 'dateAdded', 'expr' => 'gte', 'val' => $value];
                //     break;
                // case 'toDate':
                //     $criteria[] = ['col' => 'dateAdded', 'expr' => 'lt', 'val' => $value];
                //     break;
                default:
                    if (!empty($value)) {
                        $criteria[] = ['col' => $name, 'expr' => 'eq', 'val' => $value];
                    }
            }
        }

        $start = ($page - 1) * $limit;

        if (null === $orderBy || null === $orderBy[0]) { //empty array or no fieldname in first index
            if (!$session->has('mautic.contactclient.'.$contactClient->getId().'.files.orderby')) {
                $session->set('mautic.contactclient.'.$contactClient->getId().'.files.orderby', 'date_added');
                $session->set('mautic.contactclient.'.$contactClient->getId().'.files.orderbydir', 'DESC');
            }

            $orderBy = [
                $session->get('mautic.contactclient.'.$contactClient->getId().'.files.orderby'),
                $session->get('mautic.contactclient.'.$contactClient->getId().'.files.orderbydir'),
            ];
        }

        /** @var FileRepository $fileRepository */
        $fileRepository = $this->getDoctrine()->getManager()->getRepository('MauticContactClientBundle:File');
        $files          = $fileRepository->getEntities(
            [
                'filter'     => ['where' => $criteria],
                'start'      => $start,
                'limit'      => $limit,
                'orderBy'    => 'f.'.lcfirst(str_replace('_', '', ucwords($orderBy[0], '_'))),
                'orderByDir' => $orderBy[1],
            ]
        );

        $fileCount = count($files);

        return [
            'files'    => $files,
            'filters'  => $filters,
            'order'    => $orderBy,
            'total'    => $fileCount,
            'page'     => $page,
            'limit'    => $limit,
            'maxPages' => ceil($fileCount / $limit),
        ];
    }

    /**
     * @param ContactClient $contactClient
     *
     * @return array
     */
    protected function getScheduledCampaignEvents(ContactClient $contactClient)
    {
        // Upcoming events from Campaign Bundle
        /** @var ContactClientEventLogRepository $contactClientEventLogRepository */
        $contactClientEventLogRepository = $this->getDoctrine()->getManager()->getRepository(
            'MauticCampaignBundle:ContactClientEventLog'
        );

        return $contactClientEventLogRepository->getUpcomingEvents(
            [
                'contactClient' => $contactClient,
                'eventType'     => ['action', 'condition'],
            ]
        );
    }

    /**
     * @param $a
     * @param $b
     *
     * @return int
     */
    private function cmp($a, $b)
    {
        if ($a['timestamp'] === $b['timestamp']) {
            return 0;
        }

        return ($a['timestamp'] < $b['timestamp']) ? +1 : -1;
    }
}
