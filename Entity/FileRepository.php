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
     * Get the files for a client.
     *
     * @param      $contactClientId
     * @param null $fromDate
     * @param null $toDate
     *
     * @return array
     */
    public function getFiles($contactClientId, $fromDate = null, $toDate = null)
    {

        $q = $this->createQueryBuilder('f');

        $expr = $q->expr()->andX(
            $q->expr()->eq('IDENTITY(f.contactclient)', (int) $contactClientId)
        );

        if ($fromDate) {
            $expr->add(
                $q->expr()->gte('f.dateAdded', ':fromDate')
            );
            $q->setParameter('fromDate', $fromDate);
        }
        if ($toDate) {
            $expr->add(
                $q->expr()->lte('f.dateAdded', ':toDate')
            );
            $q->setParameter('toDate', $toDate);
        }

        return $q->getQuery()->getArrayResult();
    }
}
