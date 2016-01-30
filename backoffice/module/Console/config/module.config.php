<?php
return array(
    'controllers' => array(
        'invokables' => array(
            'Console\Controller\Index'                    => 'Console\Controller\IndexController',
            'Console\Controller\User'                     => 'Console\Controller\UserController',
            'Console\Controller\Currency'                 => 'Console\Controller\CurrencyController',
            'Console\Controller\CurrencyVault'            => 'Console\Controller\CurrencyVaultController',
            'Console\Controller\ReservationEmail'         => 'Console\Controller\ReservationEmailController',
            'Console\Controller\Booking'                  => 'Console\Controller\BookingController',
            'Console\Controller\ChannelManager'           => 'Console\Controller\ChannelManagerController',
            'Console\Controller\Availability'             => 'Console\Controller\AvailabilityController',
            'Console\Controller\ContactUs'                => 'Console\Controller\ContactUsController',
            'Console\Controller\Apartment'                => 'Console\Controller\ApartmentController',
            'Console\Controller\ApartmentGroup'           => 'Console\Controller\ApartmentGroupController',
            'Console\Controller\Tools'                    => 'Console\Controller\ToolsController',
            'Console\Controller\OneTime'                  => 'Console\Controller\OneTimeController',
            'Console\Controller\Archive'                  => 'Console\Controller\ArchiveController',
            'Console\Controller\Crawler'                  => 'Console\Controller\CrawlerController',
            'Console\Controller\Database'                 => 'Console\Controller\DatabaseController',
            'Console\Controller\Issues'                   => 'Console\Controller\IssuesController',
            'Console\Controller\Concierge'                => 'Console\Controller\ConciergeController',
            'Console\Controller\Task'                     => 'Console\Controller\TaskController',
            'Console\Controller\Parking'                  => 'Console\Controller\ParkingController',
            'Console\Controller\Email'                    => 'Console\Controller\EmailController',
            'Console\Controller\InventorySynchronization' => 'Console\Controller\InventorySynchronizationController',
            'Console\Controller\CreditCardCreationQueue'  => 'Console\Controller\CreditCardCreationQueueController',
            'Console\Controller\ApiRequest'               => 'Console\Controller\ApiRequestController',
            'Console\Controller\Phpunit'                  => 'Console\Controller\PhpunitController',
            'Console\Controller\MaxMind'                  => 'Console\Controller\MaxMindController',
        ),
    ),
    'controller_plugins' => array(
        'invokables' => array(

        ),
    ),
    'console' => array(
        'router' => array(
            'routes' => array(
	            'availability' => array(
		            'options' => array(
			            'route'    => 'availability [update-monthly|repair|update-monthly-apartel]:mode [--date-from=] [--date-to=] [--rate-id=] [--verbose|-v]',
			            'defaults' => array(
				            'controller' => 'Console\Controller\Availability',
				            'action'     => 'index',
			            ),
		            ),
	            ),
                'channelmanager' => array(
                    'options' => array(
                        'route'    => 'chm [pullreservation|execute-queue-inventory-sync|execute-inventory-synchronization-queue|help]:mode [--verbose|-v] [start] [--start|--restart]',
                        'defaults' => array(
                            'controller' => 'Console\Controller\ChannelManager',
                            'action'     => 'index',
                        ),
                    ),
                ),
                'booking' => array(
                    'options' => array(
                        'route'    => 'booking [firstcharge|clear-links|mark-pages|check-reservation-balances]:mode [--verbose|-v] [--id=] [--email=]',
                        'defaults' => array(
                            'controller' => 'Console\Controller\Booking',
                            'action'     => 'index',
                        ),
                    ),
                ),
                'currency' => array(
                    'options' => array(
                        'route'    => 'currency [show|update|check|update-currency-vault]:mode [--nosend] [--verbose|-v]',
                        'defaults' => array(
                            'controller' => 'Console\Controller\Currency',
                            'action'     => 'index',
                        ),
                    ),
                ),
                'reservation-email' => array(
                    'options' => array(
                        'route'    => 'reservation-email [send-ki|check-ki|send-guest|send-ginosi|check-confirmation|send-overbooking|send-ccca|send-modification-cancel|send-update-payment-details-guest|send-payment-details-updated-ginosi|send-modification-ginosi|show-modification|check-review|send-review|send-receipt]:mode [--id=] [--email=] [--ccca_id=] [--ccp=] [-bo] [--overbooking|-o] [--ginosi] [--booker] [--shifted] [--verbose|-v]',
                        'defaults' => array(
                            'controller' => 'Console\Controller\ReservationEmail',
                            'action'     => 'index',
                        ),
                    ),
                ),
                'users' => array(
                    'options' => array(
                        'route'    => 'user [send-login-details|calculate-vacation-days|show|update-schedule-inventory]:mode [--id=] [--verbose|-v]',
                        'defaults' => array(
                            'controller' => 'Console\Controller\User',
                            'action'     => 'index',
                        ),
                    ),
                ),
                'contact-us' => array(
                    'options' => array(
                        'route'    => 'contact-us [send]:mode [--name=] [--email=] [--remarks=] [--verbose|-v]',
                        'defaults' => array(
                            'controller' => 'Console\Controller\ContactUs',
                            'action'     => 'index',
                        ),
                    ),
                ),
                'apartments' => array(
                    'options' => array(
                        'route'    => 'apartment [check-performance|documents-after-sixty-days-expiring|correct-apartment-reviews]:mode [--id=] [--verbose|-v]',
                        'defaults' => array(
                            'controller' => 'Console\Controller\Apartment',
                            'action'     => 'index',
                        ),
                    ),
                ),
                'apartment-groups' => array(
                    'options' => array(
                        'route'    => 'building [check-performance]:mode [--id=] [--verbose|-v]',
                        'defaults' => array(
                            'controller' => 'Console\Controller\ApartmentGroup',
                            'action'     => 'index',
                        ),
                    ),
                ),
                'database' => array(
                    'options' => array(
                        'route'    => 'db [safe-backup|help|man]:mode [--verbose|-v]',
                        'defaults' => array(
                            'controller' => 'Console\Controller\Database',
                            'action'     => 'index',
                        ),
                    ),
                ),
                'issues' => array(
                    'options' => array(
                        'route'    => 'issues [show|detect|force-resolve]:mode [--id=] [--verbose|-v]',
                        'defaults' => array(
                            'controller' => 'Console\Controller\Issues',
                            'action'     => 'index',
                        ),
                    ),
                ),
                'task' => array(
                    'options' => array(
                        'route'    => 'task [update-reservation-cleaning-tasks-for-2-days]:mode [--verbose|-v]',
                        'defaults' => array(
                            'controller' => 'Console\Controller\Task',
                            'action'     => 'index',
                        ),
                    ),
                ),
                'tools' => array(
                    'options' => array(
                        'route'    => 'tools [report-unused-files|optimize-tables|help|man]:mode [--verbose|-v] [--remove-from-disk] [--only-locations-images] [--only-profiles-images] [--only-apartments-images] [--only-blog-images] [--only-documents] [--only-purchase-order-attachments] [--only-purchase-order-item-attachments] [--only-building-maps] [--only-office-maps] [--only-users-documents] [--only-jobs-documents] [--only-booking-documents] [--only-booking-image-documents] [--only-money-account-documents] [--only-money-account-image-documents] [--only-task-attachments] [--only-parking-attachments] [--only-apartel-images]',
                        'defaults' => array(
                            'controller' => 'Console\Controller\Tools',
                            'action'     => 'index',
                        ),
                    ),
                ),
                'crawler' => array(
                    'options' => array(
                        'route'    => 'crawler [check|update|help]:mode [--product=] [--identity=] [--ota=] [--verbose|-v]',
                        'defaults' => array(
                            'controller' => 'Console\Controller\Crawler',
                            'action'     => 'index',
                        ),
                    ),
                ),
                'cc-creation-queue' => array(
                    'options' => array(
                        'route'    => 'cc-creation-queue [execute]:mode [--verbose|-v]',
                        'defaults' => array(
                            'controller' => 'Console\Controller\CreditCardCreationQueue',
                            'action'     => 'index',
                        ),
                    ),
                ),

                // one time methods here
                'onetime' => array(
		            'options' => array(
			            'route' => 'onetime <action> [--verbose|-v] [--cleanup] [--id=] [--phonecode=]',
			            'defaults' => array(
				            'controller' => 'Console\Controller\OneTime',
				            'action'     => 'index',
			            ),
		            ),
	            ),

                'archive' => array(
		            'options' => array(
			            'route' => 'archive <action> [--verbose|-v] [--expense-id=]',
			            'defaults' => array(
				            'controller' => 'Console\Controller\Archive',
				            'action'     => 'index',
			            ),
		            ),
	            ),

                // help
                'usage' => array(
                    'options' => array(
                        'route' => '[ --usage | --help | -h | --version | -v ]',
                        'defaults' => array(
                            'controller' => 'Console\Controller\Index',
                            'action'     => 'usage',
                        ),
                    ),
                ),
                // :)
                'matrix' => array( // don't touch it!
                    'options' => array(
                        'route' => '( --matrix | -m)',
                        'defaults' => array(
                            'controller' => 'Console\Controller\Index',
                            'action'     => 'index',
                        ),
                    ),
                ),

                'concierge' => [
                    'options' => [
                        'route' => 'arrivals <action>',
                        'defaults' => [
                            'controller' => 'Console\Controller\Concierge',
                            'action'     => 'send-arrivals-mail',
                        ],
                    ],
                ],

                'parking' => [
                    'options' => [
                        'route'    => 'parking [extend-inventory]:mode [--verbose|-v]',
                        'defaults' => [
                            'controller' => 'Console\Controller\Parking',
                            'action'     => 'index',
                        ],
                    ],
                ],

                'email' => [
                    'options' => [
                        'route'    => 'email [send-applicant-rejections]:mode [--verbose|-v]',
                        'defaults' => [
                            'controller' => 'Console\Controller\Email',
                            'action'     => 'index',
                        ],
                    ],
                ],

                'inventory-synchronization' => [
                    'options' => [
                        'route'    => 'inventory-synchronization [execute-inventory]:mode [--verbose|-v] [start] [--start|--restart]',
                        'defaults' => [
                            'controller' => 'Console\Controller\InventorySynchronization',
                            'action'     => 'index',
                        ],
                    ],
                ],
                'api-request' => [
                    'options' => [
                        'route'    => 'api-request [delete-expired-request]:mode [--verbose|-v]',
                        'defaults' => [
                            'controller' => 'Console\Controller\ApiRequest',
                            'action'     => 'index',
                        ],
                    ],
                ],
                'max-mind' => [
                    'options' => [
                        'route'    => 'max-mind [update-geolite-country-database]:mode [--verbose|-v]',
                        'defaults' => [
                            'controller' => 'Console\Controller\MaxMind',
                            'action'     => 'index',
                        ],
                    ],
                ],
                'phpunit' => [
                    'options' => [
                        'route'    => 'phpunit [--app=] [--verbose|-v]',
                        'defaults' => [
                            'controller' => 'Console\Controller\Phpunit',
                            'action'     => 'index',
                        ],
                    ],
                ],
            ),
        ),
    ),
);
