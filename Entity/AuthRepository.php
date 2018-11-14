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
     * Gets all token key-value pairs for a contactClient that were previously captured by succesful
     * auth requests and persisted for re-use.
     *
     * @param      $contactClientId
     * @param      $operationId
     * @param bool $test
     *
     * @return array
     */
    public function getPreviousPayloadAuthTokensByContactClient($contactClientId, $operationId = null, $test = false)
    {
        $result = [];
        $q      = $this->getEntityManager()->getConnection()->createQueryBuilder();
        $q->select('a.operation, a.type, a.field, a.val')
            ->from(MAUTIC_TABLE_PREFIX.'contactclient_auth', 'a')
            ->where(
                $q->expr()->eq('a.contactclient_id', (int) $contactClientId),
                $q->expr()->eq('a.test', (int) $test)
            );

        if ($operationId) {
            $q->andWhere(
                $q->expr()->eq('a.operation', (int) $operationId)
            );
        }

        foreach ($q->execute()->fetchAll() as $row) {
            $token          = 'payload.operations.'.$row['operation'].'.response.'.$row['type'].'.'.$row['field'];
            $result[$token] = $row['val'];
        }

        return $result;
    }

    /**
     * @param      $contactClientId
     * @param      $operationId
     * @param bool $test
     *
     * @return array
     */
    public function flushPreviousAuthTokens($contactClientId, $operationId, $test)
    {
        $q = $this->createQueryBuilder('a');
        $q->delete()
            ->where(
                $q->expr()->eq('a.contactClient', (int) $contactClientId),
                $q->expr()->eq('a.operation', (int) $operationId),
                $q->expr()->eq('a.test', (int) $test)
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
