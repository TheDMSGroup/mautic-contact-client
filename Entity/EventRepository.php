<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Digital Media Solutions, LLC
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticContactClientBundle\Entity;

use Doctrine\ORM\Tools\Pagination\Paginator;
use Mautic\CoreBundle\Entity\CommonRepository;

/**
 * Class EventRepository.
 */
class EventRepository extends CommonRepository
{
    /**
     * Fetch the base event data from the database.
     *
     * @param            $contactClientId
     * @param            $eventType
     * @param array|null $dateRange
     *
     * @return array
     */
    public function getEvents($contactClientId, $eventType = null, $dateRange = [])
    {
        $q = $this->getEntityManager()->getConnection()->createQueryBuilder()
            ->from(MAUTIC_TABLE_PREFIX.'contactclient_events', 'c')
            ->select('c.*');

        $expr = $q->expr()->eq('c.contactclient_id', ':contactClient');
        $q->where($expr)
            ->setParameter('contactClient', (int) $contactClientId);

        if (isset($dateRange['dateFrom'])) {
            if (!($dateRange['dateFrom'] instanceof \DateTime)) {
                try {
                    $dateRange['datefrom'] = new \DateTime($dateRange['dateFrom']);
                    $dateRange['dateFrom']->setTime(0, 0, 0);
                } catch (\Exception $e) {
                    $dateRange['datefrom'] = new \DateTime('midnight -1 month');
                }
            }
            $q->andWhere(
                $q->expr()->gte('c.date_added', ':dateFrom')
            )
                ->setParameter('dateFrom', $dateRange['dateFrom']->format('Y-m-d H:i:s'));
        }
        if (isset($dateRange['dateTo'])) {
            if (!($dateRange['dateTo'] instanceof \DateTime)) {
                try {
                    $dateRange['datefrom'] = new \DateTime($dateRange['dateTo']);
                    $dateRange['dateTo']->setTime(0, 0, 0);
                } catch (\Exception $e) {
                    $dateRange['datefrom'] = new \DateTime('midnight');
                }
                $dateRange['dateTo']->modify('+1 day');
            }
            $q->andWhere(
                $q->expr()->lt('c.date_added', ':dateTo')
            )
                ->setParameter('dateTo', $dateRange['dateTo']->format('Y-m-d H:i:s'));
        }

        if ($eventType) {
            $q->andWhere(
                $q->expr()->eq('c.type', ':type')
            )
                ->setParameter('type', $eventType);
        }

        return $q->execute()->fetchAll();
    }

    /**
     * @param       $contactClientId
     * @param array $options
     * @param bool  $countOnly
     *
     * @return array
     */
    public function getEventsForTimeline($contactClientId, array $options = [], $countOnly = false)
    {
        $query = $this->getEntityManager()->getConnection()->createQueryBuilder()
            ->from(MAUTIC_TABLE_PREFIX.'contactclient_events', 'c')
            ->leftJoin('c', MAUTIC_TABLE_PREFIX.'contactclient_stats', 's', 'c.contact_id = s.contact_id AND c.contactclient_id = s.contactclient_id');

        if ($countOnly) {
            $query->select('COUNT(c.id)');
        } else {
            $query->select('c.*, s.utm_source');
        }

        $query->where('c.contactclient_id = :contactClientId')
            ->setParameter('contactClientId', $contactClientId);

        if (isset($options['search']) && !empty(trim($options['search']))) {
            if (is_numeric(trim($options['search']))) {
                $query->andWhere(
                    $query->expr()->orX(
                        'c.contact_id = :search',
                        's.utm_source = :search'
                    )
                )
                ->setParameter('search', (int) $options['search']);
            } else {
                $query->andWhere(
                    $query->expr()->orX(
                        'c.type = :search',
                        'c.message LIKE :wildcard'
                    )
                )
                ->setParameter('search', trim($options['search']))
                ->setParameter('wildcard', '%'.trim($options['search']).'%');
            }
        }

        if (isset($options['dateFrom'])) {
            $query->andWhere('c.date_added >= :dateFrom')
                ->setParameter('dateFrom', $options['dateFrom']->format('Y-m-d H:i:s'));
        }
        if (isset($options['dateTo'])) {
            $query->andWhere('c.date_added < :dateTo')
                ->setParameter('dateTo', $options['dateTo']->format('Y-m-d H:i:s'));
        }

        if (isset($options['order']) && is_array($options['order']) && 2 == count($options['order'])) {
            list($orderBy, $orderByDir) = array_values($options['order']);
            if ($orderBy && $orderByDir) {
                if ('utm_source' !== $orderBy) {
                    $query->orderBy('c.'.$orderBy, $orderByDir);
                } else {
                    $query->orderBy('s.'.$orderBy, $orderByDir);
                }
            }
        }

        if (!empty($options['limit'])) {
            $query->setMaxResults($options['limit']);
            if (!empty($options['start'])) {
                $query->setFirstResult($options['start']);
            }
        }

        // Not currently needed.
        // $query->groupBy('c.id');

        $results = $query->execute()->fetchAll();

        if (!empty($options['paginated'])) {
            // Get a total count along with results
            $query->resetQueryParts(['select', 'orderBy', 'join']) //groupBy
//                ->setFirstResult(null)
//                ->setMaxResults(null)
                ->select('COUNT(*)');

            $counter = $query->execute();
            $total = $counter->fetchColumn();

            return [
                'total'   => $total,
                'results' => $results,
            ];
        }

        return $results;
    }

    /**
     * @param       $contactClientId
     * @param int   $contactId
     * @param array $options
     *
     * @return array
     */
    public function getEventsForTimelineExport($contactClientId, array $options = [], $count)
    {
        $query = $this->getEntityManager()->getConnection()->createQueryBuilder()
            ->from(MAUTIC_TABLE_PREFIX.'contactclient_events', 'c')
            ->join('c', MAUTIC_TABLE_PREFIX.'contactclient_stats', 's', 'c.contact_id=s.contact_id AND c.contactclient_id=s.contactclient_id');
        if ($count) {
            $query->select('COUNT(c.id) as count');
        } else {
            $query->select('c.type, c.message, c.logs, c.date_added, c.contact_id, s.utm_source');
        }

        $query->where(
            $query->expr()->eq('c.contactclient_id', ':contactClientId')
        )
            ->setParameter('contactClientId', $contactClientId);

        if (!empty($options['fromDate']) && !empty($options['toDate'])) {
            $query->andWhere('c.date_added BETWEEN :dateFrom AND :dateTo')
                ->setParameter('dateFrom', $options['fromDate']->format('Y-m-d H:i:s'))
                ->setParameter('dateTo', $options['toDate']->format('Y-m-d H:i:s'));
        } elseif (!empty($options['fromDate'])) {
            $query->andWhere($query->expr()->gte('c.date_added', ':dateFrom'))
                ->setParameter('dateFrom', $options['fromDate']->format('Y-m-d H:i:s'));
        } elseif (!empty($options['toDate'])) {
            $query->andWhere($query->expr()->lte('c.date_added', ':dateTo'))
                ->setParameter('dateTo', $options['toDate']->format('Y-m-d H:i:s'));
        }
        $query->orderBy('c.date_added', 'DESC');

        if (!empty($options['limit'])) {
            $query->setMaxResults($options['limit']);
            if (!empty($options['start'])) {
                $query->setFirstResult($options['start']);
            }
        }

        $results = $query->execute()->fetchAll();

        return $results;
    }

    /**
     * Get a list of entities.
     *
     * @param array $args
     *
     * @return Paginator
     */
    public function getEntities(array $args = [])
    {
        $alias = $this->getTableAlias();

        $q = $this->_em
            ->createQueryBuilder()
            ->select($alias)
            ->from('MauticContactClientBundle:Event', $alias, $alias.'.id');

        $args['qb'] = $q;

        return parent::getEntities($args);
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getTableAlias()
    {
        return 'c';
    }

    /**
     * @return array
     */
    public function getSearchCommands()
    {
        return $this->getStandardSearchCommands();
    }

    /**
     * @param \Doctrine\ORM\QueryBuilder|\Doctrine\DBAL\Query\QueryBuilder $q
     * @param                                                              $filter
     *
     * @return array
     */
    protected function addCatchAllWhereClause($q, $filter)
    {
        return $this->addStandardCatchAllWhereClause(
            $q,
            $filter,
            ['c.type', 'c.logs']
        );
    }

    /**
     * @param \Doctrine\ORM\QueryBuilder|\Doctrine\DBAL\Query\QueryBuilder $q
     * @param                                                              $filter
     *
     * @return array
     */
    protected function addSearchCommandWhereClause($q, $filter)
    {
        return $this->addStandardSearchCommandWhereClause($q, $filter);
    }

    /**
     * @return array
     */
    protected function getDefaultOrder()
    {
        return ['date_added', 'DESC'];
    }
}
