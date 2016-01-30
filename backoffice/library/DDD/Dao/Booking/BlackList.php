<?php
namespace DDD\Dao\Booking;

use Zend\Db\Sql\Select;
use Library\DbManager\TableGatewayManager;
use Library\Constants\DbTables;
use Zend\Db\Sql\Where;
use DDD\Service\Fraud;

class BlackList extends TableGatewayManager
{
	/**
	 * Main table to work with
	 * @var string
	 */
    protected $table = DbTables::TBL_BLACK_LIST;
    
    public function __construct($sm, $domain = '\ArrayObject') {
        parent::__construct($sm, $domain);
    }
    
    public function getBlackList($data) {
        $result = $this->fetchAll(function (Select $select) use ($data) {
            $select->join(
                ['reservation' => DbTables::TBL_BOOKINGS],
                $this->getTable() . '.reservation_id = reservation.id',
                ['res_number'],
                $select::JOIN_LEFT
            );
            $where = new Where();
            $where->equalTo('hash', '');
            if (!empty($data['email'])) {
                $where->or->equalTo('hash', $data['email']);
            }
            if (!empty($data['fullNamePhone'])) {
                $where->or->equalTo('hash', $data['fullNamePhone']);
            }
            if (!empty($data['fullNameAddress'])) {
                $where->or->equalTo('hash', $data['fullNameAddress']);
            }
            if (!empty($data['fullName'])) {
                $where->or->equalTo('hash', $data['fullName']);
            }
            if (!empty($data['phone'])) {
                $where->or->equalTo('hash', $data['phone']);
            }
            $select->where($where);
        });
        return $result;    
    }

    public function getBlackListByReservationIdAndType($reservationId, $types) {
        $result = $this->fetchAll(function (Select $select) use ($reservationId, $types) {
            $select->where->equalTo('reservation_id', $reservationId)
                   ->in('type', $types);
        });
        return $result;
    }
}