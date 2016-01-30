<?php

return array(
    'controllers' => array(
        'invokables' => array(
            'controller_apartment_main'                 => 'Apartment\Controller\Main',
            'controller_apartment_general'              => 'Apartment\Controller\General',
            'controller_apartment_details'              => 'Apartment\Controller\Details',
            'controller_apartment_location'             => 'Apartment\Controller\Location',
            'controller_apartment_furniture'            => 'Apartment\Controller\Furniture',
            'controller_apartment_media'                => 'Apartment\Controller\Media',
            'controller_apartment_document'             => 'Apartment\Controller\Document',
            'controller_apartment_rate'                 => 'Apartment\Controller\Rate',
            'controller_apartment_inventory_calendar'   => 'Apartment\Controller\InventoryCalendar',
            'controller_apartment_inventory_range'      => 'Apartment\Controller\InventoryRange',
            'controller_apartment_channel_connection'   => 'Apartment\Controller\ChannelConnection',
            'controller_apartment_costs'                => 'Apartment\Controller\Cost',
            'controller_apartment_statistics'           => 'Apartment\Controller\Statistics',
            'controller_apartment_review'               => 'Apartment\Controller\Review',
            'controller_apartment_history'              => 'Apartment\Controller\History',
            'controller_apartment_apartment'            => 'Apartment\Controller\ApartmentController',
            'controller_apartment_review_category'      => 'Apartment\Controller\ApartmentReviewCategoryController',
            'controller_apartment_occupancy_statistics' => 'Apartment\Controller\OccupancyStatistics',
            'controller_apartment_sales_statistics'     => 'Apartment\Controller\SalesStatistics',
            'controller_apartment_welcome_note'         => 'Apartment\Controller\WelcomeNote',
        )
    ),
    'router' => array(
        'routes' => array(
            'apartment' => array(
                'type' => 'Segment',
                'options' => array(
                    'route' => '/apartment/:apartment_id',
                    'constraints' => array(
                        'apartment_id' => '[0-9]+'
                    ),
                    'defaults' => array(
                        'controller' => 'controller_apartment_main',
                        'action' => 'index'
                    )
                ),
                'may_terminate' => true,
                'child_routes' => array(
                    'general' => array(
                        'type' => 'Literal',
                        'options' => array(
                            'route' => '/general',
                            'defaults' => array(
                                'controller' => 'controller_apartment_general',
                                'action' => 'index'
                            )
                        ),
                        'may_terminate' => true,
                        'child_routes' => array(
                            'save' => array(
                                'type' => 'Literal',
                                'options' => array(
                                    'route' => '/save',
                                    'defaults' => array(
                                        'controller' => 'controller_apartment_general',
                                        'action' => 'save'
                                    )
                                )
                            ),
                            'check-disable-possibility' => [
                                'type' => 'Literal',
                                'options' => [
                                    'route' => '/check-disable-possibility',
                                    'defaults' => [
                                        'controller' => 'controller_apartment_general',
                                        'action' => 'checkDisablePossibility'
                                    ]
                                ]
                            ]
                        )
                    ),
                    'details' => array(
                        'type' => 'Literal',
                        'options' => array(
                            'route' => '/details',
                            'defaults' => array(
                                'controller' => 'controller_apartment_details',
                                'action' => 'index',
                            ),
                        ),
                        'may_terminate' => true,
                        'child_routes' => array(
                            'save' => array(
                                'type' => 'Literal',
                                'options' => array(
                                    'route' => '/save',
                                    'defaults' => array(
                                        'controller' => 'controller_apartment_details',
                                        'action' => 'save',
                                    ),
                                ),
                            ),
                            'add-furniture' => array(
                                'type' => 'Literal',
                                'options' => array(
                                    'route' => '/add-furniture',
                                    'defaults' => array(
                                        'controller' => 'controller_apartment_details',
                                        'action' => 'add-furniture',
                                    ),
                                ),
                            ),
                            'delete-furniture' => array(
                                'type' => 'Segment',
                                'options' => array(
                                    'route' => '/delete-furniture[/:id]',
                                    'defaults' => array(
                                        'controller' => 'controller_apartment_details',
                                        'action' => 'delete-furniture',
                                    ),
                                ),
                            ),
                            'get-parking-spots' => array(
                                'type' => 'Segment',
                                'options' => array(
                                    'route' => '/get-parking-spots',
                                    'defaults' => array(
                                        'controller' => 'controller_apartment_details',
                                        'action' => 'get-parking-spots',
                                    ),
                                ),
                            ),
                        ),
                    ),
                    'location' => array(
                        'type' => 'Literal',
                        'options' => array(
                            'route' => '/location',
                            'defaults' => array(
                                'controller' => 'controller_apartment_location',
                                'action' => 'index'
                            )
                        ),
                        'may_terminate' => true,
                        'child_routes' => array(
                            'save' => array(
                                'type' => 'Literal',
                                'options' => array(
                                    'route' => '/save',
                                    'defaults' => array(
                                        'controller' => 'controller_apartment_location',
                                        'action' => 'save'
                                    )
                                )
                            ),
                            'remove-map' => array(
                                'type' => 'Literal',
                                'options' => array(
                                    'route' => '/remove-map',
                                    'defaults' => array(
                                        'controller' => 'controller_apartment_location',
                                        'action' => 'remove-map'
                                    )
                                )
                            ),
                            'get-province-options' => array(
                                'type' => 'Segment',
                                'options' => array(
                                    'route' => '/get-province-options[/:country]',
                                    'defaults' => array(
                                        'controller' => 'controller_apartment_location',
                                        'action' => 'get-province-options'
                                    )
                                )
                            ),
                            'get-city-options' => array(
                                'type' => 'Segment',
                                'options' => array(
                                    'route' => '/get-city-options[/:province]',
                                    'defaults' => array(
                                        'controller' => 'controller_apartment_location',
                                        'action' => 'get-city-options'
                                    )
                                )
                            ),
                            'get-building-section' => array(
                                'type' => 'Segment',
                                'options' => array(
                                    'route' => '/get-building-section[/:building_id]',
                                    'defaults' => array(
                                        'controller' => 'controller_apartment_location',
                                        'action' => 'get-building-section'
                                    ),
                                    'constraints' => array(
                                        'building_id' => '[0-9]*',
                                    ),
                                )
                            )
                        )
                    ),
                    'media' => array(
                        'type' => 'Segment',
                        'options' => array(
                            'route' => '/media',
                            'defaults' => array(
                                'controller' => 'controller_apartment_media',
                                'action' => 'index'
                            )
                        ),
                        'may_terminate' => true,
                        'child_routes' => array(
                            'index' => array(
                                'type' => 'Segment',
                                'options' => array(
                                    'route' => '/[:action]'
                                )
                            )
                        )
                    ),
                    'document' => array(
                        'type' => 'Segment',
                        'options' => array(
                            'route' => '/document[/:action][/:doc_id]',
                            'constraints' => array(
                                'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                                'doc_id' => '[0-9]*',
                            ),
                            'defaults' => array(
                                'controller' => 'controller_apartment_document',
                                'action' => 'index'
                            )
                        )
                    ),
                    'rate' => array(
                        'type' => 'Literal',
                        'options' => array(
                            'route' => '/rate',
                            'defaults' => array(
                                'controller' => 'controller_apartment_rate',
                                'action' => 'index'
                            )
                        ),
                        'may_terminate' => true,
                        'child_routes' => array(
                            'index' => array(
                                'type' => 'Segment',
                                'options' => array(
                                    'route' => '/[:rate_id]'
                                )
                            ),
                            'add' => array(
                                'type' => 'Literal',
                                'options' => array(
                                    'route' => '/add',
                                    'defaults' => array(
                                        'action' => 'add'
                                    )
                                )
                            ),
                            'delete' => array(
                                'type' => 'Segment',
                                'options' => array(
                                    'route' => '/delete/[:rate_id]',
                                    'defaults' => array(
                                        'action' => 'delete'
                                    )
                                )
                            ),
                            'save' => array(
                                'type' => 'Literal',
                                'options' => array(
                                    'route' => '/save',
                                    'defaults' => array(
                                        'action' => 'save'
                                    )
                                )
                            ),
                            'check-name' => array(
                                'type' => 'Segment',
                                'options' => array(
                                    'route' => '/check-name/[:rate_id]',
                                    'defaults' => array(
                                        'action' => 'ajax-check-name'
                                    )
                                )
                            ),
                        )
                    ),
                    'calendar' => array(
                        'type' => 'Segment',
                        'options' => array(
                            'route' => '/calendar[/:year][/:month]',
                            'defaults' => array(
                                'controller' => 'controller_apartment_inventory_calendar',
                                'action' => 'index'
                            )
                        ),
                        'may_terminate' => true,
                        'child_routes' => array(
                            'toggle-availability' => array(
                                'type' => 'Literal',
                                'options' => array(
                                    'route' => '/toggle-availability',
                                    'defaults' => array(
                                        'controller' => 'controller_apartment_inventory_calendar',
                                        'action' => 'ajax-toggle-availability'
                                    )
                                )
                            ),
                            'update-prices' => array(
                                'type' => 'Literal',
                                'options' => array(
                                    'route' => '/update-prices',
                                    'defaults' => array(
                                        'controller' => 'controller_apartment_inventory_calendar',
                                        'action' => 'ajax-update-rate-prices'
                                    )
                                )
                            ),
                            'synchronize-month' => array(
                                'type' => 'Literal',
                                'options' => array(
                                    'route' => '/synchronize-month',
                                    'defaults' => array(
                                        'controller' => 'controller_apartment_inventory_calendar',
                                        'action' => 'ajax-synchronize-month'
                                    )
                                )
                            ),
                        )
                    ),
                    'inventory-range' => array(
                        'type' => 'Literal',
                        'options' => array(
                            'route' => '/inventory-range',
                            'defaults' => array(
                                'controller' => 'controller_apartment_inventory_range',
                                'action' => 'index'
                            )
                        ),
                        'may_terminate' => true,
                        'child_routes' => array(
                            'index' => array(
                                'type' => 'Segment',
                                'options' => array(
                                    'route' => '/[:rate_id]'
                                )
                            ),
                            'update' => array(
                                'type' => 'Segment',
                                'options' => array(
                                    'route' => '/update/[:rate_id]',
                                    'defaults' => array(
                                        'action' => 'update'
                                    )
                                )
                            ),
                        )
                    ),
                    'channel-connection' => array(
                        'type' => 'Literal',
                        'options' => array(
                            'route' => '/channel-connection',
                            'defaults' => array(
                                'controller' => 'controller_apartment_channel_connection',
                                'action' => 'index'
                            )
                        ),
                        'may_terminate' => true,
                        'child_routes' => array(
                            'save' => array(
                                'type' => 'Literal',
                                'options' => array(
                                    'route' => '/save',
                                    'defaults' => array(
                                        'controller' => 'controller_apartment_channel_connection',
                                        'action' => 'save'
                                    )
                                )
                            ),
                            'connect' => array(
                                'type' => 'Literal',
                                'options' => array(
                                    'route' => '/connect',
                                    'defaults' => array(
                                        'controller' => 'controller_apartment_channel_connection',
                                        'action' => 'connect'
                                    )
                                )
                            ),
                            'link' => array(
                                'type' => 'Literal',
                                'options' => array(
                                    'route' => '/link',
                                    'defaults' => array(
                                        'controller' => 'controller_apartment_channel_connection',
                                        'action' => 'link'
                                    )
                                )
                            ),
                            'test-pull-reservations' => array(
                                'type' => 'Literal',
                                'options' => array(
                                    'route' => '/test-pull-reservations',
                                    'defaults' => array(
                                        'controller' => 'controller_apartment_channel_connection',
                                        'action' => 'test-pull-reservations'
                                    )
                                )
                            ),
                            'test-update-availability' => array(
                                'type' => 'Literal',
                                'options' => array(
                                    'route' => '/test-update-availability',
                                    'defaults' => array(
                                        'controller' => 'controller_apartment_channel_connection',
                                        'action' => 'test-update-availability'
                                    )
                                )
                            ),
                            'test-fetch-list' => array(
                                'type' => 'Literal',
                                'options' => array(
                                    'route' => '/test-fetch-list',
                                    'defaults' => array(
                                        'controller' => 'controller_apartment_channel_connection',
                                        'action' => 'test-fetch-list'
                                    )
                                )
                            ),
                            'add-ota' => array(
                                'type' => 'Literal',
                                'options' => array(
                                    'route' => '/add-ota',
                                    'defaults' => array(
                                        'controller' => 'controller_apartment_channel_connection',
                                        'action' => 'ajax-save-ota'
                                    )
                                )
                            ),
                            'remove-ota' => array(
                                'type' => 'Segment',
                                'options' => array(
                                    'route' => '/remove-ota[/:ota_id]',
                                    'constraints' => array(
                                        'ota_id' => '[0-9]*',
                                    ),
                                    'defaults' => array(
                                        'controller' => 'controller_apartment_channel_connection',
                                        'action' => 'remove-ota'
                                    )
                                )
                            ),
                            'check-ota-connection' => array(
                                'type' => 'Segment',
                                'options' => array(
                                    'route' => '/check-ota-connection/:ota_id',
                                    'constraints' => array(
                                        'ota_id' => '[1-9][0-9]*',
                                    ),
                                    'defaults' => array(
                                        'controller' => 'controller_apartment_channel_connection',
                                        'action' => 'ajax-check-ota-connection'
                                    )
                                )
                            ),
                        )
                    ),
                    'cost' => array(
                        'type' => 'Segment',
                        'options' => array(
                            'route' => '/cost[/:action]',
                            'constraints' => array(
                                'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                            ),
                            'defaults' => array(
                                'controller' => 'controller_apartment_costs',
                                'action' => 'index'
                            )
                        ),
                    ),
                    'review' => array(
                        'type' => 'Segment',
                        'options' => array(
                            'route' => '/review[/:action][/:review_id][/:status]',
                            'constraints' => array(
                                'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                                'review_id' => '[0-9]*',
                                'status' => '[0-9]*',
                            ),
                            'defaults' => array(
                                'controller' => 'controller_apartment_review',
                                'action' => 'index'
                            )
                        ),
                    ),
                    'statistics' => array(
                        'type' => 'Segment',
                        'options' => array(
                            'route' => '/statistics[/:action]',
                            'constraints' => array(
                                'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                            ),
                            'defaults' => array(
                                'controller' => 'controller_apartment_statistics',
                                'action' => 'index'
                            )
                        )
                    ),
                    'history' => array(
                        'type' => 'Segment',
                        'options' => array(
                            'route' => '/history[/:action][/:history_id]',
                            'constraints' => array(
                                'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                                'history_id' => '[0-9]*',
                            ),
                            'defaults' => array(
                                'controller' => 'controller_apartment_history',
                                'action' => 'index'
                            )
                        ),
                    ),
                    'welcome-note' => array(
                        'type' => 'Literal',
                        'options' => array(
                            'route' => '/welcome-note',
                            'defaults' => array(
                                'controller' => 'controller_apartment_welcome_note',
                                'action' => 'index'
                            )
                        )
                    ),
                )
            ),
            'add_apartment' => array(
                'type' => 'Literal',
                'options' => array(
                    'route' => '/apartment-new',
                    'defaults' => array(
                        'controller' => 'controller_apartment_general',
                        'action' => 'index'
                    )
                ),
            ),

            'occupancy_statistics' => [
                'type' => 'Segment',
                'options' => [
                    'route' => '/occupancy-statistics/:action[/:year][/:month]',
                    'defaults' => [
                        'controller' => 'controller_apartment_occupancy_statistics',
                        'action'     => 'index',
                    ],
                    'constraints' => [
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'year'   => '[0-9]{4}',
                        'month'  => '[0-9]{1,2}',
                    ],
                ],
            ],

            'apartment_review_category' => [
                'type' => 'Segment',
                'options' => [
                    'route' => '/apartment-review-category[/:action][/:id]',
                    'defaults' => [
                        'controller' => 'controller_apartment_review_category',
                        'action'     => 'index',
                    ],
                    'constraints' => [
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'id'   => '[0-9]*',
                    ],
                ],
            ],

            'apartments' => [
                'type' => 'Segment',
                'options' => [
                    'route' => '/apartments/:action',
                    'defaults' => [
                        'controller' => 'controller_apartment_apartment',
                        'action'     => 'search',
                    ],
                    'constraints' => [
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                    ],
                ],
            ],

            'sales_statistics' => [
                'type' => 'Literal',
                'options' => [
                    'route' => '/sales-statistics',
                    'defaults' => [
                        'controller' => 'controller_apartment_sales_statistics',
                        'action'     => 'index',
                    ]
                ],
            ],


        )
    ),
    'view_manager' => array(
        'template_map' => array(
            'apartment/partial/navigation' => __DIR__ . '/../view/partial/navigation.phtml',
            'apartment/partial/badges' => __DIR__ . '/../view/partial/badges.phtml',
        ),
        'template_path_stack' => array(
            __DIR__ . '/../view'
        ),
        'strategies' => array(
            'ViewJsonStrategy'
        )
    )
);
