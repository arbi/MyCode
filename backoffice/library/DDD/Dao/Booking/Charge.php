<?php

namespace DDD\Dao\Booking;

use DDD\Service\Booking\BookingAddon;
use Zend\Db\Sql\Select;
use Library\DbManager\TableGatewayManager;
use Library\Constants\DbTables;
use Zend\Db\Sql\Expression;
use DDD\Domain\Booking\SumTransaction;
use DDD\Service\Booking\Charge as ChargeService;
use Zend\Db\Sql\Predicate\Predicate;
use Zend\Db\Sql\Where;

/**
 * Class Charge
 * @package DDD\Dao\Booking
 */
class Charge extends TableGatewayManager
{
    protected $table = DbTables::TBL_CHARGE;

    public function __construct($sm, $domain = 'DDD\Domain\Booking\Charge')
    {
        parent::__construct($sm, $domain);
    }

    public function getChargeById($chargeId)
    {
        $prototype = $this->resultSetPrototype->getArrayObjectPrototype();
        $this->setEntity(new \DDD\Domain\Booking\Charge);
        $result = $this->fetchOne(function (Select $select) use ($chargeId) {
          $select->where(['id' => $chargeId]);
        });
        $this->setEntity($prototype);
        return $result;
    }
    /**
     * @param int $reservationId
     * @param int $check
     * @param bool $ignorePenalty
     * @return \DDD\Domain\Booking\Charge[]
     */
    public function getChargesByReservationId($reservationId, $check = 0, $type = false, $ignorePenalty = false)
    {
        $previousEntity = $this->getEntity();
        $this->setEntity(new \DDD\Domain\Booking\Charge());

        $result = $this->fetchAll(function (Select $select) use($reservationId, $check, $type, $ignorePenalty) {
            $select->join(
                ['addons' => DbTables::TBL_BOOKING_ADDONS],
                $this->getTable() . '.addons_type = addons.id',
                ['addon' => 'name', 'location_join'],
                Select::JOIN_LEFT
            );

            $select->join(
                ['users' => DbTables::TBL_BACKOFFICE_USERS],
                $this->getTable() . '.user_id = users.id',
                ['user' => new Expression("CONCAT(users.firstname, ' ', users.lastname)")],
                Select::JOIN_LEFT
            );

            if (!$check) {
                $select->join(
                    ['charge_deleted' => DbTables::TBL_CHARGE_DELETED],
                    $this->getTable() . '.id = charge_deleted.reservation_charge_id',
                    [
                        'user_delete_id' => 'user_id',
                        'date_delete' => 'date',
                        'comment_delete' => 'comment'
                    ],
                    Select::JOIN_LEFT
                );

                $select->join(
                    ['users_delete' => DbTables::TBL_BACKOFFICE_USERS],
                    'charge_deleted.user_id = users_delete.id',
                    ['user_delete' => new Expression("CONCAT(users_delete.firstname, ' ', users_delete.lastname)")],
                    Select::JOIN_LEFT
                );
            }
            $select->where
                ->equalTo($this->getTable() . '.reservation_id', $reservationId);

            if ($check) {
                $select->where
                    ->notEqualTo($this->getTable() . '.status', 1);
            }

            // Ignore ginosi penalty charges when generating receipt
            if ($ignorePenalty) {
                $select->where->notEqualTo($this->getTable() . '.type', 'g');
            }

            if ($type) {
                $select->where
                    ->equalTo($this->getTable() . '.addons_type', $type);

                $select->order([
                    $this->getTable() . '.acc_amount ASC',
                    $this->getTable() . '.commission ASC',
                    $this->getTable() . '.money_direction ASC',
                    $this->getTable() . '.reservation_nightly_date ASC',
                ]);
            } else {
                $select->order([
                    $this->getTable() . '.status ASC',
                    new Expression('-' . $this->getTable() . '.reservation_nightly_date DESC'),
                    $this->getTable() . '.reservation_nightly_id DESC',
                    $this->getTable() . '.addons_type ASC'
                ]);
            }
        });

        $this->setEntity($previousEntity);
        return $result;
    }

    /**
     * @param int $reservationId
     * @return \DDD\Domain\Booking\ChargeForView []
     */
    public function getChargesForView($reservationId)
    {
        $this->setEntity(new \DDD\Domain\Booking\ChargeForView());
        $result = $this->fetchAll(function (Select $select) use($reservationId) {
            $select->columns([
                'addons_type',
                'acc_amount',
                'apartment_currency_code' => 'acc_currency',
                'date',
                'addons_value',
                'tax_type',
                'reservation_nightly_date',
            ]);
            $select->join(
                ['addons' => DbTables::TBL_BOOKING_ADDONS],
                $this->getTable() . '.addons_type = addons.id',
                [
                    'addon' => 'name'
                ],
                Select::JOIN_LEFT
            )->join(
                ['currency' => DbTables::TBL_CURRENCY],
                $this->getTable() . '.acc_currency = currency.code',
                ['currency_symbol' => 'symbol']
            );

            $select->where
                ->equalTo($this->getTable() . '.reservation_id', $reservationId)
                ->equalTo($this->getTable() . '.status', \DDD\Service\Booking\Charge::CHARGE_STATUS_NORMAL);

            $select->order([$this->getTable() . '.status ASC', new Expression('-' . $this->getTable() . '.reservation_nightly_date DESC'), $this->getTable() . '.addons_type ASC']);
        });

        return $result;
    }

    /**
     * THIS ONE MUST BE DELETED! DO NOT USE!
     * @param int $reservationId
     * @return SumTransaction
     */
    public function chargedSum($reservationId) {
     	$this->resultSetPrototype->setArrayObjectPrototype(new \DDD\Domain\Booking\SumTransaction());

        $result = $this->fetchOne(function (Select $select) use($reservationId) {
            $select->columns(array(
                                    'sum_acc'=>new Expression("SUM(acc_amount)"),
                                    'sum_customer'=>new Expression("SUM(customer_amount)"),
                                   ));
            $select->where
                    ->equalTo('reservation_id', $reservationId)
                    ->equalTo('status', ChargeService::CHARGE_STATUS_NORMAL);
		});
        return $result;
    }

    /**
     * Calculate summary of charges both in apartment and customer currencies
     * @param int $reservationId
     * @param number $moneyDirection
     * @return TransactionSummary
     */
    public function calculateChargesSummary($reservationId, $moneyDirection)
    {
    	$this->resultSetPrototype->setArrayObjectPrototype(new \DDD\Domain\Booking\ChargeSummary());

    	$columns = array(
    		'summary_apartment_currency'=> new Expression("SUM(IF(type<>'g', acc_amount, 0))"),
    		'commission_summary_apartment_currency' => new Expression("SUM( acc_amount*commission/100 )"),
    		'commission_summary_customer_currency' => new Expression("SUM( customer_amount*commission/100 )"),
    		'summary_customer_currency'	=> new Expression("SUM(IF(type<>'g', customer_amount, 0))"),
    		'total_apartment_currency' => new Expression("SUM(acc_amount)"),
    		'total_customer_currency' => new Expression("SUM(customer_amount)")
    	);

    	if ($moneyDirection == ChargeService::CHARGE_MONEY_DIRECTION_PARTNER_COLLECT) {
    		$columns = array(
    			'summary_apartment_currency'=> new Expression("SUM(acc_amount-acc_amount*commission/100)"),
    			'summary_customer_currency'	=> new Expression("SUM(customer_amount-customer_amount*commission/100)"),
    			'total_apartment_currency' => new Expression("SUM(acc_amount)"),
    			'total_customer_currency' => new Expression("SUM(customer_amount)")
    		);
    	}

    	$result = $this->fetchOne(function (Select $select) use($reservationId, $moneyDirection, $columns) {
    		$select->columns($columns);
    		$select->where
    				->equalTo('reservation_id', $reservationId)
    				->equalTo('money_direction', $moneyDirection)
                    ->equalTo('status', ChargeService::CHARGE_STATUS_NORMAL);
    	});
    	return $result;
    }

	/**
	 * @param int $reservationId
	 *
	 * @return \Traversable|array
	 */
	public function getFirstAccTaxCharge($reservationId)
    {
    	$this->resultSetPrototype->setArrayObjectPrototype(new \DDD\Domain\Booking\ForCancelCharge());

        $result = $this->fetchAll(function (Select $select) use($reservationId) {
            $select->join(
            	['addons' => DbTables::TBL_BOOKING_ADDONS],
            	$this->getTable() . '.addons_type = addons.id',
            	[
            		'addon'=> 'name',
            		'location_tax'=>'location_join',
            		'cxl_apply'=>'cxl_apply'
            	],
            	Select::JOIN_LEFT
    		);

            $select->where->equalTo($this->getTable().'.reservation_id', $reservationId);
            $select->where->equalTo($this->table . '.status', ChargeService::CHARGE_STATUS_NORMAL);

            $accOrTaxWhere = new Predicate();
            $accOrTaxWhere->in($this->table . '.addons_type', [
                                                                BookingAddon::ADDON_TYPE_ACC,
                                                                BookingAddon::ADDON_TYPE_PARKING,
                                                                BookingAddon::ADDON_TYPE_CLEANING_FEE,
                                                                BookingAddon::ADDON_TYPE_DISCOUNT,
                                                                BookingAddon::ADDON_TYPE_COMPENSATION,
                                                                BookingAddon::ADDON_TYPE_EXTRA_PERSON,
                                                              ]); // acc or parking
            $accOrTaxWhere->OR;
            $accOrTaxWhere->notEqualTo('addons.location_join', ''); // taxes

            $select->where->addPredicate($accOrTaxWhere);

            $select->order('id ASC');
		});

        return $result;
    }

    /**
     * @param int $reservationId
     * @return SumTransaction
     */
    public function chargedSumForPenalty($reservationId)
    {
        $this->setEntity(new \ArrayObject());

        $result = $this->fetchOne(function (Select $select) use($reservationId) {
            $select->columns(array(
                                    'sum_charge'=>new Expression("SUM(acc_amount)"),
                                   ));
             $select->join(
            	['addons' => DbTables::TBL_BOOKING_ADDONS],
            	$this->getTable() . '.addons_type = addons.id',
            	[]
    		);
            $select->where
                    ->equalTo($this->getTable() . '.reservation_id', $reservationId)
                    ->greaterThanOrEqualTo('addons.cxl_apply', 1)
                    ->equalTo($this->getTable() . '.status', ChargeService::CHARGE_STATUS_NORMAL);
		});
        return $result;
    }

    /**
     * @param Where $statement
     * @param bool $isGroupSelected
     * @return \Zend\Db\ResultSet\ResultSet|array[]
     */
    public function getChargeSummary($statement, $isGroupSelected)
    {
        $this->setEntity(new \ArrayObject());

        return $this->fetchAll(function(Select $select) use ($statement, $isGroupSelected) {
            $rTable = DbTables::TBL_BOOKINGS;
            $aTable = DbTables::TBL_APARTMENTS;
            $agiTable = DbTables::TBL_APARTMENT_GROUP_ITEMS;

            $select->columns([
                'amount' => new Expression("SUM({$this->getTable()}.acc_amount)"),
                'count' => new Expression("COUNT({$this->getTable()}.acc_amount)"),
                'currency' => 'acc_currency',
                'addon_type' => 'addons_type',
                'commission',
            ]);
            $select->join(
                ['addons' => DbTables::TBL_BOOKING_ADDONS],
                $this->getTable() . '.addons_type = addons.id',
                ['addon' => 'name'],
                Select::JOIN_LEFT
            );
            $select->join(
                DbTables::TBL_BOOKINGS,
                "{$this->getTable()}.reservation_id = {$rTable}.id",
                [],
                Select::JOIN_LEFT
            );

            if ($isGroupSelected) {
                $select->join(
                    DbTables::TBL_APARTMENTS,
                    "{$rTable}.apartment_id_origin = {$aTable}.id",
                    [],
                    Select::JOIN_LEFT
                );
                $select->join(
                    DbTables::TBL_APARTMENT_GROUP_ITEMS,
                    "{$agiTable}.apartment_id = {$aTable}.id",
                    [],
                    Select::JOIN_RIGHT
                );
            }

            $select->where($statement);
            $select->group([
                $this->getTable() . '.addons_type',
                $this->getTable() . '.acc_currency',
            ]);
        });
    }

    /**
     * @param Where $statement
     * @param bool $isGroupSelected
     * @return \Zend\Db\ResultSet\ResultSet|array[]
     */
    public function getChargeDownloadable($statement, $isGroupSelected)
    {
        $this->setEntity(new \ArrayObject());

        return $this->fetchAll(function(Select $select) use ($statement, $isGroupSelected) {
            $rTable = DbTables::TBL_BOOKINGS;
            $aTable = DbTables::TBL_APARTMENTS;
            $agiTable = DbTables::TBL_APARTMENT_GROUP_ITEMS;

            $select->join(
                ['addons' => DbTables::TBL_BOOKING_ADDONS],
                $this->getTable() . '.addons_type = addons.id',
                ['addon' => 'name'],
                Select::JOIN_LEFT
            );
            $select->join(
                DbTables::TBL_BOOKINGS,
                "{$this->getTable()}.reservation_id = {$rTable}.id",
                ['res_number'],
                Select::JOIN_LEFT
            );

            if ($isGroupSelected) {
                $select->join(
                    DbTables::TBL_APARTMENTS,
                    "{$rTable}.apartment_id_origin = {$aTable}.id",
                    [],
                    Select::JOIN_LEFT
                );
                $select->join(
                    DbTables::TBL_APARTMENT_GROUP_ITEMS,
                    "{$agiTable}.apartment_id = {$aTable}.id",
                    [],
                    Select::JOIN_RIGHT
                );
            }

            $select->where($statement);
        });
    }

    public function getChargePriceByNightlyId($nightlyId)
    {
        $this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());

        $result = $this->fetchOne(function (Select $select) use($nightlyId) {
            $select->columns(array(
                'price'=>new Expression("SUM(acc_amount)"),
            ));
            $select->where
                ->equalTo('reservation_nightly_id', $nightlyId)
                ->equalTo('status', ChargeService::CHARGE_STATUS_NORMAL)
                ->equalTo('addons_type', BookingAddon::ADDON_TYPE_ACC)
            ;
        });

        return $result ? $result['price'] : 0;
    }

    public function checkChargeTypeIsParking($chargeId)
    {
        $this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());

        return $result = $this->fetchOne(function (Select $select) use($chargeId) {
            $select->columns([
                'id'
            ]);
            $select->where
                ->equalTo('id', $chargeId)
                ->equalTo('addons_type', BookingAddon::ADDON_TYPE_PARKING);
        });
    }

    public function getChargeDataByNightlyIdAddons($nightId, $addons)
    {
        $this->resultSetPrototype->setArrayObjectPrototype(new \DDD\Domain\Booking\Charge());

        return $result = $this->fetchAll(function (Select $select) use($nightId, $addons) {
            $select->columns([
                'id',
                'acc_amount',
                'addons_type',
            ]);
            $select->where
                ->equalTo('reservation_nightly_id', $nightId)
                ->equalTo('addons_type', $addons);
        });
    }

    /**
     * @param string $reservationNumber
     * @return \Zend\Db\ResultSet\ResultSet
     */
    public function getParkingInfoByReservationId($reservationNumber)
    {
        $prototype = $this->resultSetPrototype->getArrayObjectPrototype();
        $this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());
        $result = $this->fetchAll(function (Select $select) use($reservationNumber) {
            $select->columns(['reservation_nightly_id', 'reservation_nightly_date']);
            $select->join(
                ['reservations' => DbTables::TBL_BOOKINGS],
                $this->getTable() . '.reservation_id = reservations.id',
                ['guest_balance' => 'guest_balance'],
                Select::JOIN_INNER
            );

            $select->join(
                ['spots' => DbTables::TBL_PARKING_SPOTS],
                $this->getTable() . '.entity_id = spots.id',
                [
                    'spot_unit' => 'unit',
                    'permit_id' => 'permit_id'
                ],
                Select::JOIN_INNER
            );

            $select
                ->join(
                    ['lots' => DbTables::TBL_PARKING_LOTS],
                    'spots.lot_id = lots.id',
                    [
                        'lot_name' => 'name',
                        'parking_permit' => 'parking_permit',
                        'lot_id' => 'id',
                        'is_lot_virtual' => 'is_virtual'
                    ],
                    Select::JOIN_INNER
                )
                ->join(
                    ['apartments' => DbTables::TBL_APARTMENTS],
                    'reservations.apartment_id_assigned = apartments.id',
                    [
                    ],
                    Select::JOIN_INNER
                )
                ->join(
                    ['building_details' => DbTables::TBL_BUILDING_DETAILS],
                    'apartments.building_id = building_details.apartment_group_id',
                    [
                    ],
                    Select::JOIN_INNER
                )
                ->join(
                    ['office' => DbTables::TBL_OFFICES],
                    'building_details.assigned_office_id = office.id',
                    [
                        'office_phone' => 'phone',
                    ],
                    Select::JOIN_LEFT
                );

            $select->where
                ->equalTo('reservations.res_number', $reservationNumber)
                ->equalTo($this->getTable() . '.addons_type', BookingAddon::ADDON_TYPE_PARKING)
                ->equalTo($this->getTable() . '.status', ChargeService::CHARGE_STATUS_NORMAL);

        });
        $this->resultSetPrototype->setArrayObjectPrototype($prototype);
        return $result;
    }
}
