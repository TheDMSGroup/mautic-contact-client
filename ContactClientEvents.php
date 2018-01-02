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
     * The mautic.contactclient_token_replacent event is dispatched after a load content.
     *
     * The event listener receives a MauticPlugin\MauticContactClientBundle\Event\ContactClientEvent instance.
     *
     * @var string
     */
    const TOKEN_REPLACEMENT = 'mautic.contactclient_token_replacement';

    /**
     * The mautic.contactclient.on_campaign_trigger_action event is fired when the campaign action triggers.
     *
     * The event listener receives a
     * Mautic\CampaignBundle\Event\CampaignExecutionEvent
     *
     * @var string
     */
    const ON_CAMPAIGN_TRIGGER_ACTION = 'mautic.contactclient.on_campaign_trigger_action';
}
