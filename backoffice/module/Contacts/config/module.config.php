<?php
return [
    'router' => [
        'routes' => [
            'contacts' => [
                'type' => 'Segment',
                'options' => [
                    'route'    => '/contacts',
                    'defaults' => [
                        'controller'    => 'contacts',
                        'action'        => 'search'
                    ],
                ],
                'may_terminate' => true,
                'child_routes'  => [
                    'id' => [
                        'type' => 'Segment',
                        'options' => [
                            'route'    => '/id/[:contact_id]',
                            'defaults' => [
                                'controller' => 'contacts',
                                'action'     => 'search'
                            ]
                        ],
                    ],
                    'ajax-search-contact' => [
                        'type' => 'Segment',
                        'options' => [
                            'route'    => '/ajax-search-contact',
                            'defaults' => [
                                'controller' => 'contacts',
                                'action'     => 'ajax-search-contact'
                            ]
                        ],
                    ],
                    'ajax-get-contact' => [
                        'type' => 'Segment',
                        'options' => [
                            'route'    => '/ajax-get-contact',
                            'defaults' => [
                                'controller' => 'contacts',
                                'action'     => 'ajax-get-contact'
                            ]
                        ],
                    ],
                    'ajax-create-contact' => [
                        'type' => 'Segment',
                        'options' => [
                            'route'    => '/ajax-create-contact',
                            'defaults' => [
                                'controller' => 'contacts',
                                'action'     => 'ajax-create-contact'
                            ]
                        ],
                    ],
                    'edit' => [
                        'type' => 'Segment',
                        'options' => [
                            'route'    => '/edit/[:contact_id]',
                            'constraints' => [
                                'contact_id' => '[a-zA-Z0-9_-]*'
                            ],
                            'defaults' => [
                                'controller' => 'contacts',
                                'action'     => 'edit'
                            ]
                        ],
                    ],
                    'ajax-update-contact' => [
                        'type' => 'Segment',
                        'options' => [
                            'route'    => '/ajax-update-contact',
                            'defaults' => [
                                'controller' => 'contacts',
                                'action'     => 'ajax-update-contact'
                            ]
                        ],
                    ],
                    'ajax-delete-contact' => [
                        'type' => 'Segment',
                        'options' => [
                            'route'    => '/ajax-delete-contact/[:contact_id]',
                            'constraints' => [
                                'contact_id' => '[a-zA-Z0-9_-]*'
                            ],
                            'defaults' => [
                                'controller' => 'contacts',
                                'action'     => 'ajax-delete-contact'
                            ]
                        ],
                    ],
                    'ajax-get-apartment-building' => [
                        'type' => 'Segment',
                        'options' => [
                            'route'    => '/ajax-get-apartment-building/[:apartment_id]',
                            'constraints' => [
                                'apartment_id' => '[a-zA-Z0-9_-]*'
                            ],
                            'defaults' => [
                                'controller' => 'contacts',
                                'action'     => 'ajax-get-apartment-building'
                            ],
                        ],
                    ],
                    'ajax-get-phone-codes' => [
                        'type' => 'Segment',
                        'options' => [
                            'route'    => '/ajax-get-phone-codes',
                            'defaults' => [
                                'controller' => 'contacts',
                                'action'     => 'ajax-get-phone-codes'
                            ],
                        ],
                    ],
                ]
            ],
        ],
    ],
    'controllers' => [
        'invokables' => [
            'contacts'  => 'Contacts\Controller\Contacts',
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
