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

use Mautic\CoreBundle\Entity\CommonRepository;

/**
 * Class StatRepository.
 */
class StatRepository extends CommonRepository
{
    /**
     * Fetch the base stat data from the database.
     *
     * @param      $contactClientId
     * @param      $type
     * @param null $fromDate
     * @param null $toDate
     *
     * @return array
     */
    public function getStats($contactClientId, $type, $fromDate = null, $toDate = null)
    {
        $q = $this->createQueryBuilder('s');

        $expr = $q->expr()->andX(
            $q->expr()->eq('IDENTITY(s.contactclient)', (int) $contactClientId),
            $q->expr()->eq('s.type', ':type')
        );

        if ($fromDate) {
            $expr->add(
                $q->expr()->gte('s.dateAdded', ':fromDate')
            );
            $q->setParameter('fromDate', $fromDate);
        }
        if ($toDate) {
            $expr->add(
                $q->expr()->lte('s.dateAdded', ':toDate')
            );
            $q->setParameter('toDate', $toDate);
        }

        $q->where($expr)
            ->setParameter('type', $type);

        return $q->getQuery()->getArrayResult();
    }

    /**
     * @param                $contactClientId
     * @param \DateTime|null $dateFrom
     * @param \DateTime|null $dateTo
     * @param string|null    $type
     *
     * @return array
     */
    public function getSourcesByClient(
        $contactClientId,
        \DateTime $dateFrom = null,
        \DateTime $dateTo = null,
        $type = null
    ) {
        $q = $this->getEntityManager()->getConnection()->createQueryBuilder();

        $q->select('distinct(s.utm_source)')
            ->from(MAUTIC_TABLE_PREFIX.'contactclient_stats', 's')
            ->where(
                $q->expr()->eq('s.contactclient_id', (int) $contactClientId)
            );

        if (empty($type)) {
            $q->andWhere('s.type !=""');
        } elseif ('revenue' == $type) {
            $q->andWhere('s.type ="converted"');
        } else {
            $q->andWhere('s.type = :type')
                ->setParameter('type', $type);
        }

        if ($dateFrom && $dateTo) {
            $q->andWhere('s.date_added BETWEEN FROM_UNIXTIME(:dateFrom) AND FROM_UNIXTIME(:dateTo)')
                ->setParameter('dateFrom', $dateFrom->getTimestamp(), \PDO::PARAM_INT)
                ->setParameter('dateTo', $dateTo->getTimestamp(), \PDO::PARAM_INT);
        }

        $utmSources = [];
        foreach ($q->execute()->fetchAll() as $row) {
            $utmSources[] = $row['utm_source'];
        }

        return $utmSources;
    }
}
