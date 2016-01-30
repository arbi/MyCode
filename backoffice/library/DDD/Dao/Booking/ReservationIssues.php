<?php

namespace DDD\Dao\Booking;

use DDD\Service\Partners;
use Library\DbManager\TableGatewayManager;
use Library\Constants\DbTables;

use Library\Constants\Constants;
use Zend\Db\Sql\Select;
use Zend\Db\Sql\Expression;

use \DDD\Service\Booking as BookingService;
use DDD\Service\Booking\ReservationIssues as ReservationIssuesService;

class ReservationIssues extends TableGatewayManager
{
    protected $table = DbTables::TBL_RESERVATION_ISSUES;

    public function __construct($sm, $domain = 'DDD\Domain\Booking\ReservationIssues')
    {
        parent::__construct($sm, $domain);
    }

    /**
     * @param string $issueId
     * @return \DDD\Domain\Booking\ReservationIssues
     */
    public function getIssueById($issueId)
    {
        $result = $this->fetchOne(function (Select $select) use ($issueId) {

            $select->columns(array(
                'id',
                'reservation_id',
                'issue_type_id',
                'date_of_detection',
            ));

            $select->join(
                    ['types' => DbTables::TBL_RESERVATION_ISSUE_TYPES],
                    $this->table .'.issue_type_id = types.id',
                    ['title'],
                    Select::JOIN_LEFT
                    );

            $select->where
                ->equalTo($this->table.'.id', $issueId);
        });

        return $result;
    }

    /**
     * @return \DDD\Domain\Booking\ReservationIssues
     */
    public function getAllIssues($filter)
    {
        $result = $this->fetchAll(function (Select $select) use ($filter) {
            $select->columns(
                [
                    'id',
                    'reservation_id',
                    'issue_type_id',
                    'date_of_detection',
                ]
            );
            $select->join(
                ['types' => DbTables::TBL_RESERVATION_ISSUE_TYPES],
                $this->table . '.issue_type_id = types.id',
                ['title'],
                Select::JOIN_LEFT
            );
            $select->join(
                ['reservation' => DbTables::TBL_BOOKINGS],
                $this->table . '.reservation_id = reservation.id',
                ['reservation_number' => 'res_number']
            );
            $select->where->notEqualTo($this->table . '.issue_type_id', ReservationIssuesService::ISSUE_CCN_DATE_HAS_EXPIRED);
            if ($filter) {
                $select->where->notIn(
                    $this->table . '.issue_type_id',
                    [ReservationIssuesService::ISSUE_CCN_IS_MISSING, ReservationIssuesService::ISSUE_CCN_DATE_WILL_BE_EXPIRED]
                );
            }
            $select->where->or->nest
                ->equalTo($this->table . '.issue_type_id', ReservationIssuesService::ISSUE_CCN_DATE_HAS_EXPIRED)
                ->and->nest
                ->equalTo('reservation.status', BookingService::BOOKING_STATUS_BOOKED)
                ->or
                ->equalTo('reservation.status', BookingService::BOOKING_STATUS_CANCELLED_MOVED);
        });

        return $result;
    }

    /**
     * @param $filter
     * @return mixed
     */
    public function getAllIssuesCount($filter)
    {
        $this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());
        $result = $this->fetchOne(function (Select $select) use ($filter) {

            $select->columns(['count' => new Expression('COUNT(*)')]);

            $select->join(
                ['types' => DbTables::TBL_RESERVATION_ISSUE_TYPES],
                $this->table . '.issue_type_id = types.id',
                [],
                Select::JOIN_LEFT
            );

            $select->join(
                ['reservation' => DbTables::TBL_BOOKINGS],
                $this->table . '.reservation_id = reservation.id',
                []
            );

            if ($filter) {
                $select->where->notIn(
                    $this->table . '.issue_type_id',
                    [ReservationIssuesService::ISSUE_CCN_IS_MISSING, ReservationIssuesService::ISSUE_CCN_DATE_WILL_BE_EXPIRED]
                );
            }
        });

        return $result['count'];
    }

    /**
     * @return \DDD\Domain\Booking\ReservationIssues
     */
    public function getAllIssuesAndLessThan9DayFromTodayOrbitz($filter)
    {
        $result = $this->fetchAll(function (Select $select) use ($filter) {

            $select->columns(
                [
                    'id',
                    'reservation_id',
                    'issue_type_id',
                    'date_of_detection',
                ]
            );

            $select->join(
                ['types' => DbTables::TBL_RESERVATION_ISSUE_TYPES],
                $this->table . '.issue_type_id = types.id',
                ['title'],
                Select::JOIN_LEFT
            );

            $select->join(
                ['reservation' => DbTables::TBL_BOOKINGS],
                $this->table . '.reservation_id = reservation.id',
                [
                    'reservation_number' => 'res_number',
                    'date_from',
                    'partner_id',
                    'partner_ref',
                    'partner_name',
                ]
            );

            $select->where->notEqualTo($this->table . '.issue_type_id', ReservationIssuesService::ISSUE_CCN_DATE_HAS_EXPIRED);
            $select->where->notEqualTo($this->table . '.issue_type_id', ReservationIssuesService::ISSUE_APARTMENT_OCUPANCY_REDUCED);
            $select->where->greaterThanOrEqualTo('reservation.date_to', date(Constants::DATABASE_DATE_TIME_FORMAT, strtotime('-5 days', strtotime(date(Constants::DATABASE_DATE_TIME_FORMAT)))));

            if ($filter) {
                $select->where->notIn(
                    $this->table . '.issue_type_id',
                    [ReservationIssuesService::ISSUE_CCN_IS_MISSING, ReservationIssuesService::ISSUE_CCN_DATE_WILL_BE_EXPIRED]
                );
            }

            $select->where->and->nest
                ->notEqualTo('reservation.partner_id', Partners::PARTNER_ORBITZ)
                ->or->nest
                ->equalTo('reservation.partner_id', Partners::PARTNER_ORBITZ)
                ->and
                ->lessThanOrEqualTo('reservation.date_from', date(Constants::DATABASE_DATE_TIME_FORMAT, strtotime('+9 days', strtotime(date(Constants::DATABASE_DATE_TIME_FORMAT)))));

            $select->where->or->nest
                ->equalTo($this->table . '.issue_type_id', ReservationIssuesService::ISSUE_CCN_DATE_HAS_EXPIRED)
                ->and->nest
                ->equalTo('reservation.status', BookingService::BOOKING_STATUS_BOOKED)
                ->or
                ->equalTo('reservation.status', BookingService::BOOKING_STATUS_CANCELLED_MOVED);

            $select->order('reservation.date_from ASC');
        });

        return $result;
    }

    /**
     * @return \DDD\Domain\Booking\ReservationIssues
     */
    public function getAllIssuesAndLessThan9DayFromTodayOrbitzCount($filter)
    {
        $this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());
        $result = $this->fetchOne(function (Select $select) use ($filter) {

            $select->columns(['count' => new Expression('COUNT(*)')]);

            $select->join(
                ['types' => DbTables::TBL_RESERVATION_ISSUE_TYPES],
                $this->table . '.issue_type_id = types.id',
                [],
                Select::JOIN_LEFT
            );

            $select->join(
                ['reservation' => DbTables::TBL_BOOKINGS],
                $this->table . '.reservation_id = reservation.id',
                []
            );

            $select->where->notEqualTo($this->table . '.issue_type_id', ReservationIssuesService::ISSUE_APARTMENT_OCUPANCY_REDUCED);
            $select->where->notEqualTo($this->table . '.issue_type_id', ReservationIssuesService::ISSUE_CCN_DATE_HAS_EXPIRED);
            $select->where->greaterThanOrEqualTo('reservation.date_to', date(Constants::DATABASE_DATE_TIME_FORMAT, strtotime('-5 days', strtotime(date(Constants::DATABASE_DATE_TIME_FORMAT)))));

            if ($filter) {
                $select->where->notIn(
                    $this->table . '.issue_type_id',
                    [ReservationIssuesService::ISSUE_CCN_IS_MISSING, ReservationIssuesService::ISSUE_CCN_DATE_WILL_BE_EXPIRED]
                );
            }

            $select->where->and->nest
                ->notEqualTo('reservation.partner_id', Partners::PARTNER_ORBITZ)
                ->or->nest
                ->equalTo('reservation.partner_id', Partners::PARTNER_ORBITZ)
                ->and
                ->lessThanOrEqualTo('reservation.date_from', date(Constants::DATABASE_DATE_TIME_FORMAT, strtotime('+9 days', strtotime(date(Constants::DATABASE_DATE_TIME_FORMAT)))));

            $select->where->or->nest
                ->equalTo($this->table . '.issue_type_id', ReservationIssuesService::ISSUE_CCN_DATE_HAS_EXPIRED)
                ->and->nest
                ->equalTo('reservation.status', BookingService::BOOKING_STATUS_BOOKED)
                ->or
                ->equalTo('reservation.status', BookingService::BOOKING_STATUS_CANCELLED_MOVED);
        });

        return $result['count'];
    }

    /**
     *
     * @param string $reservationId
     * @return \DDD\Domain\Booking\ReservationIssues
     */
    public function getIssuesByReservationId($reservationId, $type = FALSE)
    {
        if ($type) {
            $result = $this->fetchOne(function (Select $select) use ($reservationId, $type) {

                $select->columns(array(
                    'id',
                    'reservation_id',
                    'issue_type_id',
                    'date_of_detection',
                ));

                $select->join(
                        ['types' => DbTables::TBL_RESERVATION_ISSUE_TYPES],
                        $this->table .'.issue_type_id = types.id',
                        ['title'],
                        Select::JOIN_LEFT
                        );

                $select->where
                        ->equalTo('reservation_id', $reservationId)
                        ->and
                        ->equalTo('issue_type_id', $type);
            });
        } else {
            $result = $this->fetchAll(function (Select $select) use ($reservationId) {

            $select->columns(array(
                'id',
                'reservation_id',
                'issue_type_id',
                'date_of_detection',
            ));

            $select->join(
                    ['types' => DbTables::TBL_RESERVATION_ISSUE_TYPES],
                    $this->table .'.issue_type_id = types.id',
                    ['title'],
                    Select::JOIN_LEFT
                    );

            $select->join(
                    ['reservation' => DbTables::TBL_BOOKINGS],
                    $this->table . '.reservation_id = reservation.id',
                    ['reservation_number' => 'res_number']
                    );

            $select->where
                   ->equalTo('reservation_id', $reservationId);
            });
        }

        return $result;
    }

    public function getGreaterOccupancyResIssues()
    {
        $result = $this->fetchAll(function (Select $select) {

            $select->join(
                ['types' => DbTables::TBL_RESERVATION_ISSUE_TYPES],
                new Expression ($this->table . '.issue_type_id = types.id AND types.id =' . ReservationIssuesService::ISSUE_APARTMENT_OCUPANCY_REDUCED),
                ['title'],
                Select::JOIN_LEFT
            );

            $select->join(
                ['reservation' => DbTables::TBL_BOOKINGS],
                $this->table . '.reservation_id = reservation.id',
                [
                    'reservation_number' => 'res_number',
                    'date_from',
                    'partner_id',
                    'partner_ref',
                    'partner_name',
                ]
            );

            $select->where
                ->greaterThan('reservation.date_from', new Expression('DATE_SUB(CURDATE(), INTERVAL 1 DAY)'))
                ->equalTo($this->getTable() . '.issue_type_id', ReservationIssuesService::ISSUE_APARTMENT_OCUPANCY_REDUCED);

            $select->group($this->table . '.reservation_id');
        });

        return $result;
    }

    public function getGreaterOccupancyResIssuesCount()
    {
        $this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());

        $result = $this->fetchOne(function (Select $select) {
            $select->columns(['count' => new Expression('COUNT(*)')]);

            $select->join(
                ['types' => DbTables::TBL_RESERVATION_ISSUE_TYPES],
                new Expression ($this->table . '.issue_type_id = types.id AND types.id =' . ReservationIssuesService::ISSUE_APARTMENT_OCUPANCY_REDUCED),
                ['title'],
                Select::JOIN_LEFT
            );

            $select->join(
                ['reservation' => DbTables::TBL_BOOKINGS],
                $this->table . '.reservation_id = reservation.id',
                [                    'reservation_number' => 'res_number',
                                    'date_from',
                                    'partner_id',
                                    'partner_ref',
                                    'partner_name',]
            );

            $select->where
                ->greaterThan('reservation.date_from', new Expression('DATE_SUB(CURDATE(), INTERVAL 1 DAY)'))
                ->equalTo($this->getTable() . '.issue_type_id', ReservationIssuesService::ISSUE_APARTMENT_OCUPANCY_REDUCED);

            // $select->group($this->table . '.reservation_id');
        });

        return $result['count'];
    }
}
