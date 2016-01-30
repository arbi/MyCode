<?php

namespace DDD\Dao\Parking;

use DDD\Service\Booking\BookingAddon;
use Library\DbManager\TableGatewayManager;
use Library\Constants\DbTables;
use Library\Utility\Debug;
use Zend\Db\Sql\Expression;
use Zend\Db\Sql\Select;
use Zend\Db\Sql\Where;
use Zend\ServiceManager\ServiceLocatorInterface;

class Spot extends TableGatewayManager
{
	/**
	 * @access protected
	 * @var string
	 */
    protected $table = DbTables::TBL_PARKING_SPOTS;

    /**
     * @access public
     * @param ServiceLocatorInterface $sm
     * @param string $domain
     */
    public function __construct($sm, $domain = 'DDD\Domain\Parking\Spot'){
        parent::__construct($sm, $domain);
    }

    /**
     * @param int $spotId
     * @return \DDD\Domain\Parking\Spot
     */
    public function getParkingSpotById($spotId)
    {
        $result = $this->fetchOne(function (Select $select) use($spotId) {
            $where = new Where();
            $where->equalTo('id', $spotId);

            $select->where($where);
        });

        return $result;
    }

    /**
     * @param int $spotId
     * @return \ArrayObject
     */
    public function getUsages($spotId)
    {
        $prototype = $this->resultSetPrototype->getArrayObjectPrototype();
        $this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());
        $result = $this->fetchAll(function (Select $select) use($spotId) {
            $where = new Where();
            $where->equalTo($this->getTable() . '.id', $spotId);

            $select
                ->columns([])
                ->join(
                    ['apartment_spots' => DbTables::TBL_APARTMENT_SPOTS],
                    $this->getTable() . '.id = apartment_spots.spot_id',
                    [],
                    Select::JOIN_INNER
                )
                ->join(
                    ['apartments' => DbTables::TBL_APARTMENTS],
                    'apartment_spots.apartment_id = apartments.id',
                    ['name'],
                    Select::JOIN_INNER
                )
                ->where($where);
        });
        $this->resultSetPrototype->setArrayObjectPrototype($prototype);

        return $result;
    }

    public function isUnitUniqueInLot($params)
    {
        $prototype = $this->resultSetPrototype->getArrayObjectPrototype();
        $this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());
        $result = $this->fetchOne(function (Select $select) use($params) {
            $where = new Where();
            if ($params['id']) {
                $where->notEqualTo($this->getTable() . '.id', $params['id']);
            }
            $where->equalTo($this->getTable() . '.unit',$params['unit']);
            $where->equalTo($this->getTable() . '.lot_id',$params['lot_id']);
            $select
                ->columns(['id'])
                ->where($where);
        });
        $this->resultSetPrototype->setArrayObjectPrototype($prototype);
        return $result;
    }

    /**
     * @param $lotId
     * @param $from
     * @param $to
     * @return \Zend\Db\ResultSet\ResultSet
     */
    public function getSpotsForInventory($lotId, $from, $to)
    {
        $this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());
        $result = $this->fetchAll(function (Select $select) use($lotId, $from, $to) {
            $select
                ->columns(['id', 'unit'])
                ->join(
                    ['inventory' => DbTables::TBL_PARKING_INVENTORY],
                    new Expression($this->getTable() . '.id = inventory.spot_id AND inventory.availability = 0'),
                    [
                        'date',
                    ],
                    Select::JOIN_INNER
                )
                ->join(
                    ['charge' => DbTables::TBL_CHARGE],
                    new Expression($this->getTable() . ".id = charge.entity_id AND charge.addons_type = " . BookingAddon::ADDON_TYPE_PARKING . "
                        AND charge.reservation_nightly_date = inventory.date AND status = 0"),
                    [
                        'reservation_id'
                    ],
                    Select::JOIN_LEFT
                )
                ->join(
                    ['reservation' => DbTables::TBL_BOOKINGS],
                    'charge.reservation_id = reservation.id',
                    [
                        'res_number',
                        'date_from',
                        'date_to',
                    ],
                    Select::JOIN_LEFT
                );
                $select->where->equalTo($this->getTable() . '.lot_id', $lotId)
                              ->greaterThanOrEqualTo('inventory.date', $from)
                              ->lessThanOrEqualTo('inventory.date', $to)
                ;
                $select->order(['charge.reservation_id', 'inventory.date']);
        });

        return $result;
    }

    /**
     * @param $lotId
     * @return \Zend\Db\ResultSet\ResultSet
     */
    public function getAllSpotsByLot($lotId)
    {
        $this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());
        $result = $this->fetchAll(function (Select $select) use($lotId) {
            $select->columns(['id', 'unit'])
                   ->where->equalTo('lot_id', $lotId);
            $select->order('unit');
        });

        return $result;
    }

    /**
     * @param $buildingId
     * @return \Zend\Db\ResultSet\ResultSet
     */
    public function getSpotsByBuilding($buildingId)
    {
        // $this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());
        $result = $this->fetchAll(function (Select $select) use($buildingId) {
            $select->join(
                ['b_l' => DbTables::TBL_BUILDING_LOTS],
                $this->getTable() . '.lot_id = b_l.lot_id',
                []
            );
            $select->join(
                ['b_s' => DbTables::TBL_BUILDING_SECTIONS],
                'b_l.building_section_id = b_s.id',
                []
            );
            $select->join(
                ['lot' => DbTables::TBL_PARKING_LOTS],
                $this->getTable() . '.lot_id = lot.id',
                ['lot_name' => 'name']
            );

            $select->where->equalTo('b_s.building_id', $buildingId);
        });

        return $result;
    }
}
