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
 * Class FileRepository.
 */
class FileRepository extends CommonRepository
{
    /**
     * Gets the number of files (ready/sent by default) on a given date.
     *
     * @param \DateTime|null $date
     * @param                $contactClientId
     * @param array          $statuses
     * @param bool           $test
     *
     * @return int
     */
    public function getCountByDate(
        \DateTime $date = null,
        $contactClientId,
        $statuses = [File::STATUS_READY, File::STATUS_SENT, File::STATUS_ERROR],
        $test = false
    ) {
        $start = clone $date;
        $end   = clone $date;
        $q     = $this->getEntityManager()->getConnection()->createQueryBuilder();
        $q->select('COUNT(*)')
            ->from(MAUTIC_TABLE_PREFIX.$this->getTableName());

        $start->setTime(0, 0, 0);
        $end->setTime(0, 0, 0)->modify('+1 day');
        $timezone = new \DateTimeZone(date_default_timezone_get());
        $start->setTimezone($timezone);
        $end->setTimezone($timezone);

        $q->where(
            $q->expr()->eq('contactclient_id', (int) $contactClientId),
            $q->expr()->gte('date_added', ':start'),
            $q->expr()->lt('date_added', ':end'),
            $q->expr()->eq('test', $test ? 1 : 0)
        );
        $q->setParameter('start', $start->format('Y-m-d H:i:s'));
        $q->setParameter('end', $end->format('Y-m-d H:i:s'));
        $q->andWhere('status IN (\''.implode('\',\'', $statuses).'\')');

        return (int) $q->execute()->fetchColumn();
    }

    /**
     * @return string
     */
    public function getTableAlias()
    {
        return 'f';
    }
}
