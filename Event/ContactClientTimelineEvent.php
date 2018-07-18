<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Digital Media Solutions, LLC
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticContactClientBundle\Event;

use Mautic\CoreBundle\Helper\Chart\ChartQuery;
use Mautic\CoreBundle\Helper\DateTimeHelper;
use MauticPlugin\MauticContactClientBundle\Entity\ContactClient;
use Symfony\Component\EventDispatcher\Event;

/**
 * Class ContactClientTimelineEvent.
 */
class ContactClientTimelineEvent extends Event
{
    /**
     * Container with all filtered events.
     *
     * @var array
     */
    protected $events = [];

    /**
     * Container with all registered events types.
     *
     * @var array
     */
    protected $eventTypes = [];

    /**
     * Array of filters
     *  search => (string) search term
     *  includeEvents => (array) event types to include
     *  excludeEvents => (array) event types to exclude.
     *  dateFrom      => DateTime **passed in via filters, moved to property
     *  dateTo        => DateTime **passed in via filters, moved to property
     *
     * @var array
     */
    protected $filters = [];

    /**
     * @var array|null
     */
    protected $orderBy = null;

    /**
     * ContactClient entity for the contactClient the timeline is being generated for.
     *
     * @var ContactClient
     */
    protected $contactClient;

    /**
     * @var int
     */
    protected $totalEvents = [];

    /**
     * @var array
     */
    protected $totalEventsByUnit = [];

    /**
     * @var int
     */
    protected $page = 1;

    /**
     * @var int
     */
    protected $limit;

    /**
     * @var bool
     */
    protected $countOnly = false;

    /**
     * @var \DateTime|null
     */
    protected $dateFrom = null;

    /**
     * @var \DateTime|null
     */
    protected $dateTo = null;

    /**
     * Time unit to group counts by (M = month, D = day, Y = year, null = no grouping).
     *
     * @var string
     */
    protected $groupUnit;

    /**
     * @var ChartQuery
     */
    protected $chartQuery;

    /**
     * @var bool
     */
    protected $forTimeline = true;

    /**
     * @var
     */
    protected $siteDomain;

    /**
     * @var bool
     */
    protected $fetchTypesOnly = false;

    /**
     * @var int
     */
    protected $queryTotal;

    /**
     * @var array
     */
    protected $serializerGroups = [
        'ipAddressList',
    ];

    /**
     * ContactClientTimelineEvent constructor.
     *
     * @param ContactClient|null $contactClient
     * @param array              $filters
     * @param array              $orderBy
     * @param int                $page
     * @param int                $limit          Limit per type
     * @param bool               $forTimeline
     * @param string|null        $siteDomain
     */
    public function __construct(
        ContactClient $contactClient = null,
        array $filters = [],
        array $orderBy = ['date_added', 'DESC'],
        $page = 1,
        $limit = 25,
        $forTimeline = true,
        $siteDomain = null
    ) {
        $this->contactClient = $contactClient;

        $this->filters['search'] = null;
        foreach ($filters as $filter => $value) {
            switch ($filter) {
            case 'dateFrom':
            case 'dateTo':
                if (!($value instanceof \DateTime)) {
                    try {
                        $value = new \DateTime(\strtotime($value));
                    } catch (\Exception $e) {
                        $default = ($filter == 'dateFrom') ?
                            $this->get('mautic.helper.core_parameters')->getParameter('default_daterange_filter', '-1 month') :
                            null;
                        $value = new \DateTime($default);
                    }
                }
                $this->{$filter} = $value;

                break;
            default:
                $this->filters[$filter] = $value;
            }
        }

        $this->orderBy       = $orderBy;
        $this->page          = $page;
        $this->limit         = $limit;
        $this->forTimeline   = $forTimeline;
        $this->siteDomain    = $siteDomain;
    }

    /**
     * Add an event to the container.
     *
     * The data should be an associative array with the following data:
     * 'event'     => string    The event name
     * 'timestamp' => \DateTime The timestamp of the event
     * 'extra'     => array     An optional array of extra data for the event
     *
     * @param array $data Data array for the table
     */
    public function addEvent(array $data)
    {
        if ($this->countOnly) {
            // BC support for old format
            if ($this->groupUnit && $this->chartQuery) {
                $countData = [
                    [
                        'date'  => $data['timestamp'],
                        'count' => 1,
                    ],
                ];

                $count = $this->chartQuery->completeTimeData($countData);
                $this->addToCounter($data['event'], $count);
            } else {
                if (!isset($this->totalEvents[$data['event']])) {
                    $this->totalEvents[$data['event']] = 0;
                }
                ++$this->totalEvents[$data['event']];
            }
        } else {
            if (!isset($this->events[$data['event']])) {
                $this->events[$data['event']] = [];
            }

            if (!$this->isForTimeline()) {
                // standardize the payload
                $keepThese = [
                    'event'              => true,
                    'eventId'            => true,
                    'eventLabel'         => true,
                    'eventType'          => true,
                    'timestamp'          => true,
                    'message'            => true,
                    'integratonEntityId' => true,
                    'contactId'          => true,
                    'extra'              => true,
                ];

                $data = array_intersect_key($data, $keepThese);

                // Rename extra to details
                if (isset($data['extra'])) {
                    $data['details'] = $data['extra'];
                    $data['details'] = $this->prepareDetailsForAPI($data['details']);
                    unset($data['extra']);
                }

                // Ensure a full URL
                if ($this->siteDomain && isset($data['eventLabel']) && is_array(
                        $data['eventLabel']
                    ) && isset($data['eventLabel']['href'])) {
                    // If this does not have a http, then assume a Mautic URL
                    if (false === strpos($data['eventLabel']['href'], '://')) {
                        $data['eventLabel']['href'] = $this->siteDomain.$data['eventLabel']['href'];
                    }
                }
            }

            if (empty($data['eventId'])) {
                // Every entry should have an eventId so generate one if the listener itself didn't handle this
                $data['eventId'] = $this->generateEventId($data);
            }

            $this->events[$data['event']][] = $data;
        }
    }

    /**
     * Add to the event counters.
     *
     * @param int|array $count
     */
    public function addToCounter($eventType, $count)
    {
        if (!isset($this->totalEvents[$eventType])) {
            $this->totalEvents[$eventType] = 0;
        }

        if (is_array($count)) {
            if (isset($count['total'])) {
                $this->totalEvents[$eventType] += $count['total'];
            } elseif ($this->isEngagementCount() && $this->groupUnit) {
                // Group counts across events by unit
                foreach ($count as $key => $data) {
                    if (!isset($this->totalEventsByUnit[$key])) {
                        $this->totalEventsByUnit[$key] = 0;
                    }
                    $this->totalEventsByUnit[$key] += (int) $data;
                    $this->totalEvents[$eventType] += (int) $data;
                }
            } else {
                $this->totalEvents[$eventType] = array_sum($count);
            }
        } else {
            $this->totalEvents[$eventType] += (int) $count;
        }
    }

    /**
     * Check if the event is getting an engagement count only.
     *
     * @return bool
     */
    public function isEngagementCount()
    {
        return $this->countOnly;
    }

    /**
     * Check if the data is to be display for the contact's timeline or used for the API.
     *
     * @return bool
     */
    public function isForTimeline()
    {
        return $this->forTimeline;
    }

    /**
     * Convert all snake case keys o camel case for API congruency.
     *
     * @param array $details
     *
     * @return array
     */
    private function prepareDetailsForAPI(array $details)
    {
        foreach ($details as $key => &$detailValues) {
            if (is_array($detailValues)) {
                $this->prepareDetailsForAPI($detailValues);
            }

            if ('contactClient_id' === $key) {
                // Don't include this as it should be included in parent as contactId
                unset($details[$key]);
                continue;
            }

            if (strstr($key, '_')) {
                $newKey           = lcfirst(str_replace('_', '', ucwords($key, '_')));
                $details[$newKey] = $details[$key];
                unset($details[$key]);
            }
        }

        return $details;
    }

    /**
     * Generate something consistent for this event to identify this log entry.
     *
     * @param array $data
     *
     * @return string
     */
    private function generateEventId(array $data)
    {
        return $data['eventType'].hash('crc32', json_encode($data), false);
    }

    /**
     * Fetch the events.
     *
     * @return array Events sorted by timestamp with most recent event first
     */
    public function getEvents()
    {
        if (empty($this->events)) {
            return [];
        }

        $events = call_user_func_array('array_merge', $this->events);

        foreach ($events as &$e) {
            if (!$e['timestamp'] instanceof \DateTime) {
                $dt             = new DateTimeHelper($e['timestamp'], 'Y-m-d H:i:s', 'UTC');
                $e['timestamp'] = $dt->getDateTime();
                unset($dt);
            }
        }

        if (!empty($this->orderBy)) {
            usort(
                $events,
                function ($a, $b) {
                    switch ($this->orderBy[0]) {
                        case 'eventLabel':
                            $aLabel = '';
                            if (isset($a['eventLabel'])) {
                                $aLabel = (is_array($a['eventLabel'])) ? $a['eventLabel']['label'] : $a['eventLabel'];
                            }

                            $bLabel = '';
                            if (isset($b['eventLabel'])) {
                                $bLabel = (is_array($b['eventLabel'])) ? $b['eventLabel']['label'] : $b['eventLabel'];
                            }

                            return strnatcmp($aLabel, $bLabel);

                        case 'date_added':
                            if ($a['timestamp'] == $b['timestamp']) {
                                $aPriority = isset($a['eventPriority']) ? (int) $a['eventPriority'] : 0;
                                $bPriority = isset($b['eventPriority']) ? (int) $b['eventPriority'] : 0;

                                return $aPriority - $bPriority;
                            }

                            return $a['timestamp'] < $b['timestamp'] ? -1 : 1;

                        case 'contact_id':
                            if ($a['contactId'] == $b['contactId']) {
                                return 0;
                            }

                            return $a['contactId'] < $b['contactId'] ? -1 : 1;

                        case 'type':

                            return strnatcmp($a['eventType'], $b['eventType']);

                        default:

                            return strnatcmp($a[$this->orderBy[0]], $b[$this->orderBy[0]]);
                    }
                }
            );

            if ('DESC' == $this->orderBy[1]) {
                $events = array_reverse($events);
            }
        }

        return $events;
    }

    /**
     * Get the max number of pages for pagination.
     *
     * @return float|int
     */
    public function getMaxPage()
    {
        // Max page is total count (queryTotal) / $limit
        return ($this->queryTotal) ? ceil($this->queryTotal / $this->limit) : 1;
    }

    /**
     * Add an event type to the container.
     *
     * @param string $eventTypeKey  Identifier of the event type
     * @param string $eventTypeName Name of the event type for humans
     */
    public function addEventType($eventTypeKey, $eventTypeName)
    {
        $this->eventTypes[$eventTypeKey] = $eventTypeName;
    }

    /**
     * Fetch the event types.
     *
     * @return array of available types
     */
    public function getEventTypes()
    {
        natcasesort($this->eventTypes);

        return $this->eventTypes;
    }

    /**
     * Fetch the filter array for queries.
     *
     * @return array of wanted filteres. Empty == all
     */
    public function getEventFilters()
    {
        return $this->filters['search'];
    }

    /**
     * Fetch the order for queries.
     *
     * @return array|null
     */
    public function getEventOrder()
    {
        return $this->orderBy;
    }

    /**
     * @return array
     */
    public function getQueryOptions()
    {
        return array_merge(
            [
                'search'     => $this->filters['search'],
                'logs'       => isset($this->filters['logs']) ? $this->filters['logs'] : null,
                'order'      => $this->orderBy,
                'paginated'  => !$this->countOnly,
                'unitCounts' => $this->countOnly && $this->groupUnit,
                'unit'       => $this->groupUnit,
                'fromDate'   => $this->dateFrom,
                'toDate'     => $this->dateTo,
                'chartQuery' => $this->chartQuery,
            ],
            $this->getEventLimit()
        );
    }

    /**
     * Fetch start/limit for queries.
     *
     * @return array
     */
    public function getEventLimit()
    {
        return [
            'contactClientId' => ($this->contactClient instanceof ContactClient) ? $this->contactClient->getId() : null,
            'limit'           => $this->limit,
            'start'           => (1 >= $this->page) ? 0 : ($this->page - 1) * $this->limit,
        ];
    }

    /**
     * @return int
     */
    public function getQueryTotal()
    {
        return $this->queryTotal;
    }

    public function setQueryTotal($queryTotal)
    {
        $this->queryTotal = $queryTotal;

        return $this;
    }

    /**
     * Fetches the contactClient being acted on.
     *
     * @return ContactClient
     */
    public function getContactClient()
    {
        return $this->contactClient;
    }

    /**
     * Returns the contactClient ID if any.
     *
     * @return int|null
     */
    public function getContactClientId()
    {
        return ($this->contactClient instanceof ContactClient) ? $this->contactClient->getId() : null;
    }

    /**
     * Get the date range to get counts by.
     *
     * @return array
     */
    public function getCountDateRange()
    {
        return ['from' => $this->dateFrom, 'to' => $this->dateTo];
    }

    /**
     * Get the unit counts are to be grouped by.
     *
     * @return string
     */
    public function getCountGroupingUnit()
    {
        return $this->groupUnit;
    }

    /**
     * Get total number of events for pagination.
     */
    public function getEventCounter()
    {
        // BC support for old formats
        foreach ($this->events as $type => $events) {
            if (!isset($this->totalEvents[$type])) {
                $this->totalEvents[$type] = count($events);
            }
        }

        $counter = [
            'total' => array_sum($this->totalEvents),
        ];

        if ($this->countOnly && $this->groupUnit) {
            $counter['byUnit'] = $this->totalEventsByUnit;
        }

        return $counter;
    }

    /**
     * Subtract from the total counter if there is an event that was skipped for whatever reason.
     *
     * @param $eventType
     * @param $count
     */
    public function subtractFromCounter($eventType, $count = 1)
    {
        $this->totalEvents[$eventType] -= $count;
    }

    /**
     * Calculate engagement counts only.
     *
     * @param \DateTime       $dateFrom
     * @param \DateTime       $dateTo
     * @param string          $groupUnit
     * @param ChartQuery|null $chartQuery
     */
    public function setCountOnly(
        \DateTime $dateFrom,
        \DateTime $dateTo,
        $groupUnit = '',
        ChartQuery $chartQuery = null
    ) {
        $this->countOnly  = true;
        $this->dateFrom   = $dateFrom;
        $this->dateTo     = $dateTo;
        $this->groupUnit  = $groupUnit;
        $this->chartQuery = $chartQuery;
    }

    /**
     * Get chart query helper to format dates.
     *
     * @return ChartQuery
     */
    public function getChartQuery()
    {
        return $this->chartQuery;
    }

    /**
     * Add a serializer group for API formatting.
     *
     * @param $group
     */
    public function addSerializerGroup($group)
    {
        if (is_array($group)) {
            $this->serializerGroups = array_merge(
                $this->serializerGroups,
                $group
            );
        } else {
            $this->serializerGroups[$group] = $group;
        }
    }

    /**
     * @return array
     */
    public function getSerializerGroups()
    {
        return $this->serializerGroups;
    }

    /**
     * Will cause isApplicable to return false for all in order to just compile a list of event types.
     */
    public function fetchTypesOnly()
    {
        $this->fetchTypesOnly = true;
    }

    /**
     * @return \DateTime|null
     */
    public function getDateFrom()
    {
        return $this->dateFrom;
    }

    /**
     * @param \DateTime|null $dateFrom
     * @return ContactClientTimelineEvent
     */
    public function setDateFrom($dateFrom)
    {
        $this->dateFrom = $dateFrom;
        return $this;
    }

    /**
     * @return \DateTime|null
     */
    public function getDateTo()
    {
        return $this->dateTo;
    }

    /**
     * @param \DateTime|null $dateTo
     * @return ContactClientTimelineEvent
     */
    public function setDateTo($dateTo)
    {
        $this->dateTo = $dateTo;
        return $this;
    }
}
