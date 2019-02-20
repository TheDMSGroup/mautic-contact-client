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

    /** @var array */
    protected $channels = [];

    /**
     * ContactDncCheckEvent constructor.
     *
     * @param Contact|null $contact
     * @param array        $channels
     */
    public function __construct(
        Contact $contact = null,
        $channels = []
    ) {
        $this->contact  = $contact;
        $this->channels = $channels;
    }

    /**
     * @return Contact
     */
    public function getContact()
    {
        return $this->contact;
    }

    /**
     * @return array
     */
    public function getChannels()
    {
        return $this->channels;
    }
}
