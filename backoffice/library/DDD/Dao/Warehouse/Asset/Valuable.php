<?php
namespace DDD\Dao\Warehouse\Asset;

use Zend\Db\Sql\Expression;
use Zend\Db\Sql\Select;
use Library\DbManager\TableGatewayManager;
use Library\Constants\DbTables;
use Zend\Db\Sql\Where;
use DDD\Service\Warehouse\Asset as AssetService;
use DDD\Service\Team\Team as TeamService;

class Valuable extends TableGatewayManager
{
    protected $table = DbTables::TBL_ASSETS_VALUABLE;

    public function __construct($sm, $domain = 'DDD\Domain\Warehouse\Assets\Valuable')
    {
        parent::__construct($sm, $domain);
    }

    public function saveNewValuableAsset(
        $categoryId,
        $locationEntityType,
        $locationEntityId,
        $serialNumber,
        $name,
        $assigneeId,
        $description,
        $userId,
        $status,
        $shipmentStatus = 1
    )
    {

        $data = [
            'category_id'          => $categoryId,
            'location_entity_type' => $locationEntityType,
            'location_entity_id'   => $locationEntityId,
            'serial_number'        => $serialNumber,
            'name'                 => $name,
            'description'          => $description,
            'last_updated_by_id'   => $userId,
            'status'               => $status,
            'shipment_status'      => $shipmentStatus
        ];

        if (is_null($assigneeId)) {
            $data['assignee_id'] = $assigneeId;
        }

        return  $this->save($data);
    }

    public function updateValuableAsset(
        $id,
        $categoryId,
        $locationEntityType,
        $locationEntityId,
        $serialNumber,
        $name,
        $assigneeId,
        $description,
        $userId,
        $status,
        $statusComment
    )
    {
        $data = [
            'location_entity_type' => $locationEntityType,
            'location_entity_id'   => $locationEntityId,
            'serial_number'        => $serialNumber,
            'name'                 => $name,
            'assignee_id'          => $assigneeId,
            'description'          => $description,
            'last_updated_by_id'   => $userId,
            'status'               => $status,
        ];
        if ($categoryId) {
            $data['category_id'] = $categoryId;
        }

        $where = ['id' => $id];

        $this->save($data, $where);
    }

    public function getRowBySerialNumber($serialNumber, $assetId)
    {
        return  $this->fetchOne(function (Select $select) use($serialNumber, $assetId) {
            $where = new Where();
                $where->equalTo('serial_number', $serialNumber);
            if (FALSE !== $assetId) {
                $where->notEqualTo('id', $assetId);
            }
            $select
                ->columns(['id'])
                ->where($where);
        });
    }

    public function getListForSearch($iDisplayStart, $iDisplayLength, $filterParams,   $sortCol = 0, $sortDir = 'DESC')
    {
        $where = new Where();
        if ($filterParams["status"] > 0) {
            $where->equalTo($this->getTable() . '.status', $filterParams["status"]);
        }

        if ($filterParams["category"] > 0) {
            $where->equalTo($this->getTable() . '.category_id', $filterParams["category"]);
        }

        if (FALSE !== strpos($filterParams["location"], '_')) {
            $locationsArray = explode('_', $filterParams["location"]);
            $locationId   = $locationsArray[1];
            $where->equalTo($this->getTable() . '.location_entity_id',$locationId);
            $where->equalTo($this->getTable() . '.location_entity_type',$locationsArray[0]);
        }

        $sortColumns = array(
            'name',
            'category_id',
            'location_name',
            'status_name',
            'assignee_id',
        );

        $result = $this->fetchAll(function (Select $select) use($filterParams, $sortColumns, $iDisplayStart, $iDisplayLength, $where, $sortCol, $sortDir) {

            $select->columns([
                'location_name' => new Expression(" (CASE " . $this->getTable() . ".location_entity_type " .
                    "WHEN " . AssetService::ENTITY_TYPE_APARTMENT . " THEN apartments.name " .
                    "WHEN " . AssetService::ENTITY_TYPE_OFFICE . " THEN offices.name " .
                    "WHEN " . AssetService::ENTITY_TYPE_BUILDING . " THEN apartment_groups.name " .
                    "WHEN " . AssetService::ENTITY_TYPE_STORAGE . " THEN storages.name " .
                    " END)"),
                'id',
                'name',
                'category_id',
                'location_entity_id',
                'location_entity_type',
                'serial_number'
            ]);
            $select
                ->join(
                    ['categories' => DbTables::TBL_ASSET_CATEGORIES],
                    $this->getTable() . '.category_id = categories.id',
                    ['category_name' => 'name'],
                    Select::JOIN_INNER
                )
                ->join(
                    ['statuses' => DbTables::TBL_ASSETS_VALUABLE_STATUSES],
                    $this->getTable() . '.status = statuses.id',
                    ['status_name' => 'name'],
                    Select::JOIN_INNER
                )
                ->join(
                    ['users' => DbTables::TBL_BACKOFFICE_USERS],
                    $this->getTable() . '.assignee_id = users.id',
                    [
                        'firstname' => 'firstname',
                        'lastname' => 'lastname'
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
        $statement        = $this->adapter->query('SELECT FOUND_ROWS() as total');
        $result2          = $statement->execute();
        $row              = $result2->current();
        $return['count']  = $row['total'];

        return $return;

    }

    public function getValuableBasicInfoById($id)
    {
        return  $this->fetchOne(function (Select $select) use($id) {
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
                    ['statuses' => DbTables::TBL_ASSETS_VALUABLE_STATUSES],
                    $this->getTable() . '.status = statuses.id',
                    ['status_name' => 'name'],
                    Select::JOIN_INNER
                )
                ->join(
                    ['users' => DbTables::TBL_BACKOFFICE_USERS],
                    $this->getTable() . '.assignee_id = users.id',
                    [
                        'firstname' => 'firstname',
                        'lastname'  => 'lastname'
                    ],
                    Select::JOIN_LEFT
                )
                ->join(
                    ['users2' => DbTables::TBL_BACKOFFICE_USERS],
                    $this->getTable() . '.last_updated_by_id = users2.id',
                    [
                        'firstname_last_updated' => 'firstname',
                        'lastname_last_updated'  => 'lastname'
                    ],
                    Select::JOIN_LEFT
                );

            $select->where($where);
        });
    }

    /**
     * @return \DDD\Domain\Warehouse\Assets\Valuable []
     */
    public function getAssetsAwaitingApproval()
    {
        return  $this->fetchAll(function (Select $select) {
            $where = new Where();
            $where->equalTo($this->getTable() . '.shipment_status', AssetService::SHIPMENT_STATUS_NOT_OK);
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
                ])
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
    }

    /**
     * @return int
     */
    public function getAssetsAwaitingApprovalCount()
    {
        return  $this->fetchAll(function (Select $select) {
            $where = new Where();
            $where->equalTo($this->getTable() . '.shipment_status', AssetService::SHIPMENT_STATUS_NOT_OK);
            $select
                ->where($where);
        })->count();
    }


// FROM MOBILE API //
    public function getAssetBySerialNumber($serialNumber)
    {
        $prototype = $this->resultSetPrototype->getArrayObjectPrototype();
        $this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());

        $result = $this->fetchOne(function (Select $select) use($serialNumber) {
            $select->columns([
                    'id',
                    'status',
                    'categoryId'         => 'category_id',
                    'locationEntityId'   => 'location_entity_id',
                    'locationEntityType' => 'location_entity_type',
                    'serialNumber'       => 'serial_number',
                    'locationName'       => new Expression(" (CASE " . $this->getTable() . ".location_entity_type " .
                        "WHEN " . AssetService::ENTITY_TYPE_APARTMENT . " THEN apartments.name " .
                        "WHEN " . AssetService::ENTITY_TYPE_OFFICE . " THEN offices.name " .
                        "WHEN " . AssetService::ENTITY_TYPE_BUILDING . " THEN apartment_groups.name " .
                        "WHEN " . AssetService::ENTITY_TYPE_STORAGE . " THEN storages.name " .
                        " END)"),
                ])
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
            )->join(
                ['statuses' => DbTables::TBL_ASSETS_VALUABLE_STATUSES],
                $this->getTable() . '.status = statuses.id',
                ['statusName' => 'name'],
                Select::JOIN_INNER
            );

            $select->where->equalTo('serial_number', $serialNumber);
        });

        $this->resultSetPrototype->setArrayObjectPrototype($prototype);

        return $result;
    }

    public function getAssetByUser($countryId)
    {
        $prototype = $this->resultSetPrototype->getArrayObjectPrototype();
        $this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());

        $result = $this->fetchAll(function (Select $select) use ($countryId){

            $select->columns([
                'locationName' => new Expression(" (CASE " . $this->getTable() . ".location_entity_type " .
                    "WHEN " . AssetService::ENTITY_TYPE_APARTMENT . " THEN apartments.name " .
                    "WHEN " . AssetService::ENTITY_TYPE_OFFICE    . " THEN offices.name " .
                    "WHEN " . AssetService::ENTITY_TYPE_BUILDING  . " THEN apartment_groups.name " .
                    " END)"),
                'id',
                'name',
                'categoryId'         => 'category_id',
                'locationEntityId'   => 'location_entity_id',
                'locationEntityType' => 'location_entity_type',
                'serialNumber'       => 'serial_number',
            ]);

            $select->join(
                    ['categories' => DbTables::TBL_ASSET_CATEGORIES],
                    $this->getTable() . '.category_id = categories.id',
                    ['categoryName' => 'name'],
                    Select::JOIN_INNER
                )->join(
                    ['statuses' => DbTables::TBL_ASSETS_VALUABLE_STATUSES],
                    $this->getTable() . '.status = statuses.id',
                    ['statusName' => 'name'],
                    Select::JOIN_INNER
                )->join(
                    ['users' => DbTables::TBL_BACKOFFICE_USERS],
                    $this->getTable() . '.assignee_id = users.id',
                    [
                        'assigneeFirstname' => 'firstname',
                        'assigneeLastname'  => 'lastname'
                    ],
                    Select::JOIN_LEFT
                )->join(
                    ['apartments' => DbTables::TBL_APARTMENTS],
                    new Expression(
                        $this->getTable() . '.location_entity_id = apartments.id AND ' .
                        $this->getTable() . '.location_entity_type = ' . AssetService::ENTITY_TYPE_APARTMENT
                    ),
                    [],
                    Select::JOIN_LEFT
                )
                ->join(
                    ['offices' => DbTables::TBL_OFFICES],
                    new Expression(
                        $this->getTable() . '.location_entity_id = offices.id AND ' .
                        $this->getTable() . '.location_entity_type = ' . AssetService::ENTITY_TYPE_OFFICE
                    ),
                    [],
                    Select::JOIN_LEFT
                )->join(
                    ['apartment_groups' => DbTables::TBL_APARTMENT_GROUPS],
                    new Expression(
                        $this->getTable() . '.location_entity_id = apartment_groups.id AND ' .
                        $this->getTable() . '.location_entity_type = ' . AssetService::ENTITY_TYPE_BUILDING
                    ),
                    [],
                    Select::JOIN_LEFT
                );

                $select->where->expression($countryId . " = (CASE " . $this->getTable() . ".location_entity_type " .
                    "WHEN " . AssetService::ENTITY_TYPE_APARTMENT . " THEN apartments.country_id " .
                    "WHEN " . AssetService::ENTITY_TYPE_OFFICE    . " THEN offices.country_id " .
                    "WHEN " . AssetService::ENTITY_TYPE_BUILDING  . " THEN apartment_groups.country_id " .
                    " END)", []);
        });

        $this->resultSetPrototype->setArrayObjectPrototype($prototype);
        return $result;

    }

    public function getStorageAssetsByUser($userId)
    {
        $prototype = $this->resultSetPrototype->getArrayObjectPrototype();
        $this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());

        $result = $this->fetchAll(function (Select $select) use ($userId){
            $select->columns([
                'id',
                'name',
                'categoryId'         => 'category_id',
                'locationEntityId'   => 'location_entity_id',
                'locationEntityType' => 'location_entity_type',
                'serialNumber'       => 'serial_number',
                ]);
            $select
                ->join(
                    ['categories' => DbTables::TBL_ASSET_CATEGORIES],
                    $this->getTable() . '.category_id = categories.id',
                    ['categoryName' => 'name'],
                    Select::JOIN_INNER
                )->join(
                    ['statuses' => DbTables::TBL_ASSETS_VALUABLE_STATUSES],
                    $this->getTable() . '.status = statuses.id',
                    ['statusName' => 'name'],
                    Select::JOIN_INNER
                )->join(
                    ['users' => DbTables::TBL_BACKOFFICE_USERS],
                    $this->getTable() . '.assignee_id = users.id',
                    [
                        'asigneeFirstname' => 'firstname',
                        'asigneeLastname'  => 'lastname'
                    ],
                    Select::JOIN_LEFT
                )
                ->join(
                    ['storages' => DbTables::TBL_WM_STORAGE],
                    $this->getTable() . '.location_entity_id = storages.id',
                    ['locationName' => 'name'],
                    Select::JOIN_INNER
                )
                ->join(
                    ['teams' => DbTables::TBL_TEAMS],
                    'teams.id = storages.team_id',
                    [],
                    Select::JOIN_INNER
                )
                ->join(
                    ['staff' => DbTables::TBL_TEAM_STAFF],
                    new Expression('teams.id = staff.team_id AND staff.type !=' . TeamService::STAFF_DIRECTOR . ' AND staff.user_id = ' . $userId),
                    [],
                    Select::JOIN_INNER
                );

            $select->group($this->getTable() . '.id');

        });

        $this->resultSetPrototype->setArrayObjectPrototype($prototype);

        return $result;
    }

    public function checkValuableAssetExist($id, $isSerialNumber = false) {
        $result = $this->fetchOne(function (Select $select) use ($id, $isSerialNumber) {

            if ($isSerialNumber) {
                $select->where->equalTo('serial_number', $id);
            } else {
                $select->where->equalTo('id', $id);
            }
        });

        return $result;
    }
// END OF REQUEST FROM MOBILE API
}
