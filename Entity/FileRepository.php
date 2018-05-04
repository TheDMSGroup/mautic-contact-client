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

use Mautic\CoreBundle\Entity\CommonRepository;

/**
 * Class FileRepository.
 */
class FileRepository extends CommonRepository
{
    /**
     * Gets the number of files (ready/sent by default) on a given date.
     *
     * @param \DateTime|null $date The date of the client, including client timezone.
     * @param                $contactClientId
     * @param array          $statuses
     *
     * @return bool|string
     */
    public function getCountByDate(
        \DateTime $date = null,
        $contactClientId,
        $statuses = [File::STATUS_READY, File::STATUS_SENT]
    ) {
        $start = $end = $date;
        $q = $this->getEntityManager()->getConnection()->createQueryBuilder();
        $q->select('count(*)')
            ->from(MAUTIC_TABLE_PREFIX.$this->getTableName());

        $start->setTime(0,0,0);
        $start->setTime(0,0,0)->modify('+1 days');
        // Convert range to the system timezone. Assume database is the same.
        // $tz = new \DateTimeZone(date_default_timezone_get());
        // $start->setTimezone($tz);
        // $end->setTimezone($tz);

        $q->where(
            $q->expr()->eq('contactclient_id', (int) $contactClientId),
            $q->expr()->gte('date_added', (int) $start->format('U')),
            $q->expr()->lt('date_added', (int) $end->format('U')),
            $q->expr()->in('status', ':statuses')
        );
        $q->setParameter('statuses', $statuses);

        return $q->execute()->fetchColumn();
    }

    /**
     * @return string
     */
    public function getTableAlias()
    {
        return 'f';
    }
}
