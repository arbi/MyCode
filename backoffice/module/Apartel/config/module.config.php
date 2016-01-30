<?php

return [
    'controllers' => [
        'invokables' => [
            'controller_apartel_general'    => 'Apartel\Controller\General',
            'controller_apartel_content'    => 'Apartel\Controller\Content',
            'controller_apartel_connection' => 'Apartel\Controller\Connection',
            'controller_apartel_type_rate'  => 'Apartel\Controller\TypeRate',
            'controller_apartel_calendar'   => 'Apartel\Controller\Calendar',
            'controller_apartel_inventory'  => 'Apartel\Controller\Inventory',
            'controller_apartel_history'    => 'Apartel\Controller\History',
        ]
    ],
    'router' => [
        'routes' => [
            'apartel' => [
                'type'          => 'Segment',
                'options'       => [
                    'route'       => '/apartel/:apartel_id',
                    'constraints' => [
                        'apartel_id' => '[0-9]+'
                    ],
                    'defaults'    => [
                        'controller' => 'controller_apartel_general',
                        'action'     => 'index'
                    ]
                ],
                'may_terminate' => true,
                'child_routes' => [
                    'general' => [
                        'type'          => 'Literal',
                        'options'       => [
                            'route'    => '/general',
                            'defaults' => [
                                'controller' => 'controller_apartel_general',
                                'action'     => 'index'
                            ]
                        ],
                        'may_terminate' => true,
                        'child_routes'  => [
                            'save' => [
                                'type'    => 'Literal',
                                'options' => [
                                    'route'    => '/save',
                                    'defaults' => [
                                        'controller' => 'controller_apartel_general',
                                        'action'     => 'save'
                                    ]
                                ]
                            ],
                            'save-fiscal' => [
                                'type'    => 'Literal',
                                'options' => [
                                    'route'    => '/save-fiscal',
                                    'defaults' => [
                                        'controller' => 'controller_apartel_general',
                                        'action'     => 'ajax-save-fiscal'
                                    ]
                                ]
                            ],
                            'delete-fiscal' => [
                                'type'    => 'Segment',
                                'options' => [
                                    'route'    => '/delete-fiscal[/:fiscal_id]',
                                    'defaults' => [
                                        'controller' => 'controller_apartel_general',
                                        'action'     => 'delete-fiscal'
                                    ],
                                    'constraints' => [
                                        'fiscal_id' => '[0-9]+',
                                    ],
                                ]
                            ],
                        ]
                    ],
                    'content' => [
                        'type'          => 'Literal',
                        'options'       => [
                            'route'    => '/content',
                            'defaults' => [
                                'controller' => 'controller_apartel_content',
                                'action'     => 'index'
                            ]
                        ],
                        'may_terminate' => true,
                        'child_routes'  => [
                            'save' => [
                                'type'    => 'Literal',
                                'options' => [
                                    'route'    => '/save',
                                    'defaults' => [
                                        'controller' => 'controller_apartel_content',
                                        'action'     => 'save'
                                    ]
                                ]
                            ],
                        ]
                    ],
                    'type-rate'  => [
                        'type'          => 'Literal',
                        'options'       => [
                            'route'    => '/type-rate',
                            'defaults' => [
                                'controller' => 'controller_apartel_type_rate',
                            ]
                        ],
                        'may_terminate' => true,
                        'child_routes'  => [
                            'home' => [
                                'type'    => 'Literal',
                                'options' => [
                                    'route'    => '/home',
                                    'defaults' => [
                                        'action' => 'home',
                                    ],
                                ],
                            ],
                            'type' => [
                                'type'          => 'Segment',
                                'options'       => [
                                    'route'       => '[/:type_id]',
                                    'constraints' => [
                                        'type_id' => '[0-9]+',
                                    ],
                                    'defaults'    => [
                                        'action' => 'index'
                                    ],

                                ],
                                'may_terminate' => true,
                                'child_routes'  => [
                                    'type-delete' => [
                                        'type'    => 'Segment',
                                        'options' => [
                                            'route'    => '/delete',
                                            'defaults' => [
                                                'action' => 'delete',
                                            ]
                                        ]
                                    ],
                                    'rate'        => [
                                        'type'    => 'Segment',
                                        'options' => [
                                            'route'       => '/rate[/:rate_id]',
                                            'defaults'    => [
                                                'action' => 'rate'
                                            ],
                                            'constraints' => [
                                                'type_id' => '[0-9]+',
                                                'rate_id' => '[0-9]+',
                                            ],
                                        ]
                                    ],
                                    'rate-delete' => [
                                        'type'    => 'Segment',
                                        'options' => [
                                            'route'       => '/rate/[:rate_id]/delete',
                                            'defaults'    => [
                                                'action' => 'rate-delete'
                                            ],
                                            'constraints' => [
                                                'type_id' => '[0-9]+',
                                                'rate_id' => '[0-9]+',
                                            ],
                                        ]
                                    ],
                                ]
                            ],
                        ]
                    ],

                    'calendar'   => [
                        'type'          => 'Segment',
                        'options'       => [
                            'route'    => '[/:type_id]/calendar[/:year][/:month]',
                            'defaults' => [
                                'controller' => 'controller_apartel_calendar',
                                'action'     => 'index'
                            ]
                        ],
                        'may_terminate' => true,
                        'child_routes'  => [
                            'update-prices'     => [
                                'type'    => 'Literal',
                                'options' => [
                                    'route'    => '/update-prices',
                                    'defaults' => [
                                        'controller' => 'controller_apartel_calendar',
                                        'action'     => 'ajax-update-rate-prices'
                                    ]
                                ]
                            ],
                            'synchronize-month' => [
                                'type'    => 'Literal',
                                'options' => [
                                    'route'    => '/synchronize-month',
                                    'defaults' => [
                                        'controller' => 'controller_apartel_calendar',
                                        'action'     => 'ajax-synchronize-month'
                                    ]
                                ]
                            ],
                        ]
                    ],

                    'inventory'  => [
                        'type'          => 'Segment',
                        'options'       => [
                            'route'    => '/inventory[/:type_id]',
                            'defaults' => [
                                'controller' => 'controller_apartel_inventory',
                                'action'     => 'index'
                            ]
                        ],
                        'may_terminate' => true,
                        'child_routes'  => [
                            'update-prices' => [
                                'type'    => 'Literal',
                                'options' => [
                                    'route'    => '/update-prices',
                                    'defaults' => [
                                        'controller' => 'controller_apartel_inventory',
                                        'action'     => 'ajax-update-prices'
                                    ]
                                ]
                            ],
                        ]
                    ],

                    'connection' => [
                        'type'          => 'Literal',
                        'options'       => [
                            'route'    => '/connection',
                            'defaults' => [
                                'controller' => 'controller_apartel_connection',
                                'action'     => 'index'
                            ]
                        ],
                        'may_terminate' => true,
                        'child_routes'  => [
                            'save'                     => [
                                'type'    => 'Literal',
                                'options' => [
                                    'route'    => '/save',
                                    'defaults' => [
                                        'controller' => 'controller_apartel_connection',
                                        'action'     => 'save'
                                    ]
                                ]
                            ],
                            'connect'                  => [
                                'type'    => 'Literal',
                                'options' => [
                                    'route'    => '/connect',
                                    'defaults' => [
                                        'controller' => 'controller_apartel_connection',
                                        'action'     => 'connect'
                                    ]
                                ]
                            ],
                            'link'                     => [
                                'type'    => 'Literal',
                                'options' => [
                                    'route'    => '/link',
                                    'defaults' => [
                                        'controller' => 'controller_apartel_connection',
                                        'action'     => 'link'
                                    ]
                                ]
                            ],
                            'test-pull-reservations'   => [
                                'type'    => 'Literal',
                                'options' => [
                                    'route'    => '/test-pull-reservations',
                                    'defaults' => [
                                        'controller' => 'controller_apartel_connection',
                                        'action'     => 'test-pull-reservations'
                                    ]
                                ]
                            ],
                            'test-update-availability' => [
                                'type'    => 'Literal',
                                'options' => [
                                    'route'    => '/test-update-availability',
                                    'defaults' => [
                                        'controller' => 'controller_apartel_connection',
                                        'action'     => 'test-update-availability'
                                    ]
                                ]
                            ],
                            'test-fetch-list'          => [
                                'type'    => 'Literal',
                                'options' => [
                                    'route'    => '/test-fetch-list',
                                    'defaults' => [
                                        'controller' => 'controller_apartel_connection',
                                        'action'     => 'test-fetch-list'
                                    ]
                                ]
                            ],
                            'ajax-save-ota'            => [
                                'type'    => 'Literal',
                                'options' => [
                                    'route'    => '/ajax-save-ota',
                                    'defaults' => [
                                        'controller' => 'controller_apartel_connection',
                                        'action'     => 'ajax-save-ota'
                                    ]
                                ]
                            ],
                            'remove-ota'               => [
                                'type'    => 'Segment',
                                'options' => [
                                    'route'       => '/remove-ota/[:ota_id]',
                                    'defaults'    => [
                                        'action' => 'remove-ota'
                                    ],
                                    'constraints' => [
                                        'type_id' => '[0-9]+',
                                    ],
                                ]
                            ],
                            'ajax-check-ota'           => [
                                'type'    => 'Segment',
                                'options' => [
                                    'route'       => '/ajax-check-ota/[:ota_id]',
                                    'defaults'    => [
                                        'action' => 'ajax-check-ota'
                                    ],
                                    'constraints' => [
                                        'type_id' => '[0-9]+',
                                    ],
                                ]
                            ],


                        ]
                    ],

                    'history'  => [
                        'type'          => 'Literal',
                        'options'       => [
                            'route'    => '/history',
                            'defaults' => [
                                'controller' => 'controller_apartel_history',
                                'action'     => 'index'
                            ]
                        ],
                    ],
                ]
            ],
        ]
    ],
    'view_manager' => [
        'template_map'        => [
            'apartel/partial/navigation' => __DIR__ . '/../view/partial/navigation.phtml',
        ],
        'template_path_stack' => [
            __DIR__ . '/../view'
        ],
        'strategies'          => [
            'ViewJsonStrategy'
        ]
    ]
];
