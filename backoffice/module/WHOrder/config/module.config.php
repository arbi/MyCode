<?php
return [
    'router' => [
        'routes' => [
            'orders' => [
                'type' => 'Segment',
                'options' => [
                    'route'    => '/orders',
                    'defaults' => [
                        'controller'    => 'warehouse_order',
                        'action'        => 'index'
                    ],
                ],
                'may_terminate' => true,
                'child_routes'  => [
                    'create-item-transaction' => [
                        'type' => 'Segment',
                        'options' => [
                            'route'    => '/create-item-transaction',
                            'defaults' => [
                                'controller' => 'warehouse_order',
                                'action'     => 'ajax-create-item-transaction'
                            ]
                        ],
                    ],
                    'ajax-search-orders' => [
                        'type' => 'Segment',
                        'options' => [
                            'route'    => '/ajax-search-orders',
                            'defaults' => [
                                'controller' => 'warehouse_order',
                                'action'     => 'ajax-search-orders'
                            ]
                        ],
                    ],
                    'ajax-create-order' => [
                        'type' => 'Segment',
                        'options' => [
                            'route'    => '/ajax-create-order',
                            'defaults' => [
                                'controller' => 'warehouse_order',
                                'action'     => 'ajax-create-order'
                            ]
                        ],
                    ],
                    'ajax-get-order-locations' => [
                        'type' => 'Segment',
                        'options' => [
                            'route'    => '/ajax-get-order-locations',
                            'defaults' => [
                                'controller' => 'warehouse_order',
                                'action'     => 'ajax-get-order-locations'
                            ]
                        ],
                    ],
                    'ajax-get-order-statuses' => [
                        'type' => 'Segment',
                        'options' => [
                            'route'    => '/ajax-get-order-statuses',
                            'defaults' => [
                                'controller' => 'warehouse_order',
                                'action'     => 'ajax-get-order-statuses'
                            ]
                        ],
                    ],
                    'ajax-get-order-categories' => [
                        'type' => 'Segment',
                        'options' => [
                            'route'    => '/ajax-get-order-categories',
                            'defaults' => [
                                'controller' => 'warehouse_order',
                                'action'     => 'ajax-get-order-categories'
                            ]
                        ],
                    ],
                    'ajax-get-order-suppliers' => [
                        'type' => 'Segment',
                        'options' => [
                            'route'    => '/ajax-get-order-suppliers',
                            'defaults' => [
                                'controller' => 'warehouse_order',
                                'action'     => 'ajax-get-order-suppliers'
                            ]
                        ],
                    ],
                    'edit' => [
                        'type' => 'Segment',
                        'options' => [
                            'route'    => '/edit[/:order_id]',
                            'constraints' => [
                                'order_id' => '[a-zA-Z0-9_-]*'
                            ],
                            'defaults' => [
                                'controller' => 'warehouse_order',
                                'action'     => 'edit'
                            ]
                        ],
                    ],
                    'add' => [
                        'type' => 'Segment',
                        'options' => [
                            'route'    => '/add',
                            'defaults' => [
                                'controller' => 'warehouse_order',
                                'action'     => 'add'
                            ]
                        ],
                    ],
                    'ajax-update-order' => [
                        'type' => 'Segment',
                        'options' => [
                            'route'    => '/ajax-update-order',
                            'defaults' => [
                                'controller' => 'warehouse_order',
                                'action'     => 'ajax-update-order'
                            ]
                        ],
                    ],
                    'ajax-create-po-item' => [
                        'type' => 'Segment',
                        'options' => [
                            'route'    => '/ajax-create-po-item',
                            'defaults' => [
                                'controller' => 'warehouse_order',
                                'action'     => 'ajax-create-po-item'
                            ]
                        ],
                    ],
                    'ajax-request-advance' => [
                        'type' => 'Segment',
                        'options' => [
                            'route'    => '/ajax-request-advance',
                            'defaults' => [
                                'controller' => 'warehouse_order',
                                'action'     => 'ajax-request-advance'
                            ]
                        ],
                    ],
                    'ajax-change-manager' => [
                        'type' => 'Segment',
                        'options' => [
                            'route'    => '/ajax-change-manager',
                            'defaults' => [
                                'controller' => 'warehouse_order',
                                'action'     => 'ajax-change-manager'
                            ]
                        ],
                    ],
                    'reject' => [
                        'type' => 'Segment',
                        'options' => [
                            'route'    => '/reject[/:order_id]',
                            'constraints' => [
                                'order_id' => '[a-zA-Z0-9_-]*'
                            ],
                            'defaults' => [
                                'controller' => 'warehouse_order',
                                'action'     => 'reject'
                            ]
                        ],
                    ],
                    'ajax-create-refund-po-item' => [
                        'type' => 'Segment',
                        'options' => [
                            'route'    => '/ajax-create-refund-po-item[/:order_id]',
                            'constraints' => [
                                'order_id' => '[a-zA-Z0-9_-]*'
                            ],
                            'defaults' => [
                                'controller' => 'warehouse_order',
                                'action'     => 'ajax-create-refund-po-item'
                            ]
                        ],
                    ],
                    'ajax-get-item-account-details' => [
                        'type' => 'Segment',
                        'options' => [
                            'route'    => '/ajax-get-item-account-details[/:order_id]',
                            'constraints' => [
                                'order_id' => '[a-zA-Z0-9_-]*'
                            ],
                            'defaults' => [
                                'controller' => 'warehouse_order',
                                'action'     => 'ajax-get-item-account-details'
                            ]
                        ],
                    ],
                ]
            ],
        ],
    ],
    'controllers' => [
        'invokables' => [
            'warehouse_order'  => 'WHOrder\Controller\OrderController',
        ],
    ],
    'controller_plugins' => [
        'invokables' => [
        ],
    ],
    'view_manager' => [
        'template_path_stack' => [
            __DIR__ . '/../view'
        ],
        'strategies' => [
            'ViewJsonStrategy'
        ]
    ]
];
