<?php
return [
	'controllers' => [
		'invokables' => [
			'controller_cc_test' => 'CreditCard\Controller\TestController',
		]
	],

	'router' => [
		'routes' => [

            'cc-demo' => array(
                'type'          => 'Literal',
                'options'       => array(
                    'route'    => '/cc-demo',
                    'defaults' => array(
                        'controller'    => 'controller_cc_test',
                        'action'        => 'index'
                    ),
                ),
                'may_terminate' => true,
                'child_routes'  => array(


                    'store' => array(
                        'type'    => 'Zend\Mvc\Router\Http\Literal',
                        'options' => array(
                            'route'       => '/store',
                            'defaults'    => array(
                                'controller'    => 'controller_cc_test',
                                'action'        => 'store-card'
                            ),
                        ),
                    ),
                    'retrieve' => array(
                        'type'    => 'Zend\Mvc\Router\Http\Segment',
                        'options' => array(
                            'route'       => '/retrieve/:cc_id',
                            'defaults'    => array(
                                'controller'    => 'controller_cc_test',
                                'action'        => 'get-card',
                            ),
                        ),
                    ),
                ),
            ),

		]
	],
	'view_manager' => array (
		'template_map' => array (
		),
		'template_path_stack' => array (
			__DIR__ . '/../view'
		),
		'strategies' => array (
			'ViewJsonStrategy'
		)
	)
];
