<?php

return [
    'controllers' => [
        'invokables' => [
            'controller_document' => 'Document\Controller\Document',
        ],
    ],
    'router' => [
        'routes' => [
            'documents' => [
                'type' => 'Segment',
                'options' => [
                    'route' => '/documents',
                    'defaults' => [
                        'controller' => 'controller_document',
                        'action' => 'search'
                    ],
                ],
                'may_terminate' => true,
                'child_routes' => [
                    'edit_document' => [
                        'type' => 'Segment',
                        'options' => [
                            'route' => '/edit[/][:id]',
                            'constraints' => [
                                'id' => '[0-9]+'
                            ],
                            'defaults' => [
                                'controller' => 'controller_document',
                                'action' => 'edit'
                            ],
                        ],
                    ],
                    'delete' => [
                        'type' => 'Segment',
                        'options' => [
                            'route' => '/delete/:id',
                            'constraints' => [
                                'id' => '[0-9]+'
                            ],
                            'defaults' => [
                                'controller' => 'controller_document',
                                'action' => 'delete'
                            ],
                        ],
                    ],
                    'delete-attachment' => [
                        'type' => 'Segment',
                        'options' => [
                            'route' => '/delete-attachment/:id',
                            'constraints' => [
                                'id' => '[0-9]+'
                            ],
                            'defaults' => [
                                'controller' => 'controller_document',
                                'action' => 'delete-attachment'
                            ],
                        ],
                    ],
                    'download' => [
                        'type' => 'Segment',
                        'options' => [
                            'route' => '/download/:id',
                            'constraints' => [
                                'id' => '[0-9]+'
                            ],
                            'defaults' => [
                                'controller' => 'controller_document',
                                'action' => 'download'
                            ],
                        ],
                    ],
                    'get-entity-list' => [
                        'type' => 'Segment',
                        'options' => [
                            'route' => '/get-entity-list',
                            'defaults' => [
                                'controller' => 'controller_document',
                                'action' => 'ajax-get-entity-list'
                            ],
                        ],
                    ],
                    'get-supplier-list' => [
                        'type' => 'Segment',
                        'options' => [
                            'route' => '/get-supplier-list',
                            'defaults' => [
                                'controller' => 'controller_document',
                                'action' => 'ajax-get-supplier-list'
                            ],
                        ],
                    ],
                    'get-user-list' => [
                        'type' => 'Segment',
                        'options' => [
                            'route' => '/get-user-list',
                            'defaults' => [
                                'controller' => 'controller_document',
                                'action' => 'ajax-get-user-list'
                            ],
                        ],
                    ],
                    'get-json' => [
                        'type' => 'Segment',
                        'options' => [
                            'route' => '/get-json',
                            'defaults' => [
                                'controller' => 'controller_document',
                                'action' => 'ajax-get-documents-json'
                            ],
                        ],
                    ],
                    'download-csv' => [
                        'type' => 'Segment',
                        'options' => [
                            'route' => '/download-csv',
                            'defaults' => [
                                'controller' => 'controller_document',
                                'action' => 'download-csv'
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ],
    'view_manager' => [
        'template_map' => [],
        'template_path_stack' => [
            __DIR__ . '/../view'
        ],
        'strategies' => [
            'ViewJsonStrategy'
        ]
    ],
];
