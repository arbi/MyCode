<?php
return [
    'router' => [
        'routes' => [           
            'recruitment' => [
                'type'    => 'literal',
                'options' => [
                    'route'    => '/recruitment',
                ],
                'may_terminate' => true,
                'child_routes'  => [
                    'default'   => [
                        'type'    => 'segment',
                        'options' => [
                            'route' => '[/:controller[/:action[/:id]]]',
                            'constraints' => [
                                'controller'    => '[a-zA-Z][a-zA-Z0-9_-]*',
                                'action'        => '[a-zA-Z][a-zA-Z0-9_-]*',
                                'id'            => '[a-zA-Z0-9_-]*',
                            ]
                        ],
                    ],

                    'jobs'   => [
                        'type'    => 'segment',
                        'options' => [
                            'route' => '/jobs[/:action[/:id]]',
                            'constraints' => [
                                'action'        => '[a-zA-Z][a-zA-Z0-9_-]*',
                                'id'            => '[a-zA-Z0-9_-]*',
                            ],
                            'defaults' => [
                                '__NAMESPACE__' => 'Recruitment\Controller',
                                'controller'    => 'Jobs',
                                'action'        => 'index',
                            ],
                        ],
                    ],

                    'applicants'   => [
                        'type'    => 'segment',
                        'options' => [
                            'route' => '/applicants[/:action[/:id]]',
                            'constraints' => [
                                'action'        => '[a-zA-Z][a-zA-Z0-9_-]*',
                                'id'            => '[a-zA-Z0-9_-]*',
                            ],
                            'defaults' => [
                                '__NAMESPACE__' => 'Recruitment\Controller',
                                'controller'    => 'Applicants',
                                'action'        => 'index',
                            ],
                        ],
                    ],
                ]
            ]
        ]
    ],

    'controllers' => [
        'invokables' => [
            'Recruitment\Controller\Jobs'       => 'Recruitment\Controller\JobsController',
            'Recruitment\Controller\Applicants' => 'Recruitment\Controller\ApplicantsController',
        ]
    ],

    'view_manager' => [
        'template_path_stack' => [
                __DIR__ . '/../view'
        ],
    ],
];
