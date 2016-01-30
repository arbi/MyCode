<?php
namespace DDD\Dao\Booking;

use Zend\Db\Sql\Select;
use Library\DbManager\TableGatewayManager;
use Library\Constants\DbTables;
use Zend\Db\Sql\Where;

class FraudDetection extends TableGatewayManager
{
	/**
	 * Main table to work with
	 * @var string
	 */
    protected $table = DbTables::TBL_FRAUD_DETECTION_CC;
    
    public function __construct($sm, $domain = '\ArrayObject') {
        parent::__construct($sm, $domain);
    }

    public function getFraudByReservationId ($reservationId)
    {
        $result = $this->fetchAll(function (Select $select) use($reservationId) {
             $select->join(
                    ['reservation' => DbTables::TBL_BOOKINGS],
                    $this->getTable() . '.from_reservation_id = reservation.id',
                    ['res_number_from' => 'res_number'],
                    $select::JOIN_LEFT
                )
                ->where([$this->getTable() . '.reservation_id' => $reservationId]);
        });
        return $result;
    }

    public function getFraudListByReservationAndType ($reservationId, $types)
    {
        $result = $this->fetchAll(function (Select $select) use($reservationId, $types) {
            $select->where->equalTo('reservation_id', $reservationId)
                          ->in('type', $types);
        });
        return $result;
    }

}