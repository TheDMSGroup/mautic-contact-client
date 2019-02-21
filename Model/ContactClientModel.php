<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Digital Media Solutions, LLC
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticContactClientBundle\Model;

use Doctrine\DBAL\Query\QueryBuilder;
use Mautic\CampaignBundle\Entity\CampaignRepository;
use Mautic\CoreBundle\Helper\Chart\ChartQuery;
use Mautic\CoreBundle\Helper\Chart\LineChart;
use Mautic\CoreBundle\Helper\TemplatingHelper;
use Mautic\CoreBundle\Model\FormModel;
use Mautic\LeadBundle\Entity\Lead as Contact;
use Mautic\LeadBundle\Model\LeadModel as ContactModel;
use Mautic\PageBundle\Model\TrackableModel;
use MauticPlugin\MauticContactClientBundle\ContactClientEvents;
use MauticPlugin\MauticContactClientBundle\Entity\ContactClient;
use MauticPlugin\MauticContactClientBundle\Entity\Event as EventEntity;
use MauticPlugin\MauticContactClientBundle\Entity\Stat;
use MauticPlugin\MauticContactClientBundle\Event\ContactClientEvent;
use MauticPlugin\MauticContactClientBundle\Event\ContactClientStatEvent;
use MauticPlugin\MauticContactClientBundle\Event\ContactClientTimelineEvent;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;

/**
 * Class ContactClientModel.
 */
class ContactClientModel extends FormModel
{
    /** @var EventDispatcherInterface */
    protected $dispatcher;

    /** @var FormModel */
    protected $formModel;

    /** @var TrackableModel */
    protected $trackableModel;

    /** @var TemplatingHelper */
    protected $templating;

    /** @var ContactModel */
    protected $contactModel;

    /**
     * ContactClientModel constructor.
     *
     * @param \Mautic\FormBundle\Model\FormModel $formModel
     * @param TrackableModel                     $trackableModel
     * @param TemplatingHelper                   $templating
     * @param EventDispatcherInterface           $dispatcher
     * @param ContactModel                       $contactModel
     */
    public function __construct(
        \Mautic\FormBundle\Model\FormModel $formModel,
        TrackableModel $trackableModel,
        TemplatingHelper $templating,
        EventDispatcherInterface $dispatcher,
        ContactModel $contactModel
    ) {
        $this->formModel      = $formModel;
        $this->trackableModel = $trackableModel;
        $this->templating     = $templating;
        $this->dispatcher     = $dispatcher;
        $this->contactModel   = $contactModel;
    }

    /**
     * @return string
     */
    public function getActionRouteBase()
    {
        return 'contactclient';
    }

    /**
     * @return string
     */
    public function getPermissionBase()
    {
        return 'plugin:contactclient:items';
    }

    /**
     * {@inheritdoc}
     *
     * @param object                              $entity
     * @param \Symfony\Component\Form\FormFactory $formFactory
     * @param string                              $action
     * @param array                               $options
     *
     * @throws NotFoundHttpException
     */
    public function createForm($entity, $formFactory, $action = null, $options = [])
    {
        if (!$entity instanceof ContactClient) {
            throw new MethodNotAllowedHttpException(['ContactClient']);
        }

        if (!empty($action)) {
            $options['action'] = $action;
        }

        // Prevent clone action from complaining about extra fields.
        $options['allow_extra_fields'] = true;

        return $formFactory->create('contactclient', $entity, $options);
    }

    /**
     * @param null $id
     *
     * @return ContactClient|null|object
     */
    public function getEntity($id = null)
    {
        if (null === $id) {
            return new ContactClient();
        }

        return parent::getEntity($id);
    }

    /**
     * {@inheritdoc}
     *
     * @param ContactClient $entity
     * @param bool|false    $unlock
     */
    public function saveEntity($entity, $unlock = true)
    {
        parent::saveEntity($entity, $unlock);

        $this->getRepository()->saveEntity($entity);
    }

    /**
     * {@inheritdoc}
     *
     * @return \MauticPlugin\MauticContactClientBundle\Entity\ContactClientRepository
     */
    public function getRepository()
    {
        return $this->em->getRepository('MauticContactClientBundle:ContactClient');
    }

    /**
     * Add a stat entry.
     *
     * @param ContactClient|null $contactClient
     * @param null               $type
     * @param Contact|null       $contact
     * @param int                $attribution
     * @param string             $utmSource
     * @param int                $campaignId
     * @param int                $eventId
     */
    public function addStat(
        ContactClient $contactClient = null,
        $type = null,
        Contact $contact = null,
        $attribution = 0,
        $utmSource = '',
        $campaignId = 0,
        $eventId = 0
    ) {
        $stat = new Stat();
        $stat->setDateAdded(new \DateTime());
        if ($type) {
            $stat->setType($type);
        }
        if ($contactClient) {
            $stat->setContactClientId($contactClient->getId());
        }
        if ($contact) {
            $stat->setContactId($contact->getId());
        }
        if ($attribution) {
            $stat->setAttribution($attribution);
        }
        if ($utmSource) {
            $stat->setUtmSource($utmSource);
        }
        $stat->setCampaignId($campaignId);
        $stat->setEventId($eventId);
        $this->getStatRepository()->saveEntity($stat);

        // dispatch Stat PostSave event
        try {
            $event = new ContactClientStatEvent(
                $contactClient, $campaignId, $eventId, $contact, $this->em
            );
            $this->dispatcher->dispatch(
                ContactClientEvents::STAT_SAVE,
                $event
            );
        } catch (\Exception $e) {
        }
    }

    /**
     * {@inheritdoc}
     *
     * @return \MauticPlugin\MauticContactClientBundle\Entity\StatRepository
     */
    public function getStatRepository()
    {
        if (!$this->em->isOpen()) {
            $this->em = $this->em->create(
                $this->em->getConnection(),
                $this->em->getConfiguration(),
                $this->em->getEventManager()
            );
        }

        return $this->em->getRepository('MauticContactClientBundle:Stat');
    }

    /**
     * Add transactional log in contactclient_events.
     *
     * @param ContactClient|null $contactClient
     * @param string             $type
     * @param Contact|null       $contact
     * @param null               $logs
     * @param null               $message
     * @param null               $integrationEntityId
     */
    public function addEvent(
        ContactClient $contactClient = null,
        $type = null,
        Contact $contact = null,
        $logs = null,
        $message = null,
        $integrationEntityId = null
    ) {
        $event = new EventEntity();
        $event->setDateAdded(new \DateTime());
        if ($type) {
            $event->setType($type);
        }
        if ($contactClient) {
            $event->setContactClientId($contactClient->getId());
        }
        if ($contact) {
            $event->setContact($contact);
        }
        if ($logs) {
            $event->setLogs($logs);
        }
        if ($message) {
            $event->setMessage($message);
        }
        if ($integrationEntityId) {
            $event->setIntegrationEntityId($integrationEntityId);
        }

        $this->getEventRepository()->saveEntity($event);
    }

    /**
     * {@inheritdoc}
     *
     * @return \MauticPlugin\MauticContactClientBundle\Entity\StatRepository
     */
    public function getEventRepository()
    {
        return $this->em->getRepository('MauticContactClientBundle:Event');
    }

    /**
     * @param ContactClient  $contactClient
     * @param                $unit
     * @param \DateTime|null $dateFrom
     * @param \DateTime|null $dateTo
     * @param null           $campaignId
     * @param null           $dateFormat
     * @param bool           $canViewOthers
     *
     * @return array
     */
    public function getStats(
        ContactClient $contactClient,
        $unit,
        \DateTime $dateFrom = null,
        \DateTime $dateTo = null,
        $campaignId = null,
        $dateFormat = null,
        $canViewOthers = true
    ) {
        $query = new ChartQuery($this->em->getConnection(), $dateFrom, $dateTo, $unit);
        $unit  = (null === $unit) ? $this->getTimeUnitFromDateRange($dateFrom, $dateTo) : $unit;
        $chart = new LineChart($unit, $dateFrom, $dateTo, $dateFormat);
        $stat  = new Stat();

        $params = ['contactclient_id' => $contactClient->getId()];

        if ($campaignId) {
            $params['campaign_id'] = $campaignId;
        }

        foreach ($stat->getAllTypes() as $type) {
            $params['type'] = $type;
            $q              = $query->prepareTimeDataQuery(
                'contactclient_stats',
                'date_added',
                $params
            );

            if (!in_array($unit, ['H', 'i', 's'])) {
                // For some reason, Mautic only sets UTC in Query Date builder
                // if its an intra-day date range ¯\_(ツ)_/¯
                // so we have to do it here.
                $userTZ        = new \DateTime('now');
                $userTzName    = $userTZ->getTimezone()->getName();
                $paramDateTo   = $q->getParameter('dateTo');
                $paramDateFrom = $q->getParameter('dateFrom');
                $paramDateTo   = new \DateTime($paramDateTo);
                $paramDateTo->setTimeZone(new \DateTimeZone('UTC'));
                $q->setParameter('dateTo', $paramDateTo->format('Y-m-d H:i:s'));
                $paramDateFrom = new \DateTime($paramDateFrom);
                $paramDateFrom->setTimeZone(new \DateTimeZone('UTC'));
                $q->setParameter('dateFrom', $paramDateFrom->format('Y-m-d H:i:s'));
                $select    = $q->getQueryPart('select')[0];
                $newSelect = str_replace(
                    't.date_added,',
                    "CONVERT_TZ(t.date_added, @@global.time_zone, '$userTzName'),",
                    $select
                );
                $q->resetQueryPart('select');
                $q->select($newSelect);

                // AND adjust the group By, since its using db timezone Date values
                $groupBy    = $q->getQueryPart('groupBy')[0];
                $newGroupBy = str_replace(
                    't.date_added,',
                    "CONVERT_TZ(t.date_added, @@global.time_zone, '$userTzName'),",
                    $groupBy
                );
                $q->resetQueryPart('groupBy');
                $q->groupBy($newGroupBy);
            }

            if (!$canViewOthers) {
                $this->limitQueryToCreator($q);
            }

            $data = $query->loadAndBuildTimeData($q);
            foreach ($data as $val) {
                if (0 !== $val) {
                    $chart->setDataset($this->translator->trans('mautic.contactclient.graph.'.$type), $data);
                    break;
                }
            }
        }

        return $chart->render();
    }

    /**
     * Returns appropriate time unit from a date range so the line/bar charts won't be too full/empty.
     *
     * @param $dateFrom
     * @param $dateTo
     *
     * @return string
     */
    public function getTimeUnitFromDateRange($dateFrom, $dateTo)
    {
        $dayDiff = $dateTo->diff($dateFrom)->format('%a');
        $unit    = 'd';

        if ($dayDiff <= 1) {
            $unit = 'H';

            $sameDay    = $dateTo->format('d') == $dateFrom->format('d') ? 1 : 0;
            $hourDiff   = $dateTo->diff($dateFrom)->format('%h');
            $minuteDiff = $dateTo->diff($dateFrom)->format('%i');
            if ($sameDay && !intval($hourDiff) && intval($minuteDiff)) {
                $unit = 'i';
            }
            $secondDiff = $dateTo->diff($dateFrom)->format('%s');
            if (!intval($minuteDiff) && intval($secondDiff)) {
                $unit = 'm';
            }
        }
        if ($dayDiff > 31) {
            $unit = 'W';
        }
        if ($dayDiff > 63) {
            $unit = 'm';
        }
        if ($dayDiff > 1000) {
            $unit = 'Y';
        }

        return $unit;
    }

    /**
     * Joins the email table and limits created_by to currently logged in user.
     *
     * @param QueryBuilder $q
     */
    public function limitQueryToCreator(QueryBuilder $q)
    {
        $q->join('t', MAUTIC_TABLE_PREFIX.'contactclient', 'm', 'e.id = t.contactclient_id')
            ->andWhere('m.created_by = :userId')
            ->setParameter('userId', $this->userHelper->getUser()->getId());
    }

    /**
     * @param ContactClient  $contactClient
     * @param                $unit
     * @param                $type
     * @param \DateTime|null $dateFrom
     * @param \DateTime|null $dateTo
     * @param null           $campaignId
     * @param null           $dateFormat
     * @param bool           $canViewOthers
     *
     * @return array
     */
    public function getStatsBySource(
        ContactClient $contactClient,
        $unit,
        $type,
        \DateTime $dateFrom = null,
        \DateTime $dateTo = null,
        $campaignId = null,
        $dateFormat = null,
        $canViewOthers = true
    ) {
        $unit           = (null === $unit) ? $this->getTimeUnitFromDateRange($dateFrom, $dateTo) : $unit;
        $dateToAdjusted = clone $dateTo;
        $dateToAdjusted->setTime(23, 59, 59);
        $chart      = new LineChart($unit, $dateFrom, $dateToAdjusted, $dateFormat);
        $query      = new ChartQuery($this->em->getConnection(), $dateFrom, $dateToAdjusted, $unit);
        $utmSources = $this->getStatRepository()->getSourcesByClient(
            $contactClient->getId(),
            $dateFrom,
            $dateToAdjusted,
            $type
        );

        //if (isset($campaignId)) {
        if (!empty($campaignId)) {
            $params['campaign_id'] = (int) $campaignId;
        }
        $params['contactclient_id'] = $contactClient->getId();

        $userTZ     = new \DateTime('now');
        $userTzName = $userTZ->getTimezone()->getName();

        if ('revenue' != $type) {
            $params['type'] = $type;
            foreach ($utmSources as $utmSource) {
                $params['utm_source'] = empty($utmSource) ? ['expression' => 'isNull'] : $utmSource;
                $q                    = $query->prepareTimeDataQuery(
                    'contactclient_stats',
                    'date_added',
                    $params
                );

                if (!in_array($unit, ['H', 'i', 's'])) {
                    // For some reason, Mautic only sets UTC in Query Date builder
                    // if its an intra-day date range ¯\_(ツ)_/¯
                    // so we have to do it here.
                    $paramDateTo   = $q->getParameter('dateTo');
                    $paramDateFrom = $q->getParameter('dateFrom');
                    $paramDateTo   = new \DateTime($paramDateTo);
                    $paramDateTo->setTimeZone(new \DateTimeZone('UTC'));
                    $q->setParameter('dateTo', $paramDateTo->format('Y-m-d H:i:s'));
                    $paramDateFrom = new \DateTime($paramDateFrom);
                    $paramDateFrom->setTimeZone(new \DateTimeZone('UTC'));
                    $q->setParameter('dateFrom', $paramDateFrom->format('Y-m-d H:i:s'));
                    $select    = $q->getQueryPart('select')[0];
                    $newSelect = str_replace(
                        't.date_added,',
                        "CONVERT_TZ(t.date_added, @@global.time_zone, '$userTzName'),",
                        $select
                    );
                    $q->resetQueryPart('select');
                    $q->select($newSelect);

                    // AND adjust the group By, since its using db timezone Date values
                    $groupBy    = $q->getQueryPart('groupBy')[0];
                    $newGroupBy = str_replace(
                        't.date_added,',
                        "CONVERT_TZ(t.date_added, @@global.time_zone, '$userTzName'),",
                        $groupBy
                    );
                    $q->resetQueryPart('groupBy');
                    $q->groupBy($newGroupBy);
                }
                if (!$canViewOthers) {
                    $this->limitQueryToCreator($q);
                }
                $data = $query->loadAndBuildTimeData($q);
                foreach ($data as $val) {
                    if (0 !== $val) {
                        if (empty($utmSource)) {
                            $utmSource = 'No Source';
                        }
                        $chart->setDataset($utmSource, $data);
                        break;
                    }
                }
            }
        } else {
            $params['type'] = Stat::TYPE_CONVERTED;
            // Add attribution to the chart.
            $q = $query->prepareTimeDataQuery(
                'contactclient_stats',
                'date_added',
                $params
            );

            if (!$canViewOthers) {
                $this->limitQueryToCreator($q);
            }
            $dbUnit        = $query->getTimeUnitFromDateRange($dateFrom, $dateTo);
            $dbUnit        = $query->translateTimeUnit($dbUnit);
            $dateConstruct = "DATE_FORMAT(CONVERT_TZ(t.date_added, @@global.time_zone, '$userTzName'), '$dbUnit.')";
            foreach ($utmSources as $utmSource) {
                $q->select($dateConstruct.' AS date, ROUND(SUM(t.attribution), 2) AS count')
                    ->groupBy($dateConstruct);
                if (empty($utmSource)) { // utmSource can be a NULL value
                    $q->andWhere('utm_source IS NULL');
                } else {
                    $q->andWhere('utm_source = :utmSource')
                        ->setParameter('utmSource', $utmSource);
                }

                $data = $query->loadAndBuildTimeData($q);
                foreach ($data as $val) {
                    if (0 !== $val) {
                        if (empty($utmSource)) {
                            $utmSource = 'No Source';
                        }
                        $chart->setDataset($utmSource, $data);
                        break;
                    }
                }
            }
        }

        return $chart->render();
    }

    /**
     * @param ContactClient $contactClient
     * @param array         $filters
     * @param array         $orderBy
     * @param int           $page
     * @param int           $limit
     *
     * @return array|\Doctrine\ORM\Internal\Hydration\IterableResult|\Doctrine\ORM\Tools\Pagination\Paginator
     */
    public function getFiles(
        ContactClient $contactClient,
        array $filters = [],
        array $orderBy = [],
        $page = 1,
        $limit = 25
    ) {
        $args          = array_merge($filters, $orderBy);
        $args['page']  = $page;
        $args['limit'] = $limit;

        /** @var \MauticPlugin\MauticContactClientBundle\Entity\FileRepository $repo */
        $repo = $this->em->getRepository('MauticContactClientBundle:File');

        return $repo->getEntities($args);
    }

    /**
     * Get timeline/engagement data.
     *
     * @param ContactClient|null $contactClient
     * @param array              $filters
     * @param array              $orderBy
     * @param int                $page
     * @param int                $limit
     * @param bool               $forTimeline
     *
     * @return array
     */
    public function getEngagements(
        ContactClient $contactClient,
        $filters = [],
        $orderBy = [],
        $page = 1,
        $limit = 25,
        $forTimeline = true
    ) {
        /** @var \MauticPlugin\MauticContactClientBundle\Event\ContactClientTimelineEvent $event */
        $event = $this->dispatcher->dispatch(
            ContactClientEvents::TIMELINE_ON_GENERATE,
            new ContactClientTimelineEvent(
                $contactClient,
                $filters,
                $orderBy,
                $page,
                $limit,
                $forTimeline,
                $this->coreParametersHelper->getParameter('site_url')
            )
        );

        return $event;
    }

    /**
     * @return array
     */
    public function getEngagementTypes()
    {
        $event = new ContactClientTimelineEvent();
        $event->fetchTypesOnly();

        $this->dispatcher->dispatch(ContactClientEvents::TIMELINE_ON_GENERATE, $event);

        return $event->getEventTypes();
    }

    /**
     * Get engagement counts by time unit.
     *
     * @param ContactClient   $contactClient
     * @param \DateTime|null  $dateFrom
     * @param \DateTime|null  $dateTo
     * @param string          $unit
     * @param ChartQuery|null $chartQuery
     *
     * @return array
     */
    public function getEngagementCount(
        ContactClient $contactClient,
        \DateTime $dateFrom = null,
        \DateTime $dateTo = null,
        $unit = 'm',
        ChartQuery $chartQuery = null
    ) {
        $event = new ContactClientTimelineEvent($contactClient);
        $event->setCountOnly($dateFrom, $dateTo, $unit, $chartQuery);

        $this->dispatcher->dispatch(ContactClientEvents::TIMELINE_ON_GENERATE, $event);

        return $event->getEventCounter();
    }

    /**
     * @param $contactclientId
     *
     * @return array|\Doctrine\ORM\Internal\Hydration\IterableResult|\Doctrine\ORM\Tools\Pagination\Paginator
     */
    public function getCampaigns($contactclientId)
    {
        /** @var CampaignRepository $campaignRepo */
        $campaignRepo = $this->em->getRepository('MauticCampaignBundle:Campaign');

        return $campaignRepo->getEntities(
            [
                'filter'     => [
                    'column' => 'canvasSettings',
                    'expr'   => 'like',
                    'value'  => "%'contactclient': $contactclientId,%",
                ],
                'orderBy'    => 'c.name',
                'orderByDir' => 'ASC',
            ]
        );
    }

    /**
     * {@inheritdoc}
     *
     * @return bool|ContactClientEvent
     *
     * @throws \Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException
     */
    protected function dispatchEvent($action, &$entity, $isNew = false, Event $event = null)
    {
        if (!$entity instanceof ContactClient) {
            throw new MethodNotAllowedHttpException(['ContactClient']);
        }

        switch ($action) {
            case 'pre_save':
                $name = ContactClientEvents::PRE_SAVE;
                break;
            case 'post_save':
                $name = ContactClientEvents::POST_SAVE;
                break;
            case 'pre_delete':
                $name = ContactClientEvents::PRE_DELETE;
                break;
            case 'post_delete':
                $name = ContactClientEvents::POST_DELETE;
                break;
            default:
                return null;
        }

        if ($this->dispatcher->hasListeners($name)) {
            if (empty($event)) {
                $event = new ContactClientEvent($entity, $isNew);
                $event->setEntityManager($this->em);
            }

            $this->dispatcher->dispatch($name, $event);

            return $event;
        } else {
            return null;
        }
    }
}
