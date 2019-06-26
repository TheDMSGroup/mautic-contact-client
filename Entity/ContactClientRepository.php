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

use Doctrine\DBAL\Query\QueryBuilder;
use Mautic\CoreBundle\Entity\CommonRepository;
use Doctrine\DBAL\Connections\MasterSlaveConnection;

/**
 * Class ContactClientRepository.
 */
class ContactClientRepository extends CommonRepository
{
    /**
     * Get a list of entities.
     *
     * @param array $args
     *
     * @return \Doctrine\ORM\Tools\Pagination\Paginator
     */
    public function getEntities(array $args = [])
    {
        $alias = $this->getTableAlias();

        $q = $this->_em
            ->createQueryBuilder()
            ->select($alias)
            ->from('MauticContactClientBundle:ContactClient', $alias, $alias.'.id');

        if (empty($args['iterator_mode'])) {
            $q->leftJoin($alias.'.category', 'c');
        }

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
        return 'f';
    }

    /**
     * @return array
     */
    public function getSearchCommands()
    {
        return $this->getStandardSearchCommands();
    }

    /**
     * @return array
     */
    public function getContactClientList($currentId)
    {
        $alias = $this->getTableAlias();
        $q     = $this->createQueryBuilder($alias);
        $q->select('partial '.$alias.'.{id, name, description}')->orderBy($alias.'.name');

        return $q->getQuery()->getArrayResult();
    }

    /**
     * @param \Doctrine\ORM\QueryBuilder|\Doctrine\DBAL\Query\QueryBuilder $q
     * @param                                                              $filter
     *
     * @return array
     */
    protected function addCatchAllWhereClause($q, $filter)
    {
        $alias = $this->getTableAlias();

        return $this->addStandardCatchAllWhereClause(
            $q,
            $filter,
            [$alias.'.name', $alias.'.website', $alias.'.description']
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
        return [
            [$this->getTableAlias().'.name', 'ASC'],
        ];
    }

    /**
     * @param $clientId
     *
     * @return array
     */
    public function getPendingEventsData($clientId, $eventIds)
    {
        $query = $this->slaveQueryBuilder();
        $query->select(
            'el.campaign_id, 
            #cp.name, 
            el.event_id,
            #ce.name, 
            COUNT(el.lead_id) as count'
        );
        $query->from(MAUTIC_TABLE_PREFIX.'campaign_lead_event_log el');
        $query->join('el', 'campaigns', 'cp', 'el.campaign_id = cp.id');
        $query->join('el', 'campaign_events', 'ce', 'el.event_id = ce.id');
        $query->where('el.is_scheduled = 1');
        $query->andWhere ('el.event_id IN :eventIds');
        $query->andWhere('el.trigger_date <= NOW()');
        $query->andWhere('el.trigger_date > DATE_ADD(NOW(), INTERVAL -2 DAY)');
        $query->groupBy('el.event_id');
        $query->setParameter('eventIds', $eventIds);
        $data = $query->execute()->fetchAll();


        // $data[] = [1,'CampaignName1', 1, 'EventName1', 5434];
        // $data[] = [1,'CampaignName1', 2, 'EventName2', 145];
        // $data[] = [2,'CampaignName1', 3, 'EventName3', 9746];
        // $data[] = [2,'CampaignName1', 4, 'EventName4', 354];

        return $data;
    }

    /**
     * Create a DBAL QueryBuilder preferring a slave connection if available.
     *
     * @return QueryBuilder
     */
    private function slaveQueryBuilder()
    {
        /** @var Connection $connection */
        $connection = $this->_em->getConnection();
        if ($connection instanceof MasterSlaveConnection) {
            // Prefer a slave connection if available.
            $connection->connect('slave');
        }

        return new QueryBuilder($connection);
    }
}
