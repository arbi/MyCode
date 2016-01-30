<?php

return [
    'controllers' => [
        'invokables' => [
            'controller_reviews_general' => 'Reviews\Controller\General',
        ]
    ],
    'router' => [
        'routes' => [
            'reviews' => [
                'type'    => 'Segment',
                'options' => [
                    'route'    => '/reviews',
                    'defaults' => array(
                        'controller' => 'controller_reviews_general',
                        'action' => 'index'
                    )
                ],
                'may_terminate' => true,
                'child_routes'  => [
                    'get-datatable-data'   => [
                        'type'    => 'Segment',
                        'options' => [
                            'route' => '/get-datatable-data',
                            'defaults' => [
                                'controller'    => 'controller_reviews_general',
                                'action'        => 'get-datatable-data',
                            ],
                        ],
                    ],

                    'get-categories-info'   => [
                        'type'    => 'Segment',
                        'options' => [
                            'route' => '/get-categories-info',
                            'defaults' => [
                                'controller'    => 'controller_reviews_general',
                                'action'        => 'get-categories-info',
                            ],
                        ],
                    ],

                    'get-chart-info'   => [
                        'type'    => 'Segment',
                        'options' => [
                            'route' => '/get-chart-info',
                            'defaults' => [
                                'controller'    => 'controller_reviews_general',
                                'action'        => 'get-chart-info',
                            ],
                        ],
                    ],

                    'change-review-categories'   => [
                        'type'    => 'Segment',
                        'options' => [
                            'route' => '/change-review-categories',
                            'defaults' => [
                                'controller'    => 'controller_reviews_general',
                                'action'        => 'change-review-categories',
                            ],
                        ],
                    ],

                    'change-status'   => [
                        'type'    => 'Segment',
                        'options' => [
                            'route' => '/change-status',
                            'defaults' => [
                                'controller'    => 'controller_reviews_general',
                                'action'        => 'change-status',
                            ],
                        ],
                    ],

                    'delete'   => [
                        'type'    => 'Segment',
                        'options' => [
                            'route' => '/delete',
                            'defaults' => [
                                'controller'    => 'controller_reviews_general',
                                'action'        => 'delete',
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
