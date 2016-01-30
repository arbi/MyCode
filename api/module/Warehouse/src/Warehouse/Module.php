<?php
namespace Warehouse;

use ZF\Apigility\Provider\ApigilityProviderInterface;

class Module implements ApigilityProviderInterface
{
    public function getConfig()
    {
        return include __DIR__ . '/../../config/module.config.php';
    }

    public function getAutoloaderConfig()
    {
        return array(
            'ZF\Apigility\Autoloader' => array(
                'namespaces' => array(
                    __NAMESPACE__ => __DIR__,
                ),
            ),
        );
    }

    public function getServiceConfig()
    {
        return [
            'invokables' => [
                'service_warehouse_asset'    => 'DDD\Service\Warehouse\Asset',
                'service_warehouse_category' => 'DDD\Service\Warehouse\Category',
                'service_user'               => 'DDD\Service\User',
                'service_task'               => 'DDD\Service\Task',
                'service_location'           => 'DDD\Service\Location',
            ],
            'factories' => [
                'DDD\Dao\Accommodation\Accommodations' => function($sm){
                   $as = new \DDD\Dao\Accommodation\Accommodations($sm);
                   return $as;
                },
                'DDD\Dao\User\UserManager' => function($sm){
                   $as = new \DDD\Dao\User\UserManager($sm);
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
                'DDD\Dao\ApartmentGroup\ApartmentGroup' => function($sm){
                   $as = new \DDD\Dao\ApartmentGroup\ApartmentGroup($sm);
                   return $as;
                },
                'DDD\Dao\Office\OfficeManager' => function($sm) {
                    return new \DDD\Dao\Office\OfficeManager($sm);
                },
                'DDD\Dao\WHOrder\Order' => function($sm){
                    $dao = new \DDD\Dao\WHOrder\Order($sm);
                    return $dao;
                },
                'ActionLogger' => function($sm) {
                    return new \Library\ActionLogger\Logger($sm);
                },
                'DDD\Dao\ActionLogs\ActionLogs' => function($sm){
                    return new \DDD\Dao\ActionLogs\ActionLogs($sm);
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
                'DDD\Dao\Task\Tag' => function($sm){
                    $as = new \DDD\Dao\Task\Tag($sm);
                    return $as;
                },
                'DDD\Dao\Tag\Tag' => function($sm){
                    $as = new \DDD\Dao\Tag\Tag($sm);
                    return $as;
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
                'DDD\Dao\ActionLogs\LogsTeam' => function($sm){
                    return new \DDD\Dao\ActionLogs\LogsTeam($sm);
                },
                'DDD\Dao\User\Users' => function($sm){
                   return new \DDD\Dao\User\Users($sm);
                },
                'Warehouse\V1\Rest\Histories\HistoriesMapper' =>  function ($sm) {
                    $adapter = $sm->get('dbadapter');
                    return new \Warehouse\V1\Rest\Histories\HistoriesMapper($adapter);
                },
                'DDD\Dao\ApartmentGroup\ApartmentGroupItems' => function($sm){
                   return new \DDD\Dao\ApartmentGroup\ApartmentGroupItems($sm);
                },
            ],
            'aliases'=> [
                'dao_user_user_manager'                        => 'DDD\Dao\User\UserManager',
                'dao_apartment_group_apartment_group' 		   => 'DDD\Dao\ApartmentGroup\ApartmentGroup',
                'dao_accommodation_accommodations'             => 'DDD\Dao\Accommodation\Accommodations',
                'dao_warehouse_category'                       => 'DDD\Dao\Warehouse\Category',
                'dao_warehouse_storage'                        => 'DDD\Dao\Warehouse\Storage',
                'dao_warehouse_threshold'                      => 'DDD\Dao\Warehouse\Threshold',
                'dao_warehouse_asset_changes'                  => 'DDD\Dao\Warehouse\Asset\Changes',
                'dao_warehouse_asset_consumable'               => 'DDD\Dao\Warehouse\Asset\Consumable',
                'dao_warehouse_asset_valuable'                 => 'DDD\Dao\Warehouse\Asset\Valuable',
                'dao_warehouse_asset_valuable_status'          => 'DDD\Dao\Warehouse\Asset\ValuableStatuses',
                'dao_warehouse_asset_consumable_skus_relation' => 'DDD\Dao\Warehouse\Asset\ConsumableSkusRelation',
                'dao_office_office_manager'                    => 'DDD\Dao\Office\OfficeManager',
                'dao_wh_order_order'                           => 'DDD\Dao\WHOrder\Order',
                'dao_action_logs_action_logs' 	               => 'DDD\Dao\ActionLogs\ActionLogs',
                'dao_warehouse_sku'                            => 'DDD\Dao\Warehouse\SKU',
                'dao_task_task'                                => 'DDD\Dao\Task\Task',
                'dao_task_subtask'                             => 'DDD\Dao\Task\Subtask',
                'dao_task_staff'                               => 'DDD\Dao\Task\Staff',
                'dao_task_type'                                => 'DDD\Dao\Task\Type',
                'dao_task_attachments'                         => 'DDD\Dao\Task\Attachments',
                'dao_task_tag'                                 => 'DDD\Dao\Task\Tag',
                'dao_tag_tag'                                  => 'DDD\Dao\Tag\Tag',
                'dao_team_team'                                => 'DDD\Dao\Team\Team',
                'dao_team_staff'                               => 'DDD\Dao\Team\TeamStaff',
                'dao_team_team_frontier_apartments'            => 'DDD\Dao\Team\TeamFrontierApartments',
                'dao_team_team_frontier_buildings'             => 'DDD\Dao\Team\TeamFrontierBuildings',
                'dao_action_logs_logs_team'                    => 'DDD\Dao\ActionLogs\LogsTeam',
                'dao_user_users' 			                   => 'DDD\Dao\User\Users',
                'dao_apartment_group_apartment_group_items'    => 'DDD\Dao\ApartmentGroup\ApartmentGroupItems',

            ]
        ];
    }
}
