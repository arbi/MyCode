<?php

return [
    'router' => [
        'routes' => [
            'finance' => [
                'type'    => 'literal',
                'options' => [
                    'route'    => '/finance',
                    'defaults' => [
                        '__NAMESPACE__' => 'Finance\Controller',
                    ],
                ],
                'may_terminate' => true,
                'child_routes'  => [
                    'default'   => [
                        'type'    => 'Segment',
                        'options' => [
                            'route' => '[/:controller[/:action[/:id]]]',
                            'constraints' => [
                                'controller'    => '[a-zA-Z][a-zA-Z0-9_-]*',
                                'action'        => '[a-zA-Z][a-zA-Z0-9_-]*',
                                'id'            => '[a-zA-Z0-9_-]*',
                            ]
                        ],
                    ],
                    'suppliers'   => [
                        'type'    => 'segment',
                        'options' => [
                            'route' => '/suppliers[/:action[/:id]]',
                            'constraints' => [
                                'action'        => '[a-zA-Z][a-zA-Z0-9_-]*',
                                'id'            => '[a-zA-Z0-9_-]*',
                            ],
                            'defaults' => [
                                'controller'    => 'Suppliers',
                                'action'        => 'index',
                            ],
                        ],
                    ],

                    'money-account-download' => array(
                        'type' => 'Segment',
                        'options' => array(
                            'route'    => '/money-account-download[/:money_account_id]/download[/:files][/:doc_id]',
                            'constraints' => array(
                                'money_account_id'     => '[0-9]+',
                                'files' => '[a-zA-Z][a-zA-Z0-9_-]*',
                                'doc_id' => '[0-9]*',
                            ),
                            'defaults' => array(
                                'controller'    => 'money-account',
                                'action'        => 'download'
                            ),
                        ),
                    ),

                    'suppliers_activate' => [
                        'type' => 'segment',
                        'options' => [
                            'route'    => '/suppliers/activate[/:id[/:status]]',
                            'constraints' => [
                                    'id'     => '[0-9]*',
                                    'status' => '[0-9]*'
                            ],
                            'defaults' => [
                                'controller'    => 'Suppliers',
                                'action'        => 'activate'
                            ],
                        ],
                    ],
                    'legal-entities'   => [
                        'type'    => 'segment',
                        'options' => [
                            'route' => '/legal-entities[/:action[/:id]]',
                            'constraints' => [
                                'action'        => '[a-zA-Z][a-zA-Z0-9_-]*',
                                'id'            => '[a-zA-Z0-9_-]*',
                            ],
                            'defaults' => [
                                'controller'    => 'legal-entities',
                                'action'        => 'index',
                            ],
                        ],
                    ],

                    'legal-entities-activate' => [
                        'type' => 'segment',
                        'options' => [
                            'route'    => '/legal-entities/activate[/:id[/:status]]',
                            'constraints' => [
                                'id'     => '[0-9]*',
                                'status' => '[0-9]*'
                            ],
                            'defaults' => [
                                'controller'    => 'legal-entities',
                                'action'        => 'activate'
                            ],
                        ],
                    ],


                    'expense-item-categories' => [
                        'type'    => 'segment',
                        'options' => [
                            'route'    => '/expense-item-categories[/:action[/:id]]',
                            'defaults' => [
                                'controller'    => 'expense-item-categories',
                                'action'        => 'index'
                            ],
                            'constraints' => [
                                'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                                'id'     => '[0-9]*'
                            ],
                        ],
                    ],

                    'psp' => [
                        'type' => 'segment',
                        'options' => [
                            'route'    => '/psp[/:action][/:id]',
                            'constraints' => [
                                    'id'     => '[0-9]*',
                                    'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                            ],
                            'defaults' => [
                                'controller'    => 'Psp',
                                'action'        => 'index'
                            ],
                        ],
                    ],

                    'money-account' => [
                        'type'    => 'segment',
                        'options' => [
                            'route'    => '/money-account[/:action][/:id]',
                            'constraints' => [
                                'id'     => '[0-9]*',
                                'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                            ],
                            'defaults' => [
                                'controller'    => 'money-account',
                                'action'        => 'index'
                            ],
                        ],
                    ],

                    'money-account-verify-status' => [
                        'type'    => 'segment',
                        'options' => [
                            'route'    => '/money-account/change-verify-status[/:id][/:status]',
                            'constraints' => [
                                'id'     => '[0-9]*',
                                'status' => '[0-9]*',
                            ],
                            'defaults' => [
                                'controller'    => 'money-account',
                                'action'        => 'change-verify-status'
                            ],
                        ],
                    ],

                    'money-account-void' => [
                        'type'    => 'segment',
                        'options' => [
                            'route'    => '/money-account/void[/:id]',
                            'constraints' => [
                                'id'     => '[0-9]*',
                            ],
                            'defaults' => [
                                'controller'    => 'money-account',
                                'action'        => 'void'
                            ],
                        ],
                    ],

                    'transfer' => [
                        'type' => 'Literal',
                        'options' => [
                            'route'    => '/transfer',
                            'constraints' => [
                                'id'     => '[0-9]*',
                                'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                            ],
                            'defaults' => [
                                'controller'    => 'Transfer',
                                'action'        => 'index'
                            ],
                        ],
                        'may_terminate' => true,
                        'child_routes'  => [
                            'save-pending' => [
                                'type' => 'literal',
                                'options' => [
                                    'route'    => '/save-pending',
                                    'defaults' => [
                                        'controller'    => 'Transfer',
                                        'action'        => 'save-pending'
                                    ],
                                ],
                            ],
                            'cancel' => [
                                'type' => 'segment',
                                'options' => [
                                    'route'    => '/cancel/:id',
                                    'defaults' => [
                                        'controller'    => 'Transfer',
                                        'action'        => 'cancel'
                                    ],
                                ],
                            ],
                            'get-distribution-list' => [
                                'type' => 'literal',
                                'options' => [
                                    'route'    => '/get-apartments-and-apartels',
                                    'defaults' => [
                                        'controller'    => 'Transfer',
                                        'action'        => 'get-apartments-and-apartels'
                                    ],
                                ],
                            ],
                            'get-partner-payment-reservations' => [
                                'type'    => 'Literal',
                                'options' => [
                                    'route'    => '/get-partner-payment-reservations',
                                    'defaults' => [
                                        'action' => 'get-partner-payment-reservations',
                                    ],
                                ],
                            ],
                            'get-expense-item-balance' => [
                                'type'    => 'Literal',
                                'options' => [
                                    'route'    => '/get-expense-item-balance',
                                    'defaults' => [
                                        'action' => 'get-expense-item-balance',
                                    ],
                                ],
                            ],
                            'get-transactions-to-collect' => [
                                'type'    => 'Literal',
                                'options' => [
                                    'route'    => '/get-transactions-to-collect',
                                    'defaults' => [
                                        'action' => 'get-transactions-to-collect',
                                    ],
                                ],
                            ],
                        ],
                    ],

                    'chart' => [
                        'type'    => 'literal',
                        'options' => [
                            'route'    => '/chart',
                        ],
                        'may_terminate' => true,
                        'child_routes'  => [
                            'charge'   => [
                                'type'    => 'literal',
                                'options' => [
                                    'route'    => '/charge',
                                    'defaults' => [
                                        'controller'    => 'Chart',
                                        'action'        => 'charge'
                                    ],
                                ],
                                'may_terminate' => true,
                                'child_routes'  => [
                                    'get'   => [
                                        'type'    => 'literal',
                                        'options' => [
                                            'route'    => '/get',
                                            'defaults' => [
                                                'controller'    => 'Chart',
                                                'action'        => 'get-charge'
                                            ],
                                        ],
                                    ],
                                    'download'   => [
                                        'type'    => 'literal',
                                        'options' => [
                                            'route'    => '/download',
                                            'defaults' => [
                                                'controller'    => 'Chart',
                                                'action'        => 'download-charge'
                                            ],
                                        ],
                                    ],
                                ],
                            ],

                            'transaction'   => [
                                'type'    => 'literal',
                                'options' => [
                                    'route'    => '/transaction',
                                    'defaults' => [
                                        'controller'    => 'Chart',
                                        'action'        => 'transaction'
                                    ],
                                ],
                                'may_terminate' => true,
                                'child_routes'  => [
                                    'get'   => [
                                        'type'    => 'literal',
                                        'options' => [
                                            'route'    => '/get',
                                            'defaults' => [
                                                'controller'    => 'Chart',
                                                'action'        => 'get-transaction',
                                            ],
                                        ],
                                    ],
                                    'download'   => [
                                        'type'    => 'literal',
                                        'options' => [
                                            'route'    => '/download',
                                            'defaults' => [
                                                'controller'    => 'Chart',
                                                'action'        => 'download-transaction',
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],

                    'item' => [
                        'type' => 'literal',
                        'options' => [
                            'route'    => '/item',
                            'constraints' => [
                                'id'     => '[0-9]*',
                                'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                            ],
                            'defaults' => [
                                'controller'    => 'purchase-order',
                                'action'        => 'item',
                            ],
                        ],
                        'may_terminate' => true,
                        'child_routes'  => [

                            'search'   => [
                                'type'    => 'Literal',
                                'options' => [
                                    'route'    => '/search',
                                    'defaults' => [
                                        'action' => 'index',
                                        'controller' => 'purchase-order-item'
                                    ],

                                ],
                            ],

                            'get-datatable-data'   => [
                                'type'    => 'Literal',
                                'options' => [
                                    'route'    => '/get-datatable-data',
                                    'defaults' => [
                                        'action' => 'get-datatable-data',
                                        'controller' => 'purchase-order-item'
                                    ],

                                ],
                            ],

                            'validate-download-csv'   => [
                                'type'    => 'Literal',
                                'options' => [
                                    'route'    => '/validate-download-csv',
                                    'defaults' => [
                                        'action' => 'validate-download-csv',
                                        'controller' => 'purchase-order-item'
                                    ],
                                ],
                            ],

                            'download-csv'   => [
                                'type'    => 'Literal',
                                'options' => [
                                    'route'    => '/download-csv',
                                    'defaults' => [
                                        'action' => 'download-csv',
                                        'controller' => 'purchase-order-item'
                                    ],
                                ],
                            ],

                            'edit'   => [
                                'type'    => 'Segment',
                                'options' => [
                                    'route'    => '/:id',
                                    'defaults' => [
                                        'action' => 'item',
                                    ],
                                    'constraints' => [
                                        'id'     => '[0-9]*',
                                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                                    ],
                                ],
                            ],
                            'save'   => [
                                'type'    => 'Segment',
                                'options' => [
                                    'route'    => '[/:id]/save',
                                    'defaults' => [
                                        'action' => 'save-item',
                                    ],
                                ],
                            ],
                            'reject'   => [
                                'type'    => 'Segment',
                                'options' => [
                                    'route'    => '[/:id]/reject',
                                    'defaults' => [
                                        'action' => 'reject-item',
                                    ],
                                ],
                            ],
                            'remove'   => [
                                'type'    => 'Segment',
                                'options' => [
                                    'route'    => '[/:id]/remove',
                                    'defaults' => [
                                        'action' => 'remove-rejected-item',
                                    ],
                                ],
                            ],
                            'delete-attachment' => [
                                'type'    => 'Segment',
                                'options' => [
                                    'route'    => '/delete-attachment',
                                    'defaults' => [
                                        'action'     => 'ajax-delete-attachment',
                                        'controller' => 'purchase-order-item'
                                    ],
                                ],
                            ],
                            'complete'   => [
                                'type'    => 'Segment',
                                'options' => [
                                    'route'    => '[/:id]/complete',
                                    'defaults' => [
                                        'action' => 'complete-item',
                                    ],
                                ],
                            ],
                            'approve'   => [
                                'type'    => 'Segment',
                                'options' => [
                                    'route'    => '[/:id]/approve',
                                    'defaults' => [
                                        'action' => 'approve-item',
                                    ],
                                ],
                            ],
                            'change-manager'   => [
                                'type'    => 'Segment',
                                'options' => [
                                    'route'    => '[/:id]/change-manager',
                                    'defaults' => [
                                        'action' => 'change-item-manager',
                                    ],
                                ],
                            ],
                            'download-attachment' => [
                                'type'    => 'Segment',
                                'options' => [
                                    'route'    => '/:id/download-attachment[/:tmp]',
                                    'defaults' => [
                                        'action' => 'download-item-attachment',
                                    ],
                                ],
                            ],
                        ],
                    ],

                    'purchase-order' => [
                        'type' => 'Segment',
                        'options' => [
                            'route'    => '/purchase-order',
                            'constraints' => [
                                'id'     => '[0-9]*',
                                'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                            ],
                            'defaults' => [
                                'controller'    => 'purchase-order',
                                'action'        => 'index',
                            ],
                        ],
                        'may_terminate' => true,
                        'child_routes'  => [
                            'get-expenses' => [
                                'type'    => 'literal',
                                'options' => [
                                    'route'    => '/get-expenses',
                                    'defaults' => [
                                        'action' => 'get-expenses',
                                    ],
                                ],
                            ],
                            'get-items' => [
                                'type'    => 'literal',
                                'options' => [
                                    'route'    => '/get-items',
                                    'defaults' => [
                                        'action' => 'get-items',
                                    ],
                                ],
                            ],
                            'get-transactions' => [
                                'type'    => 'literal',
                                'options' => [
                                    'route'    => '/get-transactions',
                                    'defaults' => [
                                        'action' => 'get-transactions',
                                    ],
                                ],
                            ],
                            'add' => [
                                'type'    => 'literal',
                                'options' => [
                                    'route'    => '/ticket',
                                    'defaults' => [
                                        'action' => 'add',
                                    ],
                                ],
                            ],
                            'edit' => [
                                'type'    => 'segment',
                                'options' => [
                                    'route'    => '/ticket/:id[/:item_id]',
                                    'defaults' => [
                                        'action' => 'edit',
                                    ],
                                    'constraints' => [
                                        'id'     => '[1-9][0-9]*',
                                        'item_id'=> '[1-9][0-9]*',
                                    ],
                                ],
                            ],
                            'save' => [
                                'type'    => 'literal',
                                'options' => [
                                    'route'    => '/save',
                                    'defaults' => [
                                        'action' => 'save',
                                    ],
                                ],
                            ],
                            'get-sub-categories' => [
                                'type'    => 'literal',
                                'options' => [
                                    'route'    => '/get-sub-categories',
                                    'defaults' => [
                                        'action' => 'get-sub-categories',
                                    ],
                                ],
                            ],
                            'get-accounts' => [
                                'type'    => 'literal',
                                'options' => [
                                    'route'    => '/get-accounts',
                                    'defaults' => [
                                        'action' => 'get-accounts',
                                    ],
                                ],
                            ],
                            'get-cost-centers' => [
                                'type'    => 'literal',
                                'options' => [
                                    'route'    => '/get-cost-centers',
                                    'defaults' => [
                                        'action' => 'get-cost-centers',
                                    ],
                                ],
                            ],
                            'get-affiliates' => [
                                'type'    => 'literal',
                                'options' => [
                                    'route'    => '/get-affiliates',
                                    'defaults' => [
                                        'action' => 'get-affiliates',
                                    ],
                                ],
                            ],
                            'get-people' => [
                                'type'    => 'literal',
                                'options' => [
                                    'route'    => '/get-people',
                                    'defaults' => [
                                        'action' => 'get-people',
                                    ],
                                ],
                            ],
                            'get-money-accounts' => [
                                'type'    => 'literal',
                                'options' => [
                                    'route'    => '/get-money-accounts',
                                    'defaults' => [
                                        'action' => 'get-money-accounts',
                                    ],
                                ],
                            ],
                            'get-currencies' => [
                                'type'    => 'literal',
                                'options' => [
                                    'route'    => '/get-currencies',
                                    'defaults' => [
                                        'action' => 'get-currencies',
                                    ],
                                ],
                            ],
                            'get-offices' => [
                                'type'    => 'literal',
                                'options' => [
                                    'route'    => '/get-offices',
                                    'defaults' => [
                                        'action' => 'get-offices',
                                    ],
                                ],
                            ],
                            'preview' => [
                                'type'    => 'literal',
                                'options' => [
                                    'route'    => '/preview',
                                    'defaults' => [
                                        'action' => 'preview',
                                    ],
                                ],
                            ],
                            'download' => [
                                'type'    => 'literal',
                                'options' => [
                                    'route'    => '/download',
                                    'defaults' => [
                                        'action' => 'download',
                                    ],
                                ],
                            ],
                            'validate-download-csv' => [
                                'type'    => 'literal',
                                'options' => [
                                    'route'    => '/validate-download-csv',
                                    'defaults' => [
                                        'action' => 'validate-download-csv',
                                    ],
                                ],
                            ],
                            'download-attachment' => [
                                'type'    => 'literal',
                                'options' => [
                                    'route'    => '/download-attachment',
                                    'defaults' => [
                                        'action' => 'download-attachment',
                                    ],
                                ],
                            ],
                            'delete' => [
                                'type'    => 'Segment',
                                'options' => [
                                    'route'    => '/delete/:id',
                                    'defaults' => [
                                        'action' => 'delete',
                                    ],
                                ],
                            ],
                            'close' => [
                                'type'    => 'Segment',
                                'options' => [
                                    'route'    => '/close/:id',
                                    'defaults' => [
                                        'action' => 'close',
                                    ],
                                ],
                            ],
                            'duplicate' => [
                                'type'    => 'Segment',
                                'options' => [
                                    'route'    => '/duplicate/:id',
                                    'defaults' => [
                                        'action' => 'duplicate',
                                    ],
                                ],
                            ],
                            'approve' => [
                                'type'    => 'Segment',
                                'options' => [
                                    'route'    => '/approve/:id',
                                    'defaults' => [
                                        'action' => 'approve',
                                    ],
                                ],
                            ],
                            'reject' => [
                                'type'    => 'Segment',
                                'options' => [
                                    'route'    => '/reject/:id',
                                    'defaults' => [
                                        'action' => 'reject',
                                    ],
                                ],
                            ],
                            'handle' => [
                                'type'    => 'Segment',
                                'options' => [
                                    'route'    => '/handle/:id',
                                    'defaults' => [
                                        'action' => 'handle',
                                    ],
                                ],
                            ],
                            'ready' => [
                                'type'    => 'Segment',
                                'options' => [
                                    'route'    => '/ready/:id',
                                    'defaults' => [
                                        'action' => 'ready',
                                    ],
                                ],
                            ],
                            'settle' => [
                                'type'    => 'Segment',
                                'options' => [
                                    'route'    => '/settle/:id',
                                    'defaults' => [
                                        'action' => 'settle',
                                    ],
                                ],
                            ],
                            'unsettle' => [
                                'type'    => 'Segment',
                                'options' => [
                                    'route'    => '/unsettle/:id',
                                    'defaults' => [
                                        'action' => 'unsettle',
                                    ],
                                ],
                            ],
                            'revoke' => [
                                'type'    => 'Segment',
                                'options' => [
                                    'route'    => '/revoke/:id',
                                    'defaults' => [
                                        'action' => 'revoke',
                                    ],
                                ],
                            ],
                            'item' => [
                                'type' => 'Segment',
                                'options' => [
                                    'route'    => '/item/:id',
                                ],
                                'may_terminate' => true,
                                'child_routes'  => [
                                    'remove' => [
                                        'type'    => 'Segment',
                                        'options' => [
                                            'route'    => '/remove',
                                            'defaults' => [
                                                'action' => 'remove-item',
                                            ],
                                        ],
                                    ],
                                    'attach-transaction' => [
                                        'type'    => 'Literal',
                                        'options' => [
                                            'route'    => '/attach-transaction',
                                            'defaults' => [
                                                'action' => 'attach-transaction',
                                            ],
                                        ],
                                    ],
                                    'detach-transaction' => [
                                        'type'    => 'Literal',
                                        'options' => [
                                            'route'    => '/detach-transaction',
                                            'defaults' => [
                                                'action' => 'detach-transaction',
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                            'transaction' => [
                                'type' => 'Segment',
                                'options' => [
                                    'route'    => '/transaction/:id',
                                ],
                                'may_terminate' => true,
                                'child_routes'  => [
                                    'verify' => [
                                        'type'    => 'Literal',
                                        'options' => [
                                            'route'    => '/verify',
                                            'defaults' => [
                                                'action' => 'verify-transaction',
                                            ],
                                        ],
                                    ],
                                    'void' => [
                                        'type'    => 'Literal',
                                        'options' => [
                                            'route'    => '/void',
                                            'defaults' => [
                                                'action' => 'void-transaction',
                                            ],
                                        ],
                                    ],
                                    'attach-item' => [
                                        'type'    => 'Literal',
                                        'options' => [
                                            'route'    => '/attach-item',
                                            'defaults' => [
                                                'action' => 'attach-item',
                                            ],
                                        ],
                                    ],
                                    'detach-item' => [
                                        'type'    => 'Literal',
                                        'options' => [
                                            'route'    => '/detach-item',
                                            'defaults' => [
                                                'action' => 'detach-item',
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],


                    'budget' => [
                        'type' => 'Segment',
                        'options' => [
                            'route'    => '/budget',
                            'constraints' => [
                                'id'     => '[0-9]*',
                                'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                            ],
                            'defaults' => [
                                'controller'    => 'budget',
                                'action'        => 'index',
                            ],
                        ],
                        'may_terminate' => true,
                        'child_routes'  => [
                            'edit' => [
                                'type'    => 'segment',
                                'options' => [
                                    'route'    => '/edit[/:id]',
                                    'constraints' => [
                                        'id'     => '[0-9]*',
                                    ],
                                    'defaults' => [
                                        'action' => 'edit',
                                    ],
                                ],
                            ],
                            'frozen' => [
                                'type'    => 'segment',
                                'options' => [
                                    'route'    => '/frozen[/:id[/:frozen]]',
                                    'constraints' => [
                                        'id' => '[0-9]*',
                                        'frozen' => '[0-9]*',
                                    ],
                                    'defaults' => [
                                        'action' => 'frozen',
                                    ],
                                ],
                            ],
                            'get-datatable' => [
                                'type'    => 'segment',
                                'options' => [
                                    'route'    => '/get-datatable',
                                    'defaults' => [
                                        'action' => 'get-datatable',
                                    ],
                                ],
                            ],
                            'archive' => [
                                'type'    => 'segment',
                                'options' => [
                                    'route'    => '/archive[/:id[/:archive]]',
                                    'constraints' => [
                                        'id' => '[0-9]*',
                                        'frozen' => '[0-9]*',
                                    ],
                                    'defaults' => [
                                        'action' => 'archive',
                                    ],
                                ],
                            ],
                            'chart' => [
                                'type'    => 'segment',
                                'options' => [
                                    'route'    => '/chart',
                                    'defaults' => [
                                        'action' => 'chart',
                                    ],
                                ],
                            ],
                            'draw-chart' => [
                                'type'    => 'segment',
                                'options' => [
                                    'route'    => '/draw-chart',
                                    'defaults' => [
                                        'action' => 'draw-chart',
                                    ],
                                ],
                            ],
                        ],
                    ],

                    'espm' => [
                        'type' => 'Segment',
                        'options' => [
                            'route'    => '/espm',
                            'constraints' => [
                                'id'     => '[0-9]*',
                                'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                            ],
                            'defaults' => [
                                'controller'    => 'espm',
                                'action'        => 'index',
                            ],
                        ],
                        'may_terminate' => true,
                        'child_routes'  => [
                            'edit' => [
                                'type'    => 'segment',
                                'options' => [
                                    'route'    => '/edit[/:id]',
                                    'constraints' => [
                                        'id'     => '[0-9]*',
                                    ],
                                    'defaults' => [
                                        'action' => 'edit',
                                    ],
                                ],
                            ],
                            'get-datatable' => [
                                'type'    => 'segment',
                                'options' => [
                                    'route'    => '/get-datatable',
                                    'defaults' => [
                                        'action' => 'get-datatable',
                                    ],
                                ],
                            ],
                            'archive' => [
                                'type'    => 'segment',
                                'options' => [
                                    'route'    => '/archive[/:id[/:archive]]',
                                    'constraints' => [
                                        'id' => '[0-9]*',
                                        'frozen' => '[0-9]*',
                                    ],
                                    'defaults' => [
                                        'action' => 'archive',
                                    ],
                                ],
                            ],
                            'get-supplier-account' => [
                                'type'    => 'segment',
                                'options' => [
                                    'route'    => '/get-supplier-account[/:supplier_id]',
                                    'constraints' => [
                                        'id' => '[0-9]*',
                                        'frozen' => '[0-9]*',
                                    ],
                                    'defaults' => [
                                        'action' => 'get-supplier-account',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ],

    'controllers' => [
        'invokables' => [
            'Finance\Controller\Suppliers'             => 'Finance\Controller\SuppliersController',
            'Finance\Controller\LegalEntities'         => 'Finance\Controller\LegalEntitiesController',
            'Finance\Controller\ExpenseItemCategories' => 'Finance\Controller\ExpenseItemCategoriesController',
            'Finance\Controller\Psp'                   => 'Finance\Controller\PspController',
            'Finance\Controller\MoneyAccount'          => 'Finance\Controller\MoneyAccountController',
            'Finance\Controller\Transfer'              => 'Finance\Controller\TransferController',
            'Finance\Controller\Chart'                 => 'Finance\Controller\ChartController',
            'Finance\Controller\PurchaseOrder'         => 'Finance\Controller\PurchaseOrderController',
            'Finance\Controller\PurchaseOrderItem'     => 'Finance\Controller\PurchaseOrderItemController',
            'Finance\Controller\Budget'                => 'Finance\Controller\BudgetController',
            'Finance\Controller\Espm'                  => 'Finance\Controller\EspmController',
        ]
    ],

    'view_manager' => [
        'template_path_stack' => [
            __DIR__ . '/../view'
        ],
    ],
];
