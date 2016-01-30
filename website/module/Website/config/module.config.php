<?php

use Library\Constants\Constants;

return [
    'router' => [
        'routes' => [
            'home' => [
                'type' => 'Zend\Mvc\Router\Http\Literal',
                'options' => [
                    'route'    => '/',
                    'defaults' => [
                        'controller' => 'Website\Controller\Index',
                        'action'     => 'index',
                    ],
                ],
            ],
            'contact-us' => [
                'type' => 'Segment',
                'options' => [
                    'route'    => '/contact-us[/[:action]]',
                    'constraints' => [
                        'action'     => '[a-zA-Z][a-zA-Z0-9_-]*',
                    ],
                    'defaults' => [
                        '__NAMESPACE__' => 'Website\Controller',
                        'controller'    => 'ContactUs',
                        'action'        => 'index',
                    ],
                ],
            ],
	        'booking' => [
		        'type' => 'Segment',
		        'options' => [
			        'route'    => '/booking[/:action]',
			        'constraints' => [
				        'action'     => '[a-zA-Z][a-zA-Z0-9_-]*',
			        ],
			        'defaults' => [
				        '__NAMESPACE__' => 'Website\Controller',
				        'controller'    => 'Booking',
				        'action'        => 'index',
			        ],
		        ],
	        ],
            'about-us' => [
                'type' => 'Segment',
                'options' => [
                    'route'    => '/about-us[/[:action]]',
                    'constraints' => [
                        'action'     => '[a-zA-Z][a-zA-Z0-9_-]*',
                    ],
                    'defaults' => [
                        '__NAMESPACE__' => 'Website\Controller',
                        'controller'    => 'AboutUs',
                        'action'        => 'index',
                    ],
                ],
            ],
            'faq' => [
                'type' => 'Literal',
                'options' => [
                    'route'    => '/faq',
                    'defaults' => [
                        'controller' => 'Website\Controller\Faq',
                        'action'     => 'index',
                    ],
                ],
            ],
            'jobs' => [
                'type' => 'Literal',
                'options' => [
                    'route'    => '/jobs',
                    'defaults' => [
                        'controller' => 'Website\Controller\Jobs',
                        'action'     => 'index',
                    ],
                ],
                'may_terminate' => true,
                'child_routes' => [
                    'jobs_apply' => [
                        'type' => 'Literal',
                        'options' => [
                            'route' => '/apply',
                            'defaults' => [
                                'action' => 'apply',
                            ]
                        ]
                    ],
                    'jobs_announcement' => [
                        'type' => 'Segment',
                        'options' => [
                            'route' => '/:location/:slug',
                            'defaults' => [
                                'action'     => 'announcement',
                            ],
                            'constraints' => [
                                'location' => '[a-zA-Z0-9-_]*',
                                'slug'  => '[a-zA-Z0-9-_]*'
                            ],
                        ]
                    ]
                ]
            ],
            'blog' => [
                'type' => 'Segment',
                'options' => [
                    'route' => '/blog',
                    'defaults' => [
                        'controller' => 'Website\Controller\Blog',
                        'action'     => 'index'
                    ],
                    'constraints' => [
                        'page'       => '[0-9]*',
                    ],
                ],
                'may_terminate' => true,
                'child_routes' => [
                    'child' => [
                        'type' => 'Segment',
                        'options' => [
                            'route' => '[/:article]',
                            'defaults' => [
                                'action'     => 'article',
                            ],
                            'constraints' => [
                                'article'  => '[a-zA-Z][a-zA-Z0-9_-]*'
                            ],
                        ]
                    ],
                    'feed' => [
                        'type' => 'Literal',
                        'options' => [
                            'route' => '/feed',
                            'defaults' => [
                                'action'     => 'feed',
                            ],
                        ]
                    ],
                ]
            ],
            'news' => [
                'type' => 'Segment',
                'options' => [
                    'route' => '/news',
                    'defaults' => [
                        'controller' => 'Website\Controller\News',
                        'action'     => 'index'
                    ],
                    'constraints' => [
                        'page'       => '[0-9]*',
                    ],
                ],
                'may_terminate' => true,
                'child_routes' => [
                    'child' => [
                        'type' => 'Segment',
                        'options' => [
                            'route' => '[/:article]',
                            'defaults' => [
                                'action'     => 'article',
                            ],
                            'constraints' => [
                                'article'  => '[a-zA-Z][a-zA-Z0-9._-]*'
                            ],
                        ]
                    ],
                    'feed' => [
                        'type' => 'Literal',
                        'options' => [
                            'route' => '/feed',
                            'defaults' => [
                                'action'     => 'feed',
                            ],
                        ]
                    ],
                ]
            ],

            'location' => [
                'type' => 'Segment',
                'options' => [
                    'route' => '/location',
                    'defaults' => [
                        'controller' => 'Website\Controller\Location',
                        'action'     => 'index'
                    ]
                ],
                'may_terminate' => true,
                'child_routes' => [
                    'child' => [
                        'type' => 'Segment',
                        'options' => [
                            'route' => '[/:cityProvince[/:poi]]',
                            'defaults' => [
                                'action'     => 'location',
                            ],
                            'constraints' => [
                                'cityProvince'  => '[a-zA-Z][a-zA-Z0-9_-]*',
                                'poi'       => '[a-zA-Z][a-zA-Z0-9_-]*',
                            ],
                        ]
                    ]
                ]
            ],

            'apartment' => [
                'type' => 'Segment',
                'options' => [
                    'route'    => '/apartment[/:apartmentTitle]',
                    'defaults' => [
                        'controller' => 'Website\Controller\Apartment',
                        'action'     => 'index',
                    ],
                    'constraints' => [
                        'apartmentTitle' => '[a-zA-Z][a-zA-Z0-9_-]*',
                    ],
                ],
            ],
            'apartment_search' => [
                'type' => 'Segment',
                'options' => [
                    'route'    => '/apartment-search',
                    'defaults' => [
                        'controller' => 'Website\Controller\Apartment',
                        'action'     => 'apartment-search',
                    ]
                ],
            ],
            'apartment_review' => [
                'type' => 'Segment',
                'options' => [
                    'route'    => '/apartment-review',
                    'defaults' => [
                        'controller' => 'Website\Controller\Apartment',
                        'action'     => 'ajax-apartment-review',
                    ]
                ],
            ],
            'search' => [
                'type' => 'Segment',
                'options' => [
                    'route'    => '/search[/:action]',
                    'constraints' => [
                        'action'        => '[a-zA-Z][a-zA-Z0-9_-]*',
                    ],
                    'defaults' => [
                        'controller' => 'Website\Controller\Search',
                        'action'     => 'index',
                    ],
                ],
            ],
            'key' => [
                'type' => 'Segment',
                'options' => [
                    'route'    => '/key[/]',
                    'defaults' => [
                        'controller' => 'Website\Controller\Key',
                        'action'     => 'index',
                    ],
                ],
                'may_terminate' => true,
                'child_routes' => [
                    'update-email' => [
                        'type' => 'Segment',
                        'options' => [
                            'route'    => 'update-email',
                            'defaults' => [
                                'controller' => 'Website\Controller\Key',
                                'action'     => 'update-email',
                            ],
                        ],
                    ],
                ],
            ],
	        'chat' => [
		        'type' => 'Segment',
		        'options' => [
			        'route'    => '/chat[/]',
			        'defaults' => [
				        'controller' => 'Website\Controller\Chat',
				        'action'     => 'index',
			        ],
		        ],
	        ],
            'review' => [
                'type' => 'Segment',
                'options' => [
                    'route'    => '/add-review[/[:action[/]]]',
                    'constraints' => [
                        'action'        => '[a-zA-Z][a-zA-Z0-9_-]*',
                    ],
                    'defaults' => [
                        '__NAMESPACE__' => 'Website\Controller',
                        'controller'    => 'Review',
                        'action'        => 'index',
                    ],
                ],
            ],
            'logo' => [
                'type' => 'Literal',
                'options' => [
                    'route'    => '/logo',
                    'defaults' => [
                        '__NAMESPACE__' => 'Website\Controller',
                        'controller'    => 'Brand',
                        'action'        => 'logo',
                    ],
                ],
            ],
            'phone' => [
                'type' => 'Literal',
                'options' => [
                    'route'    => '/phone',
                    'defaults' => [
                        '__NAMESPACE__' => 'Website\Controller',
                        'controller'    => 'Phone',
                        'action'        => 'xml',
                    ]
                ],
            ],
            'arrivals' => [
                'type' => 'Segment',
                'options' => [
                    'route' => '/arrivals',
                    'defaults' => [
                        'controller' => 'Website\Controller\Arrivals',
                        'action'     => 'index'
                    ]
                ],
                'may_terminate' => true,
                'child_routes' => [
                    'child' => [
                        'type' => 'Segment',
                        'options' => [
                            'route' => '[/:apartment_group_id/:date]',
                            'defaults' => [
                                'action' => 'concierge',
                            ],
                            'constraints' => [
                                'apartment_group_id' => '[1-9][0-9]*',
                                'date'               => '[1-9][0-9]*',
                            ],
                        ]
                    ]
                ]
            ],
            'receiver' => [
                'type' => 'Literal',
                'options' => [
                    'route'    => '/receiver/lockstate',
                    'defaults' => [
                        '__NAMESPACE__' => 'Website\Controller',
                        'controller'    => 'Website\Controller\Receiver',
                        'action'        => 'lockstate',
                    ]
                ],
            ],
            'ccca-page' => [
                'type' => 'Literal',
                'options' => [
                    'route'    => '/ccca-page',
                    'constraints' => [
                    ],
                    'defaults' => [
                        '__NAMESPACE__' => 'Website\Controller',
                        'controller'    => 'Website\Controller\ChargeAuthorization',
                        'action'        => 'index',
                    ],
                ],
            ],
            'apartel' => [
                'type' => 'Segment',
                'options' => [
                    'route'    => '/apartel[/:apartel-route]',
                    'defaults' => [
                        'controller' => 'Website\Controller\Apartel',
                        'action'     => 'index',
                    ],
                    'constraints' => [
                        'apartel-route' => '[a-zA-Z][a-zA-Z0-9_-]*',
                    ],
                ],
            ],
            'apartel-get-more-reviews' => [
                'type' => 'Segment',
                'options' => [
                    'route'    => '/apartel/get-more-reviews',
                    'defaults' => [
                        'controller' => 'Website\Controller\Apartel',
                        'action'     => 'get-more-reviews',
                    ],
                ],
            ],
        ],
    ],
    'service_manager' => [
        'abstract_factories' => [
            'Zend\Cache\Service\StorageCacheAbstractServiceFactory',
            'Zend\Log\LoggerAbstractServiceFactory',
        ],
        'aliases' => [
            'translator' => 'MvcTranslator',
        ],
        'invokables' => [
            'Visitor' => 'Website\Listener\Visitor',
        ],
    ],
    'translator' => [
        'locale' => 'en_US',
        'translation_file_patterns' => [
            [
                'type'     => 'gettext',
                'base_dir' => __DIR__ . '/../language',
                'pattern'  => '%s.mo',
            ],
        ],
    ],
    'controllers' => [
        'invokables' => [
            'Website\Controller\Index'                  => 'Website\Controller\IndexController',
            'Website\Controller\ContactUs'              => 'Website\Controller\ContactUsController',
            'Website\Controller\AboutUs'                => 'Website\Controller\AboutUsController',
            'Website\Controller\Faq'                    => 'Website\Controller\FaqController',
            'Website\Controller\Jobs'                   => 'Website\Controller\JobsController',
            'Website\Controller\Blog'                   => 'Website\Controller\BlogController',
            'Website\Controller\Location'               => 'Website\Controller\LocationController',
            'Website\Controller\Apartment'              => 'Website\Controller\ApartmentController',
            'Website\Controller\Search'                 => 'Website\Controller\SearchController',
            'Website\Controller\Key'                    => 'Website\Controller\KeyController',
            'Website\Controller\Review'                 => 'Website\Controller\ReviewController',
            'Website\Controller\Booking'                => 'Website\Controller\BookingController',
            'Website\Controller\Unsupported'            => 'Website\Controller\UnsupportedController',
            'Website\Controller\News'                   => 'Website\Controller\NewsController',
            'Website\Controller\Chat'                   => 'Website\Controller\ChatController',
            'Website\Controller\Brand'                  => 'Website\Controller\BrandController',
            'Website\Controller\Feed'                   => 'Website\Controller\FeedController',
            'Website\Controller\Phone'                  => 'Website\Controller\PhoneController',
            'Website\Controller\Arrivals'               => 'Website\Controller\ArrivalsController',
            'Website\Controller\Receiver'               => 'Website\Controller\ReceiverController',
            'Website\Controller\ChargeAuthorization'    => 'Website\Controller\ChargeAuthorizationController',
            'Website\Controller\Apartel'                => 'Website\Controller\ApartelController',
        ],
    ],
    // Assets
    'asset_manager' => [
        'resolver_configs' => [
            'map' => [
//                 bootstrap
                'css/bootstrap.css'             => __DIR__ . '/../../../public'.Constants::VERSION.'css/vendor/bootstrap-3.3.5.min.css',
                'js/bootstrap.js'               => __DIR__ . '/../../../public'.Constants::VERSION.'js/vendor/bootstrap-3.3.5.min.js',
                'js/bootstrap-paginator.js'     => __DIR__ . '/../../../public'.Constants::VERSION.'js/vendor/bootstrap-paginator.min.js',

                'css/style.css'                 => __DIR__ . '/../../../public'.Constants::VERSION.'css/style.css',
                'css/selectize.bootstrap3.css'  => __DIR__ . '/../../../public'.Constants::VERSION.'css/selectize.bootstrap3.css',
                'css/pagination.css'            => __DIR__ . '/../../../public'.Constants::VERSION.'css/pagination.css',
                'css/ginosicustomicons.css'     => __DIR__ . '/../../../public'.Constants::VERSION.'css/ginosicustomicons.css',

                'js/jquery.min.js'              => __DIR__ . '/../../../public'.Constants::VERSION.'js/jquery.min.js',

                'css/datepicker.css'            => __DIR__ . '/../../../public'.Constants::VERSION.'css/vendor/datepicker3.min.css',
                'css/royalslider.css'           => __DIR__ . '/../../../public'.Constants::VERSION.'css/royalslider.css',
                'css/rs-default.css'            => __DIR__ . '/../../../public'.Constants::VERSION.'css/rs-default.css',

                'css/contact-us.css'            => __DIR__ . '/../../../public'.Constants::VERSION.'css/contact-us.css',

	            'js/jquery.js'                  => __DIR__ . '/../../../public'.Constants::VERSION.'js/vendor/jquery-2.1.4.min.js',

	            'js/datepicker.js'              => __DIR__ . '/../../../public'.Constants::VERSION.'js/vendor/bootstrap-datepicker.min.js',
	            'js/royalslider.js'             => __DIR__ . '/../../../public'.Constants::VERSION.'js/vendor/jquery.royalslider.min.js',
	            'js/jquery.validatie.js'        => __DIR__ . '/../../../public'.Constants::VERSION.'js/vendor/jquery.validatie.min.js',
	            'js/custom.js'                  => __DIR__ . '/../../../public'.Constants::VERSION.'js/custom.js',
	            'js/selectize.js'               => __DIR__ . '/../../../public'.Constants::VERSION.'js/vendor/selectize.min.js',

	            // Inline Scripts
                'js/search.inbound.js'          => __DIR__ . '/../../../public'.Constants::VERSION.'js/search.inbound.js',
                'js/search.outbound.js'         => __DIR__ . '/../../../public'.Constants::VERSION.'js/search.outbound.js',
                'js/apartment.maps.js'          => __DIR__ . '/../../../public'.Constants::VERSION.'js/apartment.maps.js',
                'js/apartment.slider.js'        => __DIR__ . '/../../../public'.Constants::VERSION.'js/apartment.slider.js',
                'js/apartment.page.js'          => __DIR__ . '/../../../public'.Constants::VERSION.'js/apartment.page.js',
                'js/checkout.js'                => __DIR__ . '/../../../public'.Constants::VERSION.'js/checkout.js',
                'js/checkout.page.js'           => __DIR__ . '/../../../public'.Constants::VERSION.'js/checkout.page.js',
                'js/checkout.validate.js'       => __DIR__ . '/../../../public'.Constants::VERSION.'js/checkout.validate.js',
	            'js/destination.maps.js'        => __DIR__ . '/../../../public'.Constants::VERSION.'js/destination.maps.js',


                'js/pages/search.js'            => __DIR__ . '/../../../public'.Constants::VERSION.'js/pages/search.js',

                'js/pages/review.js'            => __DIR__ . '/../../../public'.Constants::VERSION.'js/pages/review.js',

                'js/contactus.page.js'           => __DIR__ . '/../../../public'.Constants::VERSION.'js/contactus.page.js',
                'js/contactus.validate.js'       => __DIR__ . '/../../../public'.Constants::VERSION.'js/contactus.validate.js',

                // JS Validators
                'js/jquery.validate.min.js'       => __DIR__ . '/../../../public'.Constants::VERSION.'js/vendor/jquery.validate.min.js',
            ],
            'collections' => [
                substr(Constants::VERSION,1).'css/layout.css' => [
                    'css/bootstrap.css',
	                'css/ginosicustomicons.css',
	                'css/style.css',
	                'css/selectize.bootstrap3.css',
                ],
                substr(Constants::VERSION,1).'js/layout.js' => [
                    'js/jquery.js',
                    'js/bootstrap.js',
                    'js/custom.js',
                    'js/selectize.js',
                ],

                substr(Constants::VERSION,1).'css/pagination.css' => [
                    'css/pagination.css',
                ],

                // Contact Us
                substr(Constants::VERSION,1).'css/contact-us.css' => [
                    'css/contact-us.css',
                ],


	            // Apartment
	            substr(Constants::VERSION,1).'js/apartment.js' => [
		            'js/royalslider.js',
		            'js/datepicker.js',
		            'js/search.inbound.js',
		            'js/apartment.maps.js',
		            'js/apartment.slider.js',
                    'js/bootstrap-paginator.js',
		            'js/apartment.page.js',
	            ],
	            substr(Constants::VERSION,1).'css/apartment.css' => [
		            'css/royalslider.css',
		            'css/rs-default.css',
		            'css/datepicker.css',
	            ],

	            // Apartment Search
	            substr(Constants::VERSION,1).'js/search.js' => [
		            'js/search.outbound.js',
                    'js/bootstrap-paginator.js',
                    'js/pages/search.js',
	            ],
	            substr(Constants::VERSION,1).'css/search.css' => [
		            'css/datepicker.css',
	            ],

                // Review Page
                substr(Constants::VERSION,1).'js/review.js' => [
		            'js/pages/review.js',
	            ],

                // Contact Us
                substr(Constants::VERSION,1).'js/contactus.js' => [
		            'js/jquery.validate.min.js',
		            'js/contactus.validate.js',
                    'js/contactus.page.js',
	            ],
            ],
            'paths' => [
               'Website' => __DIR__ . '/../assets',
            ],
        ],
        'caching' => [
            'default' => [
                'cache' => 'Apc',
            ],
            substr(Constants::VERSION,1).'css/layout.css' => [
                'cache' => 'Apc',
//                'cache'     => 'FilePath',
//                'options' => [
//                    'dir' => __DIR__ . '/../cache'
//                ],
            ],
            substr(Constants::VERSION,1).'js/layout.js' => [
                'cache' => 'Apc',
//                'cache'     => 'FilePath',
//                'options' => [
//                    'dir' => __DIR__ . '/../cache'
//                ],
            ],
        ],
    ],
    // Session
    // @todo move remember time and cookie domain to Constants
    'session' => [
        'name'                => 'DEV_GINOSI_WS',
        'remember_me_seconds' => 86400, // 86400 <- one day
        'use_cookies'         => true,
        'cookie_httponly'     => false,
        'cookie_domain'       => '.ginosi.com'
    ],
];
