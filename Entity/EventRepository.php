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
use Mautic\LeadBundle\Entity\TimelineTrait;

/**
 * Class EventRepository
 * @package MauticPlugin\MauticContactClientBundle\Entity
 */
class EventRepository extends CommonRepository
{
    use TimelineTrait;

    /**
     * Fetch the base event data from the database.
     *
     * @param $contactClientId
     * @param $eventType
     * @param \DateTime|null $dateAdded
     * @return array
     */
    public function getEvents($contactClientId, $eventType = null, \DateTime $dateAdded = null)
    {
        $q = $this->getEntityManager()->getConnection()->createQueryBuilder()
            ->from(MAUTIC_TABLE_PREFIX.'contactclient_events', 'c')
            ->select('c.*');

        $expr = $q->expr()->eq('c.contactclient_id', ':contactClient');
        $q->where($expr)
            ->setParameter('contactClient', (int)$contactClientId);

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
     * @param $contactClientId
     * @param null $contactId
     * @param array $options
     * @return array
     */
    public function getEventsForTimeline($contactClientId, $contactId = null, array $options = [])
    {
        $eventType = null;

        $query = $this->getEntityManager()->getConnection()->createQueryBuilder()
            ->from(MAUTIC_TABLE_PREFIX.'contactclient_events', 'c')
            ->select('c.*');

        $query->where(
            $query->expr()->eq('c.contactclient_id', ':contactClientId')
        )
            ->setParameter('contactClientId', $contactClientId);


        if ($eventType) {
            $query->andWhere(
                $query->expr()->eq('c.type', ':type')
            )
                ->setParameter('type', $eventType);
        }

        if ($contactId) {
            $query->andWhere('c.contact_id = '.(int)$contactId);
        }

        if (isset($options['search']) && $options['search']) {
            $query->andWhere(
                $query->expr()->orX(
                    $query->expr()->like('c.type', $query->expr()->literal('%'.$options['search'].'%')),
                    $query->expr()->like('c.logs', $query->expr()->literal('%'.$options['search'].'%'))
                )
            );
        }

        return $this->getTimelineResults($query, $options, 'c.type', 'c.date_added', [], ['date_added']);
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
