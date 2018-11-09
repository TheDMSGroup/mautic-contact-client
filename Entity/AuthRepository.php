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
 * Class AuthRepository.
 */
class AuthRepository extends CommonRepository
{
    /**
     * Gets all key-value pairs for a contactClient.
     *
     * @param int $contactClientId
     *
     * @return int
     */
    public function getByContactClient($contactClientId)
    {
        $alias = $this->getTableAlias();
        $q     = $this->createQueryBuilder($alias);
        $q->select('partial '.$alias.'.{key, value}')
            ->where(
                $q->expr()->eq($alias.'.contactclient_id', (int) $contactClientId)
            );

        return $q->getQuery()->getArrayResult();
    }

    /**
     * @return string
     */
    public function getTableAlias()
    {
        return 'a';
    }
}
