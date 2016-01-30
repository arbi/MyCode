<?php
return array(
    'router' => array(
        'routes' => array(
            'group_activate' => array(
                'type' => 'Segment',
                'options' => array(
                    'route'    => '/concierge/activate[/:id[/:status]]',
                    'constraints' => array(
                        'id'     => '[0-9]*',
                        'status' => '[0-9]*'
                    ),
                    'defaults' => array(
                        '__NAMESPACE__' => 'Backoffice\Controller',
                        'controller'    => 'Concierge',
                        'action'        => 'activate'
                    ),
                ),
            ),

            'backoffice' => array(
                'type'    => 'Segment',
                'options' => array(
                    'route'    => '',
                    'defaults' => array(
                        '__NAMESPACE__' => 'Backoffice\Controller',
                        'controller'    => 'Home',
                        'action'        => 'index'
                    ),
                ),
                'may_terminate' => true,
                'child_routes'  => array(
                    'default'   => array(
                        'type'    => 'Zend\Mvc\Router\Http\Segment',
                        'options' => array(
                            'route' => '[/:controller[/:action[/:id]]]',
                            'constraints' => array(
                                'controller'    => '[a-zA-Z][a-zA-Z0-9_-]*',
                                'action'        => '[a-zA-Z][a-zA-Z0-9_-]*',
                                'id'            => '[a-zA-Z0-9_-]*',
                            ),
                            'defaults' => array(
                                '__NAMESPACE__' => 'Backoffice\Controller',
                                'controller'    => 'Home',
                                'action'        => 'index',
                            ),
                        ),
                    ),
                ),
            ),

            'profile' => array(
                'type' => 'Segment',
                'options' => array(
                    'route'    => '/profile[/:id]',
                    'constraints' => array(
                        'id'     => '[0-9]+',
                    ),
                    'defaults' => array(
                        '__NAMESPACE__' => 'Backoffice\Controller',
                        'controller'    => 'Profile',
                        'action'        => 'index'
                    ),
                ),
            ),
            'booking' => array(
                'type' => 'Segment',
                'options' => array(
                    'route'    => '/booking[/:apartment]',
                    'constraints' => array(
                        'apartment'     => '[0-9]+',
                    ),
                    'defaults' => array(
                        '__NAMESPACE__' => 'Backoffice\Controller',
                        'controller'    => 'Booking',
                        'action'        => 'index'
                    ),
                ),
            ),

            'frontier' => array(
                'type' => 'Segment',
                'options' => array(
                    'route'    => '/frontier',
                    'defaults' => array(
                        '__NAMESPACE__' => 'Backoffice\Controller',
                        'controller'    => 'Frontier',
                        'action'        => 'cards'
                    ),
                ),
                'may_terminate' => true,
                'child_routes'  => [
                    'apartel' => [
                        'type' => 'Segment',
                        'options' => [
                            'route'    => '/charge[/:booking_id[/:item_id[/:hash]]]',
                            'defaults' => [
                                'controller' => 'Frontier',
                                'action'     => 'charge'
                            ]
                        ],
                    ],
                    'print-parking-permits' => [
                        'type' => 'Segment',
                        'options' => [
                            'route'    => '/print-parking-permits/:res_num[/:page]',
                            'defaults' => [
                                'controller' => 'Frontier',
                                'action'     => 'print-parking-permits'
                            ]
                        ],
                    ],
                ]
            ),

            'booking-download' => array(
                'type' => 'Segment',
                'options' => array(
                    'route'    => '/booking-download[/:booking_id]/download[/:files][/:doc_id]',
                    'constraints' => array(
                        'booking_id'     => '[0-9]+',
                        'files' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'doc_id' => '[0-9]*',
                    ),
                    'defaults' => array(
                        '__NAMESPACE__' => 'Backoffice\Controller',
                        'controller'    => 'Booking',
                        'action'        => 'download'
                    ),
                ),
            ),

            'booking_search' => array(
                    'type' => 'Segment',
                    'options' => array(
                            'route'    => '/booking-search[/:email[/:status]]',
                            'constraints' => array(
                                    'email'     => '[^/]*',
                                    'status'     => '[0-9]*',
                            ),
                            'defaults' => array(
                                    '__NAMESPACE__' => 'Backoffice\Controller',
                                    'controller'    => 'Booking',
                                    'action'        => 'index'
                            ),
                    ),
            ),

            'partner_activate' => array(
                    'type' => 'Segment',
                    'options' => array(
                            'route'    => '/partners/activate[/:id[/:status]]',
                            'constraints' => array(
                                    'id'     => '[0-9]*',
                                    'status' => '[0-9]*'
                            ),
                            'defaults' => array(
                                    '__NAMESPACE__' => 'Backoffice\Controller',
                                    'controller'    => 'Partners',
                                    'action'        => 'activate'
                            ),
                    ),
            ),
            'partner_city_commission_delete' => array(
                'type' => 'Segment',
                'options' => array(
                    'route'    => '/partners/partner-city-commission-delete[/:partner_id[/:item_id]]',
                    'constraints' => array(
                        'partner_id' => '[0-9]*',
                        'item_id' => '[0-9]*'
                    ),
                    'defaults' => array(
                        '__NAMESPACE__' => 'Backoffice\Controller',
                        'controller'    => 'Partners',
                        'action'        => 'partner-city-commission-delete'
                    ),
                ),
            ),
            'apartel_ota_remove' => array(
                'type' => 'Segment',
                'options' => array(
                    'route'    => '/apartel_ota_remove[/:apartel_id[/:ota_id]]',
                    'constraints' => array(
                        'apartel_id' => '[0-9]+',
                        'ota_id'     => '[0-9]+',
                    ),
                    'defaults' => array(
                        '__NAMESPACE__' => 'Backoffice\Controller',
                        'controller'    => 'apartment-group-apartel',
                        'action'        => 'remove-ota'
                    ),
                ),
            ),
            'apartel_ota_check' => array(
                'type' => 'Segment',
                'options' => array(
                    'route'    => '/apartel_ota_check[/:apartel_id[/:ota_id]]',
                    'constraints' => array(
                        'apartel_id' => '[1-9][0-9]*',
                        'ota_id'     => '[1-9][0-9]*',
                    ),
                    'defaults' => array(
                        '__NAMESPACE__' => 'Backoffice\Controller',
                        'controller'    => 'apartment-group-apartel',
                        'action'        => 'ajax-check-ota'
                    ),
                ),
            ),

	        'notification' => array(
		        'type'    => 'Segment',
		        'options' => array(
			        'route'    => '/notification',
			        'defaults' => array(
				        '__NAMESPACE__' => 'Backoffice\Controller',
				        'controller'    => 'Notification',
				        'action'        => 'index'
			        ),
		        ),
	        ),

	        'team' => [
		        'type'    => 'Segment',
		        'options' => [
			        'route'    => '/team[/:action][/:id]',
			        'defaults' => [
				        '__NAMESPACE__' => 'Backoffice\Controller',
				        'controller'    => 'Team',
				        'action'        => 'index'
			        ],
                    'constraints' => [
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'id'     => '[0-9]+',
                    ],
		        ],
	        ],
	        'office' => [
		        'type'    => 'Segment',
		        'options' => [
			        'route'    => '/office[/:action][/:id]',
			        'defaults' => [
				        '__NAMESPACE__' => 'Backoffice\Controller',
				        'controller'    => 'Office',
				        'action'        => 'index'
			        ],
                    'constraints' => [
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'id'     => '[0-9]*',
                    ],
		        ],
	        ],

            'task-download' => array(
                'type' => 'Segment',
                'options' => array(
                    'route'    => '/task[/:task_id]/download-attachment[/:attachment_id]',
                    'constraints' => array(
                        'task_id'     => '[0-9]+',
                        'attachment_id' => '[0-9]*',
                    ),
                    'defaults' => array(
                        '__NAMESPACE__' => 'Backoffice\Controller',
                        'controller'    => 'Task',
                        'action'        => 'download-attachment'
                    ),
                ),
            ),

            'apartment-group' => [
                'type'    => 'Segment',
                'options' => [
                    'route'    => '/concierge/edit[/:id]',
                    'defaults' => [
                        '__NAMESPACE__' => 'Backoffice\Controller',
                        'controller'    => 'apartment-group-general',
                        'action'        => 'index'
                    ],
                    'constraints' => [
                        'id' => '[0-9]+',
                    ],
                ],
                'may_terminate' => true,
                'child_routes'  => [
                    'apartel' => [
                        'type' => 'literal',
                        'options' => [
                            'route'    => '/apartel',
                            'defaults' => [
                                'controller' => 'apartment-group-apartel',
                                'action'     => 'index'
                            ]
                        ],
                    ],
                    'building' => [
                        'type' => 'segment',
                        'options' => [
                            'route'    => '/building[/:action]',
                            'defaults' => [
                                'controller' => 'apartment-group-building',
                                'action'     => 'index'
                            ],
                            'constraints' => [
                                'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                            ],
                        ],
                    ],
                    'building-section-delete' => [
                        'type' => 'Segment',
                        'options' => [
                            'route'    => '/building/delete[/:section_id]',
                            'defaults' => [
                                'controller' => 'apartment-group-building',
                                'action'     => 'delete'
                            ],
                            'constraints' => [
                                'section_id' => '[0-9]*',
                            ],
                        ],
                    ],
                    'document' => [
                        'type' => 'literal',
                        'options' => [
                            'route'    => '/document',
                            'defaults' => [
                                'controller' => 'apartment-group-document',
                                'action'     => 'index'
                            ]
                        ],
                    ],
                    'document-save' => [
                        'type' => 'Segment',
                        'options' => [
                            'route'    => '/document/save[/:doc_id]',
                            'defaults' => [
                                'controller' => 'apartment-group-document',
                                'action'     => 'save'
                            ]
                        ],
                    ],
                    'document-download' => [
                        'type' => 'Segment',
                        'options' => [
                            'route'    => '/document/download/:doc_id',
                            'defaults' => [
                                'controller' => 'apartment-group-document',
                                'action'     => 'download'
                            ]
                        ],
                    ],
                    'document-remove-attachment' => [
                        'type' => 'Segment',
                        'options' => [
                            'route'    => '/document/remove-attachment/:doc_id',
                            'defaults' => [
                                'controller' => 'apartment-group-document',
                                'action'     => 'remove-attachment'
                            ]
                        ],
                    ],
                    'document-delete' => [
                        'type' => 'Segment',
                        'options' => [
                            'route'    => '/document/delete/:doc_id',
                            'defaults' => [
                                'controller' => 'apartment-group-document',
                                'action'     => 'delete'
                            ]
                        ],
                    ],
                    'concierge' => [
                        'type' => 'literal',
                        'options' => [
                            'route'    => '/concierge',
                            'defaults' => [
                                'controller' => 'apartment-group-concierge',
                                'action'     => 'index'
                            ]
                        ],
                    ],
                    'history' => [
                        'type' => 'literal',
                        'options' => [
                            'route'    => '/history',
                            'defaults' => [
                                'controller' => 'apartment-group-history',
                                'action'     => 'index'
                            ]
                        ],
                    ],
                    'contacts' => [
                        'type' => 'literal',
                        'options' => [
                            'route'    => '/contacts',
                            'defaults' => [
                                'controller' => 'apartment-group-contacts',
                                'action'     => 'index'
                            ]
                        ],
                    ],
                ]
            ]
        ),
    ),
    'service_manager' => array(
        'factories' => array(

        ),
    ),
   'session' => array(
       'config' => array(
           'class' => 'Zend\Session\Config\SessionConfig',
           'options' => array(
               'cookie_domain'       => '.ginosi.com',
               'cookie_lifetime'     => 14400,
               'cookie_secure'       => false,
               'cookie_httponly'     => false,
               'gc_maxlifetime'      => 14400,
               'remember_me_seconds' => 14400,
               'save_path' => '/tmp',
               'name' => 'DEV_GINOSI_BO',
            ),
       ),
       'storage' => 'Zend\Session\Storage\SessionArrayStorage',
       'validators' => array(
           'Zend\Session\Validator\RemoteAddr',
           'Zend\Session\Validator\HttpUserAgent',
       ),
   ),

    'controllers' => array(
        'invokables' => array(
            'Backoffice\Controller\Authentication'          => 'Backoffice\Controller\AuthenticationController',
            'Backoffice\Controller\User'                    => 'Backoffice\Controller\UserController',
            'Backoffice\Controller\CompanyDirectory'        => 'Backoffice\Controller\CompanyDirectoryController',
            'Backoffice\Controller\Home'                    => 'Backoffice\Controller\HomeController',
            'Backoffice\Controller\Profile'                 => 'Backoffice\Controller\ProfileController',
            'Backoffice\Controller\Concierge'               => 'Backoffice\Controller\ConciergeController',
            'Backoffice\Controller\Currency'                => 'Backoffice\Controller\CurrencyController',
            'Backoffice\Controller\Upload'                  => 'Backoffice\Controller\UploadController',
            'Backoffice\Controller\OmniSearch'              => 'Backoffice\Controller\OmniSearchController',
            'Backoffice\Controller\Cron'                    => 'Backoffice\Controller\CronController',
            'Backoffice\Controller\Partners'                => 'Backoffice\Controller\PartnersController',
            'Backoffice\Controller\Location'                => 'Backoffice\Controller\LocationController',
            'Backoffice\Controller\Booking'                 => 'Backoffice\Controller\BookingController',
            'Backoffice\Controller\Blog'                    => 'Backoffice\Controller\BlogController',
            'Backoffice\Controller\News'                    => 'Backoffice\Controller\NewsController',
            'Backoffice\Controller\Language'                => 'Backoffice\Controller\LanguageController',
            'Backoffice\Controller\System'                  => 'Backoffice\Controller\SystemController',
            'Backoffice\Controller\Translation'             => 'Backoffice\Controller\TranslationController',
            'Backoffice\Controller\Task'                    => 'Backoffice\Controller\TaskController',
            'Backoffice\Controller\Tag'                     => 'Backoffice\Controller\TagController',
            'Backoffice\Controller\Test'                    => 'Backoffice\Controller\TestController',
            'Backoffice\Controller\TestResults'             => 'Backoffice\Controller\TestResultsController',
            'Backoffice\Controller\GroupInventory'          => 'Backoffice\Controller\GroupInventoryController',
            'Backoffice\Controller\Index'                   => 'Backoffice\Controller\IndexController',
            'Backoffice\Controller\Psp'                     => 'Backoffice\Controller\PspController',
            'Backoffice\Controller\Notification'            => 'Backoffice\Controller\NotificationController',
            'Backoffice\Controller\DistributionView'        => 'Backoffice\Controller\DistributionViewController',
            'Backoffice\Controller\Team'                    => 'Backoffice\Controller\TeamController',
            'Backoffice\Controller\Feedback'                => 'Backoffice\Controller\FeedbackController',
            'Backoffice\Controller\Office'                  => 'Backoffice\Controller\OfficeController',
            'Backoffice\Controller\ApartmentGroup'          => 'Backoffice\Controller\ApartmentGroupController',
            'Backoffice\Controller\ConciergeDashboard'      => 'Backoffice\Controller\ConciergeDashboardController',
            'Backoffice\Controller\CcProvide'               => 'Backoffice\Controller\CcProvideController',
            'Backoffice\Controller\Common'                  => 'Backoffice\Controller\CommonController',
            'Backoffice\Controller\Frontier'                => 'Backoffice\Controller\FrontierController',

            'Backoffice\Controller\ApartmentGroupGeneral'   => 'Backoffice\Controller\ApartmentGroupGeneralController',
            'Backoffice\Controller\ApartmentGroupBuilding'  => 'Backoffice\Controller\ApartmentGroupBuildingController',
            'Backoffice\Controller\ApartmentGroupDocument'  => 'Backoffice\Controller\ApartmentGroupDocumentController',
            'Backoffice\Controller\ApartmentGroupConcierge' => 'Backoffice\Controller\ApartmentGroupConciergeController',
            'Backoffice\Controller\ApartmentGroupHistory'   => 'Backoffice\Controller\ApartmentGroupHistoryController',
            'Backoffice\Controller\ApartmentGroupContacts'  => 'Backoffice\Controller\ApartmentGroupContactsController',
            'Backoffice\Controller\HousekeepingTasks'       => 'Backoffice\Controller\HousekeepingTasksController',
            'Backoffice\Controller\ChargeAuthorization'     => 'Backoffice\Controller\ChargeAuthorizationController',
        ),
    ),
    'controller_plugins' => array(
        'invokables' => array(
            'emailPlugin'  => 'Library\Plugins\Email',
        ),
    ),
);
