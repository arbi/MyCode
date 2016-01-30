<?php

return array(
    'controllers' => array(
        'invokables' => array(
            'controller_lock_general'    => 'Lock\Controller\General',
        )
    ),
    'router' => array(
        'routes' => array(
            'lock' => array(
                'type' => 'Segment',
                'options' => array(
                    'route' => '/lock',
                    'defaults' => array(
                        'controller' => 'controller_lock_general',
                        'action' => 'index'
                    )
                ),
                'may_terminate' => true,
                'child_routes' => array(
                    'edit' => array(
                        'type' => 'Segment',
                        'options' => array(
                            'route' => '/edit/:id',
                            'constraints' => array(
                                'id' => '[0-9]+'
                            ),
                            'defaults' => array(
                                'controller' => 'controller_lock_general',
                                'action' => 'edit'
                            )
                        ),
                    ),
                    'add' => array(
                        'type' => 'Literal',
                        'options' => array(
                            'route' => '/add',
                            'defaults' => array(
                                'controller' => 'controller_lock_general',
                                'action' => 'add'
                            )
                        ),
                    ),
                    'ajax-get-settings-by-type' => array(
                        'type' => 'Literal',
                        'options' => array(
                            'route' => '/ajax-get-settings-by-type',
                            'defaults' => array(
                                'controller' => 'controller_lock_general',
                                'action' => 'ajaxGetSettingsByType'
                            )
                        ),
                    ),
                    'ajax-save-new-lock' => array(
                        'type' => 'Literal',
                        'options' => array(
                            'route' => '/ajax-save-new-lock',
                            'defaults' => array(
                                'controller' => 'controller_lock_general',
                                'action' => 'ajaxSaveNewLock'
                            )
                        ),
                    ),
                    'ajax-edit-lock' => array(
                        'type' => 'Literal',
                        'options' => array(
                            'route' => '/ajax-edit-lock',
                            'defaults' => array(
                                'controller' => 'controller_lock_general',
                                'action' => 'ajaxEditLock'
                            )
                        ),
                    ),
                    'ajax-delete-lock' => array(
                        'type' => 'Literal',
                        'options' => array(
                            'route' => '/ajax-delete-lock',
                            'defaults' => array(
                                'controller' => 'controller_lock_general',
                                'action' => 'ajaxDeleteLock'
                            )
                        ),
                    ),
                    'get-lock-json' => array(
                        'type' => 'Literal',
                        'options' => array(
                            'route' => '/get-lock-json',
                            'defaults' => array(
                                'controller' => 'controller_lock_general',
                                'action' => 'getLockJson'
                            )
                        ),
                    ),
                )
            ),
        )

    ),
    'view_manager' => [
        'template_map' => [],
        'template_path_stack' => [
            __DIR__ . '/../view'
        ],
        'strategies' => [
            'ViewJsonStrategy'
        ]
    ]
);
