<?php

namespace DDD\Dao\Booking;

use DDD\Service\Reservation\Main;
use Library\Utility\Debug;
use Zend\Db\Sql\Expression;
use Zend\Db\Sql\Select;
use Library\DbManager\TableGatewayManager;
use Library\Constants\DbTables;

class ReservationNightly extends TableGatewayManager
{
    protected $table = DbTables::TBL_RESERVATION_NIGHTLY;

    public function __construct($sm, $domain = '\ArrayObject')
    {
        parent::__construct($sm, $domain);
    }

    public function getReservationPrice($reservationId)
    {
        $result = $this->fetchOne(function (Select $select) use($reservationId) {
            $select->columns(array(
                'sum' => new Expression('SUM(price)'),
            ));
            $select->where
                ->equalTo($this->getTable() . '.reservation_id', $reservationId)
                ->in($this->getTable() . '.status', [Main::STATUS_BOOKED, Main::STATUS_CANCELED]);
        });

        return $result ? $result['sum'] : 0;
    }

    public function getRatesNameCapacity($reservationId)
    {
        $sql = "SELECT MIN(main.capacity) AS capacity, GROUP_CONCAT(main.rate_name separator ', ') AS rates_name FROM (
                        SELECT
                        n.capacity, n.rate_name
                    FROM
                        ga_reservation_nightly AS n
                    WHERE
                        n.reservation_id = ?
                        AND n.status = ?
                    GROUP BY n.rate_id
                ) AS main";
        $statement = $this->adapter->createStatement($sql, [$reservationId, Main::STATUS_BOOKED]);
        $result = $statement->execute();
        return $result->current();
    }

    public function getNightsDataByIds($ids)
    {
        return $this->fetchAll(function (Select $select) use($ids) {
            $select->columns(array(
                'id',
                'price',
            ));
            $select->where
                ->in($this->getTable() . '.id', $ids);
        });
    }

    public function getNightsDateWithCharge($reservationId) {
        $result = $this->fetchAll(function (Select $select) use ($reservationId) {
            $select->columns(array(
                'id',
                'apartment_id',
                'rate_id',
                'rate_name',
                'price',
                'date'
            ));

            $select->join(
                ['charge' => DbTables::TBL_CHARGE],
                $this->getTable() . '.id = charge.reservation_nightly_id',
                ['charge_id' => 'id'],
                Select::JOIN_LEFT
            );

            $select->where
                ->equalTo($this->getTable() . '.reservation_id', $reservationId)
                ->equalTo($this->getTable() . '.status', Main::STATUS_BOOKED);
            $select->order($this->getTable() . '.date ASC');
        });

        return $result;
    }

    public function getNightlyDataByResIdDate($reservationId, $from, $to, $apartel)
    {
        $rateTable = $apartel ? DbTables::TBL_APARTEL_RATES : DbTables::TBL_APARTMENT_RATES;
        return $this->fetchAll(function (Select $select) use($reservationId, $from, $to, $rateTable) {
            $select->columns([
                'id',
                'date',
                'reservation_id',
                'room_id',
            ]);

            $select->join(
                ['rates' => $rateTable],
                $this->getTable() . '.rate_id = rates.id',
                [
                    'rate_type' => 'type',
                    'rate_capacity' => 'capacity',
                ],
                Select::JOIN_LEFT
            );

            $select->where
                ->equalTo($this->getTable() . '.reservation_id', $reservationId)
                ->greaterThanOrEqualTo($this->getTable() . '.date', $from)
                ->lessThanOrEqualTo($this->getTable() . '.date', $to)
            ;
        });
    }

    public function getBookedMonthlyData($apartmentId, $start, $end)
    {
        $result = $this->fetchOne(function (Select $select) use($apartmentId, $start, $end) {
            $select->columns(array(
                'count' => new Expression('COUNT(*)'),
                'sum' => new Expression('SUM('.$this->getTable() . '.price)'),
                'min' => new Expression('min('.$this->getTable() . '.price)'),
                'max' => new Expression('max('.$this->getTable() . '.price)'),
            ));

            $select->join(
                ['reservation' => DbTables::TBL_BOOKINGS],
                $this->getTable() . '.reservation_id = reservation.id',
                [],
                Select::JOIN_INNER
            );

            $select->where
                ->equalTo($this->getTable() . '.apartment_id', $apartmentId)
                ->greaterThanOrEqualTo($this->getTable() . '.date', $start)
                ->lessThan($this->getTable() . '.date', $end)
                ->equalTo($this->getTable() . '.status', Main::STATUS_BOOKED)
                ->notEqualTo('reservation.overbooking_status', 1);
        });

        $sum = $count = $min = $max = 0;
        if ($result) {
            $count = $result['count'];
            $sum = $result['sum'];
            $min = $result['min'];
            $max = $result['max'];
        }

        return [
            'sum' => $sum,
            'count' => $count,
            'max' => $max,
            'min' => $min,
        ];
    }
    
    /**
     * 
     * @param int $apartmentId
     * @param string $date
     * @return \ArrayObject
     */
    public function getNightDataByDateAndApartmentId($apartmentId, $date)
    {
        return $this->fetchOne(function (Select $select) use($apartmentId, $date) {
            $select->columns(array(
                'id',
                'reservation_id',
                'apartment_id',
                'room_id',
                'rate_id',
                'date',
                'status'
            ));
            
            $select->where([
                'apartment_id'  => $apartmentId,
                'date'          => $date
            ]);
        });
    }
}
