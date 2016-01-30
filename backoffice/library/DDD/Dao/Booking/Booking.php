<?php

namespace DDD\Dao\Booking;

use DDD\Domain\Booking\ChargeAuthorization\Email;
use DDD\Domain\Booking\ChargeAuthorizationForm;
use DDD\Domain\Booking\KeyInstructionPage;
use DDD\Domain\Booking\SaleStatisticsItem;
use DDD\Domain\UniversalDashboard\Widget\AwaitingPaymentDetails;
use DDD\Domain\UniversalDashboard\Widget\ChargeApartelReservations;
use DDD\Domain\UniversalDashboard\Widget\CollectFromCustomer;
use DDD\Domain\UniversalDashboard\Widget\NotChargedApartelReservations;
use DDD\Domain\UniversalDashboard\Widget\OverbookingReservation;
use DDD\Domain\Booking\ForCancel;
use DDD\Domain\Booking\PrepareData;
use DDD\Domain\Booking\BookingTicket;
use DDD\Service\Booking\BookingTicket as BookingTicketService;
use DDD\Domain\UniversalDashboard\Widget\ValidateCC;

use DDD\Service\Booking as BookingService;
use DDD\Service\Booking\BookingTicket as ReservationTicketService;
use DDD\Service\User as UserService;
use DDD\Service\Booking\BookingAddon;
use DDD\Service\Task as TaskService;
use DDD\Service\Reservation\ChargeAuthorization;
use DDD\Service\Lock\General as LockService;
use DDD\Service\Booking\Charge as ChargeService;

use Library\ActionLogger\Logger;
use Library\DbManager\TableGatewayManager;
use Library\Constants\Constants;
use Library\Constants\DbTables;
use Library\Utility\Debug;

use Zend\Db\ResultSet\ResultSet;
use Zend\Db\Sql\Select;
use Zend\Db\Sql\Expression;
use Zend\Db\Sql\Where;
use Zend\Db\Sql\Predicate\Predicate;
use Zend\Db\Sql\Predicate\PredicateSet;
use Zend\Stdlib\ArrayObject;

class Booking extends TableGatewayManager
{
    protected $table = DbTables::TBL_BOOKINGS;

    public function __construct($sm, $domain = 'DDD\Domain\Booking\Booking')
    {
        parent::__construct($sm, $domain);
    }

    public function getReservationForToday()
    {
    	$columns = array(
    		'id' 			=> 'id',
    		'res_number' 	=> 'res_number',
    		'status' 		=> 'status',
    		'date_from' 	=> 'date_from',
    		'date_to' 		=> 'date_to',
    		'acc_city_name' => 'acc_city_name',
    		'timestamp' 	=> 'timestamp',
    		'pax' 			=> 'man_count',
    		'guest_first_name',
    		'guest_last_name',
    		'guest_phone',
            'guest_balance' => 'guest_balance',
            'apartment_currency_code'
    	);

        $timeNow        = date('Y-m-d H:i:s');
        $dateNow        = date('Y-m-d');
        $currentTime    = time();
        $timeBack       = $currentTime-(60 * 60 * 30); // minus 30 hours
        $timeAfter      = $currentTime+(60 * 60 * 30); // minus 30 hours
        $timeShiftBack  = date('Y-m-d H:i:s', $timeBack);
        $timeShiftAfter = date('Y-m-d', $timeAfter);

        $result = $this->fetchAll(function (Select $select) use($timeNow, $dateNow, $timeShiftBack, $timeShiftAfter, $columns) {
			$select->columns($columns);

            $select->join(
                ['apartments' => DbTables::TBL_APARTMENTS],
                $this->getTable() . '.apartment_id_assigned = apartments.id',
                ['acc_name' => 'name']
            );

            $select->join(
                ['c' => DbTables::TBL_CURRENCY],
                $this->getTable() . '.apartment_currency_code = c.code',
                ['symbol' => 'symbol']
            );

            $select->where
                ->between($this->getTable() . '.timestamp', $timeShiftBack, $timeNow)
                ->between($this->getTable() . '.date_from', $dateNow, $timeShiftAfter)
                ->equalTo($this->getTable() . '.status', BookingService::BOOKING_STATUS_BOOKED)
                ->equalTo($this->getTable() . '.lmr_resolved', '0')
                ->notIn(
                    'apartment_id_assigned',
                    [
                        Constants::TEST_APARTMENT_1,
                        Constants::TEST_APARTMENT_2
                    ]
                );

            $select->order($this->getTable() . '.man_count DESC');
        });

        return $result;
    }

    public function getReservationForTodayCount()
    {
        $this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());

        $timeNow        = date('Y-m-d H:i:s');
        $dateNow        = date('Y-m-d');
        $currentTime    = time();
        $timeBack       = $currentTime-(60*60*30); // minus 30 hours
        $timeAfter      = $currentTime+(60*60*30); // minus 30 hours
        $timeShiftBack  = date('Y-m-d H:i:s', $timeBack);
        $timeShiftAfter = date('Y-m-d', $timeAfter);

        $result = $this->fetchOne(function (Select $select) use($timeNow, $dateNow, $timeShiftBack, $timeShiftAfter) {
			$select->columns(['count' => new Expression('COUNT(*)')]);

            $select->join(
                ['apartments' => DbTables::TBL_APARTMENTS],
                $this->getTable() . '.apartment_id_assigned = apartments.id',
                []
            );

            $select->join(
                ['c' => DbTables::TBL_CURRENCY],
                $this->getTable() . '.apartment_currency_code = c.code',
                []
            );

            $select->where
                ->between($this->getTable() . '.timestamp', $timeShiftBack, $timeNow)
                ->between($this->getTable() . '.date_from', $dateNow, $timeShiftAfter)
                ->equalTo($this->getTable() . '.status', BookingService::BOOKING_STATUS_BOOKED)
                ->equalTo($this->getTable() . '.lmr_resolved', '0')
                ->notIn(
                    'apartment_id_assigned',
                    [
                        Constants::TEST_APARTMENT_1,
                        Constants::TEST_APARTMENT_2
                    ]
                );
        });

        return $result['count'];
    }

    /**
     * Get reservations basic info sorted and paginated
     *
     * @param string|null $iDisplayStart
     * @param string|null $iDisplayLength
     * @param Where $where
     * @param int $sortCol
     * @param string $sortDir
     * @param array $params
     *
     * @return \DDD\Domain\Booking\BookingTableRow
     *
     * @author Tigran Petrosyan
     */
    public function getReservationsBasicInfo(
        $iDisplayStart = null,
        $iDisplayLength = null,
        $where,
        $sortCol = 4,
        $sortDir = 'DESC',
        $params
    ){

        $columns = [
            'id'            => 'id',
            'res_number'    => 'res_number',
            'partner_id'    => 'partner_id',
            'status'        => 'status',
            'timestamp'     => new Expression('DATE(' . $this->getTable() . '.timestamp)'),
            'guest_first_name',
            'guest_last_name',
            'date_from'     => 'date_from',
            'date_to'       => 'date_to',
            'pax'           => 'man_count',
            'occupancy'     => 'occupancy',
            'guest_balance' => 'guest_balance',
            'apartment_currency_code',
            'rate_name'     => 'rate_name',
            'locked'        => 'locked',
        ];

    	$sortColumns = [
            'res_number',
            'status',
            'timestamp',
            'acc_name',
            'first_name',
            'date_from',
            'date_to',
            'rate_name',
    		'charged',
            'guest_balance',
    	];

    	$result = $this->fetchAll(
            function (Select $select) use(
                $columns,
                $sortColumns,
                $iDisplayStart,
                $iDisplayLength,
                $where,
                $sortCol,
                $sortDir,
                $params
            ) {
                $select->columns( $columns );

                $select->join(
                    ['apartments' => DbTables::TBL_APARTMENTS],
                    $this->getTable() .
                    '.apartment_id_assigned = apartments.id',
                    ['acc_name' => 'name']
                );

                if (isset($params['charge_auth_number'])) {
                    $select->join(
                        ['transaction'=>DbTables::TBL_CHARGE_TRANSACTION],
                        new Expression(
                            $this->getTable() . ".id = " .
                            "transaction.reservation_id AND " .
                            "transaction.auth_code like '%" .
                            $params['charge_auth_number'] ."%'"
                        ),
                        []
                    );
                }

                if (isset($params['transaction_amount'])) {
                    $select->join(
                        ['transaction1'=>DbTables::TBL_CHARGE_TRANSACTION],
                        new Expression(
                            $this->getTable() . ".id = " .
                            "transaction1.reservation_id AND " .
                            "transaction1.bank_amount = '" .
                            $params['transaction_amount'] . "'"
                        ),
                        [],
                        Select::JOIN_INNER
                    );
                }

                if (!empty($params['group_id'])) {
                    $select->join(
                        ['agi'=>DbTables::TBL_APARTMENT_GROUP_ITEMS],
                        new Expression(
                            $this->getTable() . '.apartment_id_assigned = ' .
                            'agi.apartment_id AND agi.apartment_group_id = ' .
                            $params['group_id']
                        ),
                        []
                    );
                }

                if (isset($params['comment']) && $params['comment']) {
                    $select->join(
                        ['logs' => DbTables::TBL_ACTION_LOGS],
                        new Expression(
                            $this->getTable() . '.id = logs.identity_id AND logs.module_id = ' . Logger::MODULE_BOOKING
                            . ' AND logs.action_id = ' . Logger::ACTION_COMMENT . ' AND logs.value like \'%' . $params['comment']. '%\''
                        ),
                        []
                    );
                }

                if ($where !== null) {
                    $select->where($where);
                }

                $select->quantifier(new Expression('SQL_CALC_FOUND_ROWS'));

                if (   $iDisplayLength !== null
                    && $iDisplayStart  !== null
                ) {
                    $select->limit((int)$iDisplayLength);
                    $select->offset((int)$iDisplayStart);
                }

                $select->order($sortColumns[$sortCol].' '.$sortDir);
                $select->group($this->getTable() . '.id');
            }
        );

        $statement = $this->adapter->query('SELECT FOUND_ROWS() as total');
        $result2   = $statement->execute();
        $row       = $result2->current();
        $total     = $row['total'];

        $return = [
            'result' => $result,
            'total'  => $total
        ];

        return $return;
    }

    /**
     * @param $where
     * @param $params
     * @return ResultSet
     */
    public function validateDownloadCsv($where, $params)
    {
        $previousEntity = $this->resultSetPrototype->getArrayObjectPrototype();
        $this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());

        $result = $this->fetchOne(
            function (Select $select) use($where, $params) {
                $select->columns(['count' => new Expression("Count('id')")]);
                $select->join(
                    DbTables::TBL_APARTMENTS,
                    $this->getTable() . '.apartment_id_assigned = ' .
                        DbTables::TBL_APARTMENTS . '.id',
                    [],
                    $select::JOIN_LEFT
                );

                $select->join(
                    ['ap_group' => DbTables::TBL_APARTMENT_GROUPS],
                    DbTables::TBL_APARTMENTS . '.building_id = ap_group.id',
                    [],
                    $select::JOIN_LEFT
                );

                $select->join(
                    ['country' => DbTables::TBL_COUNTRIES],
                    $this->getTable() . '.guest_country_id = country.id',
                    [],
                    $select::JOIN_LEFT
                );

                $select->join(
                    ['geo_detail' => DbTables::TBL_LOCATION_DETAILS],
                    'geo_detail.id = country.detail_id',
                    [],
                    $select::JOIN_LEFT
                );

                $select->join(
                    ['review' => DbTables::TBL_PRODUCT_REVIEWS],
                    $this->table . '.id = review.res_id',
                    [],
                    Select::JOIN_LEFT
                );

                $select->join(
                    ['ag' => DbTables::TBL_APARTMENT_GROUPS],
                    $this->getTable().'.apartel_id = ag.id',
                    [],
                    Select::JOIN_LEFT
                );

                $select->join(
                    ['customer_identity' => DbTables::TBL_CUSTOMER_IDENTITY],
                    $this->getTable().'.id = customer_identity.reservation_id',
                    [],
                    Select::JOIN_LEFT
                );

                if(!empty($params['group'])) {
                    $select->join(
                        ['agi' => DbTables::TBL_APARTMENT_GROUP_ITEMS],
                        new Expression(
                            $this->getTable() .
                            '.apartment_id_assigned = agi.apartment_id ' .
                            'AND agi.apartment_group_id = ' . $params['group']
                        ),
                        []
                    );
                }

                if (isset($params['comment']) && $params['comment']) {
                    $select->join(
                        ['logs' => DbTables::TBL_ACTION_LOGS],
                        new Expression(
                            $this->getTable() . '.id = logs.identity_id AND logs.module_id = ' . Logger::MODULE_BOOKING
                            . ' AND logs.action_id = ' . Logger::ACTION_COMMENT . ' AND logs.value like \'%' . $params['comment']. '%\''
                        ),
                        []
                    );
                }

                if ($where !== null) {
                    $select->where($where);
                }
            }
        );

        $this->setEntity($previousEntity);

        return $result;
    }

    /**
     * @param null $iDisplayStart
     * @param null $iDisplayLength
     * @param $where
     * @param int $sortCol
     * @param string $sortDir
     * @param $params
     * @return \DDD\Domain\Booking\BookingExportRow[]
     */
    public function getReservationsToExport(
        $iDisplayStart = null,
        $iDisplayLength = null,
        $where,
        $sortCol = 4,
        $sortDir = 'DESC',
        $params
    ) {
    	$columns = [
            'id'                    => 'id',
            'res_number'            => 'res_number',
            'partner_id'            => 'partner_id',
            'partner_name'          => 'partner_name',
            'acc_city_name'         => 'acc_city_name',
            'status'                => 'status',
            'apartel_id'            => 'apartel_id',
            'timestamp'             => 'timestamp',
            'guest_first_name',
            'guest_last_name',
            'date_from'             => 'date_from',
            'date_to'               => 'date_to',
            'pax'                   => 'man_count',
            'price'                 => 'price',
            'apartment_currency_code',
            'guest_city_name',
            'partner_ref'           => 'partner_ref',
            'no_collection'         => 'no_collection',
            'apartment_id_assigned' => 'apartment_id_assigned',
            'rate_name'             => 'rate_name',
    		'arrival_date'          => 'arrival_date',
    		'departure_date'        => 'departure_date',
            'guest_balance'         => 'guest_balance',
            'partner_balance'       => 'partner_balance',
            'is_blacklist'          => new Expression('GROUP_CONCAT(blacklist.id)')
    	];

    	$sortColumns = [
            'res_number',
            'partner_id',
            'status',
            'timestamp',
            'acc_city_name',
            'guest_first_name',
            'date_from',
            'date_to',
            'man_count',
            'rate_name',
            'price',
    	];

    	$result = $this->fetchAll(
            function (Select $select) use(
                $columns,
                $sortColumns,
                $iDisplayStart,
                $iDisplayLength,
                $where,
                $sortCol,
                $sortDir,
                $params
            ) {

                $select->columns( $columns );

                $select->join(
                    DbTables::TBL_APARTMENTS,
                    $this->getTable() . '.apartment_id_assigned = ' .
                        DbTables::TBL_APARTMENTS . '.id',
                    ['acc_name' => 'name'],
                    $select::JOIN_LEFT
                );

                $select->join(
                    ['ap_group' => DbTables::TBL_APARTMENT_GROUPS],
                    DbTables::TBL_APARTMENTS . '.building_id = ap_group.id',
                    [
                        'apartment_building' => 'name',
                        'usage_building'     => 'usage_building'
                    ],
                    $select::JOIN_LEFT
                );

                $select->join(
                    ['country' => DbTables::TBL_COUNTRIES],
                    $this->getTable() . '.guest_country_id = country.id',
                    [],
                    $select::JOIN_LEFT
                );

                $select->join(
                    ['geo_detail' => DbTables::TBL_LOCATION_DETAILS],
                    'geo_detail.id = country.detail_id',
                    ['country_name' => 'name'],
                    $select::JOIN_LEFT
                );

                $select->join(
                    ['review' => DbTables::TBL_PRODUCT_REVIEWS],
                    $this->table . '.id = review.res_id',
                    [
                        'review_score' => 'score',
                        'like'         => 'liked',
                        'dislike'
                    ],
                    Select::JOIN_LEFT
                );

                $select->join(
                    ['ag' => DbTables::TBL_APARTMENT_GROUPS],
                    $this->getTable().'.apartel_id = ag.id',
                    ['apartel' => 'name'],
                    Select::JOIN_LEFT
                );

                $select->join(
                    ['customer_identity' => DbTables::TBL_CUSTOMER_IDENTITY],
                    $this->getTable().'.id = customer_identity.reservation_id',
                    ['ip_address'],
                    Select::JOIN_LEFT
                );

                $select->join(
                    ['blacklist' => DbTables::TBL_BLACK_LIST],
                    $this->getTable().'.id = blacklist.reservation_id',
                    [],
                    Select::JOIN_LEFT
                );

                if(!empty($params['group'])) {
                    $select->join(
                        ['agi' => DbTables::TBL_APARTMENT_GROUP_ITEMS],
                        new Expression(
                            $this->getTable() .
                            '.apartment_id_assigned = agi.apartment_id ' .
                            'AND agi.apartment_group_id = ' . $params['group']
                        ),
                        []
                    );
                }

                if (isset($params['comment']) && $params['comment']) {
                    $select->join(
                        ['logs' => DbTables::TBL_ACTION_LOGS],
                        new Expression(
                            $this->getTable() . '.id = logs.identity_id AND logs.module_id = ' . Logger::MODULE_BOOKING
                            . ' AND logs.action_id = ' . Logger::ACTION_COMMENT . ' AND logs.value like \'%' . $params['comment']. '%\''
                        ),
                        []
                    );
                }

                if (   $iDisplayLength !== null
                    && $iDisplayStart !== null
                ) {
                    $select->limit((int)$iDisplayLength);
                    $select->offset((int)$iDisplayStart);
                }

                if ($where !== null) {
                    $select->where($where);
                }

                $select->group($this->getTable() . '.id');
                $select->order($sortColumns[$sortCol].' '.$sortDir);
            });

	    return $result;
    }

    public function getBookingForReviewMail($id) {
        $currentDate    = date('Y-m-d');
		$endDay         = date('Y-m-d', strtotime('-'.Constants::REVIEW_SEND_EMAIL_AFTER_DAYS.' day', strtotime(date($currentDate))));
		$weekBefore     = date('Y-m-d', strtotime('-'.Constants::REVIEW_SEND_AN_EMAIL_TO_DAYS.' day', strtotime(date($currentDate))));

        $result = $this->fetchAll(function (Select $select) use ($id, $endDay, $weekBefore) {

            $select->columns([
                'id',
                'guest_email',
                'secondary_email',
                'res_number',
                'apartment_id_assigned',
                'guest_language_iso',
                'status',
                'review_page_hash',
                'guest_first_name',
                'guest_last_name',
                'city_name' => 'acc_city_name',
                'is_refundable',
                'refundable_before_hours',
                'penalty',
                'penalty_val',
                'partner_id',
                'guest_currency_code',
                'date_from',
            ]);

            $select->join(
                ['apartments' => DbTables::TBL_APARTMENTS],
                $this->getTable() . '.apartment_id_assigned = apartments.id',
                ['acc_name' => 'name']
            );

            $select->join(
                array('partner' => DbTables::TBL_BOOKING_PARTNERS),
                $this->getTable().'.partner_id = partner.gid',
                array()
            );

            $select->join(
                ['country' => DbTables::TBL_COUNTRIES],
                $this->getTable().'.acc_country_id = country.id',
                ['phone1' => 'contact_phone'],
                Select::JOIN_LEFT
            );

            $select->join(
                ['country2' => DbTables::TBL_COUNTRIES],
                $this->getTable() . '.guest_country_id = country2.id',
                ['phone2' => 'contact_phone'],
                Select::JOIN_LEFT
            );

            if ($id) {
                $select->where
                        ->equalTo($this->getTable().'.id', $id);
            } else {
                $select->where
                        ->equalTo($this->getTable().'.status', BookingService::BOOKING_STATUS_BOOKED)
                        ->equalTo($this->getTable().'.review_mail_sent', 0)
                        ->equalTo('partner.customer_email', 1)
                        ->between($this->getTable().'.date_to', $weekBefore, $endDay);
            }
        });

        return $result;
    }

    /**
     * @param int $id
     * @return \DDD\Domain\Booking\KeyInstructionPage[]|\ArrayObject
     */
    public function getBookingForKeyInstructionMail($id) {
        $currentDate    = date('Y-m-d');
        $yesterday      = date('Y-m-d',strtotime(date("Y-m-d", strtotime($currentDate)) . " -1 day"));
		$checkinDate    = date('Y-m-d',strtotime(date("Y-m-d", strtotime($currentDate)) . " +5 day"));
		$checkinMonth   = date('M', strtotime($checkinDate));

        $result = $this->fetchAll(function (Select $select) use ($id, $checkinDate, $checkinMonth, $currentDate, $yesterday) {
            $select->columns(array(
                'id'                    => 'id',
                'guest_email'           => 'guest_email',
                'secondary_email'       => 'secondary_email',
                'res_number'            => 'res_number',
                'ki_page_hash'          => 'ki_page_hash',
                'apartment_id_assigned' => 'apartment_id_assigned',
                'guest_language_iso',
                'guest_first_name',
                'guest_last_name',
                'date_from'               => 'date_from',
                'date_to'                 => 'date_to',
                'acc_city_id'             => 'acc_city_id',
                'acc_city_name'           => 'acc_city_name',
                'pin'                     => 'pin',
                'is_refundable',
                'refundable_before_hours',
                'penalty',
                'penalty_val',
                'partner_id',
                'guest_currency_code',
                'date_from'
            ));

            $select->join(
                ['apartments' => DbTables::TBL_APARTMENTS],
                $this->getTable() . '.apartment_id_assigned = apartments.id',
                ['acc_name' => 'name']
            );

            $select->join(
                array('partner' => DbTables::TBL_BOOKING_PARTNERS),
                $this->getTable().'.partner_id = partner.gid',
                array()
            );
            $select->join(
                ['country' => DbTables::TBL_COUNTRIES],
                $this->getTable() . '.acc_country_id = country.id',
                ['phone1' => 'contact_phone'],
                Select::JOIN_LEFT
            );
            $select->join(
                ['country2' => DbTables::TBL_COUNTRIES],
                $this->getTable() . '.guest_country_id = country2.id',
                ['phone2' => 'contact_phone'],
                Select::JOIN_LEFT
            );

            if ($id) {
                $select->where
                        ->equalTo($this->getTable().'.id', $id);
            } else {
                //#agodatest
                $select->where->or->nest
                    ->equalTo($this->getTable().'.status', BookingService::BOOKING_STATUS_BOOKED)
                    ->equalTo('partner.customer_email', 1)
                    ->notEqualTo($this->getTable().'.overbooking_status', BookingTicketService::OVERBOOKING_STATUS_OVERBOOKED)
                    ->equalTo($this->getTable().'.ki_mail_sent', 0)
                    ->equalTo($this->getTable().'.partner_id', Constants::AGODA_PARTNER_ID)
                    ->equalTo($this->getTable().'.check_charged', 1)
                    ->between($this->getTable().'.date_from', $yesterday, $checkinDate)
                    ->unnest();

                $select->where->or->nest
                    ->equalTo($this->getTable().'.status', BookingService::BOOKING_STATUS_BOOKED)
                    ->equalTo('partner.customer_email', 1)
                    ->notEqualTo($this->getTable().'.overbooking_status', BookingTicketService::OVERBOOKING_STATUS_OVERBOOKED)
                    ->equalTo($this->getTable().'.ki_mail_sent', 0)
                    ->notEqualTo($this->getTable().'.partner_id', Constants::AGODA_PARTNER_ID)
                    ->greaterThanOrEqualTo($this->getTable().'.guest_balance', 0)
                    ->equalTo($this->getTable().'.check_charged', 1)
                    ->between($this->getTable().'.date_from', $yesterday, $checkinDate)
                    ->unnest();
            }

        });

        return $result;
    }

    /**
     * Get data for booking ticket by reservation number
     *
     * @param string $resNumber
     * @return BookingTicket
     */
    function getBookingByResNumber($resNumber) {
    	$this->resultSetPrototype->setArrayObjectPrototype(new \DDD\Domain\Booking\BookingTicket());

        $result = $this->fetchOne(function (Select $select) use ($resNumber) {
            $select
                ->columns([
                    'id'                      => 'id',
                    'res_number'              => 'res_number',
                    'status'                  => 'status',
                    'overbooking_status'      => 'overbooking_status',
                    'date_from'               => 'date_from',
                    'date_to'                 => 'date_to',
                    'channel_name'            => 'channel_name',

                    'apartment_id_assigned'   => 'apartment_id_assigned',
                    'acc_name'                => 'acc_name',
                    'acc_city_id'             => 'acc_city_id',
                    'acc_address'             => 'acc_address',
                    'apartment_currency_code',

                    'room_id'                 => 'room_id',
                    'rate_name'               => 'rate_name',

                    'currency_rate'           => 'currency_rate',
                    'price'                   => 'price',

                    // Guest details
                    'customer_id'             => 'customer_id',
                    'guest_first_name',
                    'guest_last_name',
                    'guest_email'             => 'guest_email',
                    'secondary_email'         => 'secondary_email',
                    'guest_country_id',
                    'guest_city_name',
                    'guest_address',
                    'guest_zip_code',
                    'guest_phone',
                    'guest_travel_phone',
                    'guest_arrival_time'      => 'guest_arrival_time',
                    'guest_currency_code',
                    'guest_language_iso',

                    // Credit Card Validation
                    'provide_cc_page_hash'    => 'provide_cc_page_hash',

                    // Settled
                    'payment_settled'         => 'payment_settled',
                    'settled_date'            => 'settled_date',
                    'provide_cc_page_status'  => 'provide_cc_page_status',
                    'timestamp'               => 'timestamp',
                    'commission'              => 'commission',
                    'booker_price'            => 'booker_price',
                    'occupancy'               => 'occupancy',
                    'pax'                     => 'man_count',
                    'partner_id'              => 'partner_id',
                    'partner_ref'             => 'partner_ref',
                    'partner_commission'      => 'partner_commission',
                    'partner_settled'         => 'partner_settled',
                    'cancelation_date'        => 'cancelation_date',
                    'model'                   => 'model',
                    'funds_confirmed'         => 'funds_confirmed',
                    'transaction_fee_percent' => 'transaction_fee_percent',
                    'penalty'                 => 'penalty',
                    'is_refundable'           => 'is_refundable',
                    'refundable_before_hours' => 'refundable_before_hours',
                    'penalty_val'             => 'penalty_val',
                    'penalty_fixed_amount'    => 'penalty_fixed_amount',
                    'no_collection'           => 'no_collection',
                    'outside_door_code'       => 'outside_door_code',
                    'pin'                     => 'pin',
                    'ki_viewed'               => 'ki_viewed',
                    'review_page_hash'        => 'review_page_hash',
                    'ki_page_hash'            => 'ki_page_hash',
                    'arrival_status'          => 'arrival_status',
                    'ki_mail_sent'            => 'ki_mail_sent',
                    'ki_page_status'          => 'ki_page_status',
                    'ki_viewed_date'          => 'ki_viewed_date',
                    'apartel_id'              => 'apartel_id',
                    'unit'                    => 'unit_number',
                    'rate_capacity'           => 'man_count',
                    'ccca_verified'           => 'ccca_verified',
                    'locked'                  => 'locked',
                    'channel_res_id'          => 'channel_res_id',
                    'check_charged'
                ])
                ->join(
                    ['prod_general_for_origin' => DbTables::TBL_APARTMENTS],
                    $this->getTable() . '.apartment_id_origin = prod_general_for_origin.id',
                    ['apartment_origin_unit' => 'unit_number', 'bedroom_count'],
                    Select::JOIN_LEFT
                )
                ->join(
                    ['ap_group_for_origin' => DbTables::TBL_APARTMENT_GROUPS],
                    'prod_general_for_origin.building_id = ap_group_for_origin.id',
                    ['apartment_origin_building' => 'name'],
                    Select::JOIN_LEFT
                )
                ->join(
                    ['prod_general' => DbTables::TBL_APARTMENTS],
                    $this->getTable() . '.apartment_id_assigned = prod_general.id',
                    [
                        'apartment_assigned_block' => 'block',
                        'apartment_assigned_unit'  => 'unit_number',
                        'apartment_assigned'       => 'name',
                        'bedroom_count_assigned'   => 'bedroom_count',
                        'apartment_capacity'       => 'max_capacity'
                    ],
                    Select::JOIN_LEFT
                )
                ->join(
                    ['ap_group' => DbTables::TBL_APARTMENT_GROUPS],
                    'prod_general.building_id = ap_group.id',
                    [
                        'apartment_assigned_building'    => 'name',
                        'apartment_assigned_building_id' => 'id',
                        'usage_building'                 => 'usage_building'
                    ],
                    Select::JOIN_LEFT
                )
                ->join(
                    ['city' => DbTables::TBL_CITIES],
                    $this->getTable() . '.acc_city_id = city.id',
                    ['timezone'],
                    Select::JOIN_LEFT
                )
                ->join(
                    ['location_detail' => DbTables::TBL_LOCATION_DETAILS],
                    'city.detail_id = location_detail.id',
                    [
                        'tot',
                        'tot_type',
                        'tot_included',
                        'tot_additional',
                        'tot_max_duration',
                        'vat',
                        'vat_type',
                        'vat_included',
                        'vat_additional',
                        'vat_max_duration',
                        'sales_tax',
                        'sales_tax_type',
                        'sales_tax_included',
                        'sales_tax_additional',
                        'sales_tax_max_duration',
                        'city_tax',
                        'city_tax_type',
                        'city_tax_included',
                        'city_tax_additional',
                        'city_tax_max_duration',
                    ],
                    Select::JOIN_LEFT
                )
                ->join(
                    ['country' => DbTables::TBL_COUNTRIES],
                    $this->getTable() . '.acc_country_id = country.id',
                    [],
                    Select::JOIN_LEFT
                )
                ->join(
                    ['country_currency_tbl' => DbTables::TBL_CURRENCY],
                    'country.currency_id = country_currency_tbl.id',
                    ['country_currecny' => 'code'],
                    Select::JOIN_LEFT
                )
                ->join(
                    ['acc_desc' => DbTables::TBL_PRODUCT_DESCRIPTIONS],
                    $this->getTable() . '.apartment_id_assigned = acc_desc.apartment_id',
                    ['check_in' => 'check_in'],
                    Select::JOIN_LEFT
                )
                ->join(
                    ['currency1' => DbTables::TBL_CURRENCY],
                    $this->getTable() . '.guest_currency_code = currency1.code',
                    ['customer_currency_rate' => 'value'],
                    Select::JOIN_LEFT
                )
                ->join(
                    ['currency2' => DbTables::TBL_CURRENCY],
                    $this->getTable() . '.apartment_currency_code = currency2.code',
                    ['acc_currency_rate' => 'value', 'acc_currency_sign' => 'code'],
                    Select::JOIN_LEFT
                )
                ->join(
                    ['charge' => DbTables::TBL_CHARGE],
                    new Expression($this->getTable() . '.id = charge.reservation_id AND charge.addons_type = ' . BookingAddon::ADDON_TYPE_PARKING . ' AND charge.status = 0'),
                    ['parking' => 'id'],
                    Select::JOIN_LEFT
                )
                ->join(
                    ['back_list' => DbTables::TBL_BLACK_LIST],
                    $this->getTable() . '.id = back_list.reservation_id',
                    ['black_res' => 'id'],
                    Select::JOIN_LEFT
                )
                ->join(
                    ['customer_identity' => DbTables::TBL_CUSTOMER_IDENTITY],
                    $this->getTable() . '.id = customer_identity.reservation_id',
                    ['ip_address'],
                    Select::JOIN_LEFT
                )
            ->join(
                ['partner' => DbTables::TBL_BOOKING_PARTNERS],
                $this->getTable() . '.partner_id = partner.gid',
                ['partner_tax_commission' => 'additional_tax_commission'],
                Select::JOIN_INNER
            );

            $select->where->equalTo($this->getTable().'.res_number', $resNumber);
        });

        return $result;
    }

    /**
     * @param $id
     * @return array|\ArrayObject|null
     */
    public function getBasicInfoById($id)
    {
        $this->resultSetPrototype->setArrayObjectPrototype(new ArrayObject());

        $result = $this->fetchOne(function (Select $select) use ($id) {
            $select->columns([
                'id' 					=> 'id',
                'res_number' 			=> 'res_number',
                'date_from'				=> 'date_from',
                'date_to'				=> 'date_to',
                'apartment_id_assigned' => 'apartment_id_assigned',
                'occupancy'
            ]);
            $select->join(
                ['apartments' => DbTables::TBL_APARTMENTS],
                $this->getTable() . '.apartment_id_assigned = apartments.id',
                ['apartment_name' => 'name']
            );

            $select->where->equalTo($this->getTable().'.id', $id);
        });

        return $result;
    }

    /**
     * @param $id
     * @return array|\ArrayObject|null
     */
    public function getBasicInfoForAutoTaskCreationById($id)
    {
        $this->resultSetPrototype->setArrayObjectPrototype(new ArrayObject());

        $result = $this->fetchOne(function (Select $select) use ($id) {
            $select->columns([
                'id' 					=> 'id',
                'date_from'				=> 'date_from',
                'date_to'				=> 'date_to',
                'apartment_id_assigned' => 'apartment_id_assigned',
                'guest_first_name',
                'guest_last_name',
                'guest_phone',
                'gem_id'                => 'gem_id'
            ]);
            $select->join(
                ['apartments' => DbTables::TBL_APARTMENTS],
                $this->getTable() . '.apartment_id_assigned = apartments.id',
                [
                    'apartment_name' => 'name',
                    'building_id'    => 'building_id',
                    'city_id'        => 'city_id'
                ]
            );
            $select->join(
                ['cities' => DbTables::TBL_CITIES],
                'apartments.city_id = cities.id',
                []
            );
            $select->join(
                ['locations' => DbTables::TBL_LOCATION_DETAILS],
                'locations.id = cities.detail_id',
                ['city_name' => 'name']
            );

            $select->where->equalTo($this->getTable().'.id', $id);
        });

        return $result;
    }

    public function isApartmentBookedByDay($apartmentId, $date)
    {
        $result = $this->fetchOne(function (Select $select) use ($apartmentId, $date) {
            $select->columns([
                'id'
            ]);

            $select->where->equalTo($this->getTable().'.apartment_id_assigned', $apartmentId);
            $select->where->lessThanOrEqualTo($this->getTable().'.date_from', $date);
            $select->where->greaterThan($this->getTable().'.date_to', $date);
            $select->where->equalTo($this->getTable().'.status', \DDD\Service\Booking::BOOKING_STATUS_BOOKED);
        });

        if ($result) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Get data for booking ticket by reservation number
     *
     * @param int $id
     * @return \DDD\Domain\Booking\BookingTicket
     */
    function getReservationById($id)
    {
    	$this->resultSetPrototype->setArrayObjectPrototype(new \DDD\Domain\Booking\BookingTicket());

        $result = $this->fetchOne(function (Select $select) use ($id) {
        	$select->columns(array(
	            'id' 					=> 'id',
	            'res_number'    		=> 'res_number',
	        	'status'				=> 'status',
                'overbooking_status'    => 'overbooking_status',
	        	'date_from'				=> 'date_from',
	        	'date_to'				=> 'date_to',
	        	'channel_name'    		=> 'channel_name',

	        	'apartment_id_assigned' => 'apartment_id_assigned',
                'apartment_id_origin' => 'apartment_id_origin',
	        	'acc_name'              => 'acc_name',
	        	'acc_city_id'           => 'acc_city_id',
	        	'acc_address'           => 'acc_address',
	        	'apartment_currency_code',

	        	'room_id'               =>'room_id',
	        	'rate_name'             => 'rate_name',

	        	'currency_rate'         => 'currency_rate',

	        	// Guest details
	            'guest_first_name',
	            'guest_last_name',
	            'guest_email'           => 'guest_email',
	            'guest_country_id',
	            'guest_city_name',
	            'guest_address',
	            'guest_zip_code',
	            'guest_phone',
	            'guest_travel_phone',
	        	'guest_arrival_time'    => 'guest_arrival_time',
	        	'guest_currency_code',
	            'guest_language_iso',

				// Credit Card Validation
	            'provide_cc_page_hash'      => 'provide_cc_page_hash',

				// Settled
	        	'payment_settled'		=> 'payment_settled',
	            'settled_date'			=> 'settled_date',

	            'provide_cc_page_status'		=> 'provide_cc_page_status',
	            'timestamp'				=> 'timestamp',
	            'commission'			=> 'commission',

	            'price'					=> 'price',
	            'booker_price'			=> 'booker_price',

	            'pax'                   => 'man_count',
	            'partner_id'			=> 'partner_id',
	            'partner_ref'			=> 'partner_ref',
	            'partner_commission'	=> 'partner_commission',
	            'partner_settled'		=> 'partner_settled',
	            'cancelation_date'		=> 'cancelation_date',
	            'model'                 => 'model',
	            'funds_confirmed'       => 'funds_confirmed',
	            'transaction_fee_percent'    => 'transaction_fee_percent',
	            'penalty'               => 'penalty',
	            'is_refundable'                => 'is_refundable',
	            'refundable_before_hours'      => 'refundable_before_hours',
	            'penalty_val'           => 'penalty_val',
	            'penalty_fixed_amount'  => 'penalty_fixed_amount',
	            'no_collection'         => 'no_collection',
	            'outside_door_code'     => 'outside_door_code',
	            'pin'                   => 'pin',
	            'ki_viewed'             => 'ki_viewed',
	            'review_page_hash'      => 'review_page_hash',
	            'ki_page_hash'          => 'ki_page_hash',
	            'arrival_status'        => 'arrival_status',
	            'ki_mail_sent'          => 'ki_mail_sent',
	            'ki_page_status'        => 'ki_page_status',
	            'ki_viewed_date'        => 'ki_viewed_date',
        		'apartel_id'            => 'apartel_id',
                'unit'                  => 'unit_number'
            ));

            $select->join(
                    array('prod_general' => DbTables::TBL_APARTMENTS),
                    $this->getTable().'.apartment_id_assigned = prod_general.id',
                    array('apartment_assigned'=>'name', 'apartment_unit' => 'unit_number', 'apartment_assigned_building_id' => 'building_id'));

            $select->join(
                    array('ap_group' => DbTables::TBL_APARTMENT_GROUPS),
                    'prod_general.building_id = ap_group.id',
                    array(
                        'building'       => 'name',
                        'usage_building' => 'usage_building'
                    ),
                    $select::JOIN_LEFT);

	        $select->join(
                    array('city' => DbTables::TBL_CITIES),
                    $this->getTable().'.acc_city_id = city.id',
                    array(),
                    Select::JOIN_LEFT);

	        $select->join(
                    array('location_detail' => DbTables::TBL_LOCATION_DETAILS),
                    'city.detail_id = location_detail.id',
                    array(
                        'tot',
                        'vat',
                        'sales_tax',
                        'city_tax'
                        ),
                    Select::JOIN_LEFT);

	        $select->join(
	        	['building_det' => DbTables::TBL_BUILDING_DETAILS],
	        	'prod_general.building_id = building_det.apartment_group_id',
	        	[
                    'ki_page_type'  => 'ki_page_type'
	        	],
	        	Select::JOIN_LEFT);

	        $select->join(
                    array('acc_desc' => DbTables::TBL_PRODUCT_DESCRIPTIONS),
                    $this->getTable().'.apartment_id_assigned = acc_desc.apartment_id',
                    array('check_in'=>'check_in', 'check_out'=>'check_out'),
                    Select::JOIN_LEFT);

	        $select->join(
                    array('currency1' => DbTables::TBL_CURRENCY),
                    $this->getTable().'.guest_currency_code = currency1.code',
	                array(
                        'customer_currency_rate'=>'value'
                        ),
                    Select::JOIN_LEFT);

	        $select->join(
                    array('currency2' => DbTables::TBL_CURRENCY),
                    $this->getTable().'.apartment_currency_code = currency2.code',
	                array(
                        'acc_currency_rate'=>'value',
                        'acc_currency_sign' => 'code'
                        ),
                    Select::JOIN_LEFT);

	        $select->join(
                    array('charge' => DbTables::TBL_CHARGE),
                    new Expression(
                            $this->getTable().'.id = charge.reservation_id AND charge.addons_type = 6 AND charge.status = 0'
                            ),
	                array(
                        'parking'=>'id'
                        ),
                    Select::JOIN_LEFT);

	        $select->join(
                    array('back_list' => DbTables::TBL_BLACK_LIST),
                    $this->getTable().'.id = back_list.reservation_id',
                    array(
                        'black_res'=>'id'
                        ),
                    Select::JOIN_LEFT);

	        $select->join(
                ['customer_identity' => DbTables::TBL_CUSTOMER_IDENTITY],
                $this->getTable().'.id = customer_identity.reservation_id',
                [ 'IP' => 'ip_address'],
                Select::JOIN_LEFT
            );

        	$select->where->equalTo($this->getTable().'.id', $id);
        });

        return $result;
    }

    /**
     * @param string $reservationNumber
     * @return ForCancel|bool
     */
    public function getBookingForCancel($reservationNumber)
    {
        return $this->fetchOne(function (Select $select) use ($reservationNumber) {
	        $select->columns(array(
	            'id'                    => 'id',
	            'res_number'            => 'res_number',
	            'status'                => 'status',
	            'room_id'               => 'room_id',
	            'date_from'             => 'date_from',
	            'date_to'               => 'date_to',
	            'is_refundable'         => 'is_refundable',
	            'guest_currency_code',
	            'apartment_id_assigned' => 'apartment_id_assigned',
	            'apartment_currency_code',
	            'penalty_fixed_amount'  => 'penalty_fixed_amount',
	            'currency_rate'         => 'currency_rate',
	            'refundable_before_hours'  => 'refundable_before_hours',
	            'penalty_hours'         => new Expression('TIMESTAMPDIFF(HOUR,NOW(),date_from)'),
	            'price'                 => 'price',
	        	'model'				    => 'model',
	        	'partner_commission'	=> 'partner_commission',
	        	'affiliate_id'		    => 'partner_id',
                'is_overbooking'        => 'overbooking_status',
                'arrival_status'        => 'arrival_status'
	        ));

            $select->join(
                ['apartments' => DbTables::TBL_APARTMENTS],
                $this->getTable() . '.apartment_id_assigned = apartments.id',
                ['apartment_status' => 'status'],
                Select::JOIN_LEFT
            );

        	$select->join(
                ['cities' => DbTables::TBL_CITIES],
                $this->getTable() . '.acc_city_id = cities.id',
                [],
                Select::JOIN_LEFT
            );

            $select->join(
                ['loc_details' => DbTables::TBL_LOCATION_DETAILS],
                'cities.detail_id = loc_details.id',
                ['tot', 'vat', 'sales_tax', 'city_tax'],
                Select::JOIN_LEFT
            );

        	$select->where->equalTo($this->getTable() . '.res_number', $reservationNumber);
       });
    }

    /**
     * @param int $apartmentId
     * @param string $dateFrom
     * @return \DDD\Domain\Booking\Booking
     */
    public function getFollowingReservationId($apartmentId, $dateFrom) {

        $result = $this->fetchOne(function (Select $select) use ($apartmentId, $dateFrom) {
            $select->columns(['id']);

            $select->where
                    ->equalTo('apartment_id_assigned', $apartmentId)
                    ->and
                    ->greaterThanOrEqualTo('date_from', $dateFrom);

            $select->order('date_from ASC');
        });

        return $result;
    }

    /**
     * @param int $apartmentId
     * @param string $dateFrom
     * @param boolean  $getJustReservation
     * @return \ArrayObject | \DDD\Domain\Booking\Booking
     */
    public function getPreviousReservation($apartmentId, $dateFrom) {
        $result = $this->fetchOne(function (Select $select) use ($apartmentId, $dateFrom) {
            $select->where
                    ->equalTo($this->getTable().'.apartment_id_assigned', $apartmentId)
                    ->lessThanOrEqualTo($this->getTable().'.date_to', $dateFrom)
                    ->equalTo($this->getTable().'.status', BookingService::BOOKING_STATUS_BOOKED);

            $select->order('date_to DESC');

        });

        return $result;
    }

    /**
     * Get statuses of given customer reservations to count booked and cancelled reservations
     * @param string $email Email address provided by customer
     * @return PrepareData
     */
    public function getCustomerReservationsStatuses($email) {
        $result = $this->fetchAll(function (Select $select) use ($email) {
            $select->columns(array(
                                    'status',
                                    'guest_balance',
                                    'res_number'
                                  ));
            $select->where
                    ->equalTo('guest_email', $email)
                    ->expression(
                        'apartment_id_assigned NOT IN(' .
                        Constants::TEST_APARTMENT_1 . ', '.
                        Constants::TEST_APARTMENT_2 .')',
                        []
                    );
        });
        return $result;
    }

    public function getBookingForReservationConfirmationMail($id)
    {
        $result = $this->fetchAll(function (Select $select) use ($id) {
            $where = new Where();
            $where->in($this->getTable() . '.id', $id);
            $select
                ->columns([
                    'id',
                    'apartment_id_assigned',
                    'res_number',
                    'date_from',
                    'date_to',
                    'guest_language_iso',
                    'guest_first_name',
                    'guest_last_name',
                    'guest_email',
                    'secondary_email',
                    'overbooking_status',
                    'guest_arrival_time' => 'guest_arrival_time',
                    'pax' => 'man_count',
                    'occupancy' => 'occupancy',
                    'price',
                    'booker_price',
                    'penalty',
                    'penalty_val',
                    'apartment_currency_code',
                    'currency_rate',
                    'refundable_before_hours',
                    'guest_currency_code',
                    'acc_city_id',
                    'acc_country_id',
                    'guest_phone',
                    'model',
                    'review_page_hash',
                    'rate_name',
                    'is_refundable',
                    'partner_id',
                    'guest_balance',
                    'rate_capacity' => 'man_count',
                    'guest_address'
                ])
                ->join(
                    ['apartments' => DbTables::TBL_APARTMENTS],
                    $this->getTable() . '.apartment_id_assigned = apartments.id',
                    ['acc_name' => 'name', 'apartment_assigned_postal_code' => 'postal_code', 'apartment_assigned_address' => 'address'],
                    Select::JOIN_INNER
                )
                ->join(
                    ['apartment_description' => DbTables::TBL_PRODUCT_DESCRIPTIONS],
                    'apartments.id = apartment_description.apartment_id',
                    ['check_in', 'check_out'],
                    Select::JOIN_INNER
                )
                ->join(
                    ['apartment_detail' => DbTables::TBL_APARTMENTS_DETAILS],
                    'apartments.id = apartment_detail.apartment_id',
                    ['cleaning_fee'],
                    Select::JOIN_INNER
                )
                ->join(
                    ['city' => DbTables::TBL_CITIES],
                    $this->getTable() . '.acc_city_id = city.id',
                    [],
                    Select::JOIN_LEFT
                )
                ->join(
                    ['location_details' => DbTables::TBL_LOCATION_DETAILS],
                    'city.detail_id = location_details.id',
                    [
                        'city_tot' => 'tot',
                        'city_tot_type' => 'tot_type',
                        'tot_included' => 'tot_included',
                        'city_vat' => 'vat',
                        'city_vat_type' => 'vat_type',
                        'vat_included' => 'vat_included',
                        'city_tax' => 'city_tax',
                        'city_tax_type' => 'city_tax_type',
                        'city_tax_included' => 'city_tax_included',
                        'city_sales_tax' => 'sales_tax',
                        'city_sales_tax_type' => 'sales_tax_type',
                        'sales_tax_included' => 'sales_tax_included',
                        'apartment_city_thumb' => 'thumbnail',
                    ],
                    Select::JOIN_LEFT
                )
                ->join(
                    ['country' => DbTables::TBL_COUNTRIES],
                    $this->getTable().'.acc_country_id = country.id',
                    ['country_phone_apartment' => 'contact_phone'],
                    Select::JOIN_LEFT
                )->join(
                    ['country2' => DbTables::TBL_COUNTRIES],
                    $this->getTable() . '.guest_country_id = country2.id',
                    ['country_phone_guest' => 'contact_phone'],
                    Select::JOIN_LEFT
                )
                ->join(
                    ['country_currency' => DbTables::TBL_CURRENCY],
                    'country.currency_id = country_currency.id',
                    ['country_currency' => 'code'],
                    Select::JOIN_LEFT
                )
                ->join(
                    ['log' => DbTables::TBL_ACTION_LOGS],
                    new Expression($this->getTable() . '.id = log.identity_id AND log.module_id = ' .
                        Logger::MODULE_BOOKING . ' AND log.action_id = ' . Logger::ACTION_COMMENT .
                        ' AND log.user_id = ' . UserService::USER_GUEST),
                    ['remarks' => 'value'],
                    Select::JOIN_LEFT
                )
                ->join(
                    ['partner' => DbTables::TBL_BOOKING_PARTNERS],
                    $this->getTable() . '.partner_id = partner.gid',
                    ['emailing_enabled' => 'customer_email', 'partner_name'],
                    Select::JOIN_INNER
                )
                ->where($where)
                ->order('date_from');
        });

        return $result;
    }

    public function getBookingForGinosiReservationMail($id)
    {
        $result = $this->fetchAll(function (Select $select) use ($id) {
            $select->columns(array(
                'id',
                'apartment_id_assigned',
                'res_number',
                'date_from',
                'date_to',
                'guest_language_iso',
                'guest_first_name',
                'guest_last_name',
                'guest_email',
                'secondary_email',
                'overbooking_status',
                'guest_arrival_time' => 'guest_arrival_time',
                'pax' => 'man_count',
                'price',
                'booker_price',
                'penalty',
                'penalty_val',
                'apartment_currency_code',
                'currency_rate',
                'refundable_before_hours',
                'guest_currency_code',
                'acc_city_id',
                'acc_country_id',
                'guest_phone',
                'model',
                'review_page_hash',
                'rate_name',
                'partner_id',
                'channel_name',
                'occupancy',
                'is_refundable',
                'guest_balance',
                'rate_capacity' => 'man_count'
            ));

            $select->join(
                ['apartments' => DbTables::TBL_APARTMENTS],
                $this->getTable() . '.apartment_id_assigned = apartments.id',
                ['acc_name' => 'name']
            );

            $select->join(
                ['desc' => DbTables::TBL_PRODUCT_DESCRIPTIONS],
                $this->getTable().'.apartment_id_assigned = desc.apartment_id',
                ['check_in', 'check_out'],
                Select::JOIN_LEFT
            );

            $select->join(
                ['city' => DbTables::TBL_CITIES],
                $this->getTable().'.acc_city_id = city.id',
                [],
                Select::JOIN_LEFT
            );

            $select->join(
                ['details' => DbTables::TBL_LOCATION_DETAILS],
                'city.detail_id = details.id',
                [
                    'city_tot' => 'tot',
                    'city_vat' => 'vat',
                    'city_sales_tax' => 'sales_tax',
                    'city_tax' => 'city_tax',
                    'tot_included' => 'tot_included',
                    'vat_included' => 'vat_included',
                    'city_tax_included' => 'city_tax_included',
                    'sales_tax_included' => 'sales_tax_included',
                    'city_tot_type' => 'tot_type',
                    'city_vat_type' => 'vat_type',
                    'city_sales_tax_type' => 'sales_tax_type',
                    'city_tax_type' => 'city_tax_type'
                ],
                Select::JOIN_LEFT
            );
            $select->join(
                ['country' => DbTables::TBL_COUNTRIES],
                $this->getTable().'.acc_country_id = country.id',
                ['phone1' => 'contact_phone'],
                Select::JOIN_LEFT
            );

            $select->join(
                ['country2' => DbTables::TBL_COUNTRIES],
                $this->getTable() . '.guest_country_id = country2.id',
                ['phone2' => 'contact_phone'],
                Select::JOIN_LEFT
            );

            $select->join(
                ['country_currency' => DbTables::TBL_CURRENCY],
                'country.currency_id = country_currency.id',
                ['country_currency' => 'code'],
                Select::JOIN_LEFT
            );
            $select->join(
                ['log' => DbTables::TBL_ACTION_LOGS],
                new Expression($this->getTable() . '.id = log.identity_id AND log.module_id = ' .
                    Logger::MODULE_BOOKING . ' AND log.action_id = ' . Logger::ACTION_COMMENT .
                    ' AND log.user_id = ' . UserService::USER_GUEST),
                ['remarks' => 'value'],
                Select::JOIN_LEFT
            );

            $select->where->in($this->getTable().'.id', $id);

            $select->order('date_from');
        });

        return $result;
    }

    public function getBookingInfoForModificationMail($id) {
        $result = $this->fetchAll( function (Select $select) use ($id) {
            $select->columns(array(
                'id',
                'res_number',
                'guest_language_iso',
                'guest_email',
                'guest_first_name',
                'guest_last_name',
                'provide_cc_page_hash',
                'date_from',
                'partner_id',
                'apartment_id_assigned',
                'acc_city_name'
            ));

            $select->join(
                ['country' => DbTables::TBL_COUNTRIES],
                $this->getTable().'.acc_country_id = country.id',
                ['phone1' => 'contact_phone'],
                Select::JOIN_LEFT
            );

            $select->join(
                ['country2' => DbTables::TBL_COUNTRIES],
                $this->getTable() . '.guest_country_id = country2.id',
                ['phone2' => 'contact_phone'],
                Select::JOIN_LEFT
            );

            $select->where
                    ->equalTo( $this->getTable().'.id', $id);
        });

        return $result;
    }

    public function getBookingInfoForCancellationMail($id)
    {
        $result = $this->fetchAll( function (Select $select) use ($id) {
            $select->columns([
                'id',
                'res_number',
                'guest_language_iso',
                'guest_email',
                'guest_first_name',
                'guest_last_name',
                'partner_id',
                'apartment_id_assigned',
                'rate_name',
                'pax' => 'man_count',
                'date_from',
                'date_to',
                'price',
                'apartment_currency_code',
                'currency_rate_usd',
                'booker_price',
                'guest_currency_code',
                'currency_rate',
                'is_refundable',
                'penalty',
                'penalty_val',
                'penalty_fixed_amount',
                'refundable_before_hours',
                'status',
                'penalty_bit',
                'refundable_before_hours',
                'overbooking_status',
                'guest_balance'
            ])->join(
                ['apartment_description' => DbTables::TBL_PRODUCT_DESCRIPTIONS],
                $this->getTable() . '.apartment_id_assigned = apartment_description.apartment_id',
                [
                    'check_in',
                    'check_out'
                ],
                $select::JOIN_LEFT
            )->join(
                ['apartments' => DbTables::TBL_APARTMENTS],
                $this->getTable() . '.apartment_id_assigned = apartments.id',
                ['acc_name' => 'name']
            )->join(
                ['country' => DbTables::TBL_COUNTRIES],
                $this->getTable() . '.acc_country_id = country.id',
                ['phone1' => 'contact_phone'],
                Select::JOIN_LEFT
            )->join(
                ['country2' => DbTables::TBL_COUNTRIES],
                $this->getTable() . '.guest_country_id = country2.id',
                ['phone2' => 'contact_phone'],
                Select::JOIN_LEFT
            );

            $select->where
                    ->equalTo($this->getTable() . '.id', $id);
        });

        return $result;
    }





/********************************These 3 methods needs to be refactored*********************************/


    /**
     * @return SaleStatisticsItem[]
     */
    public function getLast30Reservations()
    {
        $this->resultSetPrototype->setArrayObjectPrototype(new SaleStatisticsItem());

		$result = $this->fetchAll(function (Select $select) {
			$select->where
				->greaterThan('timestamp', new Expression('DATE_ADD(CURDATE(), INTERVAL -30 DAY)'))
				->equalTo('status', BookingService::BOOKING_STATUS_BOOKED)
				->notIn(
                    'apartment_id_assigned',
                    [
                        Constants::TEST_APARTMENT_1,
                        Constants::TEST_APARTMENT_2
                    ]
                );

			$select->columns([
				'reservation_id'                            => 'id',
                'reservation_number'                        => 'res_number',
				'reservation_price_in_apartment_currency'   => 'price',
				'apartment_currency_iso_code'               => 'apartment_currency_code',
			]);

			$select->order('timestamp DESC');
		});

		return $result;
	}

    /**
     * @return SaleStatisticsItem[]
     */
    public function getTodayReservations()
    {
        $this->resultSetPrototype->setArrayObjectPrototype(new SaleStatisticsItem());

		$result = $this->fetchAll(function (Select $select) {
			$select->where
				->greaterThanOrEqualTo('timestamp', new Expression('CURDATE()'))
				->equalTo('status', BookingService::BOOKING_STATUS_BOOKED)
				->notIn(
                    'apartment_id_assigned',
                    [
                        Constants::TEST_APARTMENT_1,
                        Constants::TEST_APARTMENT_2
                    ]
                );

            $select->columns([
                'reservation_id'                            => 'id',
                'reservation_number'                        => 'res_number',
                'reservation_price_in_apartment_currency'   => 'price',
                'apartment_currency_iso_code'               => 'apartment_currency_code',
            ]);

			$select->order('timestamp DESC');
		});

		return $result;
	}

    /**
     * @return SaleStatisticsItem[]
     */
    public function getYesterdayReservations()
    {
        $this->resultSetPrototype->setArrayObjectPrototype(new SaleStatisticsItem());

		$result = $this->fetchAll(function (Select $select) {
	    $select
            ->where
				->greaterThanOrEqualTo('timestamp', new Expression('DATE_ADD(CURDATE(), INTERVAL -1 DAY)'))
				->lessThan('timestamp', new Expression('CURDATE()'))
				->equalTo('status', 1)
				->notIn(
                    'apartment_id_assigned',
                    [
                        Constants::TEST_APARTMENT_1,
                        Constants::TEST_APARTMENT_2
                    ]
                );

            $select->columns([
                'reservation_id'                            => 'id',
                'reservation_number'                        => 'res_number',
                'reservation_price_in_apartment_currency'   => 'price',
                'apartment_currency_iso_code'               => 'apartment_currency_code',
            ]);

			$select->order('timestamp DESC');
		});

		return $result;
	}
/********************************These 3 methods needs to be refactored*********************************/





    /**
     * @param bool $reservationId
     * @return array|\DDD\Domain\Booking\FirstCharge|null|ResultSet
     */
    public function getForCharge($reservationId = false)
    {
		$this->resultSetPrototype->setArrayObjectPrototype(new \DDD\Domain\Booking\FirstCharge());

		$result = $this->fetchAll(function (Select $select) use ($reservationId) {
            $select->columns([
			   'id',
               'res_number',
               'guest_currency_code',
               'apartment_currency_code',
               'price',
               'currency_rate',
               'is_refundable',
               'refundable_before_hours',
               'date_from',
               'date_to',
               'penalty_fixed_amount',
               'model',
               'apartment_id_assigned',
               'partner_commission',
               'rate_capacity' => 'man_count',
               'occupancy',
               'guest_email',
               'partner_id',
               'partner_ref',
               'channel_name',
               'nights' => new Expression('datediff(date_to, date_from)'),
            ]);

            $select->join(
            	['city' => DbTables::TBL_CITIES],
            	$this->getTable() . '.acc_city_id = city.id',
            	[],
            	Select::JOIN_LEFT
            )->join(
            	['location_detail' => DbTables::TBL_LOCATION_DETAILS],
            	'city.detail_id = location_detail.id',
            	[
                    'tot',
                    'tot_type',
                    'tot_included',
                    'tot_additional',
                    'tot_max_duration',
                    'vat',
                    'vat_type',
                    'vat_included',
                    'vat_additional',
                    'vat_max_duration',
                    'sales_tax',
                    'sales_tax_type',
                    'sales_tax_included',
                    'sales_tax_additional',
                    'sales_tax_max_duration',
                    'city_tax',
                    'city_tax_type',
                    'city_tax_included',
                    'city_tax_additional',
                    'city_tax_max_duration',
            	],
            	Select::JOIN_LEFT
           	)->join(
           		['charge' => DbTables::TBL_CHARGE],
           		$this->getTable() . '.id = charge.reservation_id',
           		['addons_type'],
            	Select::JOIN_LEFT
            )
            ->join(
                ['logs' => DbTables::TBL_ACTION_LOGS],
                new Expression($this->getTable() . '.id = logs.identity_id AND logs.user_id = ' . UserService::USER_GUEST),
                ['remarks' => 'value'],
                Select::JOIN_LEFT
            );

            $select->join(
	        	['country' => DbTables::TBL_COUNTRIES],
	        	$this->getTable().'.acc_country_id = country.id',
	        	[],
	        	Select::JOIN_LEFT
	        );

	        $select->join(
	        	['country_currency_tbl' => DbTables::TBL_CURRENCY],
	        	'country.currency_id = country_currency_tbl.id',
	        	['country_currecny' => 'code'],
	        	Select::JOIN_LEFT
	        );

            if ($reservationId) {
                $select->where->equalTo($this->getTable().'.id', $reservationId);
            } else {
                $where = new Where();
                $where->notEqualTo($this->getTable().'.check_charged', 1)
                    ->equalTo($this->getTable().'.status', BookingService::BOOKING_STATUS_BOOKED)
                    ->isNull('charge.addons_type')
                    ->expression('(' . $this->getTable() . '.is_refundable = 2 or ' . $this->getTable() . '.refundable_before_hours >= TIMESTAMPDIFF(HOUR, NOW(), ' . $this->table . '.date_from))', array());


                $apartelChannelResPredicate = new Predicate();
                $apartelChannelResPredicate->greaterThan($this->getTable().'.apartel_id', 0)
                                           ->greaterThan($this->getTable().'.channel_res_id', 0);
                $apartelChargePredicate = new Predicate();
                $apartelChargePredicate->addPredicate($apartelChannelResPredicate);
                $apartelChargePredicate->or
                                       ->equalTo($this->getTable().'.apartel_id', 0);
                $where->addPredicate($apartelChargePredicate);
                $select->where( $where );
            }
		});

        if ($reservationId && $result->count()) {
            return $result->current();
        }

		return $result;
	}

    public function getBookingsForOmniBox($resNumber) {
        $result = $this->fetchAll(function (Select $select) use ($resNumber) {
            $select->columns(array(
                'id',
                'res_number',
                'guest_first_name',
                'guest_last_name',
                'status'
            ));

            $select->join(
                ['apartments' => DbTables::TBL_APARTMENTS],
                $this->getTable() . '.apartment_id_assigned = apartments.id',
                ['acc_name' => 'name']
            );

            $select->where
                    ->equalTo('res_number', $resNumber)
                    ->or
                    ->like('res_number', $resNumber.'%');

            $select->order('id')
                    ->limit(10);
        });

        return $result;
    }

    /**
     * @param $channelResId
     * @param $channelName
     * @return array|\ArrayObject|null
     */
	public function getResIdByChannel($channelResId, $channelName)
    {
        $this->resultSetPrototype->setArrayObjectPrototype(new \DDD\Domain\Booking\ChannelReservation());
		return $this->fetchOne(function (Select $select) use ($channelResId, $channelName) {
			$select->columns([
                'id',
                'res_number',
                'status',
                'date_from',
                'date_to',
                'funds_confirmed',
                'cancelation_date'
            ]);

            $where = new Where();
            $where->equalTo($this->getTable() . '.channel_res_id', $channelResId);
            $where->equalTo($this->getTable() . '.channel_name', $channelName);

			$select->where($where);
		});
	}

	/**
	 * @return \DDD\Domain\Booking\ResId|null
	 * @author Aram Baghdasaryan
	 */
	public function getMaxId() {
		return $this->fetchOne(function (Select $select) {
			$select->columns([
				'id' => new Expression('MAX(id)')
			]);
		});
	}

	public function createBookingTicketFromScratch(array $data) {
		return $this->save($data);
	}

    /**
     * @param $channelResId
     * @param $channelName
     * @return array|\ArrayObject|null
     */
	public function getBookingTicketByChannel($channelResId, $channelName) {
        $this->resultSetPrototype->setArrayObjectPrototype(new \DDD\Domain\Booking\ChannelReservation());
		return $this->fetchOne(function (Select $select) use ($channelResId, $channelName) {
			$select->columns([
                'id',
                'res_number',
                'customer_id',
                'apartment_id_assigned',
                'guest_first_name',
                'guest_last_name',
                'status',
                'date_from',
                'date_to'
            ]);

			$whereStatement = [
				'channel_res_id' => $channelResId,
				'channel_name' => $channelName,
			];

			$select->where($whereStatement);
		});
	}

	/**
	 * @fixme: fetchOne must be replaced with fetchAll.
	 * @param string $reservationNumber
	 *
	 * @return \DDD\Domain\Booking\ResId|null
	 * @author Tigran Petrosyan
	 */
	public function getBookingTicketByReservationNumber($reservationNumber)
    {
		return $this->fetchOne(function (Select $select) use ($reservationNumber) {
			$select->columns(['id', 'res_number', 'customer_id', 'apartment_id_assigned', 'guest_first_name', 'guest_last_name', 'status', 'date_from', 'date_to', 'funds_confirmed', 'cancelation_date']);

            $select->join(
                ['apartments' => DbTables::TBL_APARTMENTS],
                $this->getTable() . '.apartment_id_assigned = apartments.id',
                ['apartment_status' => 'status'],
                Select::JOIN_LEFT
            );

			$whereStatement = [
				$this->getTable() . '.res_number' => $reservationNumber,
			];

			$select->where($whereStatement);
		});
	}

	/**
	 * @fixme: fetchOne must be replaced with fetchAll.
	 * @param int $productId
	 * @param string $guestFirstName
	 * @param string $guestLastName
	 * @param string $dateFrom
	 * @param string $dateTo
	 *
	 * @return \DDD\Domain\Booking\ResId|null
	 * @author Aram Baghdasaryan
	 */
	public function getBookingTicketByIdentity($productId, $guestFirstName, $guestLastName, $dateFrom, $dateTo, $isApartel)
    {
		return $this->fetchOne(function (Select $select) use ($productId, $guestFirstName, $guestLastName, $dateFrom, $dateTo, $isApartel) {
			$select->columns(['id', 'res_number', 'customer_id', 'apartment_id_assigned', 'guest_first_name', 'guest_last_name', 'status', 'date_from', 'date_to']);

			$whereStatement = [
				'guest_first_name'=> $guestFirstName,
				'guest_last_name'=> $guestLastName,
				'date_from'=> $dateFrom,
				'date_to'=> $dateTo,
				'overbooking_status' => 0,
				'status' => 1,
			];

            if ($isApartel) {
                $whereStatement['apartel_id'] =  $productId;
            } else {
                $whereStatement['apartment_id_assigned'] =  $productId;
            }
			$select->where($whereStatement);
		});
	}

	public function updateBookingTicketFromScratch($data, $resId) {
		return $this->update($data, ['id' => $resId]);
	}

	/**
	 * Find reservations by ginosik email addresses to show on profile page
	 *
	 * @param string $ginosiEmailAddress
	 * @param string $alternativeEmailAddress
     *
     * @return \DDD\Domain\Booking\ResId[]|\ArrayObject
	 */
    public function getGinosikResevations($ginosiEmailAddress, $alternativeEmailAddress) {
    	$columns = ['id', 'res_number', 'date_from', 'date_to', 'guest_email'];

		return $this->fetchAll(function (Select $select) use ($columns, $ginosiEmailAddress, $alternativeEmailAddress) {
			$select->columns($columns);

            $select->join(
                ['apartments' => DbTables::TBL_APARTMENTS],
                $this->getTable() . '.apartment_id_assigned = apartments.id',
                ['acc_name' => 'name']
            );

			$where = new Where();
			$where->equalTo($this->getTable() . '.status', BookingService::BOOKING_STATUS_BOOKED);
			$where->expression('YEAR(' . $this->getTable() . '.date_from) = YEAR(CURDATE())', array());
			$where->expression(
                $this->getTable() . '.apartment_id_assigned NOT IN (' .
                Constants::TEST_APARTMENT_1 . ', ' .
                Constants::TEST_APARTMENT_2 . ')',
                []
            );

			if ($alternativeEmailAddress == '') {
				$where->equalTo($this->getTable() . '.guest_email', $ginosiEmailAddress);
			} else {
				$emailPredicate = new Predicate();
				$emailPredicate->equalTo($this->getTable() . '.guest_email', $ginosiEmailAddress)
								->or
								->equalTo($this->getTable() . '.guest_email', $alternativeEmailAddress);
				$where->addPredicate($emailPredicate);
			}

            $select->where($where)
                   ->order($this->getTable() . '.date_from ASC');
		});
	}

    /**
     * @param int $apartment_id
     * @param string $start_date
     * @param string $end_date
     * @param array $notUsedStatus
     * @return \DDD\Domain\Apartment\Statistics\ForBasicDataBooking [] | ResultSet
     */
    public function getBookingsForYear($apartment_id, $start_date, $end_date, $notUsedStatus)
    {
        $result = $this->fetchAll( function (Select $select) use ($apartment_id, $start_date, $end_date, $notUsedStatus) {
        $select->columns(
            [
                'id',
                'apartment_currency_code',
                'status',
                'date_to',
                'date_from',
                'res_number',
                'check_charged',
                'commission',
                'transaction_fee_percent',
                'price',
                'guest_email'
            ]
        );
        $select->where
            ->equalTo('apartment_id_assigned', $apartment_id)
            ->greaterThanOrEqualTo('date_to', $start_date)
            ->lessThanOrEqualTo('date_to', $end_date)
            ->notIn('status', $notUsedStatus);
        });

        return $result;
    }

	public function clearExpiredEditLinks($endDate)
    {
		$where = new Where();
		$where
			->lessThanOrEqualTo('date_to', $endDate)
			->equalTo('provide_cc_page_status', BookingService\BookingTicket::PROVIDE_CC_PAGE_STATUS_PROVIDE);

		if ($this->fetchOne($where)) {
			return $this->save([
				'provide_cc_page_status' => BookingService\BookingTicket::PROVIDE_CC_PAGE_STATUS_NOT_CHECK
			], $where);
		}

		return 0;
	}

    /**
     * @param $apartmentId
     * @param $now
     * @return array|\ArrayObject|null
     */
    public function getCurrentReservationByAcc($apartmentId, $now)
    {
        $result = $this->fetchOne( function (Select $select) use ($apartmentId, $now) {
        $select->columns([
            'id',
            'res_number',
            'guest_first_name',
            'guest_last_name',
            'date_from',
            'date_to',
            'guest_phone',
            'pin',
            'guest_email'
        ]);
        $select->where
            ->equalTo('apartment_id_assigned', $apartmentId)
            ->greaterThan('date_to', $now)
            ->lessThanOrEqualTo('date_from', $now)
            ->equalTo('status', 1)
            ->notEqualTo('overbooking_status', 1);
        });
        return $result;
    }

    /**
     * @param $apartmentId
     * @param $now
     * @return array|\ArrayObject|null
     */
    public function getNextReservationByAcc($apartmentId, $now, $resId = false)
    {
        $result = $this->fetchOne( function (Select $select) use ($apartmentId, $now, $resId) {
            $select->columns([
                'id',
                'res_number',
                'guest_first_name',
                'guest_last_name',
                'date_from',
                'date_to',
                'guest_phone',
                'pin',
                'guest_email'
            ]);

            // if want to check for specific date assign that date to $now variable
            // And check that found reservation is not the one with $resId
            if ($resId) {
                $select->where
                    ->equalTo('apartment_id_assigned', $apartmentId)
                    ->equalTo('date_from', $now)
                    ->equalTo('status', 1)
                    ->notEqualTo('overbooking_status', 1)
                    ->notEqualTo('id', $resId);
            } else {
                $select->where
                    ->equalTo('apartment_id_assigned', $apartmentId)
                    ->greaterThan('date_from', $now)
                    ->equalTo('status', 1)
                    ->notEqualTo('overbooking_status', 1);
            }

            $select->order('date_from ASC');
        });
        return $result;
    }

    public function getTicketByDoorCode($code, $cityId, $dateFrom)
    {
        $result = $this->fetchOne( function (Select $select) use ($code, $cityId, $dateFrom) {

            $select->columns(['id']);

            $select->where
                ->equalTo('pin', $code)
                ->and
                ->equalTo('acc_city_id', $cityId)
                ->and
                ->greaterThanOrEqualTo('date_to', $dateFrom);
        });

        return $result;
    }

    /**
     * @param string $code
     * @return bool|\DDD\Domain\Booking\KeyInstructionPage
     */
    public function getBookingTicketByKeyCode($code)
    {
        $oldEntity = $this->getEntity();
        $this->setEntity(new KeyInstructionPage());

        $result = $this->fetchOne(function (Select $select) use ($code) {
            $columns = [
                'id',
                'res_number',
                'apartel_id',
                'partner_id',
                'apartment_id_assigned',
                'acc_country_id',
                'acc_province_id',
                'acc_city_id',
                'date_from',
                'date_to',
                'booker_price',
                'pax' => 'man_count',
                'occupancy',
                'guest_first_name',
                'guest_last_name',
                'guest_phone',
                'guest_travel_phone',
                'guest_email',
                'secondary_email',
                'guest_city_name',
                'pin',
                'outside_door_code',
                'ki_page_status',
                'ki_mail_sent',
                'ki_viewed_date',
                'timestamp',
                'acc_city_name',
                'status',
                'ki_viewed',
                'channel_res_id'
            ];

            $select
                ->columns($columns)
                ->join(
				    ['general' => DbTables::TBL_APARTMENTS],
                    'general.id = ' . $this->getTable() . '.apartment_id_assigned',
                    [
                        'acc_postal_code' => 'postal_code',
                        'acc_address' => 'address',
                        'block' => 'block',
                        'floor' => 'floor',
                        'unit'  => 'unit_number',
                        'acc_name' => 'name',
                        'building_id'
                    ],
                    Select::JOIN_LEFT
                )
                ->join(
                    ['location' => DbTables::TBL_APARTMENT_LOCATIONS],
                    'location.apartment_id = ' . $this->getTable() . '.apartment_id_assigned',
                    [
                        'geo_lat' => 'x_pos',
                        'geo_lon' => 'y_pos'
                    ],
                    Select::JOIN_LEFT
                )
                ->join(
                    ['descriptions' => DbTables::TBL_PRODUCT_DESCRIPTIONS],
                    'descriptions.apartment_id = ' . $this->getTable() . '.apartment_id_assigned',
                    [
                        'wifi_network'  => 'primary_wifi_network',
                        'wifi_password' => 'primary_wifi_pass',
                        'check_in_time' => 'check_in',
                        'check_out_time' => 'check_out'
                    ],
                    Select::JOIN_LEFT
                )
                ->join(
                    ['media' => DbTables::TBL_APARTMENT_IMAGES],
                    'media.apartment_id = ' . $this->getTable() . '.apartment_id_assigned',
                    [
                        'youtube_video'  => 'key_entry_video'
                    ],
                    Select::JOIN_LEFT
                )
                ->join(
                    ['apartment_details' => DbTables::TBL_APARTMENTS_DETAILS],
                    'apartment_details.apartment_id = general.id',
                    [
                        'show_apartment_entry_code'
                    ],
                    Select::JOIN_LEFT
                )
                ->join(
                    ['details' => DbTables::TBL_BUILDING_DETAILS],
                    'details.apartment_group_id = general.building_id',
                    [
                        'ki_page_type' => 'ki_page_type',
                        'office_id' => 'assigned_office_id',
                        'map_attachment'
                    ],
                    Select::JOIN_LEFT
                )
                ->join(
                    ['customer_identity' => DbTables::TBL_CUSTOMER_IDENTITY],
                    'customer_identity.reservation_id = ' . $this->getTable() . '.id',
                    [
                        'superviser_user_id'  => 'user_id',
                        'ip_address'
                    ],
                    Select::JOIN_LEFT
                )
                ->join(
                    ['country' => DbTables::TBL_COUNTRIES],
                    'country.id = ' . $this->getTable() . '.acc_country_id',
                    [
                        'location_phone'  => 'contact_phone'
                    ],
                    Select::JOIN_LEFT
                )
                ->join(
                    ['building' => DbTables::TBL_APARTMENT_GROUPS],
                    'building.id = general.building_id',
                    [],
                    Select::JOIN_LEFT
                )
                ->join(
                    ['building_details' => DbTables::TBL_BUILDING_DETAILS],
                    'building.id = building_details.apartment_group_id',
                    [
                        'building_phone'  => 'building_phone'
                    ],
                    Select::JOIN_LEFT
                )
                ->join(
                    ['office' => DbTables::TBL_OFFICES],
                    'office.id = details.assigned_office_id',
                    [
                        'office_phone'  => 'phone',
                        'office_map_attachment' => 'map_attachment'
                    ],
                    Select::JOIN_LEFT
                )
                ->join(
                    ['charge' => DbTables::TBL_CHARGE],
                    new Expression($this->getTable() . '.id = charge.reservation_id AND charge.addons_type = ' . BookingAddon::ADDON_TYPE_PARKING . ' AND charge.status = 0'),
                    ['parking' => 'id'],
                    Select::JOIN_LEFT
                )
                ->join(
                    ['parking_lot_buildings' => DbTables::TBL_BUILDING_LOTS],
                    'general.building_section_id = parking_lot_buildings.building_section_id',
                    [],
                    Select::JOIN_LEFT
                )

                ->join(
                    ['parking_lots' => DbTables::TBL_PARKING_LOTS],
                    'parking_lot_buildings.lot_id = parking_lots.id',
                    ['parking_textline_id' => 'direction_textline_id'],
                    Select::JOIN_LEFT
                )
                ->where
                    ->equalTo('ki_page_hash', $code);
        });

        $this->setEntity($oldEntity);

        return $result;
    }

	/**
	 * @param $code
	 *
	 * @return \DDD\Domain\Booking\ReviewPage
	 */
	public function getBookingTicketByReviewCode($code)
    {
        $oldEntity = $this->getEntity();
        $this->setEntity(new \DDD\Domain\Booking\ReviewPage());

        $result = $this->fetchOne(function (Select $select) use ($code) {

            $select->columns(array(
                'id',
                'res_number',
                'review_page_hash',

                'apartment_id_assigned',
                'acc_country_id',
                'acc_province_id',
                'acc_city_id',
                'acc_address',

                'date_from',
                'date_to',

                'guest_first_name',
                'guest_last_name',
                'guest_email',
                'guest_country_id',
                'guest_city_name',
                'partner_id'
            ));

            $select->join(
				['general' => DbTables::TBL_APARTMENTS],
				'general.id = ' . $this->getTable() . '.apartment_id_assigned',
				[
                    'acc_postal_code' => 'postal_code',
                    'acc_name' => 'name'
                ]
			);

            $select->join(
				['descriptions' => DbTables::TBL_PRODUCT_DESCRIPTIONS],
				'descriptions.apartment_id = ' . $this->getTable() . '.apartment_id_assigned',
				[
                    'check_in_time' => 'check_in',
                    'check_out_time' => 'check_out'
                ]
			);

            $select->join(
				['media' => DbTables::TBL_APARTMENT_IMAGES],
				'media.apartment_id = ' . $this->getTable() . '.apartment_id_assigned',
				['image'  => 'img1']
			);

            $select->where
                ->equalTo('review_page_hash', $code);
        });

        $this->setEntity($oldEntity);

        return $result;
    }

	public function getBookingCountryEmailCount($checker)
    {
         $sql = "SELECT COUNT(*) AS `count` FROM (
                    SELECT COUNT(*) AS `count`
                    FROM " . DbTables::TBL_BOOKINGS . " as bookings
                    INNER JOIN " . DbTables::TBL_APARTMENTS . " AS `general` ON bookings.`apartment_id_assigned` = `general`.`id`
                    WHERE bookings.`status` = '1' AND general.id NOT IN(" .
                    Constants::TEST_APARTMENT_1 . ", " .
                    Constants::TEST_APARTMENT_2 . ")
                    GROUP BY bookings.{$checker}
                 ) AS sub";
        $statement = $this->adapter->createStatement($sql);
        $result = $statement->execute();
    	return $result->current();
	}

    public function getResDataByCCUpdateCode($code)
    {
		return $this->fetchOne(function (Select $select) use ($code) {
			$select->columns([
                'id',
                'rate_name',
                'date_from',
                'date_to',
                'customer_id',
                'guest_name' => new Expression('CONCAT(guest_first_name, \' \', guest_last_name)'),
                'guest_email',
                'guest_phone',
                'amount_price' => 'booker_price',
                'res_number',
                'acc_country_id',
                'acc_city_id',
                'acc_address',
                'occupancy',
                'guest_country_id',
                'guest_zip_code',
                'guest_city_name',
                'is_refundable',
                'refundable_before_hours',
                'penalty_type' => 'penalty',
                'penalty_val',
                'acc_price' => 'price',
                'guest_currency_code',
                'apartment_currency_code',
                'rate_capacity' => 'man_count',
                'currency_rate',
                'partner_name',
                'partner_id',
			]);

            $select->join(
                ['apartments' => DbTables::TBL_APARTMENTS],
                $this->getTable() . '.apartment_id_assigned = apartments.id',
                [
                    'acc_name' => 'name',
                    'acc_id'   => 'id'
                ]
            );

			$select->join(
                ['media' => DbTables::TBL_APARTMENT_IMAGES],
                'media.apartment_id = ' . $this->getTable() . '.apartment_id_assigned',
                [
                'img1'
                ],
                Select::JOIN_LEFT
			);
			$select->join(
                ['descriptions' => DbTables::TBL_PRODUCT_DESCRIPTIONS],
                'descriptions.apartment_id = ' . $this->getTable() . '.apartment_id_assigned',
                [
                'check_in',
                'check_out'
                ],
                Select::JOIN_LEFT
			);
            $select->join(
				['currency' => DbTables::TBL_CURRENCY],
				$this->getTable() . '.guest_currency_code = currency.code',
				[
                    'symbol'
                ],
				Select::JOIN_LEFT
			);
            $select->join(
                ['country' => DbTables::TBL_COUNTRIES],
                $this->getTable().'.acc_country_id = country.id', []
            );
            $select->join(
                ['currency_country' => DbTables::TBL_CURRENCY],
                'country.currency_id = currency_country.id', ['country_currency' => 'code']
            );
			$select->where([
                $this->getTable() . '.provide_cc_page_hash' => $code,
                $this->getTable() . '.provide_cc_page_status' => BookingService\BookingTicket::PROVIDE_CC_PAGE_STATUS_PROVIDE
            ]);
		});
	}

	/**
	 * Show all those reservations which arrival date is 5 or less days from today and KI link is not viewed and guest balance is >= 0.00
	 * @return Array
	 */
	public function getKINotViewedReservations() {
		$this->resultSetPrototype->setArrayObjectPrototype(new \DDD\Domain\UniversalDashboard\Widget\KINotViewed());

		$columns = [
            'res_number'          => 'res_number',
            'arrival_date'        => 'date_from',
            'guest_first_name',
            'guest_last_name',
            'last_agent_fullname' => 'last_agent'
		];

		return $this->fetchAll(function (Select $select) use ($columns) {
			$select->columns($columns);

            $select->join(
                ['apartments' => DbTables::TBL_APARTMENTS],
                $this->getTable() . '.apartment_id_assigned = apartments.id',
                ['apartment_name' => 'name']
            );

            $select->join(
                ['countries' => DbTables::TBL_COUNTRIES],
                $this->getTable() . '.guest_country_id = countries.id',
                [],
                Select::JOIN_LEFT
            );

            $select->join(
                ['geo_details' => DbTables::TBL_LOCATION_DETAILS],
                'geo_details.id = countries.detail_id',
                ['country' => 'name'],
                Select::JOIN_LEFT
            );

			$select->where->expression('DATEDIFF(' . $this->getTable() . '.date_to, NOW()) >= 0', []);
			$select->where->equalTo($this->table . '.ki_mail_sent', 1);
            $select->where->equalTo($this->table . '.ki_viewed', 0);
			$select->where->equalTo($this->table . '.arrival_status', ReservationTicketService::BOOKING_ARRIVAL_STATUS_EXPECTED);
			$select->where->greaterThanOrEqualTo($this->getTable() . '.guest_balance', 0);
			$select->where->expression($this->getTable() . '.apartment_id_assigned NOT IN(' .
                Constants::TEST_APARTMENT_1 . ', ' .
                Constants::TEST_APARTMENT_2 . ')',
                []
            );
			$select->where->equalTo($this->getTable() . '.status', BookingService::BOOKING_STATUS_BOOKED);
		});
	}

	/**
	 * @return int
	 */
	public function getKINotViewedReservationsCount() {
		$this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());

		$result = $this->fetchOne(function (Select $select) {
			$select->columns(['count' => new Expression('COUNT(*)')]);

            $select->join(
                ['apartments' => DbTables::TBL_APARTMENTS],
                $this->getTable() . '.apartment_id_assigned = apartments.id',
                []
            );

			$select->where->expression('DATEDIFF(' . $this->getTable() . '.date_to, NOW()) >= 0', []);
			$select->where->equalTo($this->table . '.ki_mail_sent', 1);
			$select->where->equalTo($this->table . '.ki_viewed', 0);
            $select->where->equalTo($this->table . '.arrival_status', ReservationTicketService::BOOKING_ARRIVAL_STATUS_EXPECTED);
			$select->where->greaterThanOrEqualTo($this->getTable() . '.guest_balance', 0);
			$select->where->expression($this->getTable() . '.apartment_id_assigned NOT IN(' .
                Constants::TEST_APARTMENT_1 . ', ' .
                Constants::TEST_APARTMENT_2 . ')',
                []
            );
			$select->where->equalTo($this->getTable() . '.status', BookingService::BOOKING_STATUS_BOOKED);
		});

        return $result['count'];
	}

	/**
	 * Show all those reservations which are marked as "No Collection" and are not marked as "Settled"
	 * @return Array
	 */
	public function getNoCollectionReservations() {
		$this
            ->resultSetPrototype
            ->setArrayObjectPrototype(
                new \DDD\Domain\UniversalDashboard\Widget\NoCollection()
        );
		$columns = [
			'res_number' 		=> 'res_number',
			'status'			=> 'status',
			'arrival_date' 		=> 'date_from',
			'departure_date'	=> 'date_to',
			'guest_first_name',
			'guest_last_name',
			'is_cc_valid'		=> 'funds_confirmed',
			'guest_balance' 	=> 'guest_balance',
			'last_agent_fullname' => 'last_agent',
            'apartment_currency_code'
		];
		return $this->fetchAll(function (Select $select) use ($columns) {
			$select->columns($columns);

            $select->join(
                ['apartments' => DbTables::TBL_APARTMENTS],
                $this->getTable() . '.apartment_id_assigned = apartments.id',
                ['apartment_name' => 'name']
            );
            $select->join(
                ['currency' => DbTables::TBL_CURRENCY],
                $this->getTable().'.apartment_currency_code = currency.code',
                ['symbol' => 'symbol']
            );
			$select->where->equalTo($this->table . '.no_collection', 1);
			$select->where->equalTo($this->table . '.payment_settled', 0);
			$select->where->expression('apartment_id_assigned NOT IN(' .
                Constants::TEST_APARTMENT_1 . ', ' .
                Constants::TEST_APARTMENT_2 . ')',
                []
            );
		});
	}

	/**
	 * @return int
	 */
	public function getNoCollectionReservationsCount()
    {
		$this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());

		$result =  $this->fetchOne(function (Select $select) {
			$select->columns(['count' => new Expression('COUNT(*)')]);

            $select->join(
                ['apartments' => DbTables::TBL_APARTMENTS],
                $this->getTable() . '.apartment_id_assigned = apartments.id',
                []
            );
            $select->join(
                ['currency' => DbTables::TBL_CURRENCY],
                $this->getTable().'.apartment_currency_code = currency.code',
                []
            );
			$select->where->equalTo($this->table . '.no_collection', 1);
			$select->where->equalTo($this->table . '.payment_settled', 0);
			$select->where->expression('apartment_id_assigned NOT IN(' .
                Constants::TEST_APARTMENT_1 . ', ' .
                Constants::TEST_APARTMENT_2 . ')',
                []
            );
		});

        return $result['count'];
	}

	public function getPendingCancelationReservations() {
		$this->resultSetPrototype->setArrayObjectPrototype(
            new \DDD\Domain\UniversalDashboard\Widget\PendingCancelation()
        );

		return $this->fetchAll(function (Select $select) {
			$select->columns(
                [
                    'res_number', 'cancelation_date', 'partner_ref',
                    'guest_balance', 'apartel_id', 'apartment_currency_code', 'id'
                ]
            );

            $select->join(
                ['apartments' => DbTables::TBL_APARTMENTS],
                $this->getTable() . '.apartment_id_assigned = apartments.id',
                ['acc_name' => 'name']
            );

            $select->join(
                ['ag' => DbTables::TBL_APARTMENT_GROUPS],
                $this->getTable() . '.apartel_id = ag.id',
                ['apartel' => 'name'],
                Select::JOIN_LEFT
            );

			$select->join(
				['partners' => DbTables::TBL_BOOKING_PARTNERS],
				$this->getTable() . '.partner_id = partners.gid',
				['aff_name' => 'partner_name'],
				Select::JOIN_LEFT
			);

            $select->join(
                ['c' => DbTables::TBL_CURRENCY],
                $this->getTable() . '.apartment_currency_code = c.code',
                ['symbol' => 'symbol']
            );

			$select->where->equalTo(
                $this->table . '.status',
                \DDD\Service\Booking::BOOKING_STATUS_CANCELLED_PENDING
            );

			$select->where->expression('apartment_id_assigned NOT IN(' .
                Constants::TEST_APARTMENT_1 . ', ' .
                Constants::TEST_APARTMENT_2 . ')',
                []
            );
		});
	}

	public function getPendingCancelationReservationsCount() {
		$this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());

		$result = $this->fetchOne(function (Select $select) {
			$select->columns(['count' => new Expression('COUNT(*)')]);

            $select->join(
                ['apartments' => DbTables::TBL_APARTMENTS],
                $this->getTable() . '.apartment_id_assigned = apartments.id',
                []
            );

            $select->join(
                ['ag' => DbTables::TBL_APARTMENT_GROUPS],
                $this->getTable() . '.apartel_id = ag.id',
                [],
                Select::JOIN_LEFT
            );

			$select->join(
				['partners' => DbTables::TBL_BOOKING_PARTNERS],
				$this->getTable() . '.partner_id = partners.gid',
				[],
				Select::JOIN_LEFT
			);

            $select->join(
                ['c' => DbTables::TBL_CURRENCY],
                $this->getTable() . '.apartment_currency_code = c.code',
                []
            );

			$select->where->equalTo(
                $this->table . '.status',
                \DDD\Service\Booking::BOOKING_STATUS_CANCELLED_PENDING
            );

			$select->where->expression('apartment_id_assigned NOT IN(' .
                Constants::TEST_APARTMENT_1 . ', ' .
                Constants::TEST_APARTMENT_2 . ')',
                []
            );
		});

        return $result['count'];
	}

	/**
	 * Show Reservation if  Settled = 1 AND Partner Settled = 0  AND Partner Balance > 0
	 * @return ResultSet|\DDD\Domain\UniversalDashboard\Widget\PayToPartner[]
	 */
	public function getPayToPartnerReservations()
    {
		$this->resultSetPrototype->setArrayObjectPrototype(new \DDD\Domain\UniversalDashboard\Widget\PayToPartner());
		$columns = array(
			'res_number' 	  => 'res_number',
			'status'		  => 'status',
			'booking_date'	  => new Expression('DATE(timestamp)'),
			'departure_date'  => 'date_to',
			'guest_balance'   => 'guest_balance',
			'partner_balance' => 'partner_balance',
			'apartel_id'	  => 'apartel_id',
            'apartment_currency_code'
		);

		return $this->fetchAll(function (Select $select) use ($columns) {
			$select->columns($columns);

            $select->join(
                ['apartments' => DbTables::TBL_APARTMENTS],
                $this->getTable() . '.apartment_id_origin = apartments.id',
                ['apartment_name' => 'name']
            );

			$select->join(
					DbTables::TBL_BOOKING_PARTNERS,
					DbTables::TBL_BOOKING_PARTNERS . '.gid = ' . $this->getTable() . '.partner_id',
					['partner_name'],
					Select::JOIN_LEFT
			);
            $select->join(
                ['c' => DbTables::TBL_CURRENCY],
                $this->getTable() . '.apartment_currency_code = c.code',
                ['symbol' => 'symbol']
            );

			$select->where->greaterThan($this->table . '.partner_balance', 0);
			$select->where->equalTo($this->table . '.partner_settled', 0);
			$select->where->equalTo($this->table . '.payment_settled', 1);
			$select->where->expression('apartment_id_assigned NOT IN(' .
                Constants::TEST_APARTMENT_1 . ', ' .
                Constants::TEST_APARTMENT_2 . ')',
                []
            );
		});
	}

    /**
     * @param Where $where
     * @return ResultSet|\DDD\Domain\UniversalDashboard\Widget\PayToPartner[]
     */
    public function getPayToPartnerReservationsByFilter(Where $where)
    {
        $this->setEntity(new \DDD\Domain\UniversalDashboard\Widget\PayToPartner());

        return $this->fetchAll(function (Select $select) use ($where) {
            $select->columns([
                'id'              => 'id',
                'res_number' 	  => 'res_number',
                'status'		  => 'status',
                'booking_date'	  => new Expression('DATE(timestamp)'),
                'departure_date'  => 'date_to',
                'guest_balance'   => 'guest_balance',
                'partner_balance' => 'partner_balance',
                'apartel_id'	  => 'apartel_id',
                'apartment_id'    => 'apartment_id_origin',
                'apartment_currency_code',
                'partner_id'      => 'partner_id',
            ]);

            $select->join(
                ['apartments' => DbTables::TBL_APARTMENTS],
                $this->getTable() . '.apartment_id_origin = apartments.id',
                ['apartment_name' => 'name']
            );

            $select->join(
                ['partner' => DbTables::TBL_BOOKING_PARTNERS],
                'partner.gid = ' . $this->getTable() . '.partner_id',
                ['partner_name'],
                Select::JOIN_LEFT
            );
            $select->join(
                ['currency' => DbTables::TBL_CURRENCY],
                $this->getTable() . '.apartment_currency_code = currency.code',
                ['currency_id' => 'id', 'symbol' => 'symbol']
            );
            $select->join(
                ['fiscal' => DbTables::TBL_APARTEL_FISCAL],
                $this->getTable() . '.channel_partner_id = fiscal.channel_partner_id',
                [],
                Select::JOIN_LEFT
            );

            $select->where($where);
            $select->group($this->getTable() . '.id');
        });
    }

	/**
	 * Show all those reservations which customer balance not equal 0 and "no collection" is not marked and "settled" is not marked
	 * @return Array
	 */
	public function getCollectFromCustomerReservations()
    {
        $entity = $this->getEntity();
        $this->setEntity(new \DDD\Domain\UniversalDashboard\Widget\CollectFromCustomer());

        $result =  $this->fetchAll(function (Select $select) {
            $columns = [
                'id' => 'id',
                'res_number' => 'res_number',
                'status' => 'status',
                'arrival_date' => 'date_from',
                'guest_first_name',
                'guest_last_name',
                'is_cc_valid' => 'funds_confirmed',
                'guest_balance' => 'guest_balance',
                'is_waiting_for_cc_details' => 'provide_cc_page_status',
                'last_agent_fullname' => 'last_agent',
                'apartment_currency_code'
            ];

            $select->columns($columns);

            $select->join(
                ['apartments' => DbTables::TBL_APARTMENTS],
                $this->getTable() . '.apartment_id_assigned = apartments.id',
                ['apartment_name' => 'name']
            );
            $select->join(
                ['c' => DbTables::TBL_CURRENCY],
                $this->getTable() . '.apartment_currency_code = c.code',
                ['symbol' => 'symbol']
            );

            $select
                ->join(
                    ['transactions' => DbTables::TBL_CHARGE_TRANSACTION],
                    new Expression($this->getTable() . '.id = transactions.reservation_id AND transactions.status = "' . BookingService\BankTransaction::BANK_TRANSACTION_STATUS_PENDING . '"'),
                    ['pending_transactions_amount' => new Expression('SUM(transactions.acc_amount)')],
                    Select::JOIN_LEFT
                )
                ->group($this->getTable() . '.id');

            $table = $this->getTable();

            $select
                ->where->or->nest
                ->lessThan($this->table . '.guest_balance', 0)
                ->equalTo($this->table . '.no_collection', 0)
                ->equalTo($this->table . '.payment_settled', 0)
                ->notEqualTo($this->table . '.partner_id', Constants::AGODA_PARTNER_ID)
                ->expression(
                    $this->getTable() . '.apartment_id_assigned NOT IN(' .
                    Constants::TEST_APARTMENT_1 . ', ' .
                    Constants::TEST_APARTMENT_2 . ')',
                    []
                )
                ->expression("
                    (
                        (
                            is_refundable = 1
                            AND
                            TIMESTAMPDIFF(
                                HOUR,
                                NOW(),
                                CONCAT(
                                    " . $table . ".date_from,
                                    ' ',
                                    IF(
                                        " . $table . ".guest_arrival_time IS NULL,
                                        '00:00:00',
                                        " . $table . ".guest_arrival_time
                                    )
                                )
                            ) < refundable_before_hours
                        )
                        OR
                        is_refundable = 2
                    )",
                    []
                )->unnest
                ->and->nest
                    ->isNull('transactions.id')
                    ->or
                    ->notIn('transactions.type', [
                        BookingService\BankTransaction::BANK_TRANSACTION_TYPE_CHARGEBACK_DISPUTE,
                        BookingService\BankTransaction::BANK_TRANSACTION_TYPE_CHARGEBACK_FRAUD,
                        BookingService\BankTransaction::BANK_TRANSACTION_TYPE_CHARGEBACK_OTHER
                    ])
                ->unnest;


            //#agodatest
            $select
                ->where->or->nest
                ->lessThan($this->table . '.guest_balance', 0)
                ->equalTo($this->table . '.partner_id', Constants::AGODA_PARTNER_ID)
                ->equalTo($this->table . '.no_collection', 0)
                ->equalTo($this->table . '.payment_settled', 0)
                ->expression(
                    $this->getTable() . '.apartment_id_assigned NOT IN(' .
                    Constants::TEST_APARTMENT_1 . ', ' .
                    Constants::TEST_APARTMENT_2 . ')',
                    []
                )
                ->expression("
                    (
                        (
                            is_refundable = 1
                            AND
                            TIMESTAMPDIFF(
                                HOUR,
                                NOW(),
                                CONCAT(
                                    " . $table . ".date_from,
                                    ' ',
                                    IF(
                                        " . $table . ".guest_arrival_time IS NULL,
                                        '00:00:00',
                                        " . $table . ".guest_arrival_time
                                    )
                                )
                            ) < refundable_before_hours
                        )
                        OR
                        is_refundable = 2
                    )",
                    []
                )
                ->expression(
                    "(DATEDIFF(now(), {$this->getTable()}.date_to) >= 0)",
                    []
                )
                ->unnest();
        });
        $this->setEntity($entity);

        return $result;
	}

	/**
	 * @return CollectFromCustomer[]
	 */
	public function getPayToCustomerReservations()
    {
		$this->resultSetPrototype->setArrayObjectPrototype(new \DDD\Domain\UniversalDashboard\Widget\CollectFromCustomer());
		$columns = [
			'res_number' 		=> 'res_number',
            'status'			=> 'status',
            'arrival_date' 		=> 'date_from',
            'guest_first_name',
            'guest_last_name',
            'is_cc_valid'		=> 'funds_confirmed',
            'guest_balance' 	=> 'guest_balance',
            'is_waiting_for_cc_details' => 'provide_cc_page_status',
            'last_agent_fullname' => 'last_agent',
            'apartment_currency_code'
		];

		return $this->fetchAll(function (Select $select) use ($columns) {
			$select->columns($columns);

            $select->join(
                ['apartments' => DbTables::TBL_APARTMENTS],
                $this->getTable() . '.apartment_id_assigned = apartments.id',
                ['apartment_name' => 'name']
            );
            $select->join(
                ['c' => DbTables::TBL_CURRENCY],
                $this->getTable() . '.apartment_currency_code = c.code',
                ['symbol' => 'symbol']
            );
            $select->where->greaterThan($this->table . '.guest_balance', 0)
                ->equalTo($this->table . '.no_collection', 0)
                ->equalTo($this->table . '.payment_settled', 0)
                ->expression(
                    $this->getTable() . '.apartment_id_assigned NOT IN(' .
                    Constants::TEST_APARTMENT_1 . ', ' .
                    Constants::TEST_APARTMENT_2 . ')',
                    []
                );
            $select->where->nest
                ->notEqualTo($this->table . '.status', 1)
                ->where->or->nest
                ->equalTo($this->table . '.status', 1)
                ->expression($this->table . '.date_to <= CURDATE()', [])
                ->unnest()
                ->unnest();
		});
	}

	/**
	 * @return int
	 */
	public function getPayToCustomerReservationsCount()
    {
		$this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject);
		$columns = ['count' => new Expression('COUNT(*)')];

		$result = $this->fetchOne(function (Select $select) use ($columns) {
			$select->columns($columns);

            $select->join(
                ['apartments' => DbTables::TBL_APARTMENTS],
                $this->getTable() . '.apartment_id_assigned = apartments.id',
                []
            );
            $select->join(
                ['c' => DbTables::TBL_CURRENCY],
                $this->getTable() . '.apartment_currency_code = c.code',
                []
            );
            $select->where->greaterThan($this->table . '.guest_balance', 0)
                ->equalTo($this->table . '.no_collection', 0)
                ->equalTo($this->table . '.payment_settled', 0)
                ->expression(
                    $this->getTable() . '.apartment_id_assigned NOT IN(' .
                    Constants::TEST_APARTMENT_1 . ', ' .
                    Constants::TEST_APARTMENT_2 . ')',
                    []
                );
            $select->where->nest
                ->notEqualTo($this->table . '.status', 1)
                ->where->or->nest
                ->equalTo($this->table . '.status', 1)
                ->expression($this->table . '.date_to <= CURDATE()', [])
                ->unnest()
                ->unnest();
		});

        return $result['count'];
	}

	/**
     * Show all those reservations which departure date + 5 AND NOT marked as Settled
     * @return Array
     */
    public function getToBeSettledReservations()
    {
        $this->resultSetPrototype
            ->setArrayObjectPrototype(
                new \DDD\Domain\UniversalDashboard\Widget\ToBeSettled()
        );

        $columns = [
            'res_number'        => 'res_number',
            'status'            => 'status',
            'departure_date'    => 'date_to',
            'guest_first_name',
            'guest_last_name',
            'guest_balance'     => 'guest_balance',
            'partner_balance'   => 'partner_balance',
            'is_no_collection'  => 'no_collection',
            'apartment_currency_code'
        ];

        $result = $this->fetchAll(function (Select $select) use ($columns) {
            $select->columns($columns);

            $select->join(
                ['apartments' => DbTables::TBL_APARTMENTS],
                $this->getTable() . '.apartment_id_assigned = apartments.id',
                ['apartment_name' => 'name']
            );

            $select->join(
                ['c' => DbTables::TBL_CURRENCY],
                $this->getTable() . '.apartment_currency_code = c.code',
                ['symbol' => 'symbol']
            );

            $select->where
                ->equalTo($this->table . '.payment_settled', 0)
                ->and
                ->expression('DATEDIFF(NOW(), date_to) >= 5', array())
                ->and
                ->expression(
                    'apartment_id_assigned NOT IN(' .
                    Constants::TEST_APARTMENT_1 . ', ' .
                    Constants::TEST_APARTMENT_2 . ')', [])
                ->and
                ->notIn(
                    $this->getTable() . '.id',
                    [ new Expression(
                        'Select reservation_id FROM ' . DbTables::TBL_CHARGE_TRANSACTION
                        . ' WHERE status = ' . BookingService\BankTransaction::BANK_TRANSACTION_STATUS_PENDING)]
                );
        });

        return $result;
    }

    /**
     * @return int
     */
    public function getToBeSettledReservationsCount()
    {
        $this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());

        $result = $this->fetchOne(function (Select $select) {
            $select->columns([
                'count' => new Expression('COUNT(*)')
            ]);

            $select->join(
                ['apartments' => DbTables::TBL_APARTMENTS],
                $this->getTable() . '.apartment_id_assigned = apartments.id',
                []
            );

            $select->join(
                ['c' => DbTables::TBL_CURRENCY],
                $this->getTable() . '.apartment_currency_code = c.code',
                []
            );

            $select->where
                ->equalTo($this->table . '.payment_settled', 0)
                ->and
                ->expression('DATEDIFF(NOW(), date_to) >= 5', array())
                ->and
                ->expression(
                    'apartment_id_assigned NOT IN(' .
                    Constants::TEST_APARTMENT_1 . ', ' .
                    Constants::TEST_APARTMENT_2 . ')', []
                )
                ->and
                ->notIn(
                    $this->getTable() . '.id',
                    [ new Expression(
                        'Select reservation_id FROM ' . DbTables::TBL_CHARGE_TRANSACTION
                        . ' WHERE status = ' . BookingService\BankTransaction::BANK_TRANSACTION_STATUS_PENDING)]
                );
        });

        return $result['count'];
    }

    /**
	 * Show Reservation if  Settled = 1 AND Partner Settled = 0  AND Partner Balance < 0
	 * @return \DDD\Domain\UniversalDashboard\Widget\CollectFromPartner[]|ResultSet
	 */
	public function getCollectFromPartnerReservations()
    {
		$this->resultSetPrototype->setArrayObjectPrototype(
            new \DDD\Domain\UniversalDashboard\Widget\CollectFromPartner()
        );

		$columns = [
			'id' 		        => 'id',
			'res_number' 		=> 'res_number',
			'status'			=> 'status',
			'booking_date'		=> new Expression('DATE(timestamp)'),
			'departure_date'	=> 'date_to',
			'partner_balance' 	=> 'partner_balance',
            'apartment_currency_code'
		];

		return $this->fetchAll(function (Select $select) use ($columns) {
			$select->columns($columns);

            $select->join(
                ['apartments' => DbTables::TBL_APARTMENTS],
                $this->getTable() . '.apartment_id_assigned = apartments.id',
                ['apartment_name' => 'name']
            );

			$select->join(
					DbTables::TBL_BOOKING_PARTNERS,
					DbTables::TBL_BOOKING_PARTNERS . '.gid = ' . $this->getTable() . '.partner_id',
					['partner_id' => 'gid', 'partner_name'],
					Select::JOIN_LEFT
			);

            $select->join(
                ['c' => DbTables::TBL_CURRENCY],
                $this->getTable() . '.apartment_currency_code = c.code',
                ['currency_id' => 'id', 'symbol' => 'symbol']
            );

			$select->where->lessThan($this->table . '.partner_balance', 0);
			$select->where->equalTo($this->table . '.partner_settled', 0);
			$select->where->expression(
                'apartment_id_assigned NOT IN(' .
                Constants::TEST_APARTMENT_1 . ', ' .
                Constants::TEST_APARTMENT_2 . ')',
                []
            );
		});
	}

	/**
	 * @return int
	 */
	public function getCollectFromPartnerReservationsCount()
    {
		$this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject);
		$columns = ['count' => new Expression('COUNT(*)')];

		$result = $this->fetchOne(function (Select $select) use ($columns) {
			$select->columns(['count' => new Expression('count(*)')]);

            $select->join(
                ['apartments' => DbTables::TBL_APARTMENTS],
                $this->getTable() . '.apartment_id_assigned = apartments.id',
                []
            );

            $select->join(
                ['c' => DbTables::TBL_CURRENCY],
                $this->getTable() . '.apartment_currency_code = c.code',
                []
            );

			$select->where->lessThan($this->table . '.partner_balance', 0);
			$select->where->equalTo($this->table . '.partner_settled', 0);
			$select->where->expression(
                'apartment_id_assigned NOT IN(' .
                Constants::TEST_APARTMENT_1 . ', ' .
                Constants::TEST_APARTMENT_2 . ')',
                []
            );
		});

        return $result['count'];
	}

	/**
	 * Show all those reservations which CC card is
	 * Not marked as "Valid" AND is NOT marked as "No Collection" AND is NOT marked as "Settled" AND NOT ( Customer Balance >= 0 AND Departure Date is in the past)
     *
	 * @return ValidateCC[]
	 */
	public function getValidateCCReservations()
    {
		$this->resultSetPrototype->setArrayObjectPrototype(new ValidateCC());

		$columns = [
			'res_number' 		  => 'res_number',
			'booking_date'		  => new Expression('DATE(timestamp)'),
			'arrival_date' 		  => 'date_from',
			'guest_first_name',
			'guest_last_name',
			'guest_balance' 	  => 'guest_balance',
			'last_agent_fullname' => 'last_agent',
            'apartment_currency_code'
		];

		return $this->fetchAll(function (Select $select) use ($columns) {
			$select->columns($columns);

            $select->join(
                ['apartments' => DbTables::TBL_APARTMENTS],
                $this->getTable() . '.apartment_id_assigned = apartments.id',
                ['apartment_name' => 'name']
            );

            $select->join(
                ['c' => DbTables::TBL_CURRENCY],
                $this->getTable() . '.apartment_currency_code = c.code',
                ['symbol' => 'symbol']
            );

			$select->where->equalTo(
                $this->table . '.funds_confirmed',
                BookingService\BookingTicket::CC_STATUS_UNKNOWN
            );

			$select->where->equalTo($this->table . '.no_collection', 0);
			$select->where->equalTo($this->table . '.payment_settled', 0);

			$select->where->expression(
                'NOT ( ' . $this->table .
                '.`guest_balance`>=0 AND DATEDIFF(NOW(), ' .
                $this->table . '.`date_to`) > 0 )'
                , []
            );

            // avoid test apartments from result
            $select->where->notIn(
                $this->getTable() . '.apartment_id_assigned',
                [
                    Constants::TEST_APARTMENT_1,
                    Constants::TEST_APARTMENT_2
                ]
            );

            // only booked and moved reservations
            $select->where->in(
                $this->getTable() . '.status',
                [
                    BookingService::BOOKING_STATUS_BOOKED,
                    BookingService::BOOKING_STATUS_CANCELLED_MOVED
                ]
            );

            $table = $this->getTable();

			$select->where->expression(
                "(" . $table . ".is_refundable = 1
                AND
                TIMESTAMPDIFF(
                    HOUR,
                    NOW(),
                    CONCAT(
                        " . $table . ".date_from,
                        ' ',
                        IF(
                            " . $table . ".guest_arrival_time IS NULL,
                            '00:00:00',
                            " . $table . ".guest_arrival_time
                        )
                    )
                ) > refundable_before_hours)",
                []
            );
		});
	}

	/**
	 * @return int
	 */
	public function getValidateCCReservationsCount()
    {
		$this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());

		$result = $this->fetchOne(function (Select $select) {
			$select->columns(['count' => new Expression('COUNT(*)')]);

            $select->join(
                ['apartments' => DbTables::TBL_APARTMENTS],
                $this->getTable() . '.apartment_id_assigned = apartments.id',
                []
            );

            $select->join(
                ['c' => DbTables::TBL_CURRENCY],
                $this->getTable() . '.apartment_currency_code = c.code',
                []
            );
			$select->where->equalTo(
                $this->table . '.funds_confirmed',
                BookingService\BookingTicket::CC_STATUS_UNKNOWN
            );

			$select->where->equalTo($this->table . '.no_collection', 0);
			$select->where->equalTo($this->table . '.payment_settled', 0);
			$select->where->expression(
                'NOT ( ' . $this->table .
                '.`guest_balance`>=0 AND DATEDIFF(NOW(), ' .
                $this->table . '.`date_to`) > 0 )',
                []
            );

			$select->where->expression(
                $this->table . '.apartment_id_assigned NOT IN(' .
                Constants::TEST_APARTMENT_1 . ', ' .
                Constants::TEST_APARTMENT_2 . ')',
                []
            );

			$select->where->expression(
                $this->table . '.status IN (' .
                BookingService::BOOKING_STATUS_BOOKED . ', ' .
                BookingService::BOOKING_STATUS_CANCELLED_MOVED . ')',
                []
            );

            $table = $this->getTable();

            $select->where->expression(
                "(" . $table . ".is_refundable = 1
                AND
                TIMESTAMPDIFF(
                    HOUR,
                    NOW(),
                    CONCAT(
                        " . $table . ".date_from,
                        ' ',
                        IF(
                            " . $table . ".guest_arrival_time IS NULL,
                            '00:00:00',
                            " . $table . ".guest_arrival_time
                        )
                    )
                ) > refundable_before_hours)",
                []
            );
		});

        return $result['count'];
	}

    /**
     * @return AwaitingPaymentDetails[]
     */
    public function getPendingReservations()
    {
		$this->resultSetPrototype->setArrayObjectPrototype(new AwaitingPaymentDetails());

		return $this->fetchAll(function (Select $select) {
            $select->join(
                ['apartments' => DbTables::TBL_APARTMENTS],
                $this->getTable() . '.apartment_id_assigned = apartments.id',
                ['apartment_name' => 'name']
            );

			$select->join(
				['currency' => DbTables::TBL_CURRENCY],
				$this->getTable().'.apartment_currency_code = currency.code',
				['acc_symbol' => 'symbol']
			);

			$where = new Where();
			$where->equalTo($this->getTable() . '.provide_cc_page_status', BookingService\BookingTicket::PROVIDE_CC_PAGE_STATUS_PROVIDE);
			$where->equalTo($this->getTable() . '.status', BookingService::BOOKING_STATUS_BOOKED);
			$select->where->expression(
                $this->getTable() . '.apartment_id_assigned NOT IN(' .
                Constants::TEST_APARTMENT_1 . ', ' .
                Constants::TEST_APARTMENT_2 . ')',
                []
            );

			$select->columns([
                'id' 				  => 'id',
                'res_number' 		  => 'res_number',
                'status' 			  => 'status',
                'arrival_date' 		  => 'date_from',
                'guest_first_name',
                'guest_last_name',
                'guest_balance' 	  => 'guest_balance',
                'overbooking' 		  => 'overbooking_status',
                'last_agent_fullname' => 'last_agent',
                'apartment_currency_code',
                'rate_name'           => 'rate_name',
            ]);
			$select->where($where);
		});
	}

	public function getPendingReservationsCount()
    {
		$this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());

		$result = $this->fetchOne(function (Select $select) {
            $select->join(
                ['apartments' => DbTables::TBL_APARTMENTS],
                $this->getTable() . '.apartment_id_assigned = apartments.id',
                []
            );

			$select->join(
				['currency' => DbTables::TBL_CURRENCY],
				$this->getTable().'.apartment_currency_code = currency.code',
				[]
			);

			$where = new Where();
			$where->equalTo($this->getTable() . '.provide_cc_page_status', BookingService\BookingTicket::PROVIDE_CC_PAGE_STATUS_PROVIDE);
			$where->equalTo($this->getTable() . '.status', BookingService::BOOKING_STATUS_BOOKED);
			$select->where->expression(
                $this->getTable() . '.apartment_id_assigned NOT IN(' .
                Constants::TEST_APARTMENT_1 . ', ' .
                Constants::TEST_APARTMENT_2 . ')',
                []
            );

			$select->columns(['count' => new Expression('COUNT(*)')]);
			$select->where($where);
		});

        return $result['count'];
	}

    /**
     * @return NotChargedApartelReservations[]
     *
     * @author Tigran Petrosyan
     */
    public function getNotChargedApartelReservations()
    {
        $this->resultSetPrototype->setArrayObjectPrototype(new NotChargedApartelReservations());

        return $this->fetchAll(function (Select $select) {

            $columns = [
                'reservation_id' => 'id',
                'reservation_number' => 'res_number',
                'apartment_name' => 'acc_name',
                'guest_full_name' => new Expression("CONCAT(`guest_first_name`, ' ', `guest_last_name`)"),
                'checkin_date' => 'date_from',
                'checkout_date' => 'date_to',
            ];

            $select->join(
                ['apartment_groups' => DbTables::TBL_APARTMENT_GROUPS],
                $this->getTable() . '.apartel_id = apartment_groups.id',
                [
                    'apartel_name' => 'name'
                ]
            );

            $select->join(
                ['charges' => DbTables::TBL_CHARGE],
                new Expression($this->getTable() . '.id = charges.reservation_id AND charges.status=0'),
                [],
                Select::JOIN_LEFT
            );

            $where = new Where();
            $where->greaterThan($this->getTable() . '.apartel_id', 0);
            $where->equalTo($this->getTable() . '.status', \DDD\Service\Booking::BOOKING_STATUS_BOOKED);
            $where->isNull('charges.id');
            $where->notEqualTo($this->getTable().'.check_charged', 1);

            $where->expression(
                $this->getTable() . '.apartment_id_assigned NOT IN(' .
                Constants::TEST_APARTMENT_1 . ', ' .
                Constants::TEST_APARTMENT_2 . ')',
                []
            );

            $where->greaterThanOrEqualTo($this->getTable() . '.date_from', date('Y-m-d'));

            $where->expression('(' . $this->getTable() . '.is_refundable = 2 or ' . $this->getTable() . '.refundable_before_hours >= TIMESTAMPDIFF(HOUR,NOW(),date_from))', []);

            $select->columns($columns);
            $select->where($where);
        });
    }

    /**
     * @return int
     *
     * @author Tigran Petrosyan
     */
    public function getNotChargedApartelReservationsCount()
    {
        $this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());

        $result = $this->fetchOne(function (Select $select) {
            $select->join(
                ['apartment_groups' => DbTables::TBL_APARTMENT_GROUPS],
                $this->getTable() . '.apartel_id = apartment_groups.id',
                []
            );

            $select->join(
                ['charges' => DbTables::TBL_CHARGE],
                new Expression($this->getTable() . '.id = charges.reservation_id AND charges.status=0'),
                [],
                Select::JOIN_LEFT
            );

            $where = new Where();
            $where->greaterThan($this->getTable() . '.apartel_id', 0);
            $where->equalTo($this->getTable() . '.status', \DDD\Service\Booking::BOOKING_STATUS_BOOKED);
            $where->isNull('charges.id');
            $where->notEqualTo($this->getTable().'.check_charged', 1);

            $where->expression(
                $this->getTable() . '.apartment_id_assigned NOT IN(' .
                Constants::TEST_APARTMENT_1 . ', ' .
                Constants::TEST_APARTMENT_2 . ')',
                []
            );

            $where->greaterThanOrEqualTo($this->getTable() . '.date_from', date('Y-m-d'));
            $where->expression('(' . $this->getTable() . '.is_refundable = 2 or ' . $this->getTable() . '.refundable_before_hours >= TIMESTAMPDIFF(HOUR,NOW(),date_from))', []);

            $select->columns(['count' => new Expression('COUNT(*)')]);
            $select->where($where);
        });

        return $result['count'];
    }

    public function getReservationForAccOnDate($apartment_id, $start_date, $end_date)
    {
        return $this->fetchAll(function (Select $select) use ($apartment_id, $start_date, $end_date) {
            $select->columns(['date_from', 'date_to']);

            $where = new Where();
            $where->equalTo('apartment_id_assigned', $apartment_id)
                  ->equalTo('status', 1);

            $fromPredicate = new Predicate();
            $fromPredicate->greaterThanOrEqualTo('date_from', $start_date)
                          ->lessThanOrEqualTo('date_from', $end_date);

            $toPredicate = new Predicate();
            $toPredicate->greaterThanOrEqualTo('date_to', $start_date)
                        ->lessThanOrEqualTo('date_to', $end_date);

            $fromToPredicate = new PredicateSet([
                $fromPredicate,
                $toPredicate
            ], PredicateSet::COMBINED_BY_OR);
            $where->addPredicate($fromToPredicate);
            $select->where( $where );
        });
    }

    public function getBookingForFrontierCharge($id)
    {
        $this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());

        return $this->fetchOne(function (Select $select) use ($id) {
            $select->columns([
                'id',
                'price',
                'booker_price',
                'apartment_currency_code',
                'currency_rate',
                'guest_currency_code',
                'res_number',
                'apartment_id_assigned',
                'guest_first_name',
                'guest_last_name',
                'date_from',
                'date_to',
                'customer_id',
                'rate_capacity' => 'man_count',
                'partner_commission',
                'model',
                'occupancy'
            ]);

            $select->join(
                ['city' => DbTables::TBL_CITIES],
                $this->getTable().'.acc_city_id = city.id',
                [],
                Select::JOIN_LEFT
            );

            $select->join(
                ['details' => DbTables::TBL_LOCATION_DETAILS],
                'city.detail_id = details.id',
                [
                    'tot',
                    'tot_type',
                    'tot_included',
                    'tot_max_duration',
                    'tot_additional',
                    'vat',
                    'vat_type',
                    'vat_included',
                    'vat_additional',
                    'vat_max_duration',
                    'sales_tax',
                    'sales_tax_type',
                    'sales_tax_included',
                    'sales_tax_additional',
                    'sales_tax_max_duration',
                    'city_tax',
                    'city_tax_type',
                    'city_tax_included',
                    'city_tax_additional',
                    'city_tax_max_duration',
                ],
                Select::JOIN_LEFT
            );

            $select->join(
                ['currency' => DbTables::TBL_CURRENCY],
                $this->getTable().'.guest_currency_code = currency.code',
                [
                    'current_customer_currency_rate'=>'value'
                ],
                Select::JOIN_LEFT
            );

            $select->join(
                ['currency_apartment' => DbTables::TBL_CURRENCY],
                $this->getTable().'.apartment_currency_code = currency_apartment.code',
                [
                    'acc_currency_rate' => 'value',
                    'acc_currency_sign' => 'code',
                    'acc_currency_id'   => 'id'
                ],
                Select::JOIN_LEFT
            );

            $select->join(
	        	['country' => DbTables::TBL_COUNTRIES],
	        	$this->getTable().'.acc_country_id = country.id',
	        	[],
	        	Select::JOIN_LEFT
	        );

            $select->join(
	        	['country_currency_tbl' => DbTables::TBL_CURRENCY],
	        	'country.currency_id = country_currency_tbl.id',
	        	['country_currency' => 'code'],
	        	Select::JOIN_LEFT
	        );

            $select->where->equalTo($this->getTable().'.id', $id);
        });
    }

    public function getDataForCharge($resNumber)
    {
        $this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());

        return $this->fetchOne(function (Select $select) use ($resNumber) {
            $select->columns([
                'id',
                'res_number',
                'date_from',
                'date_to',
                'occupancy'
            ]);

            $select->where->equalTo($this->getTable().'.res_number', $resNumber);
        });
    }

    public function getAllBookedTicket()
    {
        $this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());

        return $this->fetchAll(function (Select $select) {
            $select->columns([
                'id',
                'res_number',
                'date_from',
                'date_to',
            ]);

            $select->where->equalTo('status', 1);
        });
    }

    public function searchReservationByResNum($reservationNumber)
    {
        return $this->fetchOne(function (Select $select) use ($reservationNumber) {
            $select->where([$this->getTable() . '.res_number' => $reservationNumber]);
        });
    }

    public function getRateDataForReservation($reservationId)
    {
        return $this->fetchOne(function (Select $select) use ($reservationId) {
            $select->columns([
                'penalty_percent'       => 'penalty_val',
                'penalty_nights'        => 'penalty_val',
                'penalty_fixed_amount'  => 'penalty_val',
                'penalty_type'          => 'penalty',
                'is_refundable'         => 'is_refundable',
                'refundable_before_hours' => 'refundable_before_hours'
            ]);

            $select->where->equalTo('id', $reservationId);
        });
    }

    /**
     * @param int $bookingTicketId
     * @return \DDD\Domain\Booking\BookingTicket|bool
     */
    public function getBookingTicketData($bookingTicketId) {
        return $this->fetchOne(function (Select $select) use ($bookingTicketId) {
            $select->join(
                ['customer' => DbTables::TBL_CUSTOMERS],
                $this->getTable() . '.customer_id = customer.id',
                ['customer_email' => 'email'],
                Select::JOIN_LEFT
            );

            $select->where([
                $this->getTable().'.id' => $bookingTicketId
			]);
        });
    }

    public function getMoveReservationsByResNumbers($resNumbers)
    {
        $this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());
        return $this->fetchAll(function (Select $select) use ($resNumbers) {
            $select->columns([
                'id',
                'res_number',
                'date_from',
                'date_to',
                'apartment_id_assigned',
                'apartel_id',
                'man_count',
                'overbooking_status',
                'occupancy'
            ]);

            $select->join(
                ['apartments' => DbTables::TBL_APARTMENTS],
                $this->getTable() . '.apartment_id_assigned = apartments.id',
                ['apartment_name' => 'name']
            );
            $select->where->in($this->getTable().'.res_number', $resNumbers);
        });
    }

    /**
     * @param $reservationId
     * @return int
     */
    public function getCustomerIdByReservationId($reservationId)
    {
        $previousEntity = $this->resultSetPrototype->getArrayObjectPrototype();
        $this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());

        $result =  $this->fetchOne(function (Select $select) use ($reservationId) {
            $select->columns([
                'customer_id'
            ]);

            $select->where->equalTo($this->getTable().'.id', $reservationId);
        });

        $this->setEntity($previousEntity);

        return $result['customer_id'];
    }

    /**
     *
     * @param $reservationId
     * @return array|\ArrayObject|null
     */
    public function getReceiptData($reservationId)
    {
        $prototype = $this->getEntity();
        $this->setEntity(new \ArrayObject());

        $result =  $this->fetchOne(function (Select $select) use ($reservationId) {
            $select->columns([
                'id',
                'guest_email',
                'secondary_email',
                'res_number',
                'date_from',
                'date_to',
                'guest_first_name',
                'guest_last_name',
                'guest_address',
                'arrival_date',
                'departure_date',
                'guest_currency_code',
                'partner_id',
            ]);

            $select->join(
                ['product' => DbTables::TBL_APARTMENTS],
                $this->getTable() . '.apartment_id_assigned = product.id',
                [
                    'apartment_name' => 'name',
                    'apartment_address' => 'address'
                ]
            );

            $select->join(
                ['product_description' => DbTables::TBL_PRODUCT_DESCRIPTIONS],
                'product.id = product_description.apartment_id',
                [
                    'check_in',
                    'check_out'
                ],
                $select::JOIN_LEFT
            );

            $select->join(
                ['currency' => DbTables::TBL_CURRENCY],
                $this->getTable() . '.apartment_currency_code = currency.code',
                [
                    'symbol'
                ],
                $select::JOIN_LEFT
            );

            $select->join(
                ['country' => DbTables::TBL_COUNTRIES],
                $this->getTable().'.acc_country_id = country.id',
                ['phone1' => 'contact_phone'],
                $select::JOIN_LEFT
            );

            $select->join(
                ['country2' => DbTables::TBL_COUNTRIES],
                $this->getTable() . '.guest_country_id = country2.id',
                ['phone2' => 'contact_phone'],
                $select::JOIN_LEFT
            );

            $select->where->equalTo($this->getTable().'.id', $reservationId);
        });
        $this->setEntity($prototype);

        return $result;
    }

    /**
     * @param $reservationId
     * @return ResultSet
     */
    public function getPendingInQueueCardsByReservationId($reservationId)
    {
        $this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());

        return $this->select(function (Select $select) use ($reservationId) {
            $select->columns([
                'id'
            ]);

            $select->join(
                ['queue' => DbTables::TBL_CC_CREATION_QUEUE],
                $this->getTable() . '.customer_id = queue.customer_id',
                []
            );

            $select->where([$this->getTable() . '.id' => $reservationId]);
        });
    }

    /**
     * @param int $reservationId
     * @return array|\ArrayObject|null
     */
    public function getPenaltyUsedData($reservationId)
    {
        $this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());
        return $this->fetchOne(function (Select $select) use ($reservationId) {
            $select->columns(array(
                'id'                => 'id',
                'res_number'        => 'res_number',
                'status'            => 'status',
                'date_from'         => 'date_from',
                'date_to'           => 'date_to',
                'is_refundable'     => 'is_refundable',
                'guest_currency_code',
                'apartment_currency_code',
                'penalty_fixed_amount'    => 'penalty_fixed_amount',
                'refundable_before_hours'  => 'refundable_before_hours',
                'penalty_hours'     => new Expression('TIMESTAMPDIFF(HOUR,NOW(),date_from)'),
                'price'             => 'price',
            ));
            $select->where->equalTo($this->getTable() . '.id', $reservationId);
        });
    }

    /**
     * @param $reservationId
     * @return array|\ArrayObject|null
     */
    public function getReservationDataForAvailability($reservationId)
    {
        $this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());
        return $this->fetchOne(function (Select $select) use ($reservationId) {
            $select->columns([
                'id'                => 'id',
                'res_number'        => 'res_number',
                'status'            => 'status',
                'date_from'         => 'date_from',
                'date_to'           => 'date_to',
                'overbooking'       => 'overbooking_status',
                'apartel_id'        => 'apartel_id',
                'room_type_id'      => 'room_id',
                'apartment_id'      => 'apartment_id_assigned',
            ]);
            $select->where->equalTo($this->getTable() . '.id', $reservationId);
        });
    }


    /**
     * @param $reservationId
     * @return array|\ArrayObject|null
     */
    public function getReservationPolicyData($reservationId)
    {
        $this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());
        return $this->fetchOne(function (Select $select) use ($reservationId) {
            $select->columns([
                'is_refundable',
                'penalty',
                'penalty_fixed_amount',
                'refundable_before_hours',
                'penalty_val',
                'status',
                'res_number',
                'date_from',
                'date_to',
                'price',
                'occupancy',
                'rate_name',
                'check_charged',
                'apartment_id_assigned',
                'penalty_hours' => new Expression('TIMESTAMPDIFF(HOUR,NOW(),date_from)'),
                'overbooking_status'
            ]);

            $select->join(
                [ 'city' => DbTables::TBL_CITIES ],
                $this->getTable() . '.acc_city_id = city.id',
                [
                    'timezone'
                ]
            );

            $select->where->equalTo($this->getTable() . '.id', $reservationId);
        });
    }

    /**
     * @param $apartmentId
     * @param $dateFrom
     * @return array|\ArrayObject|null
     */
    public function getNearBeforeReservation($apartmentId, $dateFrom)
    {
        $this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());
        return $this->fetchOne(function (Select $select) use ($apartmentId, $dateFrom) {
            $select->columns(array(
                'date_from'         => 'date_from',
                'date_to'           => 'date_to',
            ));
            $select->where
                ->equalTo($this->getTable() . '.apartment_id_assigned', $apartmentId)
                ->equalTo($this->getTable() . '.status', BookingService::BOOKING_STATUS_BOOKED)
                ->lessThanOrEqualTo($this->getTable() . '.date_to', $dateFrom);
            $select->order($this->getTable() . '.date_to DESC');
        });
    }
    /**
     * @param $apartmentId
     * @param $dateTo
     * @return array|\ArrayObject|null
     */
    public function getNearAfterReservation($apartmentId, $dateTo)
    {
        $this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());
        return $this->fetchOne(function (Select $select) use ($apartmentId, $dateTo) {
            $select->columns(array(
                'date_from'         => 'date_from',
                'date_to'           => 'date_to',
            ));
            $select->where
                ->equalTo($this->getTable() . '.apartment_id_assigned', $apartmentId)
                ->equalTo($this->getTable() . '.status', BookingService::BOOKING_STATUS_BOOKED)
                ->greaterThanOrEqualTo($this->getTable() . '.date_from', $dateTo);
            $select->order($this->getTable() . '.date_from ASC');
        });
    }

    /**
     * @param string $query
     * @param stdClass $user
     * @param int $limit
     * @return \DDD\Domain\Booking\FrontierCard[]
     */
    public function getFrontierCardList($query, $user, $limit)
    {
        $this->resultSetPrototype->setArrayObjectPrototype(new \DDD\Domain\Booking\FrontierCard());
        // Execute for search purposes only
        if (!$query) {
            return false;
        }
        return $this->select(function (Select $select) use ($query, $user, $limit) {
            $columns = [
                'id', 'res_number', 'guest_first_name', 'guest_last_name'
            ];

            $where = new Where();
            $nestedWhere = new Where();
            $nestedWhere
                ->like($this->getTable() . '.guest_first_name', '%' . $query . '%')
                ->or
                ->like($this->getTable() . '.guest_last_name', '%' . $query . '%')
                ->or
                ->equalTo($this->getTable() . '.res_number', $query);
            $where
                ->greaterThanOrEqualTo($this->getTable() . '.date_from', new Expression('DATE_SUB(CURDATE(),INTERVAL 30 DAY)'))
                ->lessThanOrEqualTo($this->getTable() . '.date_to', new Expression('DATE_ADD(CURDATE(),INTERVAL 30 DAY)'))
                ->addPredicate($nestedWhere);

            $select
                ->columns($columns)
                ->join(
                    ['apartments' => DbTables::TBL_APARTMENTS],
                    $this->getTable() . '.apartment_id_assigned = apartments.id',
                    ['apartment_assigned' => 'name'],
                    Select::JOIN_INNER
                )->join(
                    ['group_item' => DbTables::TBL_APARTMENT_GROUP_ITEMS],
                    $this->getTable() . '.apartment_id_assigned = group_item.apartment_id',
                    []
                )->join(
                    ['cda' => DbTables::TBL_CONCIERGE_DASHBOARD_ACCESS],
                    new Expression('group_item.apartment_group_id = cda.apartment_group_id AND cda.user_id = ' . $user->id),
                    []
                )
                ->where($where);
            if ($limit) {
                $select->limit($limit);
            }
        });
    }

    /**
     * @param int $id
     * @return \DDD\Domain\Booking\FrontierCard
     */
    public function getTheCard($id)
    {
        $prototype = $this->resultSetPrototype->getArrayObjectPrototype();
        $this->resultSetPrototype->setArrayObjectPrototype(new \DDD\Domain\Booking\FrontierCard());

        // Execute for search purposes only
        if (!$id || !is_numeric($id)) {
            return false;
        }
        $result = $this->fetchOne(function (Select $select) use ($id) {
            $columns = [
                'id',
                'status',
                'res_number',
                'guest_first_name',
                'guest_last_name',
                'guest_phone',
                'guest_travel_phone',
                'apartment_id_assigned',
                'arrival_status',
                'date_from',
                'date_to',
                'guest_arrival_time' => 'guest_arrival_time',
                'arrival_date',
                'departure_date',
                'ki_page_status',
                'ki_page_hash',
                'guest_email',
                'occupancy' => 'occupancy',
                'guest_balance' => 'guest_balance',
                'ccca_verified' => 'ccca_verified',
                'ccca_page_status' => 'ccca_page_status',
                'ccca_page_token' => 'ccca_page_token',
                'apartment_currency_code' => 'apartment_currency_code',
                'housekeeping_comments'  => new Expression("(
                    SELECT
                    GROUP_CONCAT(
                        CONCAT('<blockquote class=\"comment-blockquote\">', '<p>', IF(action_id = '2', 'Comment', value), IF(action_id = '2', CONCAT('\n', value), ''), '</p><footer>', users.firstname, ' ', users.lastname, ', ',  `timestamp`, ' (Amsterdam Time)', '</footer></blockquote>') SEPARATOR ''
                    )
                    FROM " . DbTables::TBL_ACTION_LOGS . "
                    LEFT JOIN " . DbTables::TBL_BACKOFFICE_USERS . " AS users ON users.id = " . DbTables::TBL_ACTION_LOGS . ".user_id
                    WHERE module_id = " . Logger::MODULE_BOOKING . " AND identity_id = " . $this->getTable() . ".`id` AND action_id = " . Logger::ACTION_HOUSEKEEPING_COMMENT . "
                )"),
            ];

            $where = new Where();
            $where
                ->greaterThanOrEqualTo($this->getTable() . '.date_from', new Expression('DATE_SUB(CURDATE(),INTERVAL 30 DAY)'))
                ->lessThanOrEqualTo($this->getTable() . '.date_to', new Expression('DATE_ADD(CURDATE(),INTERVAL 30 DAY)'))
                ->equalTo($this->getTable() . '.id', $id);

            $select
                ->columns($columns)
                ->join(
                    ['apartments' => DbTables::TBL_APARTMENTS],
                    $this->getTable() . '.apartment_id_assigned = apartments.id',
                    ['apartment_assigned' => 'name', 'unit_number', 'building_id'],
                    Select::JOIN_INNER
                )
                ->join(
                    ['buildings' => DbTables::TBL_APARTMENT_GROUPS],
                    'apartments.building_id = buildings.id',
                    [
                        'building' => 'name',
                    ],
                    Select::JOIN_LEFT
                )
                ->join(
                    [ 'cities' => DbTables::TBL_CITIES ],
                    $this->getTable() . '.acc_city_id = cities.id',
                    [
                        'timezone'
                    ]
                )
                ->join(
                    ['apartment_description' => DbTables::TBL_PRODUCT_DESCRIPTIONS],
                    $this->getTable() . '.apartment_id_assigned = apartment_description.apartment_id',
                    [
                        'apartment_check_in_time' => 'check_in',
                    ],
                    Select::JOIN_LEFT
                )
                ->join(
                    ['charge' => DbTables::TBL_CHARGE],
                    new Expression($this->getTable() . '.id = charge.reservation_id AND charge.addons_type = ' . BookingAddon::ADDON_TYPE_PARKING . ' AND charge.status = 0'),
                    ['parking' => 'id'],
                    Select::JOIN_LEFT
                )
                ->join(
                    ['tasks' => DbTables::TBL_TASK],
                    new Expression($this->getTable() . '.id = tasks.res_id AND tasks.task_type = ' . TaskService::TYPE_KEYFOB . ' AND tasks.task_status != ' . TaskService::STATUS_VERIFIED),
                    ['key_task' => 'id'],
                    Select::JOIN_LEFT
                )
                ->where($where);
        });

        $this->resultSetPrototype->setArrayObjectPrototype($prototype);

        return $result;
    }


    /**
     * @param string $resNumber
     * @return \DDD\Domain\Booking\FrontierCard
     */
    public function getResDetailsForTask($resNumber)
    {

        $prototype = $this->resultSetPrototype->getArrayObjectPrototype();
        $this->resultSetPrototype->setArrayObjectPrototype(new \DDD\Domain\Booking\FrontierCard());
        // Execute for search purposes only
        if (!$resNumber) {
            return false;
        }
        $result = $this->fetchOne(function (Select $select) use ($resNumber) {
            $columns = [
                'id',
                'res_number',
                'apartment_id_assigned',
                'date_from',
                'date_to'
            ];

            $where = new Where();
            $where
                ->equalTo($this->getTable() . '.res_number', (string)$resNumber);

            $select
                ->columns($columns)
                ->join(
                    ['apartments' => DbTables::TBL_APARTMENTS],
                    $this->getTable() . '.apartment_id_assigned = apartments.id',
                    ['apartment_assigned' => 'name', 'unit_number', 'building_id'],
                    Select::JOIN_INNER
                )
                ->join(
                    ['buildings' => DbTables::TBL_APARTMENT_GROUPS],
                    'apartments.building_id = buildings.id',
                    ['building' => 'name'],
                    Select::JOIN_LEFT
                )
                ->where($where);
        });

        $this->resultSetPrototype->setArrayObjectPrototype($prototype);

        return $result;
    }

    /**
     * @param $reservationId int
     * @return array|\ArrayObject|null
     */
    public function getReservationDataForChangeDate($reservationId)
    {
        $this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());
        return $this->fetchOne(function (Select $select) use ($reservationId) {
            $select->columns([
                'res_number',
                'date_from',
                'date_to',
                'status',
                'overbooking_status',
                'apartel_id',
                'channel_res_id'
            ]);

            $select->join(
                [ 'city' => DbTables::TBL_CITIES ],
                $this->getTable() . '.acc_city_id = city.id',
                [
                    'timezone'
                ]
            );

            $select->where->equalTo($this->getTable() . '.id', $reservationId);
        });
    }

    public function getOverbookingDataForEmail($reservationId)
    {
        $this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());
        return $this->fetchOne(function (Select $select) use ($reservationId) {
            $select->columns([
                'res_number',
                'acc_name',
            ]);

            $select->join(
                ['country' => DbTables::TBL_COUNTRIES],
                $this->getTable().'.acc_country_id = country.id',
                ['phone1' => 'contact_phone'],
                $select::JOIN_LEFT
            );

            $select->join(
                ['country2' => DbTables::TBL_COUNTRIES],
                $this->getTable() . '.guest_country_id = country2.id',
                ['phone2' => 'contact_phone'],
                $select::JOIN_LEFT
            );

            $select->where->equalTo($this->getTable() . '.id', $reservationId);
        });
    }

    /**
     * @return OverbookingReservation[]
     *
     * @author Tigran Petrosyan
     */
    public function getOverbookingReservations()
    {
        $this->resultSetPrototype->setArrayObjectPrototype(new OverbookingReservation());
        return $result = $this->fetchAll(function (Select $select) {
            $select->columns([
                'id'                => 'id',
                'res_number'        => 'res_number',
                'city_name'         => 'acc_city_name',
                'apartment_name'    => 'acc_name',
                'arrival_date'      => 'date_from',
                'guest_first_name',
                'guest_last_name',
            ]);

            $select->where
                ->equalTo('overbooking_status', \DDD\Service\Booking\BookingTicket::OVERBOOKING_STATUS_OVERBOOKED)
                ->equalTo('status', BookingService::BOOKING_STATUS_BOOKED)
            ;
        });
    }

    /**
     * @return int
     *
     * @author Tigran Petrosyan
     */
    public function getOverbookingReservationsCount()
    {
        $this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());
        $result = $this->fetchOne(function (Select $select) {
            $select->columns([
                'count' => new Expression('COUNT(*)')
            ]);

            $select->where
                ->equalTo('overbooking_status', \DDD\Service\Booking\BookingTicket::OVERBOOKING_STATUS_OVERBOOKED)
                ->equalTo('status', BookingService::BOOKING_STATUS_BOOKED)
            ;
        });

        return $result['count'];
    }

    /**
     * @param $reservationId
     * @return mixed
     */
    public function getBookingDataForRateSelector($reservationId)
    {
        $this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());
        return $result = $this->fetchOne(function (Select $select) use ($reservationId) {
            $select->columns([
                'capacity' => 'man_count',
                'is_refundable' => 'is_refundable',
                'apartment_id' => 'apartment_id_assigned',
                'apartel_id' => 'apartel_id',
                'room_id' => 'room_id',
            ]);

            $select->where
                ->equalTo('id', $reservationId);
        });
    }

    /**
     * @param $token
     * @return \DDD\Domain\Booking\ChargeAuthorization\ChargeAuthorizationForm
     *
     * @author Tigran Petrosyan
     */
    public function getReservationDataForChargeAuthorizationPage($token)
    {
        $this->resultSetPrototype->setArrayObjectPrototype(new \DDD\Domain\Booking\ChargeAuthorization\ChargeAuthorizationForm());

        return $result = $this->fetchOne(function (Select $select) use ($token) {
            $select->columns([
                'reservation_id'          => 'id',
                'reservation_number'      => 'res_number',
                'reservation_date_from'   => 'date_from',
                'reservation_date_to'     => 'date_to',
                'is_refundable'           => 'is_refundable',
                'refundable_before_hours' => 'refundable_before_hours',
                'penalty_type'            => 'penalty',
                'penalty_value'           => 'penalty_val',
                'guest_currency_code',
                'apartment_currency_code',
                'status'                  => 'status',
                'partner_id'
            ]);

            $select->join(
                ['partner' => DbTables::TBL_BOOKING_PARTNERS],
                new Expression($this->getTable() . '.partner_id = partner.gid AND partner.is_ota = 1'),
                ['reservation_partner' => 'partner_name'],
                $select::JOIN_LEFT
            );
            $select->join(
                ['apartment' => DbTables::TBL_APARTMENTS],
                $this->getTable() . '.apartment_id_assigned = apartment.id',
                ['city_id']
            );
            $select->join(
                ['city' => DbTables::TBL_CITIES],
                'apartment.city_id = city.id',
                ['timezone']
            );
            $select->join(
                ['ccca' => DbTables::TBL_CCCA],
                new Expression($this->getTable() . '.id = ccca.reservation_id AND ccca.page_token = "' . $token .
                    '" AND ccca.status in ('. ChargeAuthorization::CHARGE_AUTHORIZATION_PAGE_STATUS_GENERATED . ',' .
                        ChargeAuthorization::CHARGE_AUTHORIZATION_PAGE_STATUS_VIEWED .')'),
                ['cc_id', 'ccca_page_status' => 'status', 'created_date']
            );
            $select->where->equalTo($this->getTable() . '.status', BookingService::BOOKING_STATUS_BOOKED);
        });
    }

    /**
     * @param $reservationId
     * @return Email
     */
    public function getReservationDataForChargeAuthorizationEmail($reservationId)
    {
        $this->resultSetPrototype->setArrayObjectPrototype(new Email());

        return $result = $this->fetchOne(function (Select $select) use ($reservationId) {
            $select->columns([
                'reservation_number' => 'res_number',
                'apartment_name' => 'acc_name',
                'guest_first_name',
                'guest_last_name',
                'guest_email',
                'partner_id',
                'ccca_page_token' => 'ccca_page_token',
                'date_from' => 'date_from'
            ]);

            $select->join(
                ['country' => DbTables::TBL_COUNTRIES],
                $this->getTable() . '.acc_country_id = country.id',
                ['phone1' => 'contact_phone'],
                Select::JOIN_LEFT
            );
            $select->join(
                ['country2' => DbTables::TBL_COUNTRIES],
                $this->getTable() . '.guest_country_id = country2.id',
                ['phone2' => 'contact_phone'],
                Select::JOIN_LEFT
            );

            $select->where
                ->equalTo($this->getTable() . '.id', $reservationId);
        });
    }

        /**
     * @param int $resId
     * @param int $apartmentId
     * @param string $dateFrom
     * @return Ambigous <ArrayObject, PrepareData>
     */
    public function getPreviousReservationForApartment($resId, $apartmentId, $dateFrom)
    {
        $this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());
        $columns = array(
            'id'                    => 'id',
            'apartment_id_assigned' => 'apartment_id_assigned',
            'res_number'            => 'res_number',
            'date_to'               => 'date_to',
            'date_from'             => 'date_from',
            'pax'                   => 'man_count',
            'pin'                   => 'pin',
            'outside_door_code'     => 'outside_door_code',
            'occupancy'             => 'occupancy',
            'timestamp'             => 'timestamp',
            'no_refresh'            => 'no_refresh'
        );


        $result = $this->fetchOne(function (Select $select) use ($resId, $apartmentId, $dateFrom, $columns) {

            $select->columns($columns);
            $select->join(
                ['apartment_description' => DbTables::TBL_PRODUCT_DESCRIPTIONS],
                $this->getTable() . '.apartment_id_assigned = apartment_description.apartment_id',
                [
                    'apartment_check_in_time'  => 'check_in',
                    'apartment_check_out_time' => 'check_out'
                ],
                Select::JOIN_LEFT
            );
            $select->join(
                ['task' => DbTables::TBL_TASK],
                $this->getTable() . '.id = task.res_id',
                ['task_status'],
                Select::JOIN_LEFT
            );
            $select->where
                ->equalTo($this->getTable().'.apartment_id_assigned', $apartmentId)
                ->lessThanOrEqualTo($this->getTable().'.date_to', $dateFrom)
                ->equalTo($this->getTable().'.status', BookingService::BOOKING_STATUS_BOOKED)
                ->notEqualTo($this->getTable() . '.overbooking_status', BookingTicketService::OVERBOOKING_STATUS_OVERBOOKED);

            $select->order('date_to DESC');

        });
        return $result;
    }

    public function getLastReservationForApartment($apartmentId, $todayDate, $dateTimeToday)
    {
        $prototype = $this->resultSetPrototype->getArrayObjectPrototype();
        $this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());

        $columns = array(
            'id'                    => 'id',
            'apartment_id_assigned' => 'apartment_id_assigned',
            'res_number'            => 'res_number',
            'date_to'               => 'date_to',
            'date_from'             => 'date_from',
            'pax'                   => 'man_count',
            'pin'                   => 'pin',
            'outside_door_code'     => 'outside_door_code',
            'occupancy'             => 'occupancy',
            'timestamp'             => 'timestamp',
            'no_refresh'            => 'no_refresh'

        );

        $result = $this->fetchOne(function (Select $select) use($apartmentId, $todayDate, $dateTimeToday, $columns) {
            $select->columns($columns);

            $select->join(
                ['apartment_description' => DbTables::TBL_PRODUCT_DESCRIPTIONS],
                $this->getTable() . '.apartment_id_assigned = apartment_description.apartment_id',
                [
                    'apartment_check_in_time' => 'check_in',
                    'apartment_check_out_time' => 'check_out'
                ],
                Select::JOIN_LEFT
            );
            $select->join(
                ['task' => DbTables::TBL_TASK],
                $this->getTable() . '.id = task.res_id',
                ['task_status'],
                Select::JOIN_LEFT
            );

            $where = new Where();
            $where
                ->equalTo($this->getTable() . '.apartment_id_assigned', $apartmentId)
                ->equalTo($this->getTable() . '.status', BookingService::BOOKING_STATUS_BOOKED)
                ->notEqualTo($this->getTable() . '.overbooking_status', BookingTicketService::OVERBOOKING_STATUS_OVERBOOKED)
                ->lessThanOrEqualTo($this->getTable() . '.date_from', $todayDate)
                ->expression('CONCAT(' . $this->getTable() . '.date_from," ",apartment_description.check_in) <= ' . "'".$dateTimeToday."'",[]);

            $select->where($where);
            $select->order(array($this->getTable() . '.date_from DESC'));
        });
        $this->resultSetPrototype->setArrayObjectPrototype($prototype);
        return $result;


    }



    public function getReservationByIdForHousekeeping($resId)
    {
        $prototype = $this->resultSetPrototype->getArrayObjectPrototype();
        $this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());

        $columns = array(
            'id'                    => 'id',
            'apartment_id_assigned' => 'apartment_id_assigned',
            'res_number'            => 'res_number',
            'date_to'               => 'date_to',
            'date_from'             => 'date_from',
            'pax'                   => 'man_count',
            'pin'                   => 'pin',
            'outside_door_code'     => 'outside_door_code',
            'occupancy'             => 'occupancy',
            'timestamp'             => 'timestamp',
            'no_refresh'            => 'no_refresh'

        );

        $result = $this->fetchOne(function (Select $select) use($resId, $columns) {
            $select->columns($columns);

            $select->join(
                ['apartment_description' => DbTables::TBL_PRODUCT_DESCRIPTIONS],
                $this->getTable() . '.apartment_id_assigned = apartment_description.apartment_id',
                [
                    'apartment_check_in_time' => 'check_in',
                    'apartment_check_out_time' => 'check_out'
                ],
                Select::JOIN_LEFT
            );
            $select->join(
                ['task' => DbTables::TBL_TASK],
                $this->getTable() . '.id = task.res_id',
                ['task_status'],
                Select::JOIN_LEFT
            );

            $where = new Where();
            $where->equalTo($this->getTable() . '.id', $resId);
            ;

            $select->where($where);
            $select->order(array($this->getTable() . '.date_from DESC'));
        });
        $this->resultSetPrototype->setArrayObjectPrototype($prototype);
        return $result;


    }

    public function getNextReservationsForApartment($apartmentId, $todayDate, $lastReservation, $dateTimeAfter2days = false)
    {
        $prototype = $this->resultSetPrototype->getArrayObjectPrototype();
        $this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());



        $columns = array(
            'id'                    => 'id',
            'apartment_id_assigned' => 'apartment_id_assigned',
            'res_number'            => 'res_number',
            'date_to'               => 'date_to',
            'date_from'             => 'date_from',
            'pin'                   => 'pin',
            'pax'                   => 'man_count',
            'outside_door_code'     => 'outside_door_code',
            'occupancy'             => 'occupancy',
            'timestamp'             => 'timestamp',
            'no_refresh'            => 'no_refresh'

        );

        $result = $this->fetchAll(function (Select $select) use($apartmentId, $todayDate, $columns, $lastReservation, $dateTimeAfter2days) {
            $select->columns($columns);

            $select->join(
                ['apartment_description' => DbTables::TBL_PRODUCT_DESCRIPTIONS],
                $this->getTable() . '.apartment_id_assigned = apartment_description.apartment_id',
                [
                    'apartment_check_in_time' => 'check_in',
                    'apartment_check_out_time' => 'check_out'
                ],
                Select::JOIN_LEFT
            );
            $select->join(
                ['task' => DbTables::TBL_TASK],
                $this->getTable() . '.id = task.res_id',
                ['task_status'],
                Select::JOIN_LEFT
            );

            $where = new Where();
            $where
                ->equalTo($this->getTable() . '.apartment_id_assigned', $apartmentId)
                ->equalTo($this->getTable() . '.status', BookingService::BOOKING_STATUS_BOOKED)
                ->notEqualTo($this->getTable() . '.overbooking_status', BookingTicketService::OVERBOOKING_STATUS_OVERBOOKED)
                ->greaterThanOrEqualTo($this->getTable() . '.date_from', $todayDate)
                ->notEqualTo($this->getTable() . '.id', $lastReservation['id'])
                ->greaterThanOrEqualTo($this->getTable() . '.date_from', $lastReservation['date_to']);

            if ($dateTimeAfter2days !== false) {
                $where->lessThanOrEqualTo($this->getTable() . '.date_from', $dateTimeAfter2days);
            } else {
                $select->limit(1);
            }

            $select->where($where);
            $select->group([$this->getTable() . '.id']);
            $select->order(array($this->getTable() . '.date_from ASC'));
        });
        $this->resultSetPrototype->setArrayObjectPrototype($prototype);
        return $result;


    }

    public function getBookingInfoByApartmentIdAndCheckoutDate($apartmentId,$date)
    {
        $prototype = $this->resultSetPrototype->getArrayObjectPrototype();
        $this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());
        $columns = array(
            'pin'=> 'pin',
        );

        $res = $this->fetchOne(function (Select $select) use($apartmentId, $date, $columns) {
            $where = new Where();
            $where
                ->equalTo($this->getTable() . '.date_to', $date)
                ->equalTo($this->getTable() . '.status', BookingService::BOOKING_STATUS_BOOKED)
//                ->notEqualTo($this->getTable() . '.arrival_status', ReservationTicketService::BOOKING_ARRIVAL_STATUS_NO_SHOW)
                ->equalTo($this->getTable() . '.apartment_id_assigned', $apartmentId);
            $select
                ->columns($columns)
                ->where($where);
        });
        $this->resultSetPrototype->setArrayObjectPrototype($prototype);
        return $res['pin'];
    }

    public function getActiveReservationStartingFromDate($dateStarting, $dateTill)
    {
        $prototype = $this->resultSetPrototype->getArrayObjectPrototype();
        $this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());
        $columns = array(
            'id',
            'apartment_id_assigned',
            'date_to'
        );

        $res = $this->fetchAll(function (Select $select) use($dateStarting, $dateTill, $columns) {
            $select->join(
                ['tasks' => DbTables::TBL_TASK],
                New Expression($this->getTable() . '.id = tasks.res_id AND `tasks`.`task_type`=3'),
                [],
                Select::JOIN_LEFT
            );
            $where = new Where();
            $where
                ->expression(
                    'DATE('.$this->getTable().'.date_from) >= \'' . $dateStarting . '\'' .
                    ' AND ' .
                    'DATE('.$this->getTable().'.date_from) <= \'' . $dateTill . '\''
                    ,
                    []
                )
                ->equalTo($this->getTable() . '.status', BookingService::BOOKING_STATUS_BOOKED)
                ->notEqualTo($this->getTable() . '.arrival_status', ReservationTicketService::BOOKING_ARRIVAL_STATUS_NO_SHOW)
//                ->equalTo($this->getTable() . '.apartment_id_assigned', 42);
                ->isNull('tasks.id');
            $select
                ->columns($columns)
                ->where($where);
        });
        $this->resultSetPrototype->setArrayObjectPrototype($prototype);
        return $res;
    }

    /**
     * @param $apartmentGroupId
     * @param $from
     * @param $to
     * @param $roomCount
     * @param $roomType
     * @return ResultSet
     */
    public function getApartmentGroupOverbookingsForDateRange($apartmentGroupId, $from, $to, $roomCount, $roomType)
    {
        $to = date('Y-m-j', strtotime($to . ' +1 days'));
        $prototype = $this->resultSetPrototype->getArrayObjectPrototype();
        $this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());
        $result = $this->fetchAll(function (Select $select) use ($apartmentGroupId, $from, $to, $roomCount, $roomType) {
            $where = new Where();
            $where
                ->lessThan($this->getTable() . '.date_from', $to)
                ->greaterThan($this->getTable() . '.date_to', $from)
                ->equalTo($this->getTable() . '.status', BookingService::BOOKING_STATUS_BOOKED)
                ->equalTo($this->getTable() . '.overbooking_status', ReservationTicketService::OVERBOOKING_STATUS_OVERBOOKED);

            if ($roomCount != -1) {
                $where->equalTo('A.bedroom_count', $roomCount);
            }

            $select
                ->columns([
                    'channel_res_id', 'res_number', 'ki_viewed', 'date_from', 'date_to', 'apartel_id',
                    'apartment_id' => 'apartment_id_assigned', 'occupancy', 'guest_balance',
                    'is_locked' => 'locked',
                    'res_length' => new Expression('datediff(date_to, date_from)'),
                    'draw_length' => new Expression('datediff(least(date_to, "' . $to . '"), greatest(date_from, "' . $from . '"))'),
                    'draw_start' => new Expression('datediff(greatest(date_from, "' . $from . '"), "' . $from . '")'),
                    'res_start' => new Expression('datediff(date_from, "' . $from . '")')
                ])
                ->join(
                    ['AGI' => DbTables::TBL_APARTMENT_GROUP_ITEMS],
                    new Expression($this->getTable() . '.apartment_id_assigned = AGI.apartment_id AND AGI.apartment_group_id = ' . $apartmentGroupId),
                    [],
                    Select::JOIN_INNER
                )
                ->join(
                    ['A' => DbTables::TBL_APARTMENTS],
                    $this->getTable() . '.apartment_id_assigned = A.id',
                    ['apartment_name' => 'name'],
                    Select::JOIN_INNER
                );

                if ($roomType > 0) {
                    $select->join(
                        ['rel_apartment_room_type' => DbTables::TBL_APARTEL_REL_TYPE_APARTMENT],
                        new Expression($this->getTable() . '.apartment_id_assigned = rel_apartment_room_type.apartment_id AND rel_apartment_room_type.apartel_type_id = ' . $roomType),
                        [],
                        Select::JOIN_INNER
                    );
                }

                $select->where($where);
        });
        $this->resultSetPrototype->setArrayObjectPrototype($prototype);
        return $result;
    }

    public function getLockByReservation($resId, $usage)
    {
        $prototype = $this->resultSetPrototype->getArrayObjectPrototype();
        $this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());


        switch ($usage) {
            case LockService::USAGE_APARTMENT_TYPE:
                $selectedDb = 'apartments';
                break;
            case LockService::USAGE_BUILDING_TYPE:
                $selectedDb = 'building_section';
                break;
            case LockService::USAGE_PARKING_TYPE:
                $selectedDb = 'parking';
                break;
        }

        $result =  $this->fetchAll(function (Select $select) use($resId, $usage, $selectedDb) {
            $select->columns(['id']);

            $select->join(
                ['apartments' => DbTables::TBL_APARTMENTS],
                $this->table . '.apartment_id_assigned = apartments.id',
                ['apartment_lock_id' => 'lock_id'],
                Select::JOIN_LEFT
            );

            if ($usage == LockService::USAGE_BUILDING_TYPE) {
                $select->join(
                    ['building_section' => DbTables::TBL_BUILDING_SECTIONS],
                    'building_section.id = apartments.building_section_id',
                    ['building_lock_id' => 'lock_id'],
                    Select::JOIN_LEFT
                );
            } elseif ($usage == LockService::USAGE_PARKING_TYPE) {

                $select->join(
                    ['a_details' => DbTables::TBL_APARTMENTS_DETAILS],
                    'a_details.apartment_id= apartments.id',
                    [],
                    Select::JOIN_LEFT
                );

                $select->join(
                    ['pb' => DbTables::TBL_BUILDING_LOTS],
                    'apartments.building_section_id = pb.building_section_id',
                    [],
                    Select::JOIN_LEFT
                );

                $select->join(
                    ['parking' => DbTables::TBL_PARKING_LOTS],
                    'parking.id = pb.lot_id',
                    ['parking_lock_id' => 'lock_id'],
                    Select::JOIN_LEFT
                );
            }

            $select->join(
                ['city' => DbTables::TBL_CITIES],
                'apartments.city_id = city.id',
                ['timezone'],
                Select::JOIN_LEFT
            );
            $select->join(
                ['locks' => DbTables::TBL_LOCKS],
                'locks.id = ' . $selectedDb . '.lock_id',
                ['type_id'],
                Select::JOIN_LEFT
            );
            $select->join(
                ['lock_type_settings' => DbTables::TBL_LOCK_TYPE_SETTINGS],
                'lock_type_settings.lock_type_id = locks.type_id',
                ['setting_item_id'],
                Select::JOIN_LEFT
            );
            $select->join(
                ['lock_type_setting_items' => DbTables::TBL_LOCK_TYPE_SETTING_ITEMS],
                'lock_type_settings.setting_item_id = lock_type_setting_items.id',
                ['name'],
                Select::JOIN_LEFT
            );
            $select->join(
                ['lock_settings' => DbTables::TBL_LOCK_SETTINGS],
                new Expression('lock_settings.setting_item_id = lock_type_setting_items.id AND lock_settings.lock_id = locks.id'),
                ['value'],
                Select::JOIN_LEFT
            );

            $select->where->equalTo($this->getTable() . '.id' , $resId);
            $select->order('lock_type_settings.setting_item_id ASC');
        });
        $this->resultSetPrototype->setArrayObjectPrototype($prototype);

        return $result;
    }

    /**
     * @param int $userId
     * @param int $resId
     * @return array|\ArrayObject|null
     */
    public function checkFrontierPermission($userId, $resId)
    {
        $this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());

        return $this->fetchOne(function (Select $select) use ($userId, $resId) {
            $columns = ['id'];

            $select
                ->columns($columns)
                ->join(
                    ['apartments' => DbTables::TBL_APARTMENTS],
                    $this->getTable() . '.apartment_id_assigned = apartments.id',
                    ['apartment_assigned' => 'name'],
                    Select::JOIN_INNER
                )->join(
                    ['cda' => DbTables::TBL_CONCIERGE_DASHBOARD_ACCESS],
                    new Expression('apartments.building_id = cda.apartment_group_id AND cda.user_id = ' . $userId),
                    []
                )
                ->where->equalTo($this->getTable() . '.id', $resId);
        });
    }

    /**
     * @param $reservationId
     * @return int
     */
    public function checkIsApartel($reservationId)
    {
        $prototype = $this->resultSetPrototype->getArrayObjectPrototype();
        $this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());
        $result = $result = $this->fetchOne(function (Select $select) use ($reservationId) {
            $select->columns([
                'apartel_id',
            ]);

            $select->where->equalTo('id', $reservationId)
                          ->greaterThan('apartel_id', 0)
                          ->greaterThan('channel_res_id', 0);
        });
        $this->resultSetPrototype->setArrayObjectPrototype($prototype);
        return $result && $result['apartel_id'] ? $result['apartel_id'] : 0;
    }

    public function getInfoForCheckLastMinute($resId)
    {
        $prototype = $this->resultSetPrototype->getArrayObjectPrototype();
        $this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());
        $result = $this->fetchOne(function (Select $select) use ($resId) {
            $columns = ['date_from','timestamp'];
            $select
                ->columns($columns)
                ->where->equalTo($this->getTable() . '.id', $resId);
        });
        $this->resultSetPrototype->setArrayObjectPrototype($prototype);
        return $result;
    }

    /**
     * @param $reservationId
     * @return array|\ArrayObject|null
     */
    public function getReservationDataForResolved($reservationId)
    {
        $prototype = $this->resultSetPrototype->getArrayObjectPrototype();
        $this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());
        $result = $this->fetchOne(function (Select $select) use ($reservationId) {
            $select->columns([
                'date_from'         => 'date_from',
                'date_to'           => 'date_to',
                'apartment_id'      => 'apartment_id_assigned',
            ]);
            $select->where->equalTo($this->getTable() . '.id', $reservationId);
        });
        $this->resultSetPrototype->setArrayObjectPrototype($prototype);
        return $result;
    }


    public function getReservationByspotId($spotId)
    {
        $prototype = $this->resultSetPrototype->getArrayObjectPrototype();
        $this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());

        $result = $this->fetchAll(function (Select $select) use ($spotId) {
            $select->columns([
                'id',
                'res_number'
            ]);

            $select->join(
                ['charge' => DbTables::TBL_CHARGE],
                $this->getTable() . '.id = charge.reservation_id',
                [],
                Select::JOIN_LEFT
            );

            $select->where
                ->equalTo('charge.entity_id', $spotId)
                ->equalTo($this->getTable() . '.status', BookingService::BOOKING_STATUS_BOOKED)
                ->in(
                    $this->getTable() . '.arrival_status',
                    [BookingTicketService::BOOKING_ARRIVAL_STATUS_EXPECTED, BookingTicketService::BOOKING_ARRIVAL_STATUS_CHECKED_IN]
                )->greaterThan($this->getTable() . '.date_to', date('Y-m-d'))
                ->equalTo('charge.status', ChargeService::CHARGE_STATUS_NORMAL);

                $select->order($this->getTable() . '.date_from');
                $select->group($this->getTable() . '.id');
        });

        $this->resultSetPrototype->setArrayObjectPrototype($prototype);
        return $result;
    }

    /**
     * @param $id
     * @return BookingTicket
     */
    public function getDataForDiscountValidationById($id)
    {
        $this->resultSetPrototype->setArrayObjectPrototype(new \DDD\Domain\Booking\BookingTicket());

        $result = $this->fetchOne(function (Select $select) use ($id) {
            $select->columns([
                'guest_email' => 'guest_email',
                'partner_id' => 'partner_id',
                'partner_ref' => 'partner_ref',
                'booker_price'			=> 'booker_price',
            ]);

            $select->where->equalTo($this->getTable().'.id', $id);
        });

        return $result;
    }

    /**
     * @param $reservationId
     * @param array $fields
     * @return array|\ArrayObject|null
     */
    public function getDataById($reservationId, $fields = [])
    {
        $this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());

        $result = $this->fetchOne(function (Select $select) use ($reservationId, $fields) {
            $select->columns($fields);

            $select->where->equalTo($this->getTable().'.id', $reservationId);
        });

        return $result;
    }

    /**
     * @param $reservationId
     * @param bool $guestCurrency
     * @return \DDD\Domain\Booking\ChargeProcess |\ArrayObject|null
     */
    public function getDataForToBeCharged($reservationId, $guestCurrency = false)
    {
        $this->resultSetPrototype->setArrayObjectPrototype(new \DDD\Domain\Booking\ChargeProcess());
        $joinCurrency = $guestCurrency ? 'guest_currency_code' : 'apartment_currency_code';
        $result = $this->fetchOne(function (Select $select) use ($reservationId, $joinCurrency) {
            $select->columns([
                'guest_email',
                'booker_price',
                'price',
                'occupancy',
                'date_from',
                'date_to',
                'partner_name',
                'partner_id',
                'apartment_id' => 'apartment_id_assigned',
                'apartment_currency_code',
                'guest_currency_code',
            ]);

            $select->join(
                    ['city' => DbTables::TBL_CITIES],
                    $this->getTable().'.acc_city_id = city.id',
                    ['timezone'],
                    Select::JOIN_LEFT
                )->join(
                    ['location_detail' => DbTables::TBL_LOCATION_DETAILS],
                    'city.detail_id = location_detail.id',
                    [
                        'city_tot' => 'tot',
                        'city_tot_type' => 'tot_type',
                        'tot_included',
                        'tot_additional',
                        'tot_max_duration',
                        'city_vat' => 'vat',
                        'city_vat_type' => 'vat_type',
                        'vat_included',
                        'vat_additional',
                        'vat_max_duration',
                        'city_tax',
                        'city_tax_type',
                        'city_tax_included',
                        'city_tax_additional',
                        'city_tax_max_duration',
                        'city_sales_tax' => 'sales_tax',
                        'sales_tax_additional',
                        'city_sales_tax_type' => 'sales_tax_type',
                        'sales_tax_included',
                        'sales_tax_max_duration',
                    ],
                    Select::JOIN_LEFT
                )->join(
                    ['currency' => DbTables::TBL_CURRENCY],
                    $this->getTable() . '.' . $joinCurrency . ' = currency.code',
                    ['currency_symbol' => 'symbol']
                )->join(
                    ['logs' => DbTables::TBL_ACTION_LOGS],
                    new Expression($this->getTable() . '.id = logs.identity_id AND logs.user_id = ' . UserService::USER_GUEST),
                    ['remarks' => 'value'],
                    Select::JOIN_LEFT
                );

            $select->where->equalTo($this->getTable().'.id', $reservationId);
        });

        return $result;
    }

    public function getGreaterOccupancyRes($apartmentId)
    {
        $prototype = $this->resultSetPrototype->getArrayObjectPrototype();
        $this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());

        $result = $this->fetchAll(function (Select $select) use ($apartmentId) {
            $select->columns(['id']);

            $select->join(
                ['apartment' => DbTables::TBL_APARTMENTS],
                $this->getTable() . '.apartment_id_assigned =  apartment.id',
                []
            );

            $select->where
                ->equalTo($this->getTable() . '.status', BookingService::BOOKING_STATUS_BOOKED)
                ->equalTo($this->getTable() . '.apartment_id_assigned', $apartmentId)
                ->greaterThan($this->getTable() . '.date_from', new Expression('DATE_SUB(CURDATE(), INTERVAL 1 DAY)'))
                ->greaterThan($this->getTable() . '.occupancy', 'apartment.max_capacity');
        });

        $this->resultSetPrototype->setArrayObjectPrototype($prototype);
        return $result;
    }
}
