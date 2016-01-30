<?php

return [
    'controllers' => [
        'invokables' => [
            'controller_parking_general'            => 'Parking\Controller\General',
            'controller_parking_spots'              => 'Parking\Controller\Spots',
            'controller_parking_lots'               => 'Parking\Controller\Lots',
            'controller_parking_inventory_calendar' => 'Parking\Controller\Calendar',
            'controller_parking_inventory'          => 'Parking\Controller\Inventory',
        ],
    ],
    'router' => [
        'routes' => [
            'parking' => [
                'type' => 'Segment',
                'options' => [
                    'route' => '/parking/:parking_lot_id',
                    'constraints' => [
                        'parking_lot_id' => '[0-9]+'
                    ],
                    'defaults' => [
                        'controller' => 'controller_parking_general',
                        'action' => 'index'
                    ],
                ],
                'may_terminate' => true,
                'child_routes' => [
                    'general' => [
                        'type' => 'Literal',
                        'options' => [
                            'route' => '/general',
                            'defaults' => [
                                'controller' => 'controller_parking_general',
                                'action' => 'index'
                            ],
                        ],
                        'may_terminate' => true,
                        'child_routes' => [
                            'save' => [
                                'type' => 'Literal',
                                'options' => [
                                    'route' => '/save',
                                    'defaults' => [
                                        'controller' => 'controller_parking_general',
                                        'action' => 'save'
                                    ],
                                ],
                            ],
                            'change-parking-lot-status' => [
                                'type' => 'Literal',
                                'options' => [
                                    'route' => '/change-status',
                                    'defaults' => [
                                        'controller' => 'controller_parking_general',
                                        'action' => 'change-status'
                                    ],
                                ],
                            ],
                            'upload-parking-permit' => [
                                'type' => 'Literal',
                                'options' => [
                                    'route' => '/upload-parking-permit',
                                    'defaults' => [
                                        'controller' => 'controller_parking_general',
                                        'action' => 'upload-parking-permit'
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'spots' => [
                        'type' => 'Literal',
                        'options' => [
                            'route' => '/spots',
                            'defaults' => [
                                'controller' => 'controller_parking_spots',
                                'action' => 'index'
                            ],
                        ],
                        'may_terminate' => true,
                        'child_routes' => [
                            'index' => [
                                'type' => 'Segment',
                                'options' => [
                                    'route' => '/[:spot_id]'
                                ],
                            ],
                            'edit' => [
                                'type' => 'Segment',
                                'options' => [
                                    'route' => '/edit/[:spot_id][/:action]',
                                    'defaults' => [
                                        'action' => 'edit'
                                    ],
                                    'constraints' => [
                                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                                    ]
                                ],
                            ],
                            'delete-spot' => [
                                'type' => 'Segment',
                                'options' => [
                                    'route' => '/delete/[:spot_id]',
                                    'defaults' => [
                                        'action' => 'delete'
                                    ],
                                ],
                            ],
                            'save' => [
                                'type' => 'Literal',
                                'options' => [
                                    'route' => '/save',
                                    'defaults' => [
                                        'action' => 'save'
                                    ],
                                ],
                            ],
                            'save-permit-id' => [
                                'type' => 'Literal',
                                'options' => [
                                    'route' => '/save-permit-id',
                                    'defaults' => [
                                        'action' => 'savePermitId'
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'calendar' => [
                        'type' => 'Segment',
                        'options' => [
                            'route' => '/calendar[/:year][/:month]',
                            'defaults' => [
                                'controller' => 'controller_parking_inventory_calendar',
                                'action' => 'index'
                            ],
                        ],
                        'may_terminate' => true,
                        'child_routes' => [
                            'toggle-availability' => [
                                'type' => 'Literal',
                                'options' => [
                                    'route' => '/toggle-availability',
                                    'defaults' => [
                                        'controller' => 'controller_parking_inventory_calendar',
                                        'action' => 'ajax-toggle-availability'
                                    ],
                                ],
                            ],
                            'update-availabilities' => [
                                'type' => 'Literal',
                                'options' => [
                                    'route' => '/update-availabilities',
                                    'defaults' => [
                                        'controller' => 'controller_parking_inventory_calendar',
                                        'action' => 'ajax-update-spot-availabilities'
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'add_parking' => [
                'type' => 'Literal',
                'options' => [
                    'route' => '/parking/new',
                    'defaults' => [
                        'controller' => 'controller_parking_general',
                        'action' => 'index'
                    ],
                ],
            ],
            'parking_lots' => [
                'type' => 'Segment',
                'options' => [
                    'route' => '/parking/lots[/:action]',
                    'defaults' => [
                        'controller' => 'controller_parking_lots',
                        'action'     => 'index',
                    ],
                    'constraints' => [
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                    ],
                ],
            ],
            'parking_inventory' => [
                'type' => 'Segment',
                'options' => [
                    'route' => '/parking/inventory[/:action]',
                    'defaults' => [
                        'controller' => 'controller_parking_inventory',
                        'action'     => 'index',
                    ],
                    'constraints' => [
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                    ],
                ],
            ],
        ],
    ],
    'view_manager' => [
        'template_map' => [
            'parking/partial/navigation' => __DIR__ . '/../view/partial/navigation.phtml',
        ],
        'template_path_stack' => [
            __DIR__ . '/../view'
        ],
        'strategies' => [
            'ViewJsonStrategy'
        ],
    ],
];
