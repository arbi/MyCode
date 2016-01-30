<?php

namespace DDD\Dao\ApartmentGroup;

use DDD\Domain\ApartmentGroup\Concierge\ConciergeWebsiteView;
use DDD\Service\Booking\BookingTicket as ReservationTicketService;
use DDD\Service\Booking;
use DDD\Service\Task;
use Library\ActionLogger\Logger;
use Zend\Db\Sql\Select;
use Library\DbManager\TableGatewayManager;
use Library\Constants\DbTables;
use Zend\Db\Sql\Expression;
use Zend\Db\Sql\Where;
use CreditCard\Service\Card as CardServiceUse;
use DDD\Service\Booking\BookingTicket;


class ConciergeView extends TableGatewayManager
{
    protected $table = DbTables::TBL_BOOKINGS;

    public function __construct($sm)
    {
        parent::__construct($sm, 'DDD\Domain\ApartmentGroup\ConciergeView');
    }

    /**
     * @param $apartmentIds
     * @return \DDD\Domain\ApartmentGroup\ConciergeView
     */
    public function getCurrentStays($apartmentIds, $todayWithTimeZone)
    {
        // Maximize GROUP_CONCAT limitations for housekeeping_comment
        $this->adapter->query('SET SESSION group_concat_max_len = 100000;')->execute();

        $columns = array(
            'id',
            'ki_page_hash',
            'guest_first_name',
            'guest_last_name',
            'res_number',
            'pax' => 'man_count',
            'occupancy',
            'date_to',
            'arrival_status',
            'guest_balance',
            'housekeeping_comment' => new Expression("(
                SELECT
                GROUP_CONCAT(
                    CONCAT('<blockquote class=\"comment-blockquote\">', '<p>', value, '</p><footer>', users.firstname, ' ', users.lastname, ', ',  `timestamp`, ' (Amsterdam Time)', '</footer></blockquote>') SEPARATOR ''
                )
                FROM " . DbTables::TBL_ACTION_LOGS . "
                LEFT JOIN " . DbTables::TBL_BACKOFFICE_USERS . " AS users ON users.id = " . DbTables::TBL_ACTION_LOGS . ".user_id
                WHERE module_id = " . Logger::MODULE_BOOKING . " AND identity_id = " . $this->getTable() . ".`id` AND action_id = " . Logger::ACTION_HOUSEKEEPING_COMMENT . "
            )"),
            'ki_page_status'       => 'ki_page_status'
        );

        $datetime = new \DateTime($todayWithTimeZone);
        $dateToday = $datetime->format('Y-m-d');

        $datetime->modify('+1 day');
        $dateTomorrow = $datetime->format('Y-m-d');

        return $this->fetchAll(function (Select $select) use($apartmentIds, $columns, $dateToday, $dateTomorrow) {
            $where = new Where();
            $where
                ->lessThanOrEqualTo($this->getTable() . '.date_from', $dateToday)
                ->greaterThanOrEqualTo($this->getTable() . '.date_to', $dateTomorrow)
                ->equalTo($this->getTable() . '.status', Booking::BOOKING_STATUS_BOOKED)
                ->notEqualTo($this->getTable() . '.overbooking_status', BookingTicket::OVERBOOKING_STATUS_OVERBOOKED)
                ->in($this->getTable() . '.apartment_id_assigned', $apartmentIds);

            $select
                ->columns($columns)
                ->where($where)
                ->join(
                    ['charge' => DbTables::TBL_CHARGE],
                    new Expression($this->getTable() . '.id = charge.reservation_id AND charge.addons_type = 6 AND charge.status = 0'),
                    ['parking' => 'id'],
                    Select::JOIN_LEFT
                )
                ->join(
                    ['tasks' => DbTables::TBL_TASK],
                    new Expression($this->getTable() . '.id = tasks.res_id AND tasks.task_type = ' . Task::TYPE_KEYFOB . ' AND tasks.task_status != ' . Task::STATUS_VERIFIED),
                    ['key_task' => 'id'],
                    Select::JOIN_LEFT
                )
                ->join(
                    ['apartment' => DbTables::TBL_APARTMENTS],
                    $this->getTable() . '.apartment_id_assigned = apartment.id',
                    ['unitNumber' => 'unit_number', 'acc_name' => 'name'],
                    Select::JOIN_LEFT
                )
                ->group($this->getTable() . '.res_number')
                ->order(['guest_last_name ASC']);
        });
    }

    /**
     * @param array $accList
     * @param string $date
     *
     * @return \DDD\Domain\ApartmentGroup\ConciergeView[]|\ArrayObject
     */
    public function getArrivalsByDay($accList, $date)
    {
        $columns = array(
            'id',
            'ki_page_hash',
            'guest_first_name',
            'guest_last_name',
            'res_number',
            'pax' => 'man_count',
            'occupancy',
            'date_to',
            'arrival_status',
            'overbooking_status',
            'model',
            'check_charged',
            'guest_email',
            'guest_balance',
            'housekeeping_comment' => new Expression("(
                SELECT
                GROUP_CONCAT(
                    CONCAT('<blockquote class=\"comment-blockquote\">', '<p>', value, '</p><footer>', users.firstname, ' ', users.lastname, ', ',  `timestamp`, ' (Amsterdam Time)', '</footer></blockquote>') SEPARATOR ''
                )
                FROM " . DbTables::TBL_ACTION_LOGS . "
                LEFT JOIN " . DbTables::TBL_BACKOFFICE_USERS . " AS users ON users.id = " . DbTables::TBL_ACTION_LOGS . ".user_id
                WHERE module_id = " . Logger::MODULE_BOOKING . " AND identity_id = " . $this->getTable() . ".`id` AND action_id = " . Logger::ACTION_HOUSEKEEPING_COMMENT . "
            )"),
            'ki_page_status'       => 'ki_page_status',
            'provide_cc_page_status',
            'provide_cc_page_hash'
        );

        $select = new Select($this->getTable());
        $select->join(
            ['charge' => DbTables::TBL_CHARGE],
            new Expression($this->getTable() . '.id = charge.reservation_id AND charge.addons_type = 6 AND charge.status = 0'),
            ['parking' => 'id'],
            Select::JOIN_LEFT
        );
        $select->join(
            ['apartment' => DbTables::TBL_APARTMENTS],
            $this->getTable() . '.apartment_id_assigned = apartment.id',
            ['unitNumber' => 'unit_number', 'acc_name' => 'name'],
            Select::JOIN_LEFT
        );
        $select->join(
            ['cc_token' => DbTables::TBL_TOKEN],
            new Expression($this->getTable() . '.customer_id = cc_token.customer_id AND cc_token.status in (' .CardServiceUse::CC_STATUS_VALID. ','.CardServiceUse::CC_STATUS_UNKNOWN.')'),
            [
                'first_digits',
                'cc_type' => 'brand',
                'salt',
            ],
            Select::JOIN_LEFT
        );

        $select->where($this->getTable() . ".date_from = '" . $date . "'")
            ->where($this->getTable() . '.status = 1')
            ->where->notEqualTo($this->getTable() . '.overbooking_status', BookingTicket::OVERBOOKING_STATUS_OVERBOOKED);
        $select->where->in($this->getTable() . '.apartment_id_assigned', $accList);

        $select
            ->columns($columns)
            ->order([
                'cc_token.is_default DESC',
                'cc_token.status DESC',
                'arrival_status ASC',
                'guest_last_name ASC'
            ]);

        $sql = $select->getSqlString($this->getAdapter()->getPlatform());
        $sql = "SELECT * FROM ({$sql}) AS main GROUP BY res_number ORDER BY arrival_status ASC, guest_last_name ASC";

        $statement = $this->adapter->createStatement($sql);
        $result = $statement->execute();

        $resultSet = clone $this->resultSetPrototype;
        $resultSet->initialize($result);

        return $resultSet;

    }

    /**
     * @param array $accList
     * @param string $date
     *
     * @return \DDD\Domain\ApartmentGroup\ConciergeView[]|\ArrayObject
     */
    public function getCheckoutByDay($accList, $date)
    {
        $columns = array(
            'id',
            'ki_page_hash',
            'guest_first_name',
            'guest_last_name',
            'res_number',
            'pax' => 'man_count',
            'occupancy',
            'date_to',
            'guest_email',
            'arrival_status',
            'guest_balance',
            'housekeeping_comment' => new Expression("(
                SELECT
                GROUP_CONCAT(
                    CONCAT('<blockquote class=\"comment-blockquote\">', '<p>', value, '</p><footer>', users.firstname, ' ', users.lastname, ', ',  `timestamp`, ' (Amsterdam Time)', '</footer></blockquote>') SEPARATOR ''
                )
                FROM " . DbTables::TBL_ACTION_LOGS . "
                LEFT JOIN " . DbTables::TBL_BACKOFFICE_USERS . " AS users ON users.id = " . DbTables::TBL_ACTION_LOGS . ".user_id
                WHERE module_id = " . Logger::MODULE_BOOKING . " AND identity_id = " . $this->getTable() . ".`id` AND action_id = " . Logger::ACTION_HOUSEKEEPING_COMMENT . "
            )"),
            'ki_page_status'       => 'ki_page_status',
            'provide_cc_page_status'
        );

        return $this->fetchAll(function (Select $select) use($accList, $date, $columns) {
            $where = new Where();
            $where
                ->equalTo($this->getTable() . '.date_to', $date)
                ->equalTo($this->getTable() . '.status', Booking::BOOKING_STATUS_BOOKED)
                ->notEqualTo($this->getTable() . '.arrival_status', ReservationTicketService::BOOKING_ARRIVAL_STATUS_NO_SHOW)
                ->notEqualTo($this->getTable() . '.overbooking_status', BookingTicket::OVERBOOKING_STATUS_OVERBOOKED)
                ->in($this->getTable() . '.apartment_id_assigned', $accList);

            $select
                ->columns($columns)
                ->join(
                    ['apartment' => DbTables::TBL_APARTMENTS],
                    $this->getTable() . '.apartment_id_assigned = apartment.id',
                    [
                        'unitNumber' => 'unit_number',
                        'acc_name' => 'name'
                    ],
                    Select::JOIN_LEFT
                )
                ->join(
                    ['charge' => DbTables::TBL_CHARGE],
                    new Expression($this->getTable() . '.id = charge.reservation_id AND charge.addons_type = 6 AND charge.status = 0'),
                    ['parking' => 'id'],
                    Select::JOIN_LEFT
                )
                ->join(
                    ['tasks' => DbTables::TBL_TASK],
                    new Expression($this->getTable() . '.id = tasks.res_id AND tasks.task_type = ' . Task::TYPE_KEYFOB . ' AND tasks.task_status != ' . Task::STATUS_VERIFIED),
                    ['key_task' => 'id'],
                    Select::JOIN_LEFT
                )
                ->where($where)
                ->order([$this->getTable() . '.arrival_status ASC', $this->getTable() . '.guest_last_name ASC'])
                ->group($this->getTable() . '.id');
        });
    }

    /**
     * @param $apartmentIds []
     * @param $date string
     * @return ConciergeWebsiteView []
     */
    public function getArrivalsForWebsitePage($apartmentIds, $date)
    {
        $this->resultSetPrototype->setArrayObjectPrototype(new ConciergeWebsiteView());

        $columns = [
            'guest_first_name',
            'guest_last_name',
            'res_number' => 'res_number',
            'pax'        => 'man_count',
            'date_to'    => 'date_to'
        ];

        return $this->fetchAll(function (Select $select) use($apartmentIds, $date, $columns) {
            $select->join(
                ['apartment' => DbTables::TBL_APARTMENTS],
                $this->getTable() . '.apartment_id_assigned = apartment.id',
                [
                    'unit_number'    => 'unit_number',
                    'apartment_name' => 'name'
                ],
                Select::JOIN_LEFT
            );

            $select->where([
                $this->getTable() . '.date_from' => $date,
                $this->getTable() . '.status' => Booking::BOOKING_STATUS_BOOKED,
                $this->getTable() . '.guest_balance >= 0'
            ]);

            $select->where->in($this->getTable() . '.apartment_id_assigned', $apartmentIds);

            $select
                ->columns($columns)
                ->order([$this->getTable() . '.guest_last_name ASC']);
        });
    }
}

