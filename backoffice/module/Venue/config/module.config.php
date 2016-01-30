<?php

return [
    'controllers' => [
        'invokables' => [
            'controller_venue_general' => 'Venue\Controller\General',
            'controller_venue_charges' => 'Venue\Controller\Charges',
            'controller_venue_items'   => 'Venue\Controller\Items',
            'controller_venue_lunchroom'   => 'Venue\Controller\Lunchroom',
        ]
    ],
    'router' => [
        'routes' => [
            'venue' => [
                'type' => 'segment',
                'options' => [
                    'route'    => '/venue[/:action[/:id]]',
                    'defaults' => [
                        'controller'    => 'controller_venue_general',
                        'action'        => 'index',
                    ],
                    'constraints' => [
                        'controller' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'action'     => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'id'         => '[a-zA-Z0-9_-]*',
                    ]
                ],
            ],
            'venue-charges' => [
                'type' => 'segment',
                'options' => [
                    'route'    => '/venue/charge[/:action[/:id]]',
                    'defaults' => [
                        'controller'    => 'controller_venue_charges',
                        'action'        => 'index',
                    ],
                    'constraints' => [
                        'action'     => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'id'   => '[0-9]*',
                    ]
                ],
            ],
            'venue-items' => [
                'type' => 'segment',
                'options' => [
                    'route'    => '/venue/items[/:action]',
                    'defaults' => [
                        'controller'    => 'controller_venue_items',
                        'action'        => 'index',
                    ],
                    'constraints' => [
                        'action'     => '[a-zA-Z][a-zA-Z0-9_-]*',
                    ]
                ],
            ],
            'lunchroom' => [
                'type' => 'segment',
                'options' => [
                    'route'    => '/lunchroom[/:action[/:id]]',
                    'defaults' => [
                        'controller'    => 'controller_venue_lunchroom',
                        'action'        => 'index',
                    ],
                    'constraints' => [
                        'action'     => '[a-zA-Z][a-zA-Z0-9_-]*',
                    ]
                ],
            ]
        ]
    ],
    'view_manager' => [
        'template_map' => [

        ],
        'template_path_stack' => [
            __DIR__ . '/../view'
        ],
        'strategies' => [
            'ViewJsonStrategy'
        ]
    ]
];
