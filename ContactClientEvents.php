<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticContactClientBundle;

/**
 * Class ContactClientEvents.
 *
 * Events available for MauticContactClientBundle
 */
final class ContactClientEvents
{
    /**
     * The mautic.contactclient_pre_save event is dispatched right before a contactclient is persisted.
     *
     * The event listener receives a MauticPlugin\MauticContactClientBundle\Event\ContactClientEvent instance.
     *
     * @var string
     */
    const PRE_SAVE = 'mautic.contactclient_pre_save';

    /**
     * The mautic.contactclient_post_save event is dispatched right after a contactclient is persisted.
     *
     * The event listener receives a MauticPlugin\MauticContactClientBundle\Event\ContactClientEvent instance.
     *
     * @var string
     */
    const POST_SAVE = 'mautic.contactclient_post_save';

    /**
     * The mautic.contactclient_pre_delete event is dispatched before a contactclient is deleted.
     *
     * The event listener receives a MauticPlugin\MauticContactClientBundle\Event\ContactClientEvent instance.
     *
     * @var string
     */
    const PRE_DELETE = 'mautic.contactclient_pre_delete';

    /**
     * The mautic.contactclient_post_delete event is dispatched after a contactclient is deleted.
     *
     * The event listener receives a MauticPlugin\MauticContactClientBundle\Event\ContactClientEvent instance.
     *
     * @var string
     */
    const POST_DELETE = 'mautic.contactclient_post_delete';

    /**
     * The mautic.contactclient_timeline_on_generate event is dispatched when generating a timeline view.
     *
     * The event listener receives a
     * MauticPlugin\MauticContactClientBundle\Event\LeadTimelineEvent instance.
     *
     * @var string
     */
    const TIMELINE_ON_GENERATE = 'mautic.contactclient_timeline_on_generate';
}
