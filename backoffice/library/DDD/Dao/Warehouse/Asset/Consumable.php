<?php
namespace DDD\Dao\Warehouse\Asset;

use Zend\Db\Sql\Expression;
use Zend\Db\Sql\Select;
use Zend\Db\Sql\Where;

use Library\DbManager\TableGatewayManager;
use Library\Constants\Constants;
use Library\Constants\DbTables;
use Library\Utility\Debug;

use DDD\Service\Warehouse\Asset as AssetService;
use DDD\Service\Team\Team as TeamService;


class Consumable extends TableGatewayManager
{
    protected $table = DbTables::TBL_ASSETS_CONSUMABLE;

    public function __construct($sm)
    {
        parent::__construct($sm, 'DDD\Domain\Warehouse\Assets\Consumable');
    }

    public function saveNewConsumableAsset(
        $categoryId,
        $locationEntityType,
        $locationEntityId,
        $quantity,
        $description,
        $userId
    ) {
        $this->save(
          [
              'category_id'          => $categoryId,
              'location_entity_type' => $locationEntityType,
              'location_entity_id'   => $locationEntityId,
              'quantity'             => $quantity,
              'description'          => $description,
              'last_updated_by_id'   => $userId,
          ]
        );
    }

    public function getRowByCategoryLocationIdLocationEntitySku($categoryId, $sku, $locationEntityType, $locationEntityId)
    {
        return  $this->fetchOne(function (Select $select) use($categoryId, $sku, $locationEntityType, $locationEntityId) {

            $where = new Where();

            $select->join(
                ['sku_table' => DbTables::TBL_SKU],
                $this->getTable() . '.category_id = sku_table.asset_category_id',
                [],
                Select::JOIN_INNER
            );

            $where->equalTo($this->getTable() . '.category_id', $categoryId);
            $where->equalTo($this->getTable() . '.location_entity_type', $locationEntityType);
            $where->equalTo($this->getTable() . '.location_entity_id', $locationEntityId);
            $where->equalTo('sku_table.name', $sku);
            $select
                ->columns(['id'])
                ->where($where);
        });
    }

    public function getRowByCategoryLocationIdLocationEntity($categoryId, $locationEntityType, $locationEntityId, $assetId, $getInfo = false)
    {
        return  $this->fetchOne(function (Select $select) use($categoryId,  $locationEntityType, $locationEntityId, $assetId, $getInfo) {
            $where = new Where();
            $where->equalTo('category_id', $categoryId);
            $where->equalTo('location_entity_type', $locationEntityType);
            $where->equalTo('location_entity_id', $locationEntityId);

            if ($assetId) {
                $where->notEqualTo($this->getTable() . '.id', $assetId);
            }

            if (!$getInfo) {
                $select->columns(['id']);
            }

            $select->where($where);

        });
    }

    public function changeAssetQuantity($assetId, $quantity, $shipmentStatus)
    {
        $this->save(['quantity' => new Expression('quantity + ' . $quantity)],
            ['id' => $assetId]);
    }


    public function getListForSearch($iDisplayStart, $iDisplayLength, $filterParams, $sSearch,  $sortCol = 0, $sortDir = 'DESC')
    {
        $where = new Where();

        if ($filterParams["category"] > 0) {
            $where->equalTo($this->getTable() . '.category_id', $filterParams["category"]);
        }

        if (FALSE !== strpos($filterParams["location"], '_')) {
            $locationsArray = explode('_', $filterParams["location"]);
            $locationId   = $locationsArray[1];
            $where->equalTo($this->getTable() . '.location_entity_id',$locationId);
        }


        switch ((int) $filterParams["runningOut"]) {
            case AssetService::RUNNING_OUT_YES:
                $where->expression($this->getTable() . '.quantity <= thresholds.threshold',[]);
                break;
            case AssetService::RUNNING_OUT_NO:
                $where->expression($this->getTable() . '.quantity > thresholds.threshold',[]);
                break;
            case AssetService::RUNNING_OUT_NOT_SET:
                $where->isNull(' thresholds.threshold');
                break;
        }

        if (strlen($sSearch)) {
            $where->nest
                ->like('storages.name',"%" . $sSearch . "%")
                ->or
                ->like('categories.name',"%" . $sSearch . "%")
                ->unnest;
        }

        $sortColumns = [
            'category_name',
            'location_name',
            'quantity',
            'running_out',
            'threshold'
        ];

        $result = $this->fetchAll(function (Select $select) use($filterParams, $sortColumns, $iDisplayStart, $iDisplayLength, $where, $sSearch, $sortCol, $sortDir) {

            $select
                ->join(
                    ['categories' => DbTables::TBL_ASSET_CATEGORIES],
                    $this->getTable() . '.category_id = categories.id',
                    ['category_name' => 'name'],
                    Select::JOIN_INNER
                )

                ->join(
                    ['storages' => DbTables::TBL_WM_STORAGE],
                    $this->getTable() . '.location_entity_id = storages.id',
                    ['location_name' => 'name'],
                    Select::JOIN_INNER
                )

            ->join(
                ['thresholds' => DbTables::TBL_WM_THRESHOLD],
                $this->getTable() . '.category_id = thresholds.asset_category_id AND ' . $this->getTable() . '.location_entity_id = thresholds.storage_id',
                ['threshold' => 'threshold'],
                Select::JOIN_LEFT
            );

            if ($where !== null) {
                $select->where($where);
            }

            $select->quantifier(new Expression('SQL_CALC_FOUND_ROWS'));
            if ($iDisplayLength !== null && $iDisplayStart !== null) {
                $select->limit((int)$iDisplayLength);
                $select->offset((int)$iDisplayStart);
            }
            $select->order($sortColumns[$sortCol].' '.$sortDir);
        });

        $return['result'] = $result;

        $statement       = $this->adapter->query('SELECT FOUND_ROWS() as total');
        $result2         = $statement->execute();
        $row             = $result2->current();
        $return['count'] = $row['total'];


        return $return;
    }

    public function getConsumableBasicInfoById($id, $returnArray = false)
    {
        if ($returnArray) {
            $prototype = $this->resultSetPrototype->getArrayObjectPrototype();
            $this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());
        }

        $result = $this->fetchOne(function (Select $select) use($id) {
            $where = new Where();
            $where->equalTo($this->getTable() . '.id',$id);
            $select
                ->join(
                    ['categories' => DbTables::TBL_ASSET_CATEGORIES],
                    $this->getTable() . '.category_id = categories.id',
                    ['category_name' => 'name'],
                    Select::JOIN_INNER
                )
                ->join(
                    ['users' => DbTables::TBL_BACKOFFICE_USERS],
                    $this->getTable() . '.last_updated_by_id = users.id',
                    [
                        'firstname_last_updated' => 'firstname',
                        'lastname_last_updated' => 'lastname'
                    ],
                    Select::JOIN_LEFT
                )
                ->join(
                    ['thresholds' => DbTables::TBL_WM_THRESHOLD],
                    $this->getTable() . '.category_id = thresholds.asset_category_id AND ' . $this->getTable() . '.location_entity_id = thresholds.storage_id',
                    ['threshold' => 'threshold'],
                    Select::JOIN_LEFT
                )
                ->join(
                    ['storages' => DbTables::TBL_WM_STORAGE],
                    $this->getTable() . '.location_entity_id = storages.id',
                    ['location_name' => 'name'],
                    Select::JOIN_INNER
                );

            $select->where($where);
        });

        if ($returnArray) {
            $this->resultSetPrototype->setArrayObjectPrototype($prototype);
        }

        return $result;
    }

    public function updateConsumableAsset(
        $assetId,
        $categoryId,
        $quantity,
        $description,
        $userId
    ) {
        $this->save(
            [
                'category_id'          => $categoryId,
                'quantity'             => $quantity,
                'description'          => $description,
                'last_updated_by_id'   => $userId
            ],
            [
                'id' => $assetId
            ]
        );
    }

    public function getConsumableInfoBySku($sku)
    {
        $prototype = $this->resultSetPrototype->getArrayObjectPrototype();
        $this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());

        $result = $this->fetchOne(function (Select $select) use($sku) {

            $select->columns([
                    'id',
                    'categoryId'         =>'category_id',
                    'locationEntityId'   => 'location_entity_id',
                    'locationEntityType' => 'location_entity_type',
                    'quantity',
                    'description',
                    'locationName' => new Expression(" (CASE " . $this->getTable() . ".location_entity_type " .
                        "WHEN " . AssetService::ENTITY_TYPE_STORAGE . " THEN storages.name " .
                    " END)"),
                ]);

            $select->join(
                ['sku_table' => DbTables::TBL_SKU],
                $this->getTable() . '.category_id = sku_table.asset_category_id',
                ['skuName' => 'name', 'skuId' => 'id'],
                Select::JOIN_INNER
            )
            ->join(
                ['categories' => DbTables::TBL_ASSET_CATEGORIES],
                $this->getTable() . '.category_id = categories.id',
                ['categoryName' => 'name'],
                Select::JOIN_INNER
            )
            ->join(
                ['users' => DbTables::TBL_BACKOFFICE_USERS],
                $this->getTable() . '.last_updated_by_id = users.id',
                [
                    'firstnameLastUpdated' => 'firstname',
                    'lastnameLastUpdated'  => 'lastname'
                ],
                Select::JOIN_LEFT
            )
            ->join(
                ['storages' => DbTables::TBL_WM_STORAGE],
                new Expression($this->getTable() . '.location_entity_id = storages.id AND ' . $this->getTable() .'.location_entity_type = ' . AssetService::ENTITY_TYPE_STORAGE),
                [],
                Select::JOIN_LEFT
            );

            $select->where->equalTo('sku_table.name', $sku);
        });

        $this->resultSetPrototype->setArrayObjectPrototype($prototype);

        return $result;
    }

    public function getConsumeAssetsByUser($userId)
    {
        $prototype = $this->resultSetPrototype->getArrayObjectPrototype();
        $this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());

        $result = $this->fetchAll(function (Select $select) use ($userId){
            $select->columns(
                [
                    'id',
                    'description',
                    'quantity',
                    'lastUpdatedById'    => 'last_updated_by_id',
                    'shipmentStatus'     => 'shipment_status',
                    'locationEntityId'   => 'location_entity_id',
                    'locationEntityType' => 'location_entity_type',
                    'categoryId'         => 'category_id',
                    'skues'              => new Expression('GROUP_CONCAT(sku.name)')
                ]
            );
            $select
                ->join(
                    ['categories' => DbTables::TBL_ASSET_CATEGORIES],
                    $this->getTable() . '.category_id = categories.id',
                    ['categoryName' => 'name'],
                    Select::JOIN_INNER
                )->join(
                    ['storages' => DbTables::TBL_WM_STORAGE],
                    $this->getTable() . '.location_entity_id = storages.id',
                    ['locationName' => 'name'],
                    Select::JOIN_INNER
                )->join(
                    ['teams' => DbTables::TBL_TEAMS],
                    'teams.id = storages.team_id',
                    [],
                    Select::JOIN_INNER
                )->join(
                    ['staff' => DbTables::TBL_TEAM_STAFF],
                    new Expression('teams.id = staff.team_id AND staff.type !=' . TeamService::STAFF_DIRECTOR . ' AND staff.user_id = ' . $userId),
                    [],
                    Select::JOIN_INNER
                )->join(
                    ['sku' => DbTables::TBL_SKU],
                    $this->getTable() . '.category_id = sku.asset_category_id',
                    [],
                    Select::JOIN_LEFT
                );

            $select->group($this->getTable() . '.id');

        });

        $this->resultSetPrototype->setArrayObjectPrototype($prototype);

        return $result;
    }

}
