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
     * @param \DateTime|null $date
     * @param                $contactClientId
     * @param array          $statuses
     *
     * @return bool|string
     *
     * @throws \Exception
     */
    public function getCountByDate(
        \DateTime $date = null,
        $contactClientId,
        $statuses = [File::STATUS_READY, File::STATUS_SENT]
    ) {
        $q = $this->getEntityManager()->getConnection()->createQueryBuilder();

        $q->select('count(*)')
            ->from(MAUTIC_TABLE_PREFIX.$this->getTableName(), $this->getTableAlias());

        $q->where(
            $q->expr()->gte('contact_client_id', ':contact_client_id'),
            $q->expr()->gte('date_added', ':oldest'),
            $q->expr()->lt('date_added', ':newest'),
            $q->expr()->in('status', $statuses)
        );
        $oldest = $date->setTime(0, 0, 0, 0);
        $newest = $date->add(new \DateInterval('P1D'));
        $q->setParameter('contact_client_id', $contactClientId);
        $q->setParameter('oldest', $oldest);
        $q->setParameter('newest', $newest);

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
