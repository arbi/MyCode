<?php
return array (
	'controllers' => array (
		'invokables' => array (
			'controller_backofficeuser_authentication' => 'BackofficeUser\Controller\AuthenticationController',
			'controller_bo_user_evaluation' => 'BackofficeUser\Controller\UserEvaluationController',
			'controller_bo_user_schedule'   => 'BackofficeUser\Controller\UserScheduleController',
		)
	),
	'router' => array (
		'routes' => array (
			'backoffice_user_login' => array (
				'type' => 'Literal',
				'options' => array (
					'route' => '/authentication/login',
					'constraints' => array (
					),
					'defaults' => array (
						'controller' => 'controller_backofficeuser_authentication',
						'action' => 'login'
					)
				),
				'may_terminate' => true,
				'child_routes' => array (
				)
			),
			'backoffice_user_authenticate' => array (
				'type' => 'Literal',
				'options' => array (
					'route' => '/authentication/authenticate',
					'constraints' => array (
					),
					'defaults' => array (
						'controller' => 'controller_backofficeuser_authentication',
						'action' => 'authenticate'
					)
				),
				'may_terminate' => true,
				'child_routes' => array (
				)
			),
			'backoffice_user_logout' => array (
				'type' => 'Literal',
				'options' => array (
					'route' => '/authentication/logout',
					'constraints' => array (
					),
					'defaults' => array (
						'controller' => 'controller_backofficeuser_authentication',
						'action' => 'logout'
					)
				),
				'may_terminate' => true,
				'child_routes' => array (
				)
			),

            'backoffice_google_auth' => array (
				'type' => 'Literal',
				'options' => array (
					'route' => '/authentication/googlesignin',
					'constraints' => array (
					),
					'defaults' => array (
						'controller' => 'controller_backofficeuser_authentication',
						'action' => 'google-signin'
					)
				),
				'may_terminate' => true,
				'child_routes' => array (
				)
			),

            'schedule' => [
                'type' => 'Segment',
                'options' => [
                    'route' => '/user-schedule[/:action]',
                    'constraints' => [
                        'action'        => '[a-zA-Z][a-zA-Z0-9_-]*',
                    ],
                    'defaults' => [
                        'controller' => 'controller_bo_user_schedule',
                        'action' => 'index'
                    ],
                ],
            ],

            'evaluation' => [
                'type' => 'Literal',
                'options' => [
                    'route' => '/user-evaluation',
                    'constraints' => [
                    ],
                    'defaults' => [
                        'controller' => 'controller_bo_user_evaluation',
                        'action' => 'index'
                    ]
                ],
                'may_terminate' => true,
                'child_routes' => [
                    'add' => [
                        'type' => 'Literal',
                        'options' => [
                            'route' => '/add',
                            'defaults' => [
                                'controller' => 'controller_bo_user_evaluation',
                                'action' => 'add',
                            ],
                        ],
                    ],
                    'edit' => [
                        'type' => 'Segment',
                        'options' => [
                            'route' => '/:user_id/edit-planned-evaluation/:evaluation_id',
                            'defaults' => [
                                'controller' => 'controller_bo_user_evaluation',
                                'action' => 'edit-planned-evaluation',
                            ],
                        ],
                    ],
                    'save-planned-evaluation' => [
                        'type' => 'Literal',
                        'options' => [
                            'route' => '/save-planned-evaluation',
                            'defaults' => [
                                'controller' => 'controller_bo_user_evaluation',
                                'action' => 'ajax-save-planned-evaluation',
                            ],
                        ],
                    ],
                    'plan' => [
                        'type' => 'Literal',
                        'options' => [
                            'route' => '/plan',
                            'defaults' => [
                                'controller' => 'controller_bo_user_evaluation',
                                'action' => 'ajax-plan-evaluation',
                            ],
                        ],
                    ],
                    'delete' => [
                        'type' => 'Segment',
                        'options' => [
                            'route' => '/:user_id/delete/:evaluation_id',
                            'defaults' => [
                                'controller' => 'controller_bo_user_evaluation',
                                'action' => 'delete',
                            ],
                        ],
                    ],
                    'view' => [
                        'type' => 'Segment',
                        'options' => [
                            'route' => '/view/:evaluation_id',
                            'defaults' => [
                                'controller' => 'controller_bo_user_evaluation',
                                'action' => 'view',
                            ],
                        ],
                    ],
                    'print' => [
                        'type' => 'Segment',
                        'options' => [
                            'route' => '/print/:evaluation_id',
                            'defaults' => [
                                'controller' => 'controller_bo_user_evaluation',
                                'action' => 'print',
                            ],
                        ],
                    ],
                    'cancel' => [
                        'type' => 'Segment',
                        'options' => [
                            'route' => '/cancel/:evaluation_id',
                            'defaults' => [
                                'controller' => 'controller_bo_user_evaluation',
                                'action' => 'cancel',
                            ],
                        ],
                    ],
                    'resolve' => [
                        'type' => 'Segment',
                        'options' => [
                            'route' => '/resolve/:evaluation_id',
                            'defaults' => [
                                'controller' => 'controller_bo_user_evaluation',
                                'action' => 'resolve',
                            ],
                        ],
                    ],
                    'get-user-evaluations' => [
                        'type' => 'Segment',
                        'options' => [
                            'route' => '/get-user-evaluations[/:user_id]',
                            'defaults' => [
                                'controller' => 'controller_bo_user_evaluation',
                                'action' => 'ajax-get-user-evaluations',
                            ],
                        ],
                    ],
                ]
            ],
		)
	),
	'view_manager' => array (
		'template_map' => array (
			'layout/login' => __DIR__ . '/../view/layout/login.phtml',
			'backoffice-user/authentication/dynamic-login' => __DIR__ . '/../view/backoffice-user/authentication/dynamic-login.phtml',
		),
		'template_path_stack' => array (
			__DIR__ . '/../view'
		),
		'strategies' => array (
			'ViewJsonStrategy'
		)
	)
);
