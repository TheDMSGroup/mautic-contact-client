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

use Mautic\LeadBundle\Entity\Lead as Contact;
use Symfony\Component\EventDispatcher\Event;

/**
 * Class ContactDncCheckEvent.
 */
class ContactDncCheckEvent extends Event
{
    /** @var Contact */
    protected $contact;

    /** @var string */
    protected $channel = '';

    /** @var array */
    protected $dncEntries = [];

    /**
     * ContactDncCheckEvent constructor.
     *
     * @param Contact|null $contact
     * @param string       $channel
     * @param array        $dncEntries
     */
    public function __construct(
        Contact $contact = null,
        $channel = '',
        &$dncEntries = []
    ) {
        $this->contact    = $contact;
        $this->channel    = $channel;
        $this->dncEntries = &$dncEntries;
    }

    /**
     * @return Contact
     */
    public function getContact()
    {
        return $this->contact;
    }

    /**
     * @return Contact
     */
    public function getLead()
    {
        return $this->contact;
    }

    /**
     * @return string
     */
    public function getChannel()
    {
        return $this->channel;
    }

    /**
     * @return array
     */
    public function getDncEntries()
    {
        return $this->dncEntries;
    }

    /**
     * @param $dncEntries
     *
     * @return $this
     */
    public function setDncEntries($dncEntries)
    {
        $this->dncEntries = $dncEntries;

        return $this;
    }
}
