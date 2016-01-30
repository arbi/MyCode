<?php

return array(
    'router' => array(
        'routes' => array(
            'universal-dashboard' => array(
                'type'          => 'Literal',
                'options'       => array(
                    'route'    => '/ud',
                    'defaults' => array(
                        '__NAMESPACE__' => 'UniversalDashboard\Controller',
                        'controller'    => 'UniversalDashboard',
                        'action'        => 'index'
                    ),
                ),
                'may_terminate' => true,
                'child_routes'  => array(
                    'default' => array(
                        'type'    => 'Zend\Mvc\Router\Http\Segment',
                        'options' => array(
                            'route'       => '[/:controller[/:action[/:id]]]',
                            'constraints' => array(
                                'controller' => '[a-zA-Z][a-zA-Z0-9_-]*',
                                'action'     => '[a-zA-Z][a-zA-Z0-9_-]*',
                                'id'         => '[a-zA-Z0-9_-]*',
                            ),
                            'defaults'    => array(
                                '__NAMESPACE__' => 'UniversalDashboard\Controller',
                                'controller'    => 'UniversalDashboard',
                                'action'        => 'index',
                            ),
                        ),
                    ),
                ),
            ),
            'home' => array(
                'type' => 'regex',
                'options' => array(
                    'regex' => '/(home)?',
                    'defaults' => array(
                        '__NAMESPACE__' => 'UniversalDashboard\Controller',
                        'controller'    => 'UniversalDashboard',
                        'action'        => 'index'
                    ),
                    'spec' => '%'
                ),
            )
        ),
    ),
    'controllers'  => array(
        'invokables' => array(
            'UniversalDashboard\Controller\UniversalDashboard'     => 'UniversalDashboard\Controller\UniversalDashboardController',
            'UniversalDashboard\Controller\UniversalDashboardData' => 'UniversalDashboard\Controller\UniversalDashboardDataController',
        ),
    ),
    'view_manager' => array(
        'template_path_stack' => array(
            __DIR__ . '/../view',
            'universal-dashboard/universal-dashboard/index' => __DIR__ . '/../view/universal-dashboard/universal-dashboard/index.phtml',
        ),
    ),
    'view_helpers' => array(
        'invokables' => array(
            'UDTableWidget' => 'UniversalDashboard\View\Helper\UDTableWidget'
        )
    )
);
