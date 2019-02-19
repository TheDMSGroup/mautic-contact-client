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

    /**
     * ContactDncCheckEvent constructor.
     *
     * @param Contact|null $contact
     */
    public function __construct(
        Contact $contact = null
    ) {
        $this->contact = $contact;
    }

    /**
     * @return Contact
     */
    public function getContact()
    {
        return $this->contact;
    }
}
