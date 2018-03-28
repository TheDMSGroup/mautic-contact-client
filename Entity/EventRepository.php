<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc
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
     * @param                $contactClientId
     * @param                $eventType
     * @param \DateTime|null $dateAdded
     *
     * @return array
     */
    public function getEvents($contactClientId, $eventType = null, \DateTime $dateAdded = null)
    {
        $q = $this->getEntityManager()->getConnection()->createQueryBuilder()
            ->from(MAUTIC_TABLE_PREFIX.'contactclient_events', 'c')
            ->select('c.*');

        $expr = $q->expr()->eq('c.contactclient_id', ':contactClient');
        $q->where($expr)
            ->setParameter('contactClient', (int) $contactClientId);

        if ($dateAdded) {
            $expr->add(
                $q->expr()->gte('c.date_added', ':dateAdded')
            );
            $q->setParameter('dateAdded', $dateAdded);
        }

        if ($eventType) {
            $expr->add(
                $q->expr()->eq('c.type', ':type')
            );
            $q->setParameter('type', $eventType);
        }

        return $q->execute()->fetchAll();
    }

    /**
     * @param       $contactClientId
     * @param int   $contactId
     * @param array $options
     *
     * @return array
     */
    public function getEventsForTimeline($contactClientId, $contactId = null, array $options = [])
    {
        $query = $this->getEntityManager()->getConnection()->createQueryBuilder()
            ->from(MAUTIC_TABLE_PREFIX.'contactclient_events', 'c')
            ->select('c.*');

        $query->where(
            $query->expr()->eq('c.contactclient_id', ':contactClientId')
        )
            ->setParameter('contactClientId', $contactClientId);

        if ($contactId) {
            $query->andWhere('c.contact_id = '.(int) $contactId);
        }

        if (isset($options['search']) && $options['search']) {
            if (isset($options['logs']) && $options['logs']) {
                $expr = $query->expr()->orX(
                    $query->expr()->like('c.type', $query->expr()->literal('%'.$options['search'].'%')),
                    $query->expr()->like('c.message', $query->expr()->literal('%'.$options['search'].'%')),
                    $query->expr()->like('c.logs', $query->expr()->literal('%'.$options['search'].'%'))
                );
            } else {
                $expr = $query->expr()->orX(
                    $query->expr()->like('c.type', $query->expr()->literal('%'.$options['search'].'%')),
                    $query->expr()->like('c.message', $query->expr()->literal('%'.$options['search'].'%'))
                );
            }
            $query->andWhere($expr);
        }

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

        if (isset($options['order']) && !empty($options['order'])) {
            list($orderBy, $orderByDir) = $options['order'];
            $query->orderBy('c.'.$orderBy, $orderByDir);
        }

        if (!empty($options['limit'])) {
            $query->setMaxResults($options['limit']);
            if (!empty($options['start'])) {
                $query->setFirstResult($options['start']);
            }
        }

        $results = $query->execute()->fetchAll();

        if (!empty($options['paginated'])) {
            // Get a total count along with results
            $query->resetQueryParts(['select', 'orderBy'])
                ->setFirstResult(null)
                ->setMaxResults(null)
                ->select('count(*)');

            $total = $query->execute()->fetchColumn();

            return [
                'total'   => $total,
                'results' => $results,
            ];
        }

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
     * @return string
     */
    protected function getDefaultOrder()
    {
        return [
            [$this->getTableAlias().'.addedDate', 'ASC'],
        ];
    }
}
