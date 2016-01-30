<?php

namespace Backoffice;

use Backoffice\Navigation\BackofficeNavigation;
use Backoffice\View\Helper\Identity;
use Backoffice\View\Helper\Navigation;
use Backoffice\View\Helper\Breadcrumb;
use Backoffice\View\Helper\Info;
use Backoffice\View\Helper\ConfirmationDialog;
use Backoffice\View\Helper\GinosikBtnLong;
use Backoffice\View\Helper\GinosikBtn;
use Backoffice\View\Helper\Required;
use DDD\Dao\Finance\Customer;
use DDD\Service\Currency;
use Library\Authentication\BcryptDbAdapter;
use Library\ChannelManager\ChannelManager;
use Zend\Db\Adapter\Adapter;
use Zend\Mvc\MvcEvent;
use Zend\Mvc\ModuleRouteListener;
use Zend\Session\SessionManager;
use Zend\Session\Container;
use Library\Acl\Aclmanager;
use Library\Constants\Constants;
use Library\Constants\DomainConstants;
use Library\Utility\Helper;
use Zend\Db\TableGateway\Feature\GlobalAdapterFeature;
use Zend\Log\Logger;
use Zend\Log\Writer\Stream as LogWriterStream;
use Zend\Log\Formatter\Simple as LogFormater;
use Zend\Http\Response;
use Library\Authentication\BackofficeAuthenticationService;
use Library\Authentication\AuthStorage;
use Library\Constants\DbTables;
use Zend\View\Model\ViewModel;

use Backoffice\View\Helper\ApartmentGroupNavigation;


class Module
{
    public function onBootstrap(MvcEvent $e)
    {
        /**
         * @var Adapter $dbAdapter
         */
        $event        = $e->getApplication();
        $eventManager = $event->getEventManager();

        $this->bootstrapSession($e);

        $eventManager->attach(\Zend\Mvc\MvcEvent::EVENT_ROUTE, [$this, 'onPreDispatch']);
        $eventManager->attach(\Zend\Mvc\MvcEvent::EVENT_ROUTE, [$this, 'onViewHelper']);

        $moduleRouteListener = new ModuleRouteListener();
        $moduleRouteListener->attach($eventManager);

        // set static adapter for all module table gateways
        $serviceManager = $e->getApplication()->getServiceManager();

        $dbAdapter = $serviceManager->get('Zend\Db\Adapter\Adapter');

        GlobalAdapterFeature::setStaticAdapter($dbAdapter);
    }

    /**
     * @param MvcEvent $e
     */
    public function bootstrapSession(MvcEvent $e)
    {
		/** @var SessionManager $session */
	    $session = $e->getApplication()
    				->getServiceManager()
    				->get('Zend\Session\SessionManager');
    	$session->start();

    	$container = new Container('ginosi_backoffice');

    	if (!isset($container->init)) {
    		$session->regenerateId(true);
    		$container->init = 1;
    	}
    }


    /**
     * @param MvcEvent $e
     * @return Response, \Zend\Json\Server\Response
     */
    public function onPreDispatch(MvcEvent $e)
    {
        /**
         * @var Response $response
         */
        $routeMatch  = $e->getRouteMatch();
        $controller  = strtolower($routeMatch->getParam('controller'));
        $action      = strtolower($routeMatch->getParam('action'));
        $serviceManager = $e->getApplication()->getServiceManager();
        $request = $serviceManager->get('Request');
        $serverParam = $request->getServer();
        $serviceUserAuthentication = $serviceManager->get('library_backoffice_auth');

        $aclManager = new Aclmanager($serviceManager);

        $loginRequired = false;
        $homeRedirect  = false;

        if ($serviceUserAuthentication->hasIdentity()) {
            if (!$serviceUserAuthentication->getIdentity()) {
                $role = ROLE_GUEST;
            } else {
                $role = $serviceUserAuthentication->getIdentity()->id;
            }

            if (!$aclManager->hasResource($controller) || !$aclManager->isAllowed($role, $controller, $action)) {
                $homeRedirect = true;
            }
        } else {
        	$role = ROLE_GUEST;

        	if (!$aclManager->hasResource($controller) || !$aclManager->isAllowed($role, $controller, $action)) {
                $loginRequired = true;
            }
        }

        // store user's last visited url in session
        if (!in_array($controller, ['controller_backofficeuser_authentication']) && !$serviceManager->get('request')->isXmlHttpRequest()) {
            $session_last_visit = Helper::getSessionContainer('last_visit');
            $session_last_visit->last_visit_url = $serverParam->get('REQUEST_URI');
        }

        if ($loginRequired) {
        	if ($serviceManager->get('request')->isXmlHttpRequest()) {
        		$response = new Response();
        		$response->setContent('{"message":"_LOGIN_REQUIRED_", "aaData":[]}');

        		return $response;
        	} else {
        		$url = $e->getRouter()->assemble([], ['name' => 'backoffice_user_login']);
                $response = $e->getResponse();

                if ($serverParam->get('REQUEST_URI') != '/') {
                    $url = $url . '?request_url=' . $serverParam->get('REQUEST_URI');
                }

                $response->getHeaders()->addHeaderLine('Location', $url);
        		$response->setStatusCode(302);
        		$response->sendHeaders();

        		return $response;
        	}
        }

        if ($homeRedirect) {
            $url = $e->getRouter()->assemble([], ['name' => 'backoffice_user_login']);
            $response = $e->getResponse();

            $response->getHeaders()->addHeaderLine('Location', $url);
            $response->setStatusCode(302);
            $response->sendHeaders();

            return $response;
        }
    }


    public function getControllerPluginConfig()
    {

    }

    public function onViewHelper(MvcEvent $e)
    {
        /**
         * @var ViewModel|\ArrayObject $viewModel
         */
        $viewModel = $e->getViewModel();
        $base_url = Constants::BASE_PATH_VIEW;

        $viewModel->basePathView = $base_url;
        $viewModel->globalVersion = Constants::VERSION;
        $viewModel->globalImgDomainName = DomainConstants::IMG_DOMAIN_NAME;
        $viewModel->flashMessenger = Helper::getSessionContainer('use_zf2')->flash;
        $home_url = '/home';
        $session_home_url = Helper::getSessionContainer('default_home_url');

        if (isset($session_home_url->home_url)) {
            $home_url = $session_home_url->home_url;
        }


        $viewModel->default_home_url = $base_url . $home_url;
    }

	public function getViewHelperConfig()
    {
        return array(
            'factories' => array(
	            'info' => function($text) {
		            return new Info($text);
	            },
	            'confirmationDialog' => function($title, $id, $confirmButtonId) {
		            return new ConfirmationDialog($title, $id, $confirmButtonId);
	            },
	            'GinosikBtn' => function($text) {
		            return new GinosikBtn($text);
	            },
                'GinosikBtnLong' => function($sm) {
                    $locator = $sm->getServiceLocator();
                    $viewHelper = new GinosikBtnLong();
                    $viewHelper->setServiceLocator($locator);

                    return $viewHelper;
                },
	            'required' => function($name) {
		            return new Required($name);
	            },
	            'breadcrumb' => function() {
		            return new Breadcrumb();
	            },
	            'nav' => function($sm) {
		            return new Navigation($sm);
	            },
                'TopMenu' => function ($sm) {
                    $viewHelper = new View\Helper\Topmenu();
                    $viewHelper->setServiceLocator($sm->getServiceLocator());
                    $viewHelper->setViewTemplate('widgets/topmenu.phtml');

                    return $viewHelper;
                },
                'GoogleAnalytics' => function ($sm) {
                    $viewHelper = new View\Helper\GoogleAnalytics();
                    $viewHelper->setServiceLocator($sm->getServiceLocator());

                    return $viewHelper;
                },
                'apartmentGroupNavigation' => function ($sm) {
                    $locator    = $sm->getServiceLocator();
                    $viewHelper = new ApartmentGroupNavigation();
                    $viewHelper->setServiceLocator($locator);

                    return $viewHelper;
                },
                'identity' => function($sm) {
                    $identity = new Identity();
                    $identity->setServiceLocator($sm->getServiceLocator());

                    return $identity;
                },
                'AsanaFeedback' => function ($sm) {
                    $viewHelper = new View\Helper\AsanaFeedback();
                    $viewHelper->setServiceLocator($sm->getServiceLocator());

                    return $viewHelper;
                },
                'textline' => function($helperPluginManager) {
                    $serviceLocator = $helperPluginManager->getServiceLocator();
                    $helper = new \Library\ViewHelper\Textline\Universal($serviceLocator);

                    return $helper;
                },
                'productTextline' => function($helperPluginManager) {
                    $serviceLocator = $helperPluginManager->getServiceLocator();
                    $helper = new \Library\ViewHelper\Textline\Product($serviceLocator);

                    return $helper;
                },
                'cityName' => function($helperPluginManager) {
                    $serviceLocator = $helperPluginManager->getServiceLocator();
                    $helper = new \Library\ViewHelper\Textline\CityName($serviceLocator);

                    return $helper;
                },
                'provinceName' => function($helperPluginManager) {
                    $serviceLocator = $helperPluginManager->getServiceLocator();
                    $helper = new \Library\ViewHelper\Textline\ProvinceName($serviceLocator);

                    return $helper;
                },
                'countryName' => function($helperPluginManager) {
                    $serviceLocator = $helperPluginManager->getServiceLocator();
                    $helper = new \Library\ViewHelper\Textline\CountryName($serviceLocator);

                    return $helper;
                },
            )
        );
    }

    public function getConfig()
    {
        // retreive application envionment
        $environment = getenv('APPLICATION_ENV') ?: 'production';

        $configurationsArray = array_merge(
	        include __DIR__ . '/config/module.config.php',
	        include __DIR__ . '/config/navigation.config.php',
	        include __DIR__ . '/config/view-manager.' . $environment . '.php'
        );

        return $configurationsArray;
    }

    public function getAutoloaderConfig()
    {
        return array(
            'Zend\Loader\StandardAutoloader' => array(
                'namespaces' => array(
                    __NAMESPACE__ 	=> __DIR__ . '/src/' . __NAMESPACE__,
                    'Library'     	=> __DIR__ . '/../../library/Library',
                    'DDD'     		=> __DIR__ . '/../../library/DDD',
                ),
            ),
        );
    }

    public function getServiceConfig()
    {
        return array(
            'invokables' => array(
                'service_universal_dashboard_main'                                    => 'DDD\Service\UniversalDashboard\Main',
                'service_accommodations'                                              => 'DDD\Service\Accommodations',
                'service_upload'                                                      => 'DDD\Service\Upload',
                'service_partners'                                                    => 'DDD\Service\Partners',
                'service_partner_gcm_value'                                           => 'DDD\Service\PartnerGcmValue',
                'service_currency_currency'                                           => 'DDD\Service\Currency\Currency',
                'service_currency_currency_vault'                                     => 'DDD\Service\Currency\CurrencyVault',
                'service_profile'                                                     => 'DDD\Service\Profile',
                'service_location'                                                    => 'DDD\Service\Location',
                'service_review'                                                      => 'DDD\Service\Review',
                'service_booking'                                                     => 'DDD\Service\Booking',
                'service_blog'                                                        => 'DDD\Service\Blog',
                'service_news'                                                        => 'DDD\Service\News',
                'service_language'                                                    => 'DDD\Service\Language',
                'service_translation'                                                 => 'DDD\Service\Translation',
                'service_textline'                                                    => 'DDD\Service\Textline',
                'service_channel_manager'                                             => 'DDD\Service\ChannelManager',
                'service_queue_inventory_synchronization_queue'                       => 'DDD\Service\Queue\InventorySynchronizationQueue',
                'service_distribution'                                                => 'DDD\Service\Distribution',
                'service_queue_email_queue'                                           => 'DDD\Service\Queue\EmailQueue',
                'service_apartment_group'                                             => 'DDD\Service\ApartmentGroup',
                'service_apartment_group_main'                                        => 'DDD\Service\ApartmentGroup\Main',
                'service_apartment_group_deactivate'                                  => 'DDD\Service\ApartmentGroup\Deactivate',
                'service_apartment_group_usages_concierge'                            => 'DDD\Service\ApartmentGroup\Usages\Concierge',
                'service_apartment_group_usages_apartel'                              => 'DDD\Service\ApartmentGroup\Usages\Apartel',
                'service_apartment_group_usages_building'                             => 'DDD\Service\ApartmentGroup\Usages\Building',
                'service_user'                                                        => 'DDD\Service\User',
                'service_user_main'                                                   => 'DDD\Service\User\Main',
                'service_user_documents'                                              => 'DDD\Service\User\Documents',
                'service_user_evaluations'                                            => 'DDD\Service\User\Evaluations',
                'service_user_schedule'                                               => 'DDD\Service\User\Schedule',
                'service_user_disable_user'                                           => 'DDD\Service\User\DisableUser',
                'service_user_permissions'                                            => 'DDD\Service\User\Permissions',
                'service_user_vacation'                                               => 'DDD\Service\User\Vacation',
                'service_user_external_account'                                       => 'DDD\Service\User\ExternalAccount',
                'service_user_salary_scheme'                                          => 'DDD\Service\User\SalaryScheme',
                'service_apartment_amenities'                                         => 'DDD\Service\Apartment\Amenities',
                'service_apartment_amenity_items'                                     => 'DDD\Service\Apartment\AmenityItems',
                'service_apartment_main'                                              => 'DDD\Service\Apartment\Main',
                'service_apartment_general'                                           => 'DDD\Service\Apartment\General',
                'service_apartment_details'                                           => 'DDD\Service\Apartment\Details',
                'service_apartment_location'                                          => 'DDD\Service\Apartment\Location',
                'service_apartment_rate'                                              => 'DDD\Service\Apartment\Rate',
                'service_apartment_inventory'                                         => 'DDD\Service\Apartment\Inventory',
                'service_apartment_review'                                            => 'DDD\Service\Apartment\Review',
                'service_apartment_furniture'                                         => 'DDD\Service\Apartment\Furniture',
                'service_apartment_statistics'                                        => 'DDD\Service\Apartment\Statistics',
                'service_apartment_logs'                                              => 'DDD\Service\Apartment\Logs',
                'service_parking_general'                                             => 'DDD\Service\Parking\General',
                'service_parking_inventory'                                           => 'DDD\Service\Parking\Inventory',
                'service_parking_spot'                                                => 'DDD\Service\Parking\Spot',
                'service_parking_spot_inventory'                                      => 'DDD\Service\Parking\Spot\Inventory',
                'service_apartment_group_facilities'                                  => 'DDD\Service\ApartmentGroup\Facilities',
                'service_apartment_group_facility_items'                              => 'DDD\Service\ApartmentGroup\FacilityItems',
                'service_apartment_group_building_details'                            => 'DDD\Service\ApartmentGroup\BuildingDetails',
                'service_penalty_calculation'                                         => 'DDD\Service\PenaltyCalculation',
                'service_task'                                                        => 'DDD\Service\Task',
                'service_group_inventory'                                             => 'DDD\Service\GroupInventory',
                'service_geolite_country'                                             => 'DDD\Service\GeoliteCountry',
                'service_booking_management'                                          => 'DDD\Service\Booking\BookingManagement',
                'service_booking_booking_ticket'                                      => 'DDD\Service\Booking\BookingTicket',
                'service_booking_charge'                                              => 'DDD\Service\Booking\Charge',
                'service_booking_bank_transaction'                                    => 'DDD\Service\Booking\BankTransaction',
                'service_booking_booking_addon'                                       => 'DDD\Service\Booking\BookingAddon',
                'service_booking_reservation_issues'                                  => 'DDD\Service\Booking\ReservationIssues',
                'service_booking_attachment'                                          => 'DDD\Service\Booking\Attachment',
                'service_money_account'                                               => 'DDD\Service\MoneyAccount',
                'service_money_account_attachment'                                    => 'DDD\Service\MoneyAccountAttachment',
                'service_psp'                                                         => 'DDD\Service\Psp',
                'service_universal_dashboard_widget_ki_not_viewed'                    => 'DDD\Service\UniversalDashboard\Widget\KINotViewed',
                'service_universal_dashboard_widget_no_collection'                    => 'DDD\Service\UniversalDashboard\Widget\NoCollection',
                'service_universal_dashboard_widget_collect_from_customer'            => 'DDD\Service\UniversalDashboard\Widget\CollectFromCustomer',
                'service_universal_dashboard_widget_pay_to_customer'                  => 'DDD\Service\UniversalDashboard\Widget\PayToCustomer',
                'service_universal_dashboard_widget_to_be_settled'                    => 'DDD\Service\UniversalDashboard\Widget\ToBeSettled',
                'service_universal_dashboard_widget_pending_transaction'              => 'DDD\Service\UniversalDashboard\Widget\PendingTransaction',
                'service_universal_dashboard_widget_collect_from_partner'             => 'DDD\Service\UniversalDashboard\Widget\CollectFromPartner',
                'service_universal_dashboard_widget_validate_cc'                      => 'DDD\Service\UniversalDashboard\Widget\ValidateCC',
                'service_universal_dashboard_widget_pending_cancellation'             => 'DDD\Service\UniversalDashboard\Widget\PendingCancelation',
                'service_universal_dashboard_widget_in_registration_process'          => 'DDD\Service\UniversalDashboard\Widget\InRegistrationProcess',
                'service_universal_dashboard_widget_suspended_apartments'             => 'DDD\Service\UniversalDashboard\Widget\SuspendedApartments',
                'service_universal_dashboard_widget_pinned_reservation'               => 'DDD\Service\UniversalDashboard\Widget\PinnedReservation',
                'service_universal_dashboard_widget_resolve_comments'                 => 'DDD\Service\UniversalDashboard\Widget\ResolveComments',
                'service_universal_dashboard_widget_not_charged_apartel_reservations' => 'DDD\Service\UniversalDashboard\Widget\NotChargedApartelReservations',
                'service_universal_dashboard_widget_overbooking_reservations'         => 'DDD\Service\UniversalDashboard\Widget\OverbookingReservations',
            	'service_universal_dashboard_widget_upcoming_evaluations'             => 'DDD\Service\UniversalDashboard\Widget\UpcomingEvaluations',
            	'service_universal_dashboard_widget_evaluation_less_employees'        => 'DDD\Service\UniversalDashboard\Widget\EvaluationLessEmployees',
            	'service_universal_dashboard_widget_time_off_requests'                => 'DDD\Service\UniversalDashboard\Widget\TimeOffRequests',
                'service_ota_distribution'                                            => 'DDD\Service\OTADistribution',
                'service_website_cache'                                               => 'DDD\Service\Website\Cache',
                'service_apartment_ota_distribution'                                  => 'DDD\Service\Apartment\OTADistribution',
                'service_notifications'                                               => 'DDD\Service\Notifications',
                'service_apartment_review_category'                                   => 'DDD\Service\Apartment\ReviewCategory',
                'service_taxes'                                                       => 'DDD\Service\Taxes',
                'service_team_team'                                                   => 'DDD\Service\Team\Team',
                'service_office'                                                      => 'DDD\Service\Office',
                'service_finance_customer'                                            => 'DDD\Service\Finance\Customer',
                'service_finance_account_receivable_chart'                            => 'DDD\Service\Finance\AccountReceivable\Chart',
                'service_common'                                                      => 'DDD\Service\Common',
                'service_fraud'                                                       => 'DDD\Service\Fraud',
                'service_recruitment_applicant'                                       => 'DDD\Service\Recruitment\Applicant',
                'service_reservation_main'                                            => 'DDD\Service\Reservation\Main',
                'service_reservation_worst_cxl_policy_selector'                       => 'DDD\Service\Reservation\WorstCXLPolicySelector',
                'service_reservation_partner_specific'                                => 'DDD\Service\Reservation\PartnerSpecific',
                'service_availability'                                                => 'DDD\Service\Availability',
                'service_email_sender'                                                => 'DDD\Service\EmailSender',
                'service_frontier'                                                    => 'DDD\Service\Frontier',
                'service_finance_transaction_account'                                 => 'DDD\Service\Finance\TransactionAccount',
                'service_team_usages_base'                                            => 'DDD\Service\Team\Usages\Base',
                'service_team_usages_security'                                        => 'DDD\Service\Team\Usages\Security',
                'service_team_usages_frontier'                                        => 'DDD\Service\Team\Usages\Frontier',
                'service_team_usages_hiring'                                          => 'DDD\Service\Team\Usages\Hiring',
                'service_team_usages_procurement'                                     => 'DDD\Service\Team\Usages\Procurement',
                'service_customer'                                                    => 'DDD\Service\Customer',
                'service_reservation_rate_selector'                                   => 'DDD\Service\Reservation\RateSelector',
                'service_lock_general'                                                => 'DDD\Service\Lock\General',
                'service_lock_usages_apartment'                                       => 'DDD\Service\Lock\Usages\Apartment',
                'service_lock_usages_building'                                        => 'DDD\Service\Lock\Usages\Building',
                'service_lock_usages_parking'                                         => 'DDD\Service\Lock\Usages\Parking',
                'service_reservation_charge_authorization'                            => 'DDD\Service\Reservation\ChargeAuthorization',
                'service_cubilis_connection'                                          => 'DDD\Service\Cubilis\Connection',
                'service_reservation_identificator'                                   => 'DDD\Service\Reservation\Identificator',
                'service_tag_tag'                                                     => 'DDD\Service\Tag\Tag',
                'service_warehouse_category'                                          => 'DDD\Service\Warehouse\Category',
                'service_warehouse_storage'                                           => 'DDD\Service\Warehouse\Storage',
                'service_finance_budget'                                              => 'DDD\Service\Finance\Budget',
                'service_warehouse_asset'                                             => 'DDD\Service\Warehouse\Asset',
                'service_document_document'                                           => 'DDD\Service\Document\Document',
                'service_unit_testing'                                                => 'DDD\Service\UnitTesting',
                'service_cache_memcache'                                              => 'DDD\Service\Cache\Memcache',
                'library_service_google_auth'                                         => 'Library\Service\GoogleAuth',
                // @todo: only for unitTest module. To be Removed after creation UnitTest native module config
                'service_website_search'                                              => 'DDD\Service\Website\Search',
                'service_website_apartment'                                           => 'DDD\Service\Website\Apartment',
            ),
        	'factories' => array(
        		'Zend\Session\SessionManager' => function($sm) {
        			$config = $sm->get('config');

        			if (isset($config['session'])) {
        				$session = $config['session'];
                        $sessionSaveHandler = null;
                        $sessionStorage = null;
                        $sessionConfig = null;

                        if (isset($session['config'])) {
        					$class = isset($session['config']['class'])  ? $session['config']['class'] : 'Zend\Session\Config\SessionConfig';
        					$options = isset($session['config']['options']) ? $session['config']['options'] : [];
        					$sessionConfig = new $class();
        					$sessionConfig->setOptions($options);
        				}

        				if (isset($session['storage'])) {
        					$class = $session['storage'];
        					$sessionStorage = new $class();
        				}

        				if (isset($session['save_handler'])) {
        					// class should be fetched from service manager since it will require constructor arguments
        					$sessionSaveHandler = $sm->get($session['save_handler']);
        				}

        				$sessionManager = new SessionManager($sessionConfig, $sessionStorage, $sessionSaveHandler);

        			    if (isset($session['validator'])) {
        					$chain = $sessionManager->getValidatorChain();

        					foreach ($session['validator'] as $validator) {
        						$validator = new $validator();
        						$chain->attach('session.validate', [$validator, 'isValid']);
        					}
        				}
        			} else {
        				$sessionManager = new SessionManager();
        			}

        			Container::setDefaultManager($sessionManager);

        			return $sessionManager;
        		},

        		'Library\Authentication\BackofficeAuthenticationService' => function($sm) {
        			$authAdapter = new BcryptDbAdapter($sm->get('dbadapter'), DbTables::TBL_BACKOFFICE_USERS, 'email', 'password');
        			$authStorage = new AuthStorage();

        			$backofficeAuthenticationService = new BackofficeAuthenticationService($authStorage, $authAdapter);
        			$backofficeAuthenticationService->setServiceManager($sm);

        			return $backofficeAuthenticationService;
        		},
		        'MainNavigation' => function($sm) {
			        $authUser = $sm->get('library_backoffice_auth');

			        if (!$authUser->hasIdentity()) {
				        $url = $sm->get('router')->assemble([], ['name' => 'backoffice_user_login']);
				        $response = $sm->get('response');
				        $response->getHeaders()->addHeaderLine('Location', $url);
				        $response->setStatusCode(302);
				        $response->sendHeaders();

				        return $response;
			        }

			        $navigation = new BackofficeNavigation();
			        $navigation->setName('main');

			        return $navigation->createService($sm);
		        },
		        'ProfileNavigation' => function($sm) {
			        $navigation = new BackofficeNavigation();
			        $navigation->setName('profile');

			        return $navigation->createService($sm);
		        },
                'NotificationsNavigation' => function($sm) {
                    $navigation = new BackofficeNavigation();
                    $navigation->setName('notifications');

                    return $navigation->createService($sm);
                },
		        'ChannelManager' => function($sm) {
			        return new ChannelManager($sm);
		        },
		        'ActionLogger' => function($sm) {
			        return new \Library\ActionLogger\Logger($sm);
		        },
                'CurrencyList' => function($sm) {
                    return $sm->get('dao_currency_currency')->getAllCurrencies();
                },
                'DDD\Service\Location' => function ($sm){
                    $as = new \DDD\Service\Location();
                    $as->setServiceLocator($sm);
                    return $as;
                },
                'DDD\Service\Website\Cache' => function($sm){
                    $service = new \DDD\Service\Website\Cache();
                    $service->setServiceLocator($sm);
                    return $service;
                },
                'DDD\Service\Notifications' => function($sm){
                    $service = new \DDD\Service\Notifications();
                    $service->setServiceLocator($sm);
                    return $service;
                },
                'DDD\Dao\User\Users' => function($sm){
                   $as = new \DDD\Dao\User\Users($sm);
                   return $as;
                },
                'DDD\Dao\User\UserManager' => function($sm){
                   $as = new \DDD\Dao\User\UserManager($sm);
                   return $as;
                },
                'DDD\Dao\User\UserGroup' => function($sm){
                   $as = new \DDD\Dao\User\UserGroup($sm);
                   return $as;
                },
                'DDD\Dao\User\Schedule\Schedule' => function($sm){
                   $as = new \DDD\Dao\User\Schedule\Schedule($sm);
                   return $as;
                },
                'DDD\Dao\User\Schedule\Inventory' => function($sm){
                   $as = new \DDD\Dao\User\Schedule\Inventory($sm);
                   return $as;
                },
                'DDD\Dao\User\Document\Documents' => function($sm){
                   $as = new \DDD\Dao\User\Document\Documents($sm);
                   return $as;
                },
                'DDD\Dao\User\Document\DocumentTypes' => function($sm){
                   $as = new \DDD\Dao\User\Document\DocumentTypes($sm);
                   return $as;
                },
                'DDD\Dao\User\Evaluation\Evaluations' => function($sm){
                   $as = new \DDD\Dao\User\Evaluation\Evaluations($sm);
                   return $as;
                },
                'DDD\Dao\User\Evaluation\EvaluationItems' => function($sm){
                   $as = new \DDD\Dao\User\Evaluation\EvaluationItems($sm);
                   return $as;
                },
                'DDD\Dao\User\Evaluation\EvaluationTypes' => function($sm){
                   $as = new \DDD\Dao\User\Evaluation\EvaluationTypes($sm);
                   return $as;
                },
                'DDD\Dao\User\Evaluation\EvaluationValues' => function($sm){
                   $as = new \DDD\Dao\User\Evaluation\EvaluationValues($sm);
                   return $as;
                },
                'DDD\Dao\User\Devices' => function($sm){
                   $as = new \DDD\Dao\User\Devices($sm);
                   return $as;
                },
                'DDD\Dao\MoneyAccount\MoneyAccount' => function($sm){
                   $as = new \DDD\Dao\MoneyAccount\MoneyAccount($sm);
                   return $as;
                },
		        'DDD\Dao\MoneyAccount\MoneyAccountUsers' => function($sm){
			        $as = new \DDD\Dao\MoneyAccount\MoneyAccountUsers($sm);
			        return $as;
		        },
                'DDD\Dao\MoneyAccount\Attachment' =>  function($sm) {
                    $instance = new \DDD\Dao\MoneyAccount\Attachment($sm);
                    return $instance;
                },
                'DDD\Dao\MoneyAccount\AttachmentItem' =>  function($sm) {
                    $instance = new \DDD\Dao\MoneyAccount\AttachmentItem($sm);
                    return $instance;
                },
                'DDD\Dao\Finance\Supplier' => function($sm){
                   $as = new \DDD\Dao\Finance\Supplier($sm);
                   return $as;
                },
                'DDD\Dao\Geolocation\Countries' => function($sm){
                   $as = new \DDD\Dao\Geolocation\Countries($sm);
                   return $as;
                },
                'DDD\Dao\Geolocation\City' => function($sm){
                   $as = new \DDD\Dao\Geolocation\City($sm);
                   return $as;
                },
                'DDD\Dao\Currency\Currency' => function($sm){
                   $as = new \DDD\Dao\Currency\Currency($sm);
                   return $as;
                },
                'DDD\Dao\Currency\CurrencyVault' => function($sm){
                    $as = new \DDD\Dao\Currency\CurrencyVault($sm);
                    return $as;
                },
		        'DDD\Dao\ActionLogs\ActionLogs' => function($sm){
			        return new \DDD\Dao\ActionLogs\ActionLogs($sm);
		        },
		        'DDD\Dao\ActionLogs\LogsTeam' => function($sm){
			        return new \DDD\Dao\ActionLogs\LogsTeam($sm);
		        },
                'DDD\Dao\Finance\Expense\ExpenseItemCategories' => function($sm){
                   $as = new \DDD\Dao\Finance\Expense\ExpenseItemCategories($sm);
                   return $as;
                },
                'DDD\Dao\Accommodation\Accommodations' => function($sm){
                   $as = new \DDD\Dao\Accommodation\Accommodations($sm);
                   return $as;
                },
                'DDD\Dao\User\UserDashboards' => function($sm){
                   $as = new \DDD\Dao\User\UserDashboards($sm);
                   return $as;
                },
                'DDD\Dao\User\UserGroups' => function($sm){
                   $as = new \DDD\Dao\User\UserGroups($sm);
                   return $as;
                },
                'DDD\Dao\User\Dashboards' => function($sm){
                   $as = new \DDD\Dao\User\Dashboards($sm);
                   return $as;
                },
                'DDD\Dao\User\Vacationdays' => function($sm){
                   $as = new \DDD\Dao\User\Vacationdays($sm);
                   return $as;
                },
                'DDD\Dao\User\VacationRequest' => function($sm){
                   $as = new \DDD\Dao\User\VacationRequest($sm);
                   return $as;
                },
                'DDD\Dao\User\ExternalAccount' => function($sm){
                    $as = new \DDD\Dao\User\ExternalAccount($sm);
                    return $as;
                },
                'DDD\Dao\User\SalaryScheme' => function($sm){
                    $as = new \DDD\Dao\User\SalaryScheme($sm);
                    return $as;
                },
                'DDD\Dao\Apartment\Amenities' => function($sm){
                   $as = new \DDD\Dao\Apartment\Amenities($sm);
                   return $as;
                },
                'DDD\Dao\Apartment\AmenityItems' => function($sm){
                   $as = new \DDD\Dao\Apartment\AmenityItems($sm);
                   return $as;
                },
                'DDD\Dao\ApartmentGroup\Facilities' => function($sm){
                   $as = new \DDD\Dao\ApartmentGroup\Facilities($sm);
                   return $as;
                },
                'DDD\Dao\ApartmentGroup\FacilityItems' => function($sm){
                   $as = new \DDD\Dao\ApartmentGroup\FacilityItems($sm);
                   return $as;
                },
                'DDD\Dao\ApartmentGroup\BuildingDetails' => function($sm){
                    $as = new \DDD\Dao\ApartmentGroup\BuildingDetails($sm);
                    return $as;
                },
                'DDD\Dao\ApartmentGroup\BuildingSections' => function($sm){
                    $as = new \DDD\Dao\ApartmentGroup\BuildingSections($sm);
                    return $as;
                },
                'DDD\Dao\ApartmentGroup\BuildingLots' => function($sm){
                    $as = new \DDD\Dao\ApartmentGroup\BuildingLots($sm);
                    return $as;
                },
                'DDD\Dao\ApartmentGroup\ApartmentGroup' => function($sm){
                   $as = new \DDD\Dao\ApartmentGroup\ApartmentGroup($sm);
                   return $as;
                },
                'DDD\Dao\ApartmentGroup\ApartmentGroupItems' => function($sm){
                   $as = new \DDD\Dao\ApartmentGroup\ApartmentGroupItems($sm);
                   return $as;
                },
                'DDD\Dao\ApartmentGroup\ConciergeView' => function($sm){
                   $as = new \DDD\Dao\ApartmentGroup\ConciergeView($sm);
                   return $as;
                },
                'DDD\Dao\Booking\Booking' => function($sm){
                   $as = new \DDD\Dao\Booking\Booking($sm);
                   return $as;
                },
                'DDD\Dao\ApartmentGroup\ConciergeDashboardAccess' => function($sm){
                   $as = new \DDD\Dao\ApartmentGroup\ConciergeDashboardAccess($sm);
                   return $as;
                },
                'DDD\Dao\Partners\Partners' => function($sm){
                   $as = new \DDD\Dao\Partners\Partners($sm);
                   return $as;
                },
                'DDD\Dao\Partners\PartnerGcmValue' => function($sm){
                    $as = new \DDD\Dao\Partners\PartnerGcmValue($sm);
                    return $as;
                },
                'DDD\Dao\Partners\PartnerAccount' => function($sm){
                   $as = new \DDD\Dao\Partners\PartnerAccount($sm);
                   return $as;
                },
                'DDD\Dao\Partners\PartnerCityCommission' => function($sm){
                    $as = new \DDD\Dao\Partners\PartnerCityCommission($sm);
                    return $as;
                },
                'DDD\Dao\Geolocation\Details' => function($sm){
                   $as = new \DDD\Dao\Geolocation\Details($sm);
                   return $as;
                },
                'DDD\Dao\Geolocation\Poitype' => function($sm){
                   $as = new \DDD\Dao\Geolocation\Poitype($sm);
                   return $as;
                },
                'DDD\Dao\Geolocation\Poi' => function($sm){
                   $as = new \DDD\Dao\Geolocation\Poi($sm);
                   return $as;
                },
                'DDD\Dao\Geolocation\Provinces' => function($sm){
                   $as = new \DDD\Dao\Geolocation\Provinces($sm);
                   return $as;
                },
                'DDD\Dao\Geolocation\Cities' => function($sm){
                   $as = new \DDD\Dao\Geolocation\Cities($sm);
                   return $as;
                },
                'DDD\Dao\Geolocation\Continents' => function($sm){
                   $as = new \DDD\Dao\Geolocation\Continents($sm);
                   return $as;
                },
                'DDD\Dao\Blog\Blog' => function($sm){
                   $as = new \DDD\Dao\Blog\Blog($sm);
                   return $as;
                },
                'DDD\Dao\News\News' => function($sm){
                   $as = new \DDD\Dao\News\News($sm);
                   return $as;
                },
                'DDD\Dao\WebsiteLanguage\Language' => function($sm){
                    $as = new \DDD\Dao\WebsiteLanguage\Language($sm);
                    return $as;
                },
                'DDD\Dao\GeoliteCountry\GeoliteCountry' => function($sm){
                    $as = new \DDD\Dao\GeoliteCountry\GeoliteCountry($sm);
                    return $as;
                },
                'DDD\Dao\Textline\Universal' => function($sm){
                    $as = new \DDD\Dao\Textline\Universal($sm);
                    return $as;
                },
                'DDD\Dao\Textline\UniversalPageRel' => function($sm){
                    $as = new \DDD\Dao\Textline\UniversalPageRel($sm);
                    return $as;
                },
                'DDD\Dao\Translation\UniversalPages' => function($sm){
                    $as = new \DDD\Dao\Translation\UniversalPages($sm);
                    return $as;
                },
                'DDD\Dao\Textline\Location' => function($sm){
                    $as = new \DDD\Dao\Textline\Location($sm);
                    return $as;
                },
                'DDD\Dao\Textline\Apartment' => function($sm){
                    $as = new \DDD\Dao\Textline\Apartment($sm);
                    return $as;
                },
                'DDD\Dao\Textline\Group' => function($sm){
                    $as = new \DDD\Dao\Textline\Group($sm);
                    return $as;
                },
                'DDD\Dao\Apartment\Main' => function($sm){
                    $as = new \DDD\Dao\Apartment\Main($sm);
                    return $as;
                },
                'DDD\Dao\Apartment\General' => function($sm){
                    $as = new \DDD\Dao\Apartment\General($sm);
                    return $as;
                },
                'DDD\Dao\Apartment\Statistics' => function($sm){
                    $as = new \DDD\Dao\Apartment\Statistics($sm);
                    return $as;
                },
                'DDD\Dao\Apartment\DocumentCategory' => function($sm){
                    $as = new \DDD\Dao\Apartment\DocumentCategory($sm);
                    return $as;
                },
                'DDD\Dao\Apartment\Location' => function($sm){
                    $as = new \DDD\Dao\Apartment\Location($sm);
                    return $as;
                },
                'DDD\Dao\Apartment\Rate' => function($sm){
                    $as = new \DDD\Dao\Apartment\Rate($sm);
                    return $as;
                },
                'DDD\Dao\Apartment\Inventory' => function($sm){
                    $as = new \DDD\Dao\Apartment\Inventory($sm);
                    return $as;
                },
                'DDD\Dao\Apartment\Room' => function($sm){
                        $as = new \DDD\Dao\Apartment\Room($sm);
                        return $as;
                },

                'DDD\Dao\Accommodation\Review' => function($sm){
                   $as = new \DDD\Dao\Accommodation\Review($sm);
                   return $as;
                },
                'DDD\Dao\Accommodation\Images' => function($sm){
                   $as = new \DDD\Dao\Accommodation\Images($sm);
                   return $as;
                },

                'DDD\Dao\Booking\Addons' =>  function($sm) {
                    $instance = new \DDD\Dao\Booking\Addons($sm);
                    return $instance;
                },


                'DDD\Dao\Booking\Charge' =>  function($sm) {
                    $instance = new \DDD\Dao\Booking\Charge($sm);
                    return $instance;
                },

                'DDD\Dao\Booking\ChargeTransaction' =>  function($sm) {
                    $instance = new \DDD\Dao\Booking\ChargeTransaction($sm);
                    return $instance;
                },

                'DDD\Dao\Booking\BlackList' =>  function($sm) {
                    $instance = new \DDD\Dao\Booking\BlackList($sm);
                    return $instance;
                },

                'DDD\Dao\Booking\ReservationIssues' =>  function($sm) {
                    $instance = new \DDD\Dao\Booking\ReservationIssues($sm);
                    return $instance;
                },
                'DDD\Dao\Psp\Psp' => function($sm){
			        $instance = new \DDD\Dao\Psp\Psp($sm);
			        return $instance;
		        },
                'DDD\Dao\Notifications\Notifications' => function($sm){
			        $instance = new \DDD\Dao\Notifications\Notifications($sm);
			        return $instance;
		        },
                'DDD\Dao\Apartment\ReviewCategory' => function($sm){
			        return new \DDD\Dao\Apartment\ReviewCategory($sm);
		        },
                'DDD\Dao\Apartment\ReviewCategoryRel' => function($sm){
			        return new \DDD\Dao\Apartment\ReviewCategoryRel($sm);
		        },
                'DDD\Dao\Apartment\Review' => function($sm){
                    return new \DDD\Dao\Apartment\Review($sm);
                },
                'DDD\Dao\Team\Team' => function($sm) {
                    return new \DDD\Dao\Team\Team($sm);
                },
                'DDD\Dao\Team\TeamStaff' => function($sm) {
                    return new \DDD\Dao\Team\TeamStaff($sm);
                },
                'DDD\Dao\Team\TeamFrontierApartments' => function($sm) {
                    return new \DDD\Dao\Team\TeamFrontierApartments($sm);
                },
                'DDD\Dao\Team\TeamFrontierBuildings' => function($sm) {
                    return new \DDD\Dao\Team\TeamFrontierBuildings($sm);
                },
                'DDD\Dao\Office\OfficeManager' => function($sm) {
                    return new \DDD\Dao\Office\OfficeManager($sm);
                },

                'DDD\Dao\Office\OfficeSection' => function($sm) {
                    return new \DDD\Dao\Office\OfficeSection($sm);
                },

                'DDD\Dao\Location\Country' => function($sm){
			        return new \DDD\Dao\Location\Country($sm);
		        },

                'DDD\Dao\Booking\PinnedReservation' =>  function($sm) {
                    $instance = new \DDD\Dao\Booking\PinnedReservation($sm);
                    return $instance;
                },
                'DDD\Dao\Booking\FraudDetection' => function($sm){
                    $as = new \DDD\Dao\Booking\FraudDetection($sm);
                    return $as;
                },
                'DDD\Dao\Booking\Attachment' =>  function($sm) {
                    $instance = new \DDD\Dao\Booking\Attachment($sm);
                    return $instance;
                },
                'DDD\Dao\Booking\AttachmentItem' =>  function($sm) {
                    $instance = new \DDD\Dao\Booking\AttachmentItem($sm);
                    return $instance;
                },
                'DDD\Dao\Task\Task' =>  function($sm) {
                    $instance = new \DDD\Dao\Task\Task($sm);
                    return $instance;
                },
                'DDD\Dao\Task\Subtask' =>  function($sm) {
                    $instance = new \DDD\Dao\Task\Subtask($sm);
                    return $instance;
                },
                'DDD\Dao\Task\Staff' =>  function($sm) {
                    $instance = new \DDD\Dao\Task\Staff($sm);
                    return $instance;
                },
                'DDD\Dao\Task\Type' =>  function($sm) {
                    $instance = new \DDD\Dao\Task\Type($sm);
                    return $instance;
                },
                'DDD\Dao\Task\Attachments' =>  function($sm) {
                    $instance = new \DDD\Dao\Task\Attachments($sm);
                    return $instance;
                },
                'DDD\Dao\Booking\ReservationNightly' =>  function($sm) {
                    return new \DDD\Dao\Booking\ReservationNightly($sm);
                },
                'DDD\Dao\Booking\ChargeDeleted' => function($sm){
                    $as = new \DDD\Dao\Booking\ChargeDeleted($sm);
                    return $as;
                },
                'DDD\Dao\Queue\InventorySyncQueue' => function($sm){
                    $as = new \DDD\Dao\Queue\InventorySyncQueue($sm);
                    return $as;
                },
                'DDD\Dao\Queue\InventorySynchronizationQueue' => function($sm){
                    $as = new \DDD\Dao\Queue\InventorySynchronizationQueue($sm);
                    return $as;
                },
                'DDD\Dao\Queue\EmailQueue' => function($sm){
                    $as = new \DDD\Dao\Queue\EmailQueue($sm);
                    return $as;
                },
                'DDD\Dao\Lock\Types' => function($sm){
                    $as = new \DDD\Dao\Lock\Types($sm);
                    return $as;
                },
                'DDD\Dao\Lock\SettingItems' => function($sm){
                    $as = new \DDD\Dao\Lock\SettingItems($sm);
                    return $as;
                },
                'DDD\Dao\Lock\Locks' => function($sm){
                    $as = new \DDD\Dao\Lock\Locks($sm);
                    return $as;
                },
                'DDD\Dao\Lock\LockSettings' => function($sm){
                    $as = new \DDD\Dao\Lock\LockSettings($sm);
                    return $as;
                },
                'DDD\Dao\Parking\General' => function($sm){
                    $as = new \DDD\Dao\Parking\General($sm);
                    return $as;
                },
                'DDD\Dao\Parking\Spot' => function($sm){
                    $as = new \DDD\Dao\Parking\Spot($sm);
                    return $as;
                },
                'DDD\Dao\Parking\Spot\Inventory' => function($sm){
                    $as = new \DDD\Dao\Parking\Spot\Inventory($sm);
                    return $as;
                },
                'DDD\Dao\Apartment\Details' => function($sm){
                    $as = new \DDD\Dao\Apartment\Details($sm);
                    return $as;
                },
                'DDD\Dao\Apartment\Spots' => function($sm){
                    $as = new \DDD\Dao\Apartment\Spots($sm);
                    return $as;
                },
                'DDD\Dao\Finance\Ccca' => function($sm){
                    $as = new \DDD\Dao\Finance\Ccca($sm);
                    return $as;
                },
                'DDD\Dao\Tag\Tag' => function($sm){
                    $as = new \DDD\Dao\Tag\Tag($sm);
                    return $as;
                },
                'DDD\Dao\Task\Tag' => function($sm){
                    $as = new \DDD\Dao\Task\Tag($sm);
                    return $as;
                },
                'DDD\Dao\Document\Document' => function($sm){
                    $as = new \DDD\Dao\Document\Document($sm);
                    return $as;
                },
                'DDD\Dao\Document\Category' => function($sm){
                    $as = new \DDD\Dao\Document\Category($sm);
                    return $as;
                },
                'Zend\Log' => function ($sm) {
                    $filename = '/ginosi/log/exceptions/'.date('Y').'/'.date('m').'/'.date('Y-m-d').'.log';

                    if (!file_exists($filename)) {
                        if (!file_exists('/ginosi/log/exceptions/'.date('Y').'/'.date('m'))) {
                            mkdir('/ginosi/log/exceptions/'.date('Y').'/'.date('m'), 0755, true);
                        }
                        $file = fopen($filename, 'w');
                        chmod($filename, 0755);
                        fclose($file);
                    }

                    $formater = new LogFormater(
                        '%timestamp% %priorityName% (%priority%) - %message%'.PHP_EOL
                    );
                    $log = new Logger();
                    $writer = new LogWriterStream($filename);
                    $writer->setFormatter($formater);
                    $log->addWriter($writer);

                    return $log;
                },
                'Mailer\Email' => function ($serviceLocator){
        			$as = new \Mailer\Factory\EmailFactory();
        			$as = $as->createService($serviceLocator);
        			return $as;
        		},
                'Mailer\Email-Alerts' => function ($serviceLocator){
        			$as = new \Mailer\Factory\EmailFactory('alerts');
        			$as = $as->createService($serviceLocator);
        			return $as;
        		},
        		'Mailer\Transport' => function ($serviceLocator){
        			$as = new \Mailer\Factory\TransportFactory();
        			$as = $as->createService($serviceLocator);
        			return $as;
        		},
                'Mailer\Transport-Alerts' => function ($serviceLocator){
        			$as = new \Mailer\Factory\TransportFactory('transport-alerts');
        			$as = $as->createService($serviceLocator);
        			return $as;
        		},
        		'Mailer\Renderer' => function ($serviceLocator){
        			$as = new \Mailer\Factory\RendererFactory();
        			$as = $as->createService($serviceLocator);
        			return $as;
        		},
                'DDD\Dao\Customer\CustomerIdentity' => function($sm){
                   $as = new \DDD\Dao\Customer\CustomerIdentity($sm);
                   return $as;
                },
                'DDD\Dao\ChannelManager\ReservationIdentificator' => function($sm){
                    $as = new \DDD\Dao\ChannelManager\ReservationIdentificator($sm);
                    return $as;
                },
                'DDD\Dao\Finance\Customer' => function($sm){
                    $as = new Customer($sm);
                    return $as;
                },
                'DDD\Dao\Finance\Transaction\TransactionAccounts' => function($sm){
                    $as = new \DDD\Dao\Finance\Transaction\TransactionAccounts($sm);
                    return $as;
                },
                'DDD\Dao\Finance\Budget\Budget' => function($sm){
                    $as = new \DDD\Dao\Finance\Budget\Budget($sm);
                    return $as;
                },
                'DDD\Dao\Warehouse\Category' => function($sm){
                    $as = new \DDD\Dao\Warehouse\Category($sm);
                    return $as;
                },
                'DDD\Dao\Warehouse\Storage' => function($sm){
                    $as = new \DDD\Dao\Warehouse\Storage($sm);
                    return $as;
                },
                'DDD\Dao\Warehouse\Threshold' => function($sm){
                    $as = new \DDD\Dao\Warehouse\Threshold($sm);
                    return $as;
                },
                'DDD\Dao\Warehouse\SKU' => function($sm){
                    $as = new \DDD\Dao\Warehouse\SKU($sm);
                    return $as;
                },
                'DDD\Dao\Warehouse\Alias' => function($sm){
                    $as = new \DDD\Dao\Warehouse\Alias($sm);
                    return $as;
                },
                'DDD\Dao\Warehouse\Asset\Changes' => function($sm){
                    $as = new \DDD\Dao\Warehouse\Asset\Changes($sm);
                    return $as;
                },
                'DDD\Dao\Warehouse\Asset\Consumable' => function($sm){
                    $as = new \DDD\Dao\Warehouse\Asset\Consumable($sm);
                    return $as;
                },
                'DDD\Dao\Warehouse\Asset\Valuable' => function($sm){
                    $as = new \DDD\Dao\Warehouse\Asset\Valuable($sm);
                    return $as;
                },
                'DDD\Dao\Warehouse\Asset\ConsumableSkusRelation' => function($sm){
                    $as = new \DDD\Dao\Warehouse\Asset\ConsumableSkusRelation($sm);
                    return $as;
                },
                'DDD\Dao\Warehouse\Asset\ValuableStatuses' => function($sm){
                    $as = new \DDD\Dao\Warehouse\Asset\ValuableStatuses($sm);
                    return $as;
                },
                'DDD\Dao\Translation\Universal' => function($sm){
                    $as = new \DDD\Dao\Translation\Universal($sm);
                    return $as;
                },
                'DDD\Dao\Oauth\OauthUsers' => function($sm){
                    return new \DDD\Dao\Oauth\OauthUsers($sm);
                },
                'DDD\Service\Cache\Memcache' => function($sm){
                    $service = new \DDD\Service\Cache\Memcache();
                    $service->setServiceLocator($sm);
                    return $service;
                },
                'DDD\Dao\Booking\ReviewDao' => function($sm){
                    $as = new \DDD\Dao\Booking\ReviewDao($sm);
                    return $as;
                },
                'DDD\Dao\Accommodation\Review' => function($sm){
                    $as = new \DDD\Dao\Accommodation\Review($sm);
                    return $as;
                },
            ),

            'aliases'=> array(
                'dao_accommodation_accommodations'               => 'DDD\Dao\Accommodation\Accommodations',
                'dao_accommodation_images'                       => 'DDD\Dao\Accommodation\Images',
                'dao_accommodation_review'                       => 'DDD\Dao\Accommodation\Review',
                'dao_action_logs_action_logs' 	                 => 'DDD\Dao\ActionLogs\ActionLogs',
                'dao_action_logs_logs_team'                      => 'DDD\Dao\ActionLogs\LogsTeam',
                'dao_money_account_money_account' 	             => 'DDD\Dao\MoneyAccount\MoneyAccount',
                'dao_money_account_money_account_users'          => 'DDD\Dao\MoneyAccount\MoneyAccountUsers',
                'dao_money_account_document'                     => 'DDD\Dao\MoneyAccount\Attachment',
                'dao_money_account_attachment_item'              => 'DDD\Dao\MoneyAccount\AttachmentItem',
                'dao_currency_currency' 			             => 'DDD\Dao\Currency\Currency',
            	'dao_currency_currency_vault' 		             => 'DDD\Dao\Currency\CurrencyVault',
                'dao_apartment_amenities'		                 => 'DDD\Dao\Apartment\Amenities',
                'dao_apartment_amenity_items'	                 => 'DDD\Dao\Apartment\AmenityItems',
                'dao_apartment_main'                             => 'DDD\Dao\Apartment\Main',
                'dao_apartment_general'                          => 'DDD\Dao\Apartment\General',
                'dao_apartment_statistics'                       => 'DDD\Dao\Apartment\Statistics',
                'dao_apartment_group_building_details' 			 => 'DDD\Dao\ApartmentGroup\BuildingDetails',
                'dao_apartment_group_building_sections' 		 => 'DDD\Dao\ApartmentGroup\BuildingSections',
                'dao_apartment_group_building_lots'    		     => 'DDD\Dao\ApartmentGroup\BuildingLots',
                'dao_apartment_group_apartment_group' 			 => 'DDD\Dao\ApartmentGroup\ApartmentGroup',
                'dao_apartment_group_apartment_group_items' 	 => 'DDD\Dao\ApartmentGroup\ApartmentGroupItems',
                'dao_apartment_group_concierge_view' 			 => 'DDD\Dao\ApartmentGroup\ConciergeView',
                'dao_apartment_group_concierge_dashboard_access' => 'DDD\Dao\ApartmentGroup\ConciergeDashboardAccess',
                'dao_building_facilities'		                 => 'DDD\Dao\ApartmentGroup\Facilities',
                'dao_building_facility_items'	                 => 'DDD\Dao\ApartmentGroup\FacilityItems',
                'dao_expense_expense' 			                 => 'DDD\Dao\Expense\Expense',
                'dao_geolocation_countries' 		             => 'DDD\Dao\Geolocation\Countries',
                'dao_geolocation_city' 				             => 'DDD\Dao\Geolocation\City',
            	'dao_geolocation_details'                        => 'DDD\Dao\Geolocation\Details',
            	'dao_geolocation_poi_type' 		                 => 'DDD\Dao\Geolocation\Poitype',
            	'dao_geolocation_poi' 				             => 'DDD\Dao\Geolocation\Poi',
            	'dao_geolocation_provinces' 		             => 'DDD\Dao\Geolocation\Provinces',
            	'dao_geolocation_cities' 			             => 'DDD\Dao\Geolocation\Cities',
            	'dao_geolocation_continents' 		             => 'DDD\Dao\Geolocation\Continents',
            	'dao_location_country'                           => 'DDD\Dao\Location\Country',
            	'dao_user_dashboards'		                     => 'DDD\Dao\User\Dashboards',
                'dao_user_user_group' 		                     => 'DDD\Dao\User\UserGroup',
                'dao_user_user_groups' 		                     => 'DDD\Dao\User\UserGroups',
                'dao_user_user_manager' 		                 => 'DDD\Dao\User\UserManager',
                'dao_user_users' 			                     => 'DDD\Dao\User\Users',
                'dao_user_user_dashboards'                       => 'DDD\Dao\User\UserDashboards',
                'dao_user_vacation_days' 		                 => 'DDD\Dao\User\Vacationdays',
                'dao_user_vacation_request' 	                 => 'DDD\Dao\User\VacationRequest',
                'dao_user_evaluation_evaluations'                => 'DDD\Dao\User\Evaluation\Evaluations',
                'dao_user_evaluation_evaluation_items'           => 'DDD\Dao\User\Evaluation\EvaluationItems',
                'dao_user_evaluation_evaluation_values'          => 'DDD\Dao\User\Evaluation\EvaluationValues',
                'dao_user_schedule_schedule'                     => 'DDD\Dao\User\Schedule\Schedule',
                'dao_user_schedule_inventory'                    => 'DDD\Dao\User\Schedule\Inventory',
                'dao_user_document_documents'                    => 'DDD\Dao\User\Document\Documents',
                'dao_user_document_document_types'               => 'DDD\Dao\User\Document\DocumentTypes',
                'dao_user_external_account'                      => 'DDD\Dao\User\ExternalAccount',
                'dao_user_salary_scheme'                         => 'DDD\Dao\User\SalaryScheme',
                'dao_user_devices'                               => 'DDD\Dao\User\Devices',
                'dao_partners_partner_gcm_value' 		         => 'DDD\Dao\Partners\PartnerGcmValue',
                'dao_partners_partners' 		                 => 'DDD\Dao\Partners\Partners',
                'dao_partners_partner_account' 	                 => 'DDD\Dao\Partners\PartnerAccount',
                'dao_partners_partner_city_commission' 	         => 'DDD\Dao\Partners\PartnerCityCommission',
                'dao_blog_blog' 	                             => 'DDD\Dao\Blog\Blog',
                'dao_news_news' 	                             => 'DDD\Dao\News\News',
                'dao_website_language_language' 	             => 'DDD\Dao\WebsiteLanguage\Language',
                'dao_geolite_country_geolite_country'            => 'DDD\Dao\GeoliteCountry\GeoliteCountry',
                'dao_textline_universal'                         => 'DDD\Dao\Textline\Universal',
                'dao_textline_universal_page_rel'                => 'DDD\Dao\Textline\UniversalPageRel',
                'dao_translation_universal_pages'                => 'DDD\Dao\Translation\UniversalPages',
                'dao_textline_location'                          => 'DDD\Dao\Textline\Location',
                'dao_textline_apartment'                         => 'DDD\Dao\Textline\Apartment',
                'dao_textline_group'                             => 'DDD\Dao\Textline\Group',
                'dao_booking_booking' 			                 => 'DDD\Dao\Booking\Booking',
                'dao_booking_reservation_issues' 			     => 'DDD\Dao\Booking\ReservationIssues',
            	'dao_booking_charge' 	                         => 'DDD\Dao\Booking\Charge',
            	'dao_booking_change_transaction' 	             => 'DDD\Dao\Booking\ChargeTransaction',
            	'dao_booking_addons'		                     => 'DDD\Dao\Booking\Addons',
            	'dao_booking_pinned_reservation' 	             => 'DDD\Dao\Booking\PinnedReservation',
                'dao_booking_attachment'                         => 'DDD\Dao\Booking\Attachment',
                'dao_booking_attachment_item'                    => 'DDD\Dao\Booking\AttachmentItem',
                'dao_booking_reservation_nightly'                => 'DDD\Dao\Booking\ReservationNightly',
                'dao_booking_black_list'                         => 'DDD\Dao\Booking\BlackList',
                'dao_booking_fraud_detection'                    => 'DDD\Dao\Booking\FraudDetection',
                'dao_booking_charge_deleted'                     => 'DDD\Dao\Booking\ChargeDeleted',
                'dao_booking_review'                             => 'DDD\Dao\Booking\ReviewDao',
                'dao_psp_psp'                                    => 'DDD\Dao\Psp\Psp',
                'dao_notifications_notifications'                => 'DDD\Dao\Notifications\Notifications',
                'dao_team_team'                                  => 'DDD\Dao\Team\Team',
                'dao_team_team_staff'                            => 'DDD\Dao\Team\TeamStaff',
                'dao_team_team_frontier_apartments'              => 'DDD\Dao\Team\TeamFrontierApartments',
                'dao_team_team_frontier_buildings'               => 'DDD\Dao\Team\TeamFrontierBuildings',
                'dao_office_office_manager'                      => 'DDD\Dao\Office\OfficeManager',
                'dao_office_office_section'                      => 'DDD\Dao\Office\OfficeSection',
                'dao_task_task'                                  => 'DDD\Dao\Task\Task',
                'dao_task_subtask'                               => 'DDD\Dao\Task\Subtask',
                'dao_task_staff'                                 => 'DDD\Dao\Task\Staff',
                'dao_task_type'                                  => 'DDD\Dao\Task\Type',
                'dao_task_attachments'                           => 'DDD\Dao\Task\Attachments',
                'dao_task_tag'                                   => 'DDD\Dao\Task\Tag',
                'dao_customer_customer_identity'                 => 'DDD\Dao\Customer\CustomerIdentity',
                'dao_queue_inventory_sync_queue'                 => 'DDD\Dao\Queue\InventorySyncQueue',
                'dao_queue_inventory_synchronization_queue'      => 'DDD\Dao\Queue\InventorySynchronizationQueue',
                'dao_queue_email_queue'                          => 'DDD\Dao\Queue\EmailQueue',
                'dao_lock_types'                                 => 'DDD\Dao\Lock\Types',
                'dao_lock_type_setting_items'                    => 'DDD\Dao\Lock\SettingItems',
                'dao_lock_locks'                                 => 'DDD\Dao\Lock\Locks',
                'dao_lock_settings'                              => 'DDD\Dao\Lock\LockSettings',
                'dao_apartment_room'                             => 'DDD\Dao\Apartment\Room',
                'dao_apartment_review_category'                  => 'DDD\Dao\Apartment\ReviewCategory',
                'dao_apartment_review_category_rel'              => 'DDD\Dao\Apartment\ReviewCategoryRel',
                'dao_apartment_review'                           => 'DDD\Dao\Apartment\Review',
                'dao_apartment_inventory'                        => 'DDD\Dao\Apartment\Inventory',
                'dao_apartment_rate'                             => 'DDD\Dao\Apartment\Rate',
                'dao_apartment_details'                          => 'DDD\Dao\Apartment\Details',
                'dao_apartment_spots'                            => 'DDD\Dao\Apartment\Spots',
                'dao_parking_general'                            => 'DDD\Dao\Parking\General',
                'dao_parking_spot'                               => 'DDD\Dao\Parking\Spot',
                'dao_parking_spot_inventory'                     => 'DDD\Dao\Parking\Spot\Inventory',
                'dao_channel_manager_reservation_identificator'  => 'DDD\Dao\ChannelManager\ReservationIdentificator',
                'dao_finance_supplier' 			                 => 'DDD\Dao\Finance\Supplier',
                'dao_finance_legal_entities' 			         => 'DDD\Dao\Finance\LegalEntities',
                'dao_finance_ccca'                               => 'DDD\Dao\Finance\Ccca',
                'dao_finance_customer'                           => 'DDD\Dao\Finance\Customer',
                'dao_finance_transaction_transaction_accounts'   => 'DDD\Dao\Finance\Transaction\TransactionAccounts',
                'dao_finance_budget_budget'                      => 'DDD\Dao\Finance\Budget\Budget',
                'dao_tag_tag'                                    => 'DDD\Dao\Tag\Tag',
                'dao_document_document'                          => 'DDD\Dao\Document\Document',
                'dao_document_category'                          => 'DDD\Dao\Document\Category',
                'dao_warehouse_category'                         => 'DDD\Dao\Warehouse\Category',
                'dao_warehouse_storage'                          => 'DDD\Dao\Warehouse\Storage',
                'dao_warehouse_threshold'                        => 'DDD\Dao\Warehouse\Threshold',
                'dao_warehouse_sku'                              => 'DDD\Dao\Warehouse\SKU',
                'dao_warehouse_alias'                            => 'DDD\Dao\Warehouse\Alias',
                'dao_warehouse_asset_changes'                    => 'DDD\Dao\Warehouse\Asset\Changes',
                'dao_warehouse_asset_consumable'                 => 'DDD\Dao\Warehouse\Asset\Consumable',
                'dao_warehouse_asset_valuable'                   => 'DDD\Dao\Warehouse\Asset\Valuable',
                'dao_warehouse_asset_valuable_status'            => 'DDD\Dao\Warehouse\Asset\ValuableStatuses',
                'dao_warehouse_asset_consumable_skus_relation'   => 'DDD\Dao\Warehouse\Asset\ConsumableSkusRelation',
                'dao_oauth_oauth_users'                          => 'DDD\Dao\Oauth\OauthUsers',
                'dao_universal_textline'                         => 'DDD\Dao\Translation\Universal',
                'library_backoffice_auth'                        => 'Library\Authentication\BackofficeAuthenticationService',
            ),
        );
    }
}
