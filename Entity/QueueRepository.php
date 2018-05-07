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
     * @return string
     */
    public function getTableAlias()
    {
        return 'q';
    }

    /**
     * @param array $ids
     */
    public function deleteEntitiesById(array $ids)
    {
        if (!count($ids)) {
            return;
        }

        $q = $this->_em->getConnection()->createQueryBuilder();
        $q->delete(MAUTIC_TABLE_PREFIX.$this->getTableName());
        $q->where(
            $q->expr()->in('id', $ids)
        );
        $q->execute();
    }
}
