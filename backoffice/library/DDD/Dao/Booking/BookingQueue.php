<?php

namespace DDD\Dao\Booking;

use Zend\Db\Sql\Select;
use Library\DbManager\TableGatewayManager;
use Library\Constants\DbTables;

class BookingQueue extends TableGatewayManager
{
    protected $table = DbTables::TBL_BOOKINGS_QUEUE;

    public function __construct($sm, $domain = 'DDD\Domain\Booking\BookingQueue')
    {
        parent::__construct($sm, $domain);
    }

    public function getBookingsForSendEmail()
    {
        $result = $this->fetchAll( function (Select $select) {

            $select->columns(
                [
                    'id',
                    'reservation_id'
                ]
            );

            $select->where->equalTo('error_status', 0);
        });

        return $result;
    }

    public function delBookingFromQueue($id)
    {
        $result = $this->deleteWhere(['reservation_id' => $id]);

        return $result;
    }
}

?>
