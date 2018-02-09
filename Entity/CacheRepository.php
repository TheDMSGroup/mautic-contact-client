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
use Mautic\LeadBundle\Entity\Lead as Contact;
use MauticPlugin\MauticContactClientBundle\Entity\ContactClient;

/**
 * Trait CacheEntityRepositoryTrait
 * @package MauticPlugin\MauticContactClientBundle\Entity
 */
class CacheRepository extends CommonRepository
{

    /**
     * Bitwise operators for $matchingPattern.
     */
    const MATCHING_EXPLICIT = 1;
    const MATCHING_EMAIL = 2;
    const MATCHING_PHONE = 4;
    const MATCHING_MOBILE = 8;
    const MATCHING_ADDRESS = 16;
    const MATCHING_UTM_SOURCE = 21;

    /**
     * Bitwise operators for $scope.
     */
    const SCOPE_GLOBAL = 1;
    const SCOPE_CATEGORY = 2;

    /**
     * Given a matching pattern and a contact, discern if there is a match in the cache.
     * Used for exclusivity and duplicate checking.
     *
     * @param Contact $contact
     * @param int $matchingPattern
     * @param int $scope
     * @param int $durationSeconds
     * @param array $args
     * @return bool|\Doctrine\ORM\Tools\Pagination\Paginator
     * @throws \Exception
     */
    public function findDuplicate(Contact $contact, $matchingPattern = 1, $scope = 1, $durationSeconds = 86400, $args = [])
    {

        // @todo - Match explicit
        if ($matchingPattern & self::MATCHING_EXPLICIT) {

        }
        // @todo - Match email
        // @todo - Match phone
        // @todo - Match mobile
        // @todo - Match address
        // @todo - Match utm_source (for limits only)

        // @todo - Scope Global
        // @todo - Scope Category

        // Duration
        if ($durationSeconds) {
            $endDate = new \DateTime();
            $endDate->add(new \DateInterval('PT' . $durationSeconds .'S'));
            $filters[] = [
                'column' => 'e.date_added',
                'expr' => '<',
                'value' => $endDate,
            ];
        }

        return parent::getEntities(
            array_merge(
                [
                    'limit' => 1,
                    'filter' => ['force' => $filters],
                    'hydration_mode' => 'HYDRATE_ARRAY',
                    'ignore_paginator' => true,
                    'ignore_children' => true,
                ],
                $args
            )
        );
    }

    public function findExclusive(Contact $contact, ContactClient $contactClient, $args = [])
    {

    }
}