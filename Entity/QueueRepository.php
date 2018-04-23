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
 * Class QueueRepository.
 */
class QueueRepository extends CommonRepository
{
    /**
     * Fetch Queues from the database for a client.
     *
     * @param int      $contactClientId
     * @param int|null $file
     *
     * @return mixed
     */
    public function getQueues(int $contactClientId, int $file = null)
    {
        $q = $this->createQueryBuilder('q');

        $expr = $q->expr()->andX(
            $q->expr()->eq('IDENTITY(q.contactclient)', (int) $contactClientId)
        );

        if ($file) {
            $expr->add(
                $q->expr()->gte('q.file', ':file')
            );
            $q->setParameter('file', (int) $file);
        }

        return $q->getQuery()->getArrayResult();
    }
}
