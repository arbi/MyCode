<?php

/**
 *          Ginosole
 *
 * [GBC] Ginosi Backoffice Console
 *
 * Commit number 10k!
 */

namespace Console;

use Zend\ModuleManager\ModuleManager,
    Zend\ModuleManager\Feature\AutoloaderProviderInterface,
    Zend\ModuleManager\Feature\ConfigProviderInterface,
    Zend\ModuleManager\Feature\InitProviderInterface,
    Zend\ModuleManager\ModuleManagerInterface;

use Zend\ModuleManager\Feature\ConsoleBannerProviderInterface;
use Zend\ModuleManager\Feature\ConsoleUsageProviderInterface;
use Zend\Console\Adapter\AdapterInterface as Console;
use Library\ChannelManager\ChannelManager;
use Zend\Session\Container;
use DDD\Dao\Finance\Customer;

class Module implements AutoloaderProviderInterface
    ,ConfigProviderInterface
    ,InitProviderInterface
    ,ConsoleUsageProviderInterface
{
    public function getConsoleUsage(Console $console)
    {
        echo '! type a true parameters.'.PHP_EOL
            .'* rtfm here \'ginosole -help\''.PHP_EOL;
        exit;
    }

    public function init(ModuleManagerInterface $moduleManager)
    {
    }

    public function getAutoloaderConfig()
    {
       return array(
            'Zend\Loader\ClassMapAutoloader' => array(
                __DIR__ . '/autoload_classmap.php',
            ),
            'Zend\Loader\StandardAutoloader' => array(
                'namespaces' => array(
                    __NAMESPACE__ => __DIR__ . '/src/' . __NAMESPACE__,
                    'Library'     => __DIR__ . '/../../library/Library',
                    'DDD'     => __DIR__ . '/../../library/DDD',
                	'core'     => __DIR__ . '/../../library/core',
                ),
            ),
        );
    }

    public function getConfig()
    {
        return include __DIR__ . '/config/module.config.php';
    }

    public function initializeView($e)
    {
    }

    public function getServiceConfig()
    {
        return array(
            'invokables' => array(
                'service_user'                                  => 'DDD\Service\User',
                'service_user_main'                             => 'DDD\Service\User\Main',
                'service_user_vacation'                         => 'DDD\Service\User\Vacation',
                'service_universal_dashboard'                   => 'DDD\Service\UniversalDashboard',
                'service_accommodations'                        => 'DDD\Service\Accommodations',
                'service_upload'                                => 'DDD\Service\Upload',
                'service_partners'                              => 'DDD\Service\Partners',
                'service_location'                              => 'DDD\Service\Location',
                'service_currency_currency'                     => 'DDD\Service\Currency\Currency',
                'service_currency_currency_vault'               => 'DDD\Service\Currency\CurrencyVault',
                'service_profile'                               => 'DDD\Service\Profile',
                'service_review'                                => 'DDD\Service\Review',
                'service_booking'                               => 'DDD\Service\Booking',
                'service_website_blog'                          => 'DDD\Service\Blog',
                'service_news'                                  => 'DDD\Service\News',
                'service_language'                              => 'DDD\Service\Language',
                'service_translation'                           => 'DDD\Service\Translation',
                'service_textline'                              => 'DDD\Service\Textline',
                'service_apartment_inventory'                   => 'DDD\Service\Apartment\Inventory',
                'service_channel_manager'                       => 'DDD\Service\ChannelManager',
                'service_penalty_calculation'                   => 'DDD\Service\PenaltyCalculation',
                'service_user_evaluations'                      => 'DDD\Service\User\Evaluations',
                'service_user_schedule'                         => 'DDD\Service\User\Schedule',
                'service_booking_management'                    => 'DDD\Service\Booking\BookingManagement',
                'service_booking_booking_ticket'                => 'DDD\Service\Booking\BookingTicket',
                'service_booking_charge'                        => 'DDD\Service\Booking\Charge',
                'service_booking_bank_transaction'              => 'DDD\Service\Booking\BankTransaction',
                'service_booking_booking_addon'                 => 'DDD\Service\Booking\BookingAddon',
                'service_booking_reservation_issues'            => 'DDD\Service\Booking\ReservationIssues',
                'service_apartment_ota_distribution'            => 'DDD\Service\Apartment\OTADistribution',
                'service_apartment_group'                       => 'DDD\Service\ApartmentGroup',
                'service_apartment_statistics'                  => 'DDD\Service\Apartment\Statistics',
                'service_apartment_details'                     => 'DDD\Service\Apartment\Details',
                'service_apartment_review'                      => 'DDD\Service\Apartment\Review',
                'service_notifications'                         => 'DDD\Service\Notifications',
                'service_finance_customer'                      => 'DDD\Service\Finance\Customer',
                'service_fraud'                                 => 'DDD\Service\Fraud',
                'service_reservation_main'                      => 'DDD\Service\Reservation\Main',
                'service_reservation_worst_cxl_policy_selector' => 'DDD\Service\Reservation\WorstCXLPolicySelector',
                'service_reservation_partner_specific'          => 'DDD\Service\Reservation\PartnerSpecific',
                'service_reservation_identificator'             => 'DDD\Service\Reservation\Identificator',
                'service_availability'                          => 'DDD\Service\Availability',
                'service_email_sender'                          => 'DDD\Service\EmailSender',
                'service_office'                                => 'DDD\Service\Office',
                'service_customer'                              => 'DDD\Service\Customer',
                'service_queue_inventory_synchronization_queue' => 'DDD\Service\Queue\InventorySynchronizationQueue',
                'service_reservation_rate_selector'             => 'DDD\Service\Reservation\RateSelector',
                'service_task'                                  => 'DDD\Service\Task',
                'service_apartment_general'                     => 'DDD\Service\Apartment\General',
                'service_parking_spot_inventory'                => 'DDD\Service\Parking\Spot\Inventory',
                'service_apartment_rate'                        => 'DDD\Service\Apartment\Rate',
                'service_queue_email_queue'                     => 'DDD\Service\Queue\EmailQueue',
                'service_apartel_ota_distribution'              => 'DDD\Service\Apartel\OTADistribution',
                'service_apartel_inventory'                     => 'DDD\Service\Apartel\Inventory',
                'service_apartel_type'                          => 'DDD\Service\Apartel\Type',
                'service_apartel_general'                       => 'DDD\Service\Apartel\General',
                'service_tag_tag'                               => 'DDD\Service\Tag\Tag',
                'service_document_document'                     => 'DDD\Service\Document\Document',
                'service_apartment_main'                        => 'DDD\Service\Apartment\Main',
                'service_cache_memcache'                        => 'DDD\Service\Cache\Memcache',
                'library_backoffice_auth'                       => 'Library\Authentication\BackofficeAuthenticationService',

            ),
        	'factories' => array(
                'DDD\Dao\Apartel\OTADistribution' =>  function($sm) {
                    return new \DDD\Dao\Apartel\OTADistribution($sm);
                },
                'ChannelManager' => function($sm) {
			        return new ChannelManager($sm);
		        },
                'ActionLogger' => function($sm) {
                    return new \Library\ActionLogger\Logger($sm);
                },
                'DDD\Service\Location' => function ($sm){
                    $as = new \DDD\Service\Location();
                    $as->setServiceLocator($sm);
                    return $as;
                },
                'DDD\Dao\User\Users' => function($sm){
                   $as = new \DDD\Dao\User\Users($sm);
                   return $as;
                },
                'DDD\Dao\User\Evaluation\Evaluations' => function($sm){
                    $as = new \DDD\Dao\User\Evaluation\Evaluations($sm);
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
                'DDD\Dao\User\UserManager' => function($sm){
                   $as = new \DDD\Dao\User\UserManager($sm);
                   return $as;
                },
                'DDD\Dao\User\UserGroup' => function($sm){
                   $as = new \DDD\Dao\User\UserGroup($sm);
                   return $as;
                },
                'DDD\Dao\MoneyAccount\MoneyAccount' => function($sm){
                   $as = new \DDD\Dao\MoneyAccount\MoneyAccount($sm);
                   return $as;
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
                'DDD\Dao\Finance\Expense\ExpenseItemCategories' => function($sm){
                   $as = new \DDD\Dao\Finance\Expense\ExpenseItemCategories($sm);
                   return $as;
                },
                'DDD\Dao\Finance\Expense\ExpenseCost' => function($sm){
                    $as = new \DDD\Dao\Finance\Expense\ExpenseCost($sm);
                    return $as;
                },
                'DDD\Dao\Accommodation\Accommodations' => function($sm){
                   $as = new \DDD\Dao\Accommodation\Accommodations($sm);
                   return $as;
                },
                'DDD\Dao\User\WorkingHours' => function($sm){
                   $as = new \DDD\Dao\User\WorkingHours($sm);
                   return $as;
                },
                'DDD\Dao\User\UserGroups' => function($sm){
                   $as = new \DDD\Dao\User\UserGroups($sm);
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
                'DDD\Dao\User\UserDashboards' => function($sm){
                   $as = new \DDD\Dao\User\UserDashboards($sm);
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
                'account_auth_service' => function ($sm) {
                   return new \Zend\Authentication\AuthenticationService();
                },
                'DDD\Dao\Partners\Partners' => function($sm){
                   $as = new \DDD\Dao\Partners\Partners($sm);
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
                'DDD\Dao\Apartment\Media' =>  function($sm) {
                    $instance = new \DDD\Dao\Apartment\Media($sm);
                    return $instance;
                },
                'DDD\Dao\Document\Document' =>  function($sm) {
                    $instance = new \DDD\Dao\Document\Document($sm);
                    return $instance;
                },
                'DDD\Dao\Apartment\Document' =>  function($sm) {
                    $instance = new \DDD\Dao\Apartment\Document($sm);
                    return $instance;
                },
                'DDD\Dao\ApartmentGroup\Document' => function($sm){
                    $as = new \DDD\Dao\ApartmentGroup\Document($sm);
                    return $as;
                },
                'DDD\Dao\Notifications\Notifications' => function($sm){
			        $instance = new \DDD\Dao\Notifications\Notifications($sm);
			        return $instance;
		        },
                'DDD\Dao\Accommodation\Review' => function($sm){
                   $as = new \DDD\Dao\Accommodation\Review($sm);
                   return $as;
                },
                'DDD\Dao\Accommodation\Images' => function($sm){
                   $as = new \DDD\Dao\Accommodation\Images($sm);
                   return $as;
                },
                'DDD\Dao\Apartment\Statistics' => function($sm){
                    $as = new \DDD\Dao\Apartment\Statistics($sm);
                    return $as;
                },
                'DDD\Dao\Apartment\Location' => function($sm) {
                    $as = new \DDD\Dao\Apartment\Location($sm);
                    return $as;
                },
                'DDD\Dao\User\Document\Documents' => function($sm){
                   $as = new \DDD\Dao\User\Document\Documents($sm);
                   return $as;
                },
                'DDD\Dao\Booking\FraudDetection' => function($sm){
                    $as = new \DDD\Dao\Booking\FraudDetection($sm);
                    return $as;
                },
                'DDD\Dao\Recruitment\Applicant\Applicant' =>  function($sm) {
                    return new \DDD\Dao\Recruitment\Applicant\Applicant($sm);
                },
                'DDD\Dao\Booking\AttachmentItem' =>  function($sm) {
                    $instance = new \DDD\Dao\Booking\AttachmentItem($sm);
                    return $instance;
                },
                'DDD\Dao\MoneyAccount\AttachmentItem' =>  function($sm) {
                    $instance = new \DDD\Dao\MoneyAccount\AttachmentItem($sm);
                    return $instance;
                },
                'DDD\Dao\Booking\ReservationNightly' =>  function($sm) {
                    return new \DDD\Dao\Booking\ReservationNightly($sm);
                },
                'DDD\Dao\Apartment\General' =>  function($sm) {
                    return new \DDD\Dao\Apartment\General($sm);
                },
                'DDD\Dao\Apartment\Inventory' => function($sm){
                    $as = new \DDD\Dao\Apartment\Inventory($sm);
                    return $as;
                },
                'DDD\Dao\Apartment\Rate' => function($sm){
                    $as = new \DDD\Dao\Apartment\Rate($sm);
                    return $as;
                },
                'DDD\Dao\Booking\ChargeDeleted' => function($sm){
                    $as = new \DDD\Dao\Booking\ChargeDeleted($sm);
                    return $as;
                },
                'DDD\Dao\Office\OfficeManager' => function($sm) {
                    return new \DDD\Dao\Office\OfficeManager($sm);
                },
                'DDD\Dao\Task\Task' => function($sm) {
                    return new \DDD\Dao\Task\Task($sm);
                },
                'DDD\Dao\Team\TeamStaff' => function($sm) {
                    return new \DDD\Dao\Team\TeamStaff($sm);
                },
                'DDD\Dao\Task\Attachments' => function($sm) {
                    return new \DDD\Dao\Task\Attachments($sm);
                },
                'DDD\Dao\Task\Staff' => function($sm) {
                    return new \DDD\Dao\Task\Staff($sm);
                },
                'DDD\Dao\Customer\CustomerIdentity' => function($sm){
                   $as = new \DDD\Dao\Customer\CustomerIdentity($sm);
                   return $as;
                },
                'DDD\Dao\Queue\InventorySyncQueue' => function($sm){
                    $as = new \DDD\Dao\Queue\InventorySyncQueue($sm);
                    return $as;
                },
                'DDD\Dao\ActionLogs\LogsTeam' => function($sm){
                    return new \DDD\Dao\ActionLogs\LogsTeam($sm);
                },
                'DDD\Dao\Queue\InventorySynchronizationQueue' => function($sm){
                    return new \DDD\Dao\Queue\InventorySynchronizationQueue($sm);
                },
                'DDD\Dao\Apartment\Details' => function($sm){
                    $as = new \DDD\Dao\Apartment\Details($sm);
                    return $as;
                },
                'DDD\Dao\Apartment\Spots' => function($sm){
                    $as = new \DDD\Dao\Apartment\Spots($sm);
                    return $as;
                },
                'DDD\Dao\Task\Type' =>  function($sm) {
                    $instance = new \DDD\Dao\Task\Type($sm);
                    return $instance;
                },
                'DDD\Dao\Team\Team' => function($sm) {
                    return new \DDD\Dao\Team\Team($sm);
                },
                'DDD\Dao\Team\TeamFrontierApartments' => function($sm) {
                    return new \DDD\Dao\Team\TeamFrontierApartments($sm);
                },
                'DDD\Dao\Parking\General' => function($sm){
                    $as = new \DDD\Dao\Parking\General($sm);
                    return $as;
                },
                'DDD\Dao\Parking\Spot' => function($sm) {
                    return new \DDD\Dao\Parking\Spot($sm);
                },
                'DDD\Dao\Parking\Spot\Inventory' => function($sm) {
                    return new \DDD\Dao\Parking\Spot\Inventory($sm);
                },
                'DDD\Dao\Finance\Ccca' => function($sm){
                    $as = new \DDD\Dao\Finance\Ccca($sm);
                    return $as;
                },
                'DDD\Dao\Queue\EmailQueue' => function($sm){
                    $as = new \DDD\Dao\Queue\EmailQueue($sm);
                    return $as;
                },

                'DDD\Dao\Apartel\Rate' => function($sm){
                    $as = new \DDD\Dao\Apartel\Rate($sm);
                    return $as;
                },
                'DDD\Dao\Apartel\Inventory' => function($sm){
                    $as = new \DDD\Dao\Apartel\Inventory($sm);
                    return $as;
                },
                'DDD\Dao\Apartel\RelTypeApartment' => function($sm){
                    $as = new \DDD\Dao\Apartel\RelTypeApartment($sm);
                    return $as;
                },
                'DDD\Dao\Apartel\Type' => function($sm){
                    $as = new \DDD\Dao\Apartel\Type($sm);
                    return $as;
                },
                'DDD\Dao\Apartel\General' => function($sm){
                    $as = new \DDD\Dao\Apartel\General($sm);
                    return $as;
                },
                'DDD\Dao\Apartel\Details' => function($sm){
                    $as = new \DDD\Dao\Apartel\Details($sm);
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
                'DDD\Dao\Tag\Tag' => function($sm){
                    $as = new \DDD\Dao\Tag\Tag($sm);
                    return $as;
                },
                'DDD\Dao\Task\Tag' => function($sm){
                    $as = new \DDD\Dao\Task\Tag($sm);
                    return $as;
                },
                'DDD\Dao\Apartment\Main' => function($sm){
                    $as = new \DDD\Dao\Apartment\Main($sm);
                    return $as;
                },
                'DDD\Dao\Oauth\OauthUsers' => function($sm){
                    return new \DDD\Dao\Oauth\OauthUsers($sm);
                },
                'DDD\Dao\Api\ApiRequests' => function($sm) {
                    return new \DDD\Dao\Api\ApiRequests($sm);
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
                    return new \DDD\Dao\Accommodation\Review($sm);
                },
                'DDD\Dao\Location\Country' => function($sm){
                    return new \DDD\Dao\Location\Country($sm);
                },
            ),
            'aliases'=> array(
                'dao_accommodation_accommodations'               => 'DDD\Dao\Accommodation\Accommodations',
                'dao_accommodation_images'                       => 'DDD\Dao\Accommodation\Images',
                'dao_accommodation_review'                       => 'DDD\Dao\Accommodation\Review',
                'dao_action_logs_action_logs'                    => 'DDD\Dao\ActionLogs\ActionLogs',
                'dao_money_account_money_account'                => 'DDD\Dao\MoneyAccount\MoneyAccount',
                'dao_money_account_attachment_item'              => 'DDD\Dao\MoneyAccount\AttachmentItem',
                'dao_apartment_details'                          => 'DDD\Dao\Apartment\Details',
                'dao_apartment_spots'                            => 'DDD\Dao\Apartment\Spots',
                'dao_apartment_general'                          => 'DDD\Dao\Apartment\General',
                'dao_apartment_inventory'                        => 'DDD\Dao\Apartment\Inventory',
                'dao_apartment_rate'                             => 'DDD\Dao\Apartment\Rate',
                'dao_apartment_media'                            => 'DDD\Dao\Apartment\Media',
                'dao_apartment_location'                         => 'DDD\Dao\Apartment\Location',
                'dao_apartment_main'                             => 'DDD\Dao\Apartment\Main',
                'dao_apartment_documents'                        => 'DDD\Dao\Apartment\Document',
                'dao_apartment_group_building_details' 		     => 'DDD\Dao\ApartmentGroup\BuildingDetails',
                'dao_apartment_group_building_sections' 		 => 'DDD\Dao\ApartmentGroup\BuildingSections',
                'dao_apartment_group_building_lots'    		     => 'DDD\Dao\ApartmentGroup\BuildingLots',
                'dao_apartment_group_apartment_group'            => 'DDD\Dao\ApartmentGroup\ApartmentGroup',
                'dao_apartment_group_apartment_group_items'      => 'DDD\Dao\ApartmentGroup\ApartmentGroupItems',
                'dao_apartment_group_concierge_view'             => 'DDD\Dao\ApartmentGroup\ConciergeView',
                'dao_apartment_group_concierge_dashboard_access' => 'DDD\Dao\ApartmentGroup\ConciergeDashboardAccess',
                'dao_currency_currency'                          => 'DDD\Dao\Currency\Currency',
                'dao_currency_currency_vault'                    => 'DDD\Dao\Currency\CurrencyVault',
                'dao_expense_expense'                            => 'DDD\Dao\Expense\Expense',
                'dao_geolocation_countries'                      => 'DDD\Dao\Geolocation\Countries',
                'dao_geolocation_city'                           => 'DDD\Dao\Geolocation\City',
                'dao_geolocation_details'                        => 'DDD\Dao\Geolocation\Details',
                'dao_geolocation_poi_type'                       => 'DDD\Dao\Geolocation\Poitype',
                'dao_geolocation_poi'                            => 'DDD\Dao\Geolocation\Poi',
                'dao_geolocation_provinces'                      => 'DDD\Dao\Geolocation\Provinces',
                'dao_geolocation_cities'                         => 'DDD\Dao\Geolocation\Cities',
                'dao_geolocation_continents'                     => 'DDD\Dao\Geolocation\Continents',
                'dao_location_country'                           => 'DDD\Dao\Location\Country',
                'dao_user_user_group'                            => 'DDD\Dao\User\UserGroup',
                'dao_user_user_groups'                           => 'DDD\Dao\User\UserGroups',
                'dao_user_user_manager'                          => 'DDD\Dao\User\UserManager',
                'dao_user_users'                                 => 'DDD\Dao\User\Users',
                'dao_user_user_dashboards'                       => 'DDD\Dao\User\UserDashboards',
                'dao_user_vacation_days'                         => 'DDD\Dao\User\Vacationdays',
                'dao_user_vacation_request'                      => 'DDD\Dao\User\VacationRequest',
                'dao_user_evaluation_evaluations'                => 'DDD\Dao\User\Evaluation\Evaluations',
                'dao_user_schedule_schedule'                     => 'DDD\Dao\User\Schedule\Schedule',
                'dao_user_schedule_inventory'                    => 'DDD\Dao\User\Schedule\Inventory',
                'dao_user_document_documents'                    => 'DDD\Dao\User\Document\Documents',
                'dao_partners_partners'                          => 'DDD\Dao\Partners\Partners',
                'dao_partners_partner_city_commission'           => 'DDD\Dao\Partners\PartnerCityCommission',
                'dao_blog_blog'                                  => 'DDD\Dao\Blog\Blog',
                'dao_news_news'                                  => 'DDD\Dao\News\News',
                'dao_website_language_language'                  => 'DDD\Dao\WebsiteLanguage\Language',
                'dao_geolite_country_geolite_country'            => 'DDD\Dao\GeoliteCountry\GeoliteCountry',
                'dao_textline_universal'                         => 'DDD\Dao\Textline\Universal',
                'dao_textline_universal_page_rel'                => 'DDD\Dao\Textline\UniversalPageRel',
                'dao_textline_location'                          => 'DDD\Dao\Textline\Location',
                'dao_textline_apartment'                         => 'DDD\Dao\Textline\Apartment',
                'dao_textline_group'                             => 'DDD\Dao\Textline\Group',
                'dao_booking_booking'                            => 'DDD\Dao\Booking\Booking',
                'dao_booking_reservation_issues'                 => 'DDD\Dao\Booking\ReservationIssues',
                'dao_booking_charge'                             => 'DDD\Dao\Booking\Charge',
                'dao_booking_change_transaction'                 => 'DDD\Dao\Booking\ChargeTransaction',
                'dao_booking_charge_deleted'                     => 'DDD\Dao\Booking\ChargeDeleted',
                'dao_booking_addons'                             => 'DDD\Dao\Booking\Addons',
                'dao_booking_black_list'                         => 'DDD\Dao\Booking\BlackList',
                'dao_booking_fraud_detection'                    => 'DDD\Dao\Booking\FraudDetection',
                'dao_booking_attachment_item'                    => 'DDD\Dao\Booking\AttachmentItem',
                'dao_booking_reservation_nightly'                => 'DDD\Dao\Booking\ReservationNightly',
                'dao_booking_review'                             => 'DDD\Dao\Booking\ReviewDao',
                'dao_document_document'                          => 'DDD\Dao\Document\Document',
                'dao_notifications_notifications'                => 'DDD\Dao\Notifications\Notifications',
                'dao_recruitment_applicant_applicant'            => 'DDD\Dao\Recruitment\Applicant\Applicant',
                'dao_office_office_manager'                      => 'DDD\Dao\Office\OfficeManager',
                'dao_customer_customer_identity'                 => 'DDD\Dao\Customer\CustomerIdentity',
                'dao_action_logs_logs_team'                      => 'DDD\Dao\ActionLogs\LogsTeam',
                'dao_queue_inventory_sync_queue'                 => 'DDD\Dao\Queue\InventorySyncQueue',
                'dao_queue_inventory_synchronization_queue'      => 'DDD\Dao\Queue\InventorySynchronizationQueue',
                'dao_team_team'                                  => 'DDD\Dao\Team\Team',
                'dao_team_team_staff'                            => 'DDD\Dao\Team\TeamStaff',
                'dao_parking_spot'                               => 'DDD\Dao\Parking\Spot',
                'dao_parking_spot_inventory'                     => 'DDD\Dao\Parking\Spot\Inventory',
                'dao_parking_general'                            => 'DDD\Dao\Parking\General',
                'dao_team_team_frontier_apartments'              => 'DDD\Dao\Team\TeamFrontierApartments',
                'dao_queue_email_queue'                          => 'DDD\Dao\Queue\EmailQueue',
                'dao_apartel_ota_distribution'                   => 'DDD\Dao\Apartel\OTADistribution',
                'dao_apartel_rate'                               => 'DDD\Dao\Apartel\Rate',
                'dao_apartel_inventory'                          => 'DDD\Dao\Apartel\Inventory',
                'dao_apartel_rel_type_apartment'                 => 'DDD\Dao\Apartel\RelTypeApartment',
                'dao_apartel_type'                               => 'DDD\Dao\Apartel\Type',
                'dao_apartel_general'                            => 'DDD\Dao\Apartel\General',
                'dao_apartel_details'                            => 'DDD\Dao\Apartel\Details',
                'dao_channel_manager_reservation_identificator'  => 'DDD\Dao\ChannelManager\ReservationIdentificator',
                'dao_finance_expense_expense_cost'               => 'DDD\Dao\Finance\Expense\ExpenseCost',
                'dao_finance_supplier'                           => 'DDD\Dao\Finance\Supplier',
                'dao_finance_customer'                           => 'DDD\Dao\Finance\Customer',
                'dao_finance_transaction_transaction_accounts'   => 'DDD\Dao\Finance\Transaction\TransactionAccounts',
                'dao_finance_ccca'                               => 'DDD\Dao\Finance\Ccca',
                'dao_tag_tag'                                    => 'DDD\Dao\Tag\Tag',
                'dao_task_attachments'                           => 'DDD\Dao\Task\Attachments',
                'dao_task_staff'                                 => 'DDD\Dao\Task\Staff',
                'dao_task_task'                                  => 'DDD\Dao\Task\Task',
                'dao_task_type'                                  => 'DDD\Dao\Task\Type',
                'dao_task_tag'                                   => 'DDD\Dao\Task\Tag',
                'dao_oauth_oauth_users'                          => 'DDD\Dao\Oauth\OauthUsers',
                'dao_api_api_requests'                           => 'DDD\Dao\Api\ApiRequests',
            ),
        );
    }

    public function getViewHelperConfig()
    {
        return [
            'factories' => [
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
            ]
        ];
    }
}
