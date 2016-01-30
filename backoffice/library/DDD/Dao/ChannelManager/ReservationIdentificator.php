<?php
namespace DDD\Dao\ChannelManager;

use Library\DbManager\TableGatewayManager;
use Library\Constants\DbTables;
use Library\Utility\Debug;
use Zend\Db\Sql\Select;
use Zend\Db\Sql\Expression;
use Zend\Stdlib\ArrayObject;

class ReservationIdentificator extends TableGatewayManager {

    /**
     * @var string
     */
    protected $table = DbTables::TBL_RESERVATIONS_IDENTIFICATOR;

    /**
     * @param \Zend\ServiceManager\ServiceLocatorInterface $sm
     * @param string $domain
     */
    public function __construct($sm, $domain = 'ArrayObject')
    {
        parent::__construct($sm, $domain);
    }

    /**
     * @param $channelResId
     * @return \DDD\Domain\Booking\ChannelReservation[]
     */
    public function getReservationsByChannelResId($channelResId)
    {
        $this->resultSetPrototype->setArrayObjectPrototype(new \DDD\Domain\Booking\ChannelReservation());
        return $this->fetchAll(function (Select $select) use ($channelResId) {
            $select->columns([
                'channel_res_id',
                'reservation_id',
                'room_id',
                'rate_id',
                'i_date_from' => 'date_from',
                'i_date_to' => 'date_to',
                'guest_name',
            ]);

            $select->join(
                ['booking' => DbTables::TBL_BOOKINGS],
                $this->getTable() . '.reservation_id = booking.id',
                [
                    'id',
                    'res_number',
                    'customer_id',
                    'apartment_id_assigned',
                    'guest_first_name',
                    'guest_last_name',
                    'status',
                    'date_from',
                    'date_to',
                    'funds_confirmed',
                    'guest_email'
                ],
                $select::JOIN_INNER
            );

            $select->join(
                ['apartments' => DbTables::TBL_APARTMENTS],
                'booking.apartment_id_assigned = apartments.id',
                [
                    'building_id'
                ],
                $select::JOIN_INNER
            );

            $select->where([$this->getTable() . '.channel_res_id' => $channelResId]);
        });
    }

    /**
     * @param $channelResId
     * @param $reservationId
     * @return \Zend\Db\ResultSet\ResultSet
     */
    public function getReservationsIdentificatorDataByChannelResId($channelResId, $reservationId)
    {
        $this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());
        return $this->fetchAll(function (Select $select) use ($channelResId, $reservationId) {
            $select->columns([]);
            $select->join(
                ['booking' => DbTables::TBL_BOOKINGS],
                $this->getTable() . '.reservation_id = booking.id',
                [
                    'res_number',
                    'date_from',
                    'date_to',
                    'guest' => new Expression('CONCAT(guest_first_name, " ", guest_last_name)')
                ],
                $select::JOIN_INNER
            );
            $select->where->equalTo($this->getTable() . '.channel_res_id', $channelResId)
                          ->notEqualTo($this->getTable() . '.reservation_id', $reservationId);
        });
    }

    /**
     * @param $channelResId
     * @return \Zend\Db\ResultSet\ResultSet
     */
    public function getExistingCount($channelResId)
    {
        $this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());
        $result = $this->fetchOne(function (Select $select) use ($channelResId) {
            $select->columns([
                'count' => new Expression('COUNT(id)')
            ]);
            $select->where->equalTo($this->getTable() . '.channel_res_id', $channelResId);
        });

        return $result ? $result['count'] : 0;
    }


}