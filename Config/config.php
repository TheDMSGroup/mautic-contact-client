<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

return [
    'name'        => 'Mautic Contact Client',
    'description' => 'Send contacts to third party APIs or enhance your contacts without code.',
    'version'     => '0.3',
    'author'      => 'Mautic',

    'routes' => [
        'main' => [
            'mautic_contactclient_index' => [
                'path'       => '/contactclient/{page}',
                'controller' => 'MauticContactClientBundle:ContactClient:index',
            ],
            'mautic_contactclient_action' => [
                'path'       => '/contactclient/{objectAction}/{objectId}',
                'controller' => 'MauticContactClientBundle:ContactClient:execute',
            ],
            'mautic_contactclient_timeline_action' => [
                'path'       => '/contactclient/timeline/{contactClientId}',
                'controller' => 'MauticContactClientBundle:Timeline:index',
                'requirements' => [
                    'contactClientId' => '\d+',
                ],
            ],
        ],
    ],

    'services' => [
        'events' => [
            'mautic.contactclient.subscriber.stat' => [
                'class'     => 'MauticPlugin\MauticContactClientBundle\EventListener\StatSubscriber',
                'arguments' => [
                    'mautic.contactclient.model.contactclient',
                ],
            ],
            'mautic.contactclient.subscriber.contactclient' => [
                'class'     => 'MauticPlugin\MauticContactClientBundle\EventListener\ContactClientSubscriber',
                'arguments' => [
                    'router',
                    'mautic.helper.ip_lookup',
                    'mautic.core.model.auditlog',
                    'mautic.page.model.trackable',
                    'mautic.page.helper.token',
                    'mautic.asset.helper.token',
                    'mautic.form.helper.token',
                    'mautic.contactclient.model.contactclient',
                ],
            ],
            'mautic.contactclient.stats.subscriber' => [
                'class'     => 'MauticPlugin\MauticContactClientBundle\EventListener\StatsSubscriber',
                'arguments' => [
                    'doctrine.orm.entity_manager',
                ],
            ],
        ],
        'forms' => [
            'mautic.contactclient.form.type.contactclientshow_list' => [
                'class'     => 'MauticPlugin\MauticContactClientBundle\Form\Type\ContactClientShowType',
                'arguments' => 'router',
                'alias'     => 'contactclientshow_list',
            ],
            'mautic.contactclient.form.type.contactclient_list' => [
                'class'     => 'MauticPlugin\MauticContactClientBundle\Form\Type\ContactClientListType',
                'arguments' => 'mautic.contactclient.model.contactclient',
                'alias'     => 'contactclient_list',
            ],
            'mautic.contactclient.form.type.contactclient' => [
                'class'     => 'MauticPlugin\MauticContactClientBundle\Form\Type\ContactClientType',
                'alias'     => 'contactclient',
                'arguments' => 'mautic.security',
            ],
        ],
        'models' => [
            'mautic.contactclient.model.contactclient' => [
                'class'     => 'MauticPlugin\MauticContactClientBundle\Model\ContactClientModel',
                'arguments' => [
                    'mautic.form.model.form',
                    'mautic.page.model.trackable',
                    'mautic.helper.templating',
                    'event_dispatcher',
                    'mautic.lead.model.lead',
                ],
            ],
            'mautic.contactclient.model.apipayload' => [
                'class'     => 'MauticPlugin\MauticContactClientBundle\Model\ApiPayload',
                'arguments' => [
                    'mautic.lead.model.lead',
                ],
            ],
        ],
        'integrations' => [
            'mautic.contactclient.integration' => [
                'class'     => 'MauticPlugin\MauticContactClientBundle\Integration\ClientIntegration',
                'arguments' => [
                ],
            ],
        ],
        'other' => [
            'mautic.contactclient.helper.token' => [
                'class'     => 'MauticPlugin\MauticContactClientBundle\Helper\TokenHelper',
                'arguments' => [
                    'mautic.contactclient.model.contactclient',
                    'router',
                ],
            ],
            'mautic.contactclient.guzzle.client' => [
                'class' => 'GuzzleHttp\Client',
            ],
            'mautic.contactclient.service.transport' => [
                'class'     => 'MauticPlugin\MauticContactClientBundle\Services\Transport',
                'arguments' => [
                    'mautic.contactclient.guzzle.client',
                ],
            ],
        ],
    ],

    'menu' => [
        'main' => [
            'mautic.contactclient' => [
                'route'     => 'mautic_contactclient_index',
                'access'    => 'plugin:contactclient:items:view',
                'id'        => 'mautic_contactclient_root',
                'iconClass' => 'fa-cloud-upload',
                'priority'  => 35,
                'checks'    => [
                    'integration' => [
                        'Client' => [
                            'enabled' => true,
                        ],
                    ],
                ],
            ],
        ],
    ],

    'categories' => [
        'plugin:contactclient' => 'mautic.contactclient',
    ],
];
