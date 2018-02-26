<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticContactClientBundle\Event;

use Mautic\CoreBundle\Event\CommonEvent;
use MauticPlugin\MauticContactClientBundle\Entity\ContactClient;

/**
 * Class ContactClientEvent.
 */
class ContactClientEvent extends CommonEvent
{
    /**
     * @param ContactClient $contactclient
     * @param bool|false    $isNew
     */
    public function __construct(ContactClient $contactclient, $isNew = false)
    {
        $this->entity = $contactclient;
        $this->isNew  = $isNew;
    }

    /**
     * Returns the ContactClient entity.
     *
     * @return ContactClient
     */
    public function getContactClient()
    {
        return $this->entity;
    }

    /**
     * Sets the ContactClient entity.
     *
     * @param ContactClient $contactclient
     */
    public function setContactClient(ContactClient $contactclient)
    {
        $this->entity = $contactclient;
    }
}
