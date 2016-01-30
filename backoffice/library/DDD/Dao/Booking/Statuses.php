<?php

namespace DDD\Dao\Booking;

use Zend\Db\Sql\Select;
use Library\DbManager\TableGatewayManager;
use Library\Constants\DbTables;
use Zend\Db\Sql\Where;

class Statuses extends TableGatewayManager {
    protected $table = DbTables::TBL_BOOKING_STATUSES;

    public function __construct($sm, $domain = 'DDD\Domain\Booking\Statuses') {
        parent::__construct($sm, $domain);
    }

	/**
	 * @param bool $status
	 *
	 * @return \DDD\Domain\Booking\Statuses[]|\ArrayObject
	 */
	public function getAllList($status = false) {
	    $result = $this->fetchAll(function (Select $select) use($status) {
            $where = new Where();
            $where->equalTo('visible', 1);

            if ($status) {
                // If is canceled can't be changed to booked
                if ($status != 1) {
                    $where->notEqualTo('id', 1);
                }

                // Smart select (active ones + selected one)
                $where->or
                    ->equalTo('id', $status);
            }

            $select->where($where);
			$select->columns(array('id', 'name', 'visible'))->order('id');
		});

		return $result->buffer();
    }
}
