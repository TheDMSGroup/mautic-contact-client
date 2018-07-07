<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Digital Media Solutions, LLC
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticContactClientBundle\Event;

use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\LeadBundle\Entity\Lead;
use Symfony\Component\EventDispatcher\Event;

/**
 * Class ContactLedgerContextEvent.
 */
class ContactLedgerContextEvent extends Event
{
    /** @var Campaign|null */
    protected $campaign;

    /** @var object|null */
    protected $actor;

    /** @var object|null */
    protected $activity;

    /** @var string */
    protected $memo;

    /** @var Lead */
    protected $lead;

    /**
     * ContactLedgerContextEvent constructor.
     *
     * @param Campaign|null $campaign
     * @param string        $actor
     * @param string        $activity
     * @param string        $memo
     * @param Lead|null     $lead
     */
    public function __construct(
        Campaign $campaign = null,
        $actor = null,
        $activity = null,
        $memo = null,
        Lead $lead = null
    ) {
        $this->campaign = $campaign;
        $this->actor    = $actor;
        $this->activity = $activity;
        $this->memo     = $memo;
        $this->lead     = $lead;
    }

    /**
     * @return Campaign|null
     */
    public function getCampaign()
    {
        return $this->campaign;
    }

    /**
     * @return object|null
     */
    public function getActor()
    {
        return $this->actor;
    }

    /**
     * @return object|null
     */
    public function getActivity()
    {
        return $this->activity;
    }

    /**
     * @return string
     */
    public function getMemo()
    {
        return $this->memo;
    }

    /**
     * @return Lead
     */
    public function getLead()
    {
        return $this->lead;
    }
}
