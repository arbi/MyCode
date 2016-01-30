<?php

return [
    'controllers' => [
        'invokables' => [
            'controller_warehouse_category' => 'Warehouse\Controller\Category',
            'controller_warehouse_storage' => 'Warehouse\Controller\Storage',
            'controller_warehouse_asset' => 'Warehouse\Controller\Asset',
        ]
    ],

    'router' => [
        'routes' => [
            'warehouse' => [
                'type'    => 'Literal',
                'options' => [
                    'route'    => '/warehouse',
                ],
                'may_terminate' => true,
                'child_routes'  => [
                    'category'   => [
                        'type'    => 'Segment',
                        'options' => [
                            'route' => '/category[/:action[/:id[/:param]]]',
                            'constraints' => [
                                'action'        => '[a-zA-Z][a-zA-Z0-9_-]*',
                                'id'            => '[0-9]*',
                                'param'         => '[0-9]*',
                            ],
                            'defaults' => [
                                'controller'    => 'controller_warehouse_category',
                                'action'        => 'index',
                            ],
                        ],
                    ],

                    'storage'   => [
                        'type'    => 'Segment',
                        'options' => [
                            'route' => '/storage[/:action[/:id[/:param]]]',
                            'constraints' => [
                                'action'        => '[a-zA-Z][a-zA-Z0-9_-]*',
                                'id'            => '[0-9]*',
                                'param'         => '[0-9]*',
                            ],
                            'defaults' => [
                                'controller'    => 'controller_warehouse_storage',
                                'action'        => 'index',
                            ],
                        ],
                    ],

                    'asset'   => [
                        'type'    => 'Segment',
                        'options' => [
                            'route' => '/asset[/:action[/:id[/:param]]]',
                            'constraints' => [
                                'action'        => '[a-zA-Z][a-zA-Z0-9_-]*',
                                'id'            => '[0-9]*',
                                'param'         => '[0-9]*',
                            ],
                            'defaults' => [
                                'controller'    => 'controller_warehouse_asset',
                                'action'        => 'index',
                            ],
                        ],
                    ],

                ],
            ],
        ],
    ],

    'view_manager' => [
        'template_path_stack' => [
            __DIR__ . '/../view'
        ],
    ],
];
