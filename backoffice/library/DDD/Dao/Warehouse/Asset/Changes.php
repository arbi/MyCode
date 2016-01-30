<?php
namespace DDD\Dao\Warehouse\Asset;

use Library\Utility\Debug;

use Library\DbManager\TableGatewayManager;
use Library\Constants\Constants;
use Library\Constants\DbTables;

use Zend\Db\Sql\Where;
use Zend\Db\Sql\Expression;
use Zend\Db\Sql\Select;

use DDD\Service\Warehouse\Asset as AssetService;

class Changes extends TableGatewayManager
{
    protected $table = DbTables::TBL_ASSETS_CHANGES;

    public function __construct($sm)
    {
        parent::__construct($sm, 'DDD\Domain\Warehouse\Assets\Changes');
    }

    public function logChange($categoryId, $userId, $locationEntityType, $locationEntityId, $quantityChange, $shipmentStatus, $dateTime)
    {
        $this->save(
            [
                'category_id'          => $categoryId,
                'user_id'              => $userId,
                'location_entity_type' => $locationEntityType,
                'location_entity_id'   => $locationEntityId,
                'quantity_change'      => $quantityChange,
                'shipment_status'      => $shipmentStatus,
                'action_date'          => $dateTime
            ]
        );
    }

    /**
     * @return \DDD\Domain\Warehouse\Assets\Consumable []
     */
    public function getAssetsAwaitingApproval()
    {
        $result = $this->fetchAll(function (Select $select) {
            $where = new Where();
            $where->greaterThan($this->getTable() . '.quantity_change', 0);
            $select
                ->columns([
                    'id',
                    'category_id',
                    'location_entity_id',
                    'location_entity_type',
                    'location_name' => new Expression(" (CASE " . $this->getTable() . ".location_entity_type " .
                        "WHEN " . AssetService::ENTITY_TYPE_APARTMENT . " THEN apartments.name " .
                        "WHEN " . AssetService::ENTITY_TYPE_OFFICE . " THEN offices.name " .
                        "WHEN " . AssetService::ENTITY_TYPE_BUILDING . " THEN apartment_groups.name " .
                        "WHEN " . AssetService::ENTITY_TYPE_STORAGE . " THEN storages.name " .
                        " END)"),
                    'quantity_change',
                    'shipment_status'
                ])
                ->join(
                    ['cons' => DbTables::TBL_ASSETS_CONSUMABLE],
                    $this->getTable() . '.category_id = cons.category_id',
                    ['asset_id' => 'id'],
                    Select::JOIN_INNER
                )
                ->join(
                    ['categories' => DbTables::TBL_ASSET_CATEGORIES],
                    $this->getTable() . '.category_id = categories.id',
                    ['category_name' => 'name'],
                    Select::JOIN_INNER
                )
                ->join(
                    ['users' => DbTables::TBL_BACKOFFICE_USERS],
                    'cons.last_updated_by_id = users.id',
                    [
                        'firstname_last_updated' => 'firstname',
                        'lastname_last_updated' => 'lastname'
                    ],
                    Select::JOIN_LEFT
                )
                ->join(
                    ['apartments' => DbTables::TBL_APARTMENTS],
                    new Expression($this->getTable() . '.location_entity_id = apartments.id AND ' . $this->getTable() .'.location_entity_type = ' . AssetService::ENTITY_TYPE_APARTMENT),
                    [],
                    Select::JOIN_LEFT
                )
                ->join(
                    ['offices' => DbTables::TBL_OFFICES],
                    new Expression($this->getTable() . '.location_entity_id = offices.id AND ' . $this->getTable() .'.location_entity_type = ' . AssetService::ENTITY_TYPE_OFFICE),
                    [],
                    Select::JOIN_LEFT
                )
                ->join(
                    ['apartment_groups' => DbTables::TBL_APARTMENT_GROUPS],
                    new Expression($this->getTable() . '.location_entity_id = apartment_groups.id AND ' . $this->getTable() .'.location_entity_type = ' . AssetService::ENTITY_TYPE_BUILDING),
                    [],
                    Select::JOIN_LEFT
                )
                ->join(
                    ['storages' => DbTables::TBL_WM_STORAGE],
                    new Expression($this->getTable() . '.location_entity_id = storages.id AND ' . $this->getTable() .'.location_entity_type = ' . AssetService::ENTITY_TYPE_STORAGE),
                    [],
                    Select::JOIN_LEFT
                )
                ->where($where);
        });

        return $result;
    }

    /**
     * @return int
     */
    public function getAssetsAwaitingApprovalCount()
    {
        return  $this->fetchAll(function (Select $select) {
            $select->where->greaterThan($this->getTable() . '.quantity_change', 0);
        })->count();
    }
}
