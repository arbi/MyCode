<?php

namespace DDD\Dao\Parking\Spot;

use Library\DbManager\TableGatewayManager;
use Library\Constants\DbTables;
use Zend\Db\Sql\Expression;
use Zend\Db\Sql\Select;
use Zend\Db\Sql\Where;
use Zend\ServiceManager\ServiceLocatorInterface;

use DDD\Service\Parking\Spot\Inventory as InventoryService;

class Inventory extends TableGatewayManager
{
	/**
	 * @access protected
	 * @var string
	 */
    protected $table = DbTables::TBL_PARKING_INVENTORY;

    /**
     * @access public
     * @param ServiceLocatorInterface $sm
     * @param string $domain
     */
    public function __construct($sm, $domain = 'DDD\Domain\Parking\Spot\Inventory')
    {
        parent::__construct($sm, $domain);
    }

    /**
     * @param $spotId
     * @param $startDate
     * @param $endDate
     * @return \DDD\Domain\Parking\Spot\Inventory[]
     */
    public function getSpotInventoryForRange($spotId, $startDate, $endDate)
    {
        $result = $this->fetchAll(
            function (Select $select) use($spotId, $startDate, $endDate) {
                $where = new Where();
                $where->equalTo($this->getTable() . '.spot_id', $spotId);
                $where->greaterThanOrEqualTo($this->table.'.date', $startDate);
                $where->lessThanOrEqualTo($this->table.'.date', $endDate);

                $select
                    ->join(
                        ['spot' => DbTables::TBL_PARKING_SPOTS],
                        $this->getTable() . '.spot_id = spot.id',
                        ['price'],
                        Select::JOIN_INNER
                    )
                    ->where($where);
            }
        );

        return $result;
    }

    /**
     * @param int $spotId
     * @param string $date
     * @return \DDD\Domain\Parking\Spot\Inventory|null
     */
    public function getBySpotIdAndDate($spotId, $date)
    {
        $result = $this->fetchOne(
            function (Select $select) use($spotId, $date) {
                $select->where([
                    'spot_id' => $spotId,
                    'date' => $date,
                ]);
            }
        );

        return $result;
    }

    /**
     * @param int $spotId
     * @param string $date
     * @return \DDD\Domain\Parking\Spot\Inventory|null
     */
    public function getParkingLotAvailabilityByDate($parkingLotId, $date)
    {
        $result = $this->fetchOne(
            function (Select $select) use($parkingLotId, $date) {
                $select->columns([
                    'availability' => new Expression('MAX(availability)')
                ]);
                $select->join(
                    ['spots' => DbTables::TBL_PARKING_SPOTS],
                    $this->getTable() . '.spot_id = spots.id',
                    [],
                    Select::JOIN_LEFT
                );
                $select->where([
                    'spots.lot_id'              => $parkingLotId,
                    $this->getTable() . '.date' => $date
                ]);
            }
        );

        return $result;
    }

    /**
     * @param int $parkingLotId
     * @param string $date
     * @param int $availability
     * @return \Zend\Db\Adapter\Driver\ResultInterface
     */
    public function updateParkingLotAvailability($parkingLotId, $date, $availability)
    {
        $sql = 'UPDATE ' . $this->getTable() .
            ' LEFT JOIN ' . DbTables::TBL_PARKING_SPOTS .
                ' ON ' . $this->getTable() . '.spot_id = ' . DbTables::TBL_PARKING_SPOTS . '.id' .
            ' SET ' . $this->getTable() . '.availability = ' . $availability .
            ' WHERE ' . $this->getTable() . '.date = "' . $date . '"' .
                ' AND ' . DbTables::TBL_PARKING_SPOTS . '.lot_id = "'. $parkingLotId . '"';
        $statement = $this->adapter->createStatement($sql);
        $result = $statement->execute();
        return $result;
    }

    /**
     * @return \DDD\Domain\Parking\Spot\Inventory|null
     */
    public function getEndDates()
    {
        $result = $this->fetchAll(
            function (Select $select) {
                $select
                    ->columns(['spot_id', 'date' => new Expression('MAX(date)')])
                    ->group('spot_id');
            }
        );

        return $result;
    }

    /**
     * @param $spotId
     * @param $startDate
     * @param $endDate
     * @return \DDD\Domain\Parking\Spot\Inventory[]
     */
    public function getSpotInventoryAvailability($spotId, $date)
    {
        $result = $this->fetchOne(
            function (Select $select) use($spotId, $date) {
                $select->where
                    ->equalTo('spot_id', $spotId)
                    ->equalTo('date', $date)
                    ->equalTo('availability', InventoryService::IS_AVAILABLE);
            }
        );

        return $result;
    }
}
