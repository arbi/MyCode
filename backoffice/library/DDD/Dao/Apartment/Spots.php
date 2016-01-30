<?php
namespace DDD\Dao\Apartment;

use Library\DbManager\TableGatewayManager;
use Zend\Db\Sql\Select;
use Zend\Db\Sql\Expression;
use Library\Constants\DbTables;
use Zend\Db\Sql\Where;

class Spots extends TableGatewayManager
{
    protected $table = DbTables::TBL_APARTMENT_SPOTS;

    public function __construct($sm)
    {
        parent::__construct($sm, 'ArrayObject');
    }

    public function getApartmentSpots($apartmentId)
    {
        return $this->fetchAll(function(Select $select) use ($apartmentId) {

            $select->where->equalTo('apartment_id', $apartmentId);
            $select->order('priority ASC');
        });
    }

    public function getAvailableSpotsForApartmentForDateRangeByPriority(
            $spotId,
            $startDate,
            $endDate,
            $spotsAlreadySelectedInSameChargeSession,
            $dateToday,
            $isSelectedDate = false
    ) {
		//if the reservation is in the past do not
		//look for the prioritized spots, return false here
		//and the query that takes the spots by attached lot will return everything
		//see next function

        if (!$isSelectedDate) {
            if ($dateToday >= $endDate) {
                return false;
            }
        }

		$prototype = $this->resultSetPrototype->getArrayObjectPrototype();
		$this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());
		$result = $this->fetchOne(function (Select $select) use($spotId, $startDate, $endDate, $spotsAlreadySelectedInSameChargeSession) {
			$select->columns(['apartment_id']);
			$select->join(
				['parking_inventory' => DbTables::TBL_PARKING_INVENTORY],
				$this->getTable() . '.spot_id = parking_inventory.spot_id',
				['date'],
				Select::JOIN_LEFT
			);

			$select->join(
				['parking_spots' => DbTables::TBL_PARKING_SPOTS],
				$this->getTable() . '.spot_id = parking_spots.id',
				['price', 'unit', 'parking_spot_id' => 'id'],
				Select::JOIN_LEFT
			);

			$select->join(
				['parking_lots' => DbTables::TBL_PARKING_LOTS],
				'parking_lots.id = parking_spots.lot_id',
				['name'],
				Select::JOIN_LEFT
			);

			$select->where
                ->greaterThanOrEqualTo('parking_inventory.date', $startDate)
				->lessThanOrEqualTo('parking_inventory.date', $endDate)
                ->equalTo('parking_lots.active', 1)
				->equalTo($this->getTable() . '.spot_id', $spotId);

			if (is_array($spotsAlreadySelectedInSameChargeSession) && !empty($spotsAlreadySelectedInSameChargeSession)) {
				$select->where->notIn('parking_inventory.spot_id', $spotsAlreadySelectedInSameChargeSession);
			}

            $select->group($this->getTable() . '.apartment_id')->having('MIN(`parking_inventory`.`availability`) = 1');
		});

		$this->resultSetPrototype->setArrayObjectPrototype($prototype);
		return $result;
	}

    /**
     * @param int $apartmentId
     * @return array|\ArrayObject|null
     */
    public function getApartmentParkingPrioritySpots($apartmentId)
    {
        $prototype = $this->resultSetPrototype->getArrayObjectPrototype();
        $this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());
        $result = $this->fetchAll(function (Select $select) use($apartmentId) {
            $where = new Where();
            $where->equalTo($this->getTable() . '.apartment_id', $apartmentId);
            $select->where($where);
        });
        $this->resultSetPrototype->setArrayObjectPrototype($prototype);
        return $result;
    }

    public function getAvailableSpotsForApartment($apartmentId, $dateFrom, $dateTo)
    {
        $prototype = $this->resultSetPrototype->getArrayObjectPrototype();
        $this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());

        $result = $this->fetchAll( function(Select $select) use ($apartmentId, $dateFrom, $dateTo) {
            $where = new Where();
            $where
                ->lessThan('parking_inventory.date', $dateTo)
                ->greaterThanOrEqualTo('parking_inventory.date', $dateFrom)
                ->equalTo('parking_inventory.availability', 1)
                ->equalTo($this->getTable() . '.apartment_id', $apartmentId);

            $select
                ->columns([])
                ->join(
                    ['apartments' => DbTables::TBL_APARTMENTS],
                    $this->getTable() . '.apartment_id = apartments.id',
                    [],
                    Select::JOIN_LEFT
                )
                ->join(
                     ['parking_building' => DbTables::TBL_BUILDING_LOTS],
                     'apartments.building_section_id = parking_building.building_section_id',
                     [],
                     Select::JOIN_LEFT
                 )
                ->join(
                    ['spots' => DbTables::TBL_PARKING_SPOTS],
                    'parking_building.lot_id = spots.lot_id',
                    ['spot_id' => 'id', 'spot_unit' => 'unit', 'price'],
                    Select::JOIN_LEFT
                )
                ->join(
                    ['lots' => DbTables::TBL_PARKING_LOTS],
                    'parking_building.lot_id = lots.id',
                    ['lot_name' => 'name'],
                    Select::JOIN_LEFT
                )
                ->join(
                    ['parking_inventory' => DbTables::TBL_PARKING_INVENTORY],
                    'parking_inventory.spot_id = spots.id',
                    [],
                    Select::JOIN_INNER
                )
                ->where($where)
                ->group('spots.id')
                ->having('count(parking_inventory.id) = (DATEDIFF("' . $dateTo . '", "' . $dateFrom .'"))');
        });

        $this->resultSetPrototype->setArrayObjectPrototype($prototype);
        return $result;
    }

    /**
     * @param $lotId
     */
    public function deleteApartmentSpotsByLot($lotId)
    {
        $sql = "Delete from ga_apartment_spots
                  where apartment_id IN
                    (select a.id from
                      ga_apartments AS a join ga_building_lots AS b_l on a.building_section_id = b_l.building_section_id
                      where b_l.lot_id={$lotId})";
        $statement = $this->adapter->createStatement($sql);
        $statement->execute();
    }

    /**
     * @param $apartmentId
     * @param $buildingSectionId
     */
    public function removeUnusedSpots($apartmentId, $buildingSectionId)
    {
        $sql = "Delete from ga_apartment_spots
                  where apartment_id = {$apartmentId} and spot_id NOT IN
                    (select s.id from
                      ga_parking_spots AS s INNER JOIN ga_building_lots AS b_l on s.lot_id = b_l.lot_id
                      where b_l.building_section_id = {$buildingSectionId})";
        $statement = $this->adapter->createStatement($sql);
        $statement->execute();
    }
}
