<?php
namespace DDD\Dao\Warehouse;

use Library\Utility\Debug;
use Zend\Db\Sql\Expression;
use Zend\Db\Sql\Select;
use Library\DbManager\TableGatewayManager;
use Library\Constants\Constants;
use Library\Constants\DbTables;
use Zend\Db\Sql\Where;
use DDD\Service\Warehouse\Storage as StorageService;
use DDD\Service\Team\Team as TeamService;
use DDD\Service\Warehouse\Asset as AssetService;

class Storage extends TableGatewayManager
{
    protected $table = DbTables::TBL_WM_STORAGE;

    public function __construct($sm)
    {
        parent::__construct($sm, 'DDD\Domain\Warehouse\Storage\Storage');
    }

    /**
     * @param $offset
     * @param $limit
     * @param $sortCol
     * @param $sortDir
     * @param $like
     * @param $all
     * @return array
     */
    public function getAllStorage($offset, $limit, $sortCol, $sortDir, $like, $all)
    {
        $this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());

        if ($all == '1') {
            $disable = ' AND ' . $this->getTable() . '.inactive = 0';
        } elseif ($all == '2') {
            $disable = ' AND ' . $this->getTable() . '.inactive = 1';
        } else {
            $disable = ' ';
        }

        $result = $this->fetchAll(function (Select $select) use ( $offset, $limit, $sortCol, $sortDir, $like, $disable) {
            $sortColumns = ['name', 'city_name', 'address', 'inactive'];
            $select->join(
                ['city' => DbTables::TBL_CITIES],
                $this->getTable() . '.city_id = city.id',
                [],
                $select::JOIN_LEFT )
                ->join(
                    ['location_details' => DbTables::TBL_LOCATION_DETAILS],
                    'city.detail_id = location_details.id',
                    [
                        'city_name' =>  'name'
                    ],
                    $select::JOIN_LEFT
                );
            $select->where('(' . $this->getTable() . '.name like "%' . $like . '%" OR location_details.name like "%' . $like . '%")'. $disable);
            $select
                ->group($this->getTable() . '.id')
                ->order($sortColumns[$sortCol].' '.$sortDir)
                ->offset((int)$offset)
                ->limit((int)$limit);
            $select->quantifier(new Expression('SQL_CALC_FOUND_ROWS'));
        });

        $statement = $this->adapter->query('SELECT FOUND_ROWS() as total');
        $resultCount  = $statement->execute();
        $row = $resultCount->current();
        $total = $row['total'];

        return  [
            'result' => $result,
            'total'  => $total
        ];
    }

    /**
     * @param $storageId
     * @return array|\ArrayObject|null
     */
   public function getStorageData($storageId)
   {
       $this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());

       return $this->fetchOne(function (Select $select) use ($storageId) {
           $select->columns([
               'name'     => 'name',
               'city'     => 'city_id',
               'address'  => 'address',
               'inactive' => 'inactive'
           ]);
           $select->where(['id' => $storageId]);
       });
   }

    /**
     * @param int $id
     * @param bool|true $onlyActive
     * @return array|\ArrayObject|null
     */
    public function getStorageDetails($id, $onlyActive = true)
    {
        $this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());

        return $this->fetchOne(function (Select $select) use ($id, $onlyActive) {
            $select->columns([
                'id',
                'name',
                'inactive'
            ]);

            $select->join(
                ['city' => DbTables::TBL_CITIES],
                $this->getTable() . '.city_id = city.id',
                [],
                Select::JOIN_LEFT
            );

            $select->join(
                ['location_details' => DbTables::TBL_LOCATION_DETAILS],
                'city.detail_id = location_details.id',
                ['city_name' => 'name'],
                Select::JOIN_LEFT
            );

            $select->where
                ->equalTo($this->getTable() . '.id', $id);

            if ($onlyActive) {
                $select->where
                    ->equalTo($this->getTable() . '.inactive', StorageService::STORAGE_STATUS_ACTIVE);
            }
        });
    }

    /**
     * @param string $name
     * @param bool|true $onlyActive
     *
     * @return \DDD\Domain\Warehouse\Storage\Storage[]
     */
    public function searchStorageByName($name, $onlyActive = true)
    {
        $result = $this->fetchAll(function (Select $select) use ($name, $onlyActive) {
            $select->columns([
                'id',
                'name',
                'city_id',
                'address',
                'inactive'
            ]);

            $select
                ->join(
                    ['cities' => DbTables::TBL_CITIES],
                    $this->getTable() . '.city_id = cities.id',
                    [],
                    Select::JOIN_LEFT)
                ->join(
                    ['geo' => DbTables::TBL_LOCATION_DETAILS],
                    'cities.detail_id = geo.id',
                    ['city_name' => 'name'],
                    Select::JOIN_LEFT);

            if (FALSE !== $name) {
                $select->where
                    ->like($this->getTable() . '.name', '%' . $name . '%');
            }

            if ($onlyActive) {
                $select->where
                    ->equalTo($this->getTable() . '.inactive', StorageService::STORAGE_STATUS_ACTIVE);
            }
        });

        return $result;
    }

    /**
     * @param $storageId
     * @return array|\ArrayObject|null
     */
    public function getTeamId($storageId)
    {
        $this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());

        return $this->fetchOne(function (Select $select) use ($storageId) {
            $select->columns([
                'team_id',
            ]);
            $select->where(['id' => $storageId]);
        });
    }

    /**
     * @param  int $cityId [description]
     * @return array|\ArrayObject|null
     */
    public function getStorageByUser($userId)
    {
        $prototype = $this->resultSetPrototype->getArrayObjectPrototype();
        $this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());
        $locationType = AssetService::ENTITY_TYPE_STORAGE;

        $result = $this->fetchAll(function (Select $select) use ($userId, $locationType) {
            $select->join(
                ['ts' => DbTables::TBL_TEAM_STAFF],
                new Expression($this->getTable() . '.team_id = ts.team_id AND ts.type != ' . TeamService::STAFF_CREATOR),
                [],
                Select::JOIN_LEFT
            );
            $select->join(
                ['t' => DbTables::TBL_TEAMS],
                new Expression('ts.team_id = t.id AND t.is_disable = ' . TeamService::IS_ACTIVE_TEAM),
                [],
                Select::JOIN_LEFT
            );

            $select->columns(['id', 'name', "cityId" => "city_id", "address", "locationType" => new Expression("{$locationType}")]);
            $select->where->equalTo('ts.user_id', $userId);
            $select->where->equalTo($this->getTable() . '.inactive', StorageService::STORAGE_STATUS_ACTIVE);
        });

        $this->resultSetPrototype->setArrayObjectPrototype($prototype);
        return $result;

    }
}
