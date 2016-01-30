<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2014 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Website;

use DDD\Dao\Finance\Customer;
use Website\View\Helper\GoogleTagManager;
use Zend\Mvc\ModuleRouteListener;
use Zend\Mvc\MvcEvent;

use Zend\Http\PhpEnvironment\RemoteAddress;
use Zend\Session\Config\SessionConfig;
use Zend\Session\SessionManager;
use Zend\Db\TableGateway\Feature\GlobalAdapterFeature;

class Module
{
    public function onBootstrap(MvcEvent $e)
    {
        $app            = $e->getApplication();
        $sm             = $app->getServiceManager();
        $request        = $app->getRequest();
        $response       = $app->getResponse();
        $appConfig      = $sm->get('Configuration');

        $sessionConfig = new SessionConfig();
        $sessionConfig->setOptions($appConfig['session']);
        $sessionManager = new SessionManager($sessionConfig);
        $sessionManager->start();

        $responseHeaders = $response->getHeaders();
        $requestHeaders = $request->getHeaders();

        $this->checkCloudFlareHttpHeader();

        $requestUriPath = $request->getUri()->getPath();

        $requestExtension = pathinfo($requestUriPath, PATHINFO_EXTENSION);

        if (!in_array($requestExtension, ['js', 'css'])) {
            $eventManager   = $app->getEventManager();

            $moduleRouteListener = new ModuleRouteListener();
            $moduleRouteListener->attach($eventManager);

            $sharedManager = $eventManager->getSharedManager();
            $sharedManager->attach('Zend\Mvc\Controller\AbstractActionController',  'dispatch',
                function($e) use ($sm) {
                    $controller = $e->getTarget();
                    $controller->getEventManager()->attachAggregate($sm->get('Visitor'));
                }, 2 );

            $client = new RemoteAddress;

            $queryParams = $request->getQuery();

            $setPartnerId = (isset($queryParams['gid']) AND is_numeric($queryParams['gid']))
                ? ['gid' => (int)$queryParams['gid'], 'url' => $request->getUri()] : FALSE;

            $setLang = (isset($queryParams['lang']) AND is_string($queryParams['lang']))
                ? ['lang' => $queryParams['lang'], 'url' => $request->getUri()] : FALSE;

            $setCurrency = (isset($queryParams['cur']) AND is_string($queryParams['cur']))
                ? ['cur' => $queryParams['cur'], 'url' => $request->getUri()] : FALSE;

            if ($requestHeaders->has('Accept-Language')) {
                $browserLang = $requestHeaders->get('Accept-Language')->getPrioritized();
            } else {
                $browserLang = FALSE;
            }

            if ($requestHeaders->has('User-Agent')) {
                $userAgent = $requestHeaders->get('User-Agent')->getFieldValue();
            } else {
                $userAgent = FALSE;
            }

            if ($requestHeaders->has('Referer')) {
                $referer = $requestHeaders->get('Referer');
            } else {
                $referer = FALSE;
            }

            $eventManager->trigger('detectVisitor', $this, array(
                'setLang'           => $setLang,
                'setCurrency'       => $setCurrency,
                'browserLang'       => $browserLang,
                'userAgent'         => $userAgent,
                'referer'           => $referer,
                'request'           => $request,
                'clientIp'          => $client->getIpAddress(),
                'clientProxy'       => $client->getUseProxy(),
                'setPartnerId'      => $setPartnerId,
                'sessionManager'    => $sessionManager,
                'response'          => $response,
                'responseHeaders'   => $responseHeaders,
                'requestHeaders'    => $requestHeaders,
                'serviceLocator'    => $sm,
            ));

            $eventManager->attach(\Zend\Mvc\MvcEvent::EVENT_ROUTE, array($this, 'onPreDispatch'));
        }

        $dbAdapter = $sm->get('Zend\Db\Adapter\Adapter');
        GlobalAdapterFeature::setStaticAdapter($dbAdapter);
    }

        /**
     * @access public
     * @param MvcEvent $e
     * @return Response, \Zend\Json\Server\Response
     */
    public function onPreDispatch (MvcEvent $e) {

    }

    public function getConfig()
    {
        // retreive application envionment
        $environment = getenv('APPLICATION_ENV') ?: 'production';

        $configurationsArray = array_merge(
	        include __DIR__ . '/config/module.config.php',
	        include __DIR__ . '/config/view-manager.' . $environment . '.php'
        );

        return $configurationsArray;
    }

    public function getAutoloaderConfig()
    {
        return array(
            'Zend\Loader\StandardAutoloader' => array(
                'namespaces' => array(
                    __NAMESPACE__       => __DIR__ . '/src/' . __NAMESPACE__,
                    'Library'           => '/ginosi/backoffice/library/Library',
                    'DDD'               => '/ginosi/backoffice/library/DDD',
                ),
            ),
        );
    }

    public function getServiceConfig() {
        return array(
            'invokables' => array(
                'service_website_cache'                         => 'DDD\Service\Website\Cache',
                'service_website_blog'                          => 'DDD\Service\Website\Blog',
                'service_website_apartment'                     => 'DDD\Service\Website\Apartment',
                'service_website_search'                        => 'DDD\Service\Website\Search',
                'service_accommodations'                        => 'DDD\Service\Accommodations',
                'service_website_booking'                       => 'DDD\Service\Website\Booking',
                'service_booking_reservation_issues'            => 'DDD\Service\Booking\ReservationIssues',
                'service_website_textline'                      => 'DDD\Service\Website\Textline',
                'service_website_index'                         => 'DDD\Service\Website\Index',
                'service_website_job'                           => 'DDD\Service\Website\Job',
                'service_currency_currency'                     => 'DDD\Service\Currency\Currency',
                'service_textline'                              => 'DDD\Service\Textline',
                'service_penalty_calculation'                   => 'DDD\Service\PenaltyCalculation',
                'service_channel_manager'                       => 'DDD\Service\ChannelManager',
                'service_apartment_main'                        => 'DDD\Service\Apartment\Main',
                'service_apartment_inventory'                   => 'DDD\Service\Apartment\Inventory',
                'service_apartment_details'                     => 'DDD\Service\Apartment\Details',
                'service_apartment_general'                     => 'DDD\Service\Apartment\General',
                'service_apartment_group'                       => 'DDD\Service\ApartmentGroup',
                'service_booking_booking_ticket'                => 'DDD\Service\Booking\BookingTicket',
                'service_finance_customer'                      => 'DDD\Service\Finance\Customer',
                'service_fraud'                                 => 'DDD\Service\Fraud',
                'service_notifications'                         => 'DDD\Service\Notifications',
                'service_reservation_main'                      => 'DDD\Service\Reservation\Main',
                'service_reservation_partner_specific'          => 'DDD\Service\Reservation\PartnerSpecific',
                'service_availability'                          => 'DDD\Service\Availability',
                'service_reservation_worst_cxl_policy_selector' => 'DDD\Service\Reservation\WorstCXLPolicySelector',
                'service_partners'                              => 'DDD\Service\Partners',
                'service_email_sender'                          => 'DDD\Service\EmailSender',
                'service_website_arrivals'                      => 'DDD\Service\Website\Arrivals',
                'service_office'                                => 'DDD\Service\Office',
                'service_user'                                  => 'DDD\Service\User',
                'service_customer'                              => 'DDD\Service\Customer',
                'service_queue_inventory_synchronization_queue' => 'DDD\Service\Queue\InventorySynchronizationQueue',
                'service_reservation_charge_authorization'      => 'DDD\Service\Reservation\ChargeAuthorization',
                'service_task'                                  => 'DDD\Service\Task',
                'service_booking'                               => 'DDD\Service\Booking',
                'service_website_apartel'                       => 'DDD\Service\Website\Apartel',
                'service_team_team'                             => 'DDD\Service\Team\Team',
                'service_apartel_type'                          => 'DDD\Service\Apartel\Type',
                'service_apartel_inventory'                     => 'DDD\Service\Apartel\Inventory',
                'service_apartel_rate'                          => 'DDD\Service\Apartel\Rate',
                'service_apartel_general'                       => 'DDD\Service\Apartel\General',
                'service_booking_charge'                        => 'DDD\Service\Booking\Charge',
                'service_geolite_country'                       => 'DDD\Service\GeoliteCountry',
                'service_website_news'                          => 'DDD\Service\Website\News',
                'service_website_location'                      => 'DDD\Service\Website\Location',
                'service_website_review'                        => 'DDD\Service\Website\Review',
                'service_lock_general'                          => 'DDD\Service\Lock\General',
            ),
        	'factories' => array(
                'DDD\Service\Website\Cache' => function($sm){
                    $service = new \DDD\Service\Website\Cache();
                    $service->setServiceLocator($sm);
                    return $service;
                },
                'DDD\Dao\Currency\Currency' => function($sm){
                    $as = new \DDD\Dao\Currency\Currency($sm);
                    return $as;
                },
                'DDD\Dao\Textline\Universal' => function($sm){
                    $as = new \DDD\Dao\Textline\Universal($sm);
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
                'DDD\Dao\GeoliteCountry\GeoliteCountry' => function($sm){
                    $as = new \DDD\Dao\GeoliteCountry\GeoliteCountry($sm);
                    return $as;
                },
                'ActionLogger' => function($sm) {
			        return new \Library\ActionLogger\Logger($sm);
		        },
                'DDD\Dao\ActionLogs\ActionLogs' => function($sm){
                    return new \DDD\Dao\ActionLogs\ActionLogs($sm);
                },
                'DDD\Dao\Booking\Booking' => function($sm){
                   $as = new \DDD\Dao\Booking\Booking($sm);
                   return $as;
                },
                'DDD\Dao\Booking\ReservationIssues' =>  function($sm) {
                    $instance = new \DDD\Dao\Booking\ReservationIssues($sm);
                    return $instance;
                },
                'DDD\Dao\Blog\Blog' => function($sm){
                    return new \DDD\Dao\Blog\Blog($sm);
                },
                'DDD\Dao\News\News' => function($sm){
                    return new \DDD\Dao\News\News($sm);
                },
                'DDD\Dao\Recruitment\Job\Job' => function($sm){
                   $as = new \DDD\Dao\Recruitment\Job\Job($sm);
                   return $as;
                },
                'DDD\Dao\Recruitment\Applicant\Applicant' => function($sm){
                   $as = new \DDD\Dao\Recruitment\Applicant\Applicant($sm);
                   return $as;
                },
                'DDD\Dao\Notifications\Notifications' => function($sm){
                    $instance = new \DDD\Dao\Notifications\Notifications($sm);
                    return $instance;
                },
                'DDD\Dao\Booking\ReservationNightly' =>  function($sm) {
                    return new \DDD\Dao\Booking\ReservationNightly($sm);
                },
                'DDD\Dao\Apartment\Inventory' => function($sm){
                    $as = new \DDD\Dao\Apartment\Inventory($sm);
                    return $as;
                },
                'DDD\Dao\ApartmentGroup\ApartmentGroupItems' => function($sm){
                    $as = new \DDD\Dao\ApartmentGroup\ApartmentGroupItems($sm);
                    return $as;
                },
                'DDD\Dao\Apartment\Rate' => function($sm){
                    $as = new \DDD\Dao\Apartment\Rate($sm);
                    return $as;
                },
                'DDD\Dao\ApartmentGroup\ConciergeView' => function($sm) {
                        $as = new \DDD\Dao\ApartmentGroup\ConciergeView($sm);
                        return $as;
                    },
                'DDD\Dao\ApartmentGroup\ConciergeAccommodation' => function($sm){
                        $as = new \DDD\Dao\ApartmentGroup\ConciergeAccommodation($sm);
                        return $as;
                },
                'DDD\Dao\ApartmentGroup\BuildingDetails' => function($sm){
                    $as = new \DDD\Dao\ApartmentGroup\BuildingDetails($sm);
                    return $as;
                },
                'DDD\Dao\ApartmentGroup\ApartmentGroup' => function($sm){
                        $as = new \DDD\Dao\ApartmentGroup\ApartmentGroup($sm);
                        return $as;
                    },
                'DDD\Dao\Booking\Charge' => function($sm) {
                    $instance = new \DDD\Dao\Booking\Charge($sm);
                    return $instance;
                },
                'DDD\Dao\User\UserManager' => function($sm){
                   $as = new \DDD\Dao\User\UserManager($sm);
                   return $as;
                },
                'DDD\Dao\Partners\Partners' => function($sm){
                   $as = new \DDD\Dao\Partners\Partners($sm);
                   return $as;
                },
                'DDD\Dao\Partners\PartnerCityCommission' => function($sm){
                    $as = new \DDD\Dao\Partners\PartnerCityCommission($sm);
                    return $as;
                },
                'DDD\Dao\Customer\CustomerIdentity' => function($sm){
                   $as = new \DDD\Dao\Customer\CustomerIdentity($sm);
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
                'DDD\Dao\Task\Type' =>  function($sm) {
                    $instance = new \DDD\Dao\Task\Type($sm);
                    return $instance;
                },
                'DDD\Dao\Task\Task' =>  function($sm) {
                        $instance = new \DDD\Dao\Task\Task($sm);
                        return $instance;
                    },
                'DDD\Dao\Team\Team' => function($sm) {
                    return new \DDD\Dao\Team\Team($sm);
                },
                'DDD\Dao\Task\Attachments' => function($sm) {
                    return new \DDD\Dao\Task\Attachments($sm);
                },

                'DDD\Dao\Geolocation\Countries' => function($sm){
                   $as = new \DDD\Dao\Geolocation\Countries($sm);
                   return $as;
                },
                'DDD\Dao\Geolocation\City' => function($sm){
                   $as = new \DDD\Dao\Geolocation\City($sm);
                   return $as;
                },
                'DDD\Dao\Geolocation\Provinces' => function($sm){
                   $as = new \DDD\Dao\Geolocation\Provinces($sm);
                   return $as;
                },
                'DDD\Dao\Apartment\Details' => function($sm){
                    $as = new \DDD\Dao\Apartment\Details($sm);
                    return $as;
                },
                'DDD\Dao\Finance\Ccca' => function($sm){
                    $as = new \DDD\Dao\Finance\Ccca($sm);
                    return $as;
                },
                'DDD\Dao\Accommodation\Accommodations' => function($sm){
                    $as = new \DDD\Dao\Accommodation\Accommodations($sm);
                    return $as;
                },
                'DDD\Dao\Apartment\General' => function($sm){
                    $as = new \DDD\Dao\Apartment\General($sm);
                    return $as;
                },
                'DDD\Dao\User\UserGroups' => function($sm){
                    $as = new \DDD\Dao\User\UserGroups($sm);
                    return $as;
                },

                'DDD\Dao\Apartel\Inventory' => function($sm){
                    $as = new \DDD\Dao\Apartel\Inventory($sm);
                    return $as;
                },
                'DDD\Dao\Apartel\Type' => function($sm){
                    $as = new \DDD\Dao\Apartel\Type($sm);
                    return $as;
                },
                'DDD\Dao\Apartel\RelTypeApartment' => function($sm){
                    $as = new \DDD\Dao\Apartel\RelTypeApartment($sm);
                    return $as;
                },
                'DDD\Dao\Office\OfficeManager' => function($sm) {
                    return new \DDD\Dao\Office\OfficeManager($sm);
                },
                'DDD\Dao\Office\OfficeSection' => function($sm) {
                    return new \DDD\Dao\Office\OfficeSection($sm);
                },
                'DDD\Dao\Finance\Customer' => function($sm){
                    $as = new Customer($sm);
                    return $as;
                },
                'DDD\Dao\Finance\Transaction\TransactionAccounts' => function($sm){
                    $as = new \DDD\Dao\Finance\Transaction\TransactionAccounts($sm);
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
                'DDD\Dao\Apartel\General' => function($sm){
                    $as = new \DDD\Dao\Apartel\General($sm);
                    return $as;
                },
                'DDD\Dao\Apartel\Rate' => function($sm){
                    $as = new \DDD\Dao\Apartel\Rate($sm);
                    return $as;
                },

                'DDD\Dao\Apartel\Details' => function($sm){
                    $as = new \DDD\Dao\Apartel\Details($sm);
                    return $as;
                },
                'DDD\Dao\Team\TeamStaff' => function($sm) {
                    return new \DDD\Dao\Team\TeamStaff($sm);
                },
                'DDD\Dao\Lock\Locks' => function($sm){
                    $as = new \DDD\Dao\Lock\Locks($sm);
                    return $as;
                },
                'DDD\Dao\Apartment\Spots' => function($sm){
                    $as = new \DDD\Dao\Apartment\Spots($sm);
                    return $as;
                },
                'DDD\Dao\Booking\ReviewDao' => function($sm){
                    return new \DDD\Dao\Booking\ReviewDao($sm);
                },
                'DDD\Dao\Location\Country' => function($sm){
                    return new \DDD\Dao\Location\Country($sm);
                },
                'DDD\Dao\Accommodation\Review' => function($sm){
                    $as = new \DDD\Dao\Accommodation\Review($sm);
                    return $as;
                },
            ),
            'aliases'=> array(
                'dao_team_team_staff'                          => 'DDD\Dao\Team\TeamStaff',
                'dao_currency_currency'                        => 'DDD\Dao\Currency\Currency',
                'dao_textline_universal'                       => 'DDD\Dao\Textline\Universal',
                'dao_textline_location'                        => 'DDD\Dao\Textline\Location',
                'dao_textline_apartment'                       => 'DDD\Dao\Textline\Apartment',
                'dao_geolite_country_geolite_country'          => 'DDD\Dao\GeoliteCountry\GeoliteCountry',
                'dao_booking_booking'                          => 'DDD\Dao\Booking\Booking',
                'dao_booking_reservation_issues'               => 'DDD\Dao\Booking\ReservationIssues',
                'dao_blog_blog'                                => 'DDD\Dao\Blog\Blog',
                'dao_news_news'                                => 'DDD\Dao\News\News',
                'dao_action_logs_action_logs'                  => 'DDD\Dao\ActionLogs\ActionLogs',
                'dao_recruitment_job_job'                      => 'DDD\Dao\Recruitment\Job\Job',
                'dao_recruitment_applicant_applicant'          => 'DDD\Dao\Recruitment\Applicant\Applicant',
                'dao_notifications_notifications'              => 'DDD\Dao\Notifications\Notifications',
                'dao_apartment_group_concierge_view'           => 'DDD\Dao\ApartmentGroup\ConciergeView',
                'dao_apartment_group_apartment_group_items'    => 'DDD\Dao\ApartmentGroup\ApartmentGroupItems',
                'dao_apartment_group_apartment_group'          => 'DDD\Dao\ApartmentGroup\ApartmentGroup',
                'dao_apartment_group_building_details'         => 'DDD\Dao\ApartmentGroup\BuildingDetails',
                'dao_booking_charge'                           => 'DDD\Dao\Booking\Charge',
                'dao_booking_reservation_nightly'              => 'DDD\Dao\Booking\ReservationNightly',
                'dao_booking_review'                           => 'DDD\Dao\Booking\ReviewDao',
                'dao_apartment_inventory'                      => 'DDD\Dao\Apartment\Inventory',
                'dao_apartment_rate'                           => 'DDD\Dao\Apartment\Rate',
                'dao_partners_partners'                        => 'DDD\Dao\Partners\Partners',
                'dao_partners_partner_city_commission'         => 'DDD\Dao\Partners\PartnerCityCommission',
                'dao_user_user_manager'                        => 'DDD\Dao\User\UserManager',
                'dao_customer_customer_identity'               => 'DDD\Dao\Customer\CustomerIdentity',
                'dao_queue_inventory_sync_queue'               => 'DDD\Dao\Queue\InventorySyncQueue',
                'dao_queue_inventory_synchronization_queue'    => 'DDD\Dao\Queue\InventorySynchronizationQueue',
                'dao_task_type'                                => 'DDD\Dao\Task\Type',
                'dao_team_team'                                => 'DDD\Dao\Team\Team',
                'dao_task_attachments'                         => 'DDD\Dao\Task\Attachments',
                'dao_geolocation_city'                         => 'DDD\Dao\Geolocation\City',
                'dao_geolocation_countries'                    => 'DDD\Dao\Geolocation\Countries',
                'dao_geolocation_provinces'                    => 'DDD\Dao\Geolocation\Provinces',
                'dao_location_country'                         => 'DDD\Dao\Location\Country',
                'dao_apartment_details'                        => 'DDD\Dao\Apartment\Details',
                'dao_finance_ccca'                             => 'DDD\Dao\Finance\Ccca',
                'dao_accommodation_accommodations'             => 'DDD\Dao\Accommodation\Accommodations',
                'dao_accommodation_review'                     => 'DDD\Dao\Accommodation\Review',
                'dao_apartment_general'                        => 'DDD\Dao\Apartment\General',
                'dao_user_user_groups' 		                   => 'DDD\Dao\User\UserGroups',
                'dao_apartel_rel_type_apartment'               => 'DDD\Dao\Apartel\RelTypeApartment',
                'dao_apartel_inventory'                        => 'DDD\Dao\Apartel\Inventory',
                'dao_office_office_manager'                    => 'DDD\Dao\Office\OfficeManager',
                'dao_office_office_section'                    => 'DDD\Dao\Office\OfficeSection',
                'dao_finance_customer'                         => 'DDD\Dao\Finance\Customer',
                'dao_finance_transaction_transaction_accounts' => 'DDD\Dao\Finance\Transaction\TransactionAccounts',
                'dao_tag_tag'                                  => 'DDD\Dao\Tag\Tag',
                'dao_task_tag'                                 => 'DDD\Dao\Task\Tag',
                'dao_task_task'                                => 'DDD\Dao\Task\Task',
                'dao_apartel_general'                          => 'DDD\Dao\Apartel\General',
                'dao_apartel_type'                             => 'DDD\Dao\Apartel\Type',
                'dao_apartel_details'                          => 'DDD\Dao\Apartel\Details',
                'dao_apartel_rate'                             => 'DDD\Dao\Apartel\Rate',
                'dao_lock_locks'                               => 'DDD\Dao\Lock\Locks',
                'dao_apartment_spots'                          => 'DDD\Dao\Apartment\Spots',
            ),
        );
    }

    public function getViewHelperConfig()
    {
        return array(
            'factories' => array(
                'textline' => function($helperPluginManager) {
                    $serviceLocator = $helperPluginManager->getServiceLocator();
                    $helper = new View\Helper\Textline();
                    $helper->setServiceLocator($serviceLocator);

                    return $helper;
                },
                'productTextline' => function($helperPluginManager) {
                    $serviceLocator = $helperPluginManager->getServiceLocator();
                    $helper = new View\Helper\ProductTextline();
                    $helper->setServiceLocator($serviceLocator);

                    return $helper;
                },
                'cityName' => function($helperPluginManager) {
                    $serviceLocator = $helperPluginManager->getServiceLocator();
                    $helper = new View\Helper\CityName();
                    $helper->setServiceLocator($serviceLocator);

                    return $helper;
                },
                'phoneNumber' => function($helperPluginManager) {
                    $serviceLocator = $helperPluginManager->getServiceLocator();
                    $helper = new View\Helper\PhoneNumber();
                    $helper->setServiceLocator($serviceLocator);

                    return $helper;
                },
                'provinceName' => function($helperPluginManager) {
                    $serviceLocator = $helperPluginManager->getServiceLocator();
                    $helper = new View\Helper\ProvinceName();
                    $helper->setServiceLocator($serviceLocator);

                    return $helper;
                },
                'countryName' => function($helperPluginManager) {
                    $serviceLocator = $helperPluginManager->getServiceLocator();
                    $helper = new View\Helper\CountryName();
                    $helper->setServiceLocator($serviceLocator);

                    return $helper;
                },
                'currencyList' => function($helperPluginManager) {
                    $serviceLocator = $helperPluginManager->getServiceLocator();
                    $helper = new View\Helper\CurrencyList();
                    $helper->setServiceLocator($serviceLocator);

                    return $helper;
                },
                'currencyUser' => function($helperPluginManager) {
                    $helper = new View\Helper\CurrencyUser();
                    return $helper;
                },
                'urltoCity' => function($helperPluginManager) {
                    $serviceLocator = $helperPluginManager->getServiceLocator();
                    $helper = new View\Helper\UrlToCity();
                    $helper->setServiceLocator($serviceLocator);

                    return $helper;
                },
                'customBreadcrumb' => function($helperPluginManager) {
                    $serviceLocator = $helperPluginManager->getServiceLocator();
                    $helper = new View\Helper\CustomBreadcrumb();
                    $helper->setServiceLocator($serviceLocator);

                    return $helper;
                },
                'userTracking' => function ($sm) {
                    $viewHelper = new View\Helper\UserTracking();
                    $viewHelper->setServiceLocator($sm->getServiceLocator());
                    return $viewHelper;
                },
                'googleTagManager' => function ($sm) {
                        $googleTagManagerHelper = new GoogleTagManager\GoogleTagManager();
                        $googleTagManagerHelper->setServiceLocator($sm->getServiceLocator());
                        return $googleTagManagerHelper;
                    }
            )
        );
    }

    private function checkCloudFlareHttpHeader()
    {
        if (isset($_SERVER['HTTP_CF_CONNECTING_IP']))
        {
            $_SERVER['REMOTE_ADDR'] = $_SERVER['HTTP_CF_CONNECTING_IP'];
        }
    }
}
