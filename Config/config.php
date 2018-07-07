<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Digital Media Solutions, LLC
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

return [
    'name'        => 'Contact Client',
    'description' => 'Send contacts to third party APIs or enhance your contacts without code.',
    'version'     => '1.0',
    'author'      => 'Mautic',

    'routes' => [
        'main' => [
            'mautic_contactclient_index'           => [
                'path'       => '/contactclient/{page}',
                'controller' => 'MauticContactClientBundle:ContactClient:index',
            ],
            'mautic_contactclient_action'          => [
                'path'       => '/contactclient/{objectAction}/{objectId}',
                'controller' => 'MauticContactClientBundle:ContactClient:execute',
            ],
            'mautic_contactclient_timeline_action' => [
                'path'         => '/s/contactclient/timeline/{contactClientId}/{page}',
                'controller'   => 'MauticContactClientBundle:Timeline:index',
                'requirements' => [
                    //'contactClientId' => '\d+',
                    'objectId' => '\d+',
                ],
            ],
            'mautic_contactclient_timeline_export' => [
                'path'       => '/contactclient/timeline/export/{contactClientId}',
                'controller' => 'MauticContactClientBundle:Timeline:exportTimeline',
            ],
            'mautic_contactclient_timeline_file'   => [
                'path'       => '/contactclient/timeline/file/{contactClientId}/{fileId}',
                'controller' => 'MauticContactClientBundle:Timeline:file',
            ],
        ],
    ],

    'services' => [
        'events'       => [
            'mautic.contactclient.subscriber.stat'          => [
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
            'mautic.contactclient.stats.subscriber'         => [
                'class'     => 'MauticPlugin\MauticContactClientBundle\EventListener\StatsSubscriber',
                'arguments' => [
                    'doctrine.orm.entity_manager',
                ],
            ],
        ],
        'forms'        => [
            'mautic.contactclient.form.type.contactclientshow_list' => [
                'class'     => 'MauticPlugin\MauticContactClientBundle\Form\Type\ContactClientShowType',
                'arguments' => 'router',
                'alias'     => 'contactclientshow_list',
            ],
            'mautic.contactclient.form.type.contactclient_list'     => [
                'class'     => 'MauticPlugin\MauticContactClientBundle\Form\Type\ContactClientListType',
                'arguments' => 'mautic.contactclient.model.contactclient',
                'alias'     => 'contactclient_list',
            ],
            'mautic.contactclient.form.type.contactclient'          => [
                'class'     => 'MauticPlugin\MauticContactClientBundle\Form\Type\ContactClientType',
                'alias'     => 'contactclient',
                'arguments' => 'mautic.security',
            ],
            'mautic.contactclient.form.type.chartfilter'            => [
                'class'     => 'MauticPlugin\MauticContactClientBundle\Form\Type\ChartFilterType',
                'arguments' => 'mautic.factory',
                'alias'     => 'chartfilter',
            ],
        ],
        'models'       => [
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
            'mautic.contactclient.model.apipayload'    => [
                'class'     => 'MauticPlugin\MauticContactClientBundle\Model\ApiPayload',
                'arguments' => [
                    'mautic.contactclient.model.contactclient',
                    'mautic.contactclient.service.transport',
                    'mautic.contactclient.helper.token',
                    'mautic.contactclient.model.schedule',
                ],
            ],
            'mautic.contactclient.model.filepayload'   => [
                'class'     => 'MauticPlugin\MauticContactClientBundle\Model\FilePayload',
                'arguments' => [
                    'mautic.contactclient.model.contactclient',
                    'mautic.contactclient.helper.token',
                    'doctrine.orm.entity_manager',
                    'mautic.core.model.form',
                    'mautic.campaign.model.event',
                    'mautic.lead.model.lead',
                    'mautic.helper.paths',
                    'mautic.helper.core_parameters',
                    'symfony.filesystem',
                    'mautic.helper.mailer',
                    'mautic.contactclient.model.schedule',
                    'mautic.contactclient.helper.utmsource',
                ],
            ],
            'mautic.contactclient.model.cache'         => [
                'class' => 'MauticPlugin\MauticContactClientBundle\Model\Cache',
            ],
            'mautic.contactclient.model.schedule'      => [
                'class'     => 'MauticPlugin\MauticContactClientBundle\Model\Schedule',
                'arguments' => [
                    'doctrine.orm.default_entity_manager',
                    'mautic.helper.core_parameters',
                ],
            ],
        ],
        'integrations' => [
            'mautic.contactclient.integration' => [
                'class' => 'MauticPlugin\MauticContactClientBundle\Integration\ClientIntegration',
            ],
        ],
        'other'        => [
            'mautic.contactclient.helper.token'      => [
                'class'     => 'MauticPlugin\MauticContactClientBundle\Helper\TokenHelper',
                'arguments' => [
                    'mautic.helper.core_parameters',
                ],
            ],
            'mautic.contactclient.helper.utmsource'  => [
                'class' => 'MauticPlugin\MauticContactClientBundle\Helper\UtmSourceHelper',
            ],
            'mautic.contactclient.guzzle.client'     => [
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
