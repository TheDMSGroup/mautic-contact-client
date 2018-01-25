<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticContactClientBundle\EventListener;

use Doctrine\ORM\EntityManager;
use Mautic\CoreBundle\EventListener\CommonStatsSubscriber;

/**
 * Class StatsSubscriber
 * @package MauticPlugin\MauticContactClientBundle\EventListener
 */
class StatsSubscriber extends CommonStatsSubscriber
{
    /**
     * StatsSubscriber constructor.
     *
     * @param EntityManager $em
     */
    public function __construct(EntityManager $em)
    {
        $this->addContactRestrictedRepositories($em, 'MauticContactClientBundle:Stat');
    }
}
