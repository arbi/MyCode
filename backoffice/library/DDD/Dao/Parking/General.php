<?php
namespace DDD\Dao\Parking;

use Library\DbManager\TableGatewayManager;
use Library\Utility\Debug;
use Zend\Db\Sql\Select;
use Zend\Db\Sql\Expression;
use Library\Constants\DbTables;
use Zend\Db\Sql\Where;

class General extends TableGatewayManager
{
    protected $table = DbTables::TBL_PARKING_LOTS;

    public function __construct($sm)
    {
        parent::__construct($sm, 'DDD\Domain\Parking\General');
    }

    /**
     * @param int $start
     * @param int $length
     * @param array $order
     * @param array $search
     * @param int $status 1 for active ones, 2 for inactive, 0 for all
     * @return \DDD\Domain\Parking\General[]
     */
    public function getParkingLotsForDatatable($start, $length, $order, $search, $status)
    {
        return $this->fetchAll(function (Select $select) use($start, $length, $order, $search, $status) {
            $like = $search['value'];
            $where = new Where();
            if ($status == 1) {
                $where->equalTo('active', 1);
            } else if ($status == 2) {
                $where->equalTo('active', 0);
            }

            if ($like) {
                $where
                    ->like($this->getTable() . '.name', '%' . $like . '%')
                    ->or
                    ->like('city_details.name', '%' . $like . '%')
                    ->or
                    ->like($this->getTable() . '.address', '%' . $like . '%');
            }

            $orderColumns = ['active', 'name', 'city', 'address', 'is_virtual'];

            $orderList = [];
            foreach ($order as $entity) {
                $orderList[] = $orderColumns[$entity['column']] . ' ' . $entity['dir'];
            }

            $select
                ->join(
                    ['cities' => DbTables::TBL_CITIES],
                    $this->getTable() . '.city_id = cities.id',
                    [],
                    Select::JOIN_LEFT
                )
                ->join(
                    ['city_details' => DbTables::TBL_LOCATION_DETAILS],
                    'cities.detail_id = city_details.id',
                    ['city' => 'name'],
                    Select::JOIN_LEFT
                )
                ->where($where)
                ->order($orderList)
                ->offset((int)$start)
                ->limit((int)$length);
        });
    }

    /**
     * @return \ArrayObject
     */
    public function getParkingLotsForSelect($apartmentId)
    {
        $prototype = $this->resultSetPrototype->getArrayObjectPrototype();
        $this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());
        $result = $this->fetchAll(function (Select $select) use($apartmentId) {
            $select->join(
                ['apartments' => DbTables::TBL_APARTMENTS],
                $this->getTable() . '.city_id = apartments.city_id',
                [],
                Select::JOIN_INNER
            );
            $select
                ->where([$this->getTable() . '.active' => 1,'apartments.id' => $apartmentId])
                ->order('name ASC');
        });
        $this->resultSetPrototype->setArrayObjectPrototype($prototype);
        return $result;
    }

    /**
     * @param int $reservationId
     * @param int $status 1 for active ones, 2 for inactive, 0 for all
     * @return int
     */
    public function getParkingLotsCountForDatatable($search, $status = 1)
    {
        $prototype = $this->resultSetPrototype->getArrayObjectPrototype();
        $this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());
        $result =  $this->fetchOne(function (Select $select) use($search, $status) {
            $like = $search['value'];

            $where = new Where();
            if ($status == 1) {
                $where->equalTo('active', 1);
            } else if ($status == 2) {
                $where->equalTo('active', 0);
            }

            $where->like('name', '%' . $like . '%');

            $select
                ->columns(['count' => new Expression('count(*)')])
                ->where($where);
        });

        $count = 0;
        if ($result) {
            $count = $result['count'];
        }
        $this->resultSetPrototype->setArrayObjectPrototype($prototype);
        return $count;
    }

    /**
     * @param string $name
     * @param int $id
     * @return bool
     */
    public function checkParkingLotExistence($name, $id)
    {
        $result = $this->fetchOne(function (Select $select) use ($name, $id) {
            $select->columns(['id']);
            $select->where->equalTo('name', $name);
            $select->where->notEqualTo('id', $id);
        });

        if ($result) {
            return true;
        }

        return false;
    }

    /**
     * @param int $parkingLotId
     * @return \DDD\Domain\Parking\General
     */
    public function getParkingById($parkingLotId)
    {
        $result = $this->fetchOne(function (Select $select) use($parkingLotId) {
            $where = new Where();
            $where->equalTo($this->getTable() . '.id', $parkingLotId);

            $select
                ->where($where)
                ->join(
                    ['countries' => DbTables::TBL_COUNTRIES],
                    $this->getTable() . '.country_id = countries.id',
                    [],
                    Select::JOIN_INNER
                )
                ->join(
                    ['location' => DbTables::TBL_LOCATION_DETAILS],
                    'countries.detail_id = location.id',
                    ['country' => 'name'],
                    Select::JOIN_INNER
                )
            ->join(
                ['currencies' => DbTables::TBL_CURRENCY],
                'countries.currency_id = currencies.id',
                ['currency' => 'code'],
                Select::JOIN_INNER
            );
        });

        return $result;
    }

    /**
     * @param int $parkingLotId
     * @return \ArrayObject
     */
    public function getUsages($parkingLotId)
    {
        $prototype = $this->resultSetPrototype->getArrayObjectPrototype();
        $this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());
        $result = $this->fetchAll(function (Select $select) use($parkingLotId) {
            $where = new Where();
            $where->equalTo($this->getTable() . '.id', $parkingLotId);

            $select
                ->columns([])
                ->join(
                     ['parking_building' => DbTables::TBL_BUILDING_LOTS],
                    $this->getTable() .'.id = parking_building.lot_id',
                     [],
                     Select::JOIN_LEFT
                 )->join(
                    ['building_section' => DbTables::TBL_BUILDING_SECTIONS],
                    'parking_building.building_section_id = building_section.id',
                    [],
                    Select::JOIN_LEFT
                )

                ->join(
                    ['apartments' => DbTables::TBL_APARTMENTS],
                    'apartments.building_id = building_section.building_id',
                    ['id', 'name'],
                    Select::JOIN_INNER
                )
                ->join(
                    ['apartmentspot' => DbTables::TBL_APARTMENT_SPOTS],
                    'apartments.id = apartmentspot.apartment_id',
                    [],
                    Select::JOIN_INNER
                )
                ->where($where)
                ->group('apartments.id');
        });
        $this->resultSetPrototype->setArrayObjectPrototype($prototype);

        return $result;
    }

    public function getAllParkingsWithLock($lockId)
    {
        $prototype = $this->resultSetPrototype->getArrayObjectPrototype();
        $this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());
        $result = $this->fetchAll(function (Select $select) use($lockId) {
            $where = new Where();
            $where->equalTo($this->getTable() . '.lock_id', $lockId);
            $select
                ->columns(['id','name'])
                ->where($where);
        });
        $this->resultSetPrototype->setArrayObjectPrototype($prototype);
        return $result;
    }

    /**
     * @return \Zend\Db\ResultSet\ResultSet
     */
    public function getAllLots()
    {
        $this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());
        $result = $this->fetchAll(function (Select $select) {
            $select->columns([
                'id',
                'name'
            ]);
            $select->where->equalTo('active', 1);
        });
        return $result;
    }
}
