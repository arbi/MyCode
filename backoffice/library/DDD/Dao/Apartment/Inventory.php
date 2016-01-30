<?php

namespace DDD\Dao\Apartment;

use DDD\Domain\Apartment\Inventory\RateAvailability;
use DDD\Domain\Apartment\Inventory\RateAvailabilityCancel;
use DDD\Domain\Apartment\Inventory\RateAvailabilityComplete;
use DDD\Domain\Apartment\ProductRate\CubilisRoomRate;
use DDD\Service\Apartment\Rate as RateService;
use DDD\Domain\Apartment\Inventory\RateAvailability as InventoryDomain;

use Library\DbManager\TableGatewayManager;
use Library\Constants\DbTables;
use Library\Utility\Debug;
use Library\Utility\Helper;

use Zend\Db\Sql\Expression;
use Zend\Db\Sql\Select;
use Zend\Db\Sql\Sql;
use Zend\Db\Sql\Where;
use Zend\ServiceManager\ServiceLocatorInterface;
use Library\ChannelManager\ChannelManager as libChannelManager;

/**
 * DAO class for apartment inventory
 * @author Tigran Petrosyan
 */
class Inventory extends TableGatewayManager
{
	/**
	 * @access protected
	 * @var string
	 */
    protected $table = DbTables::TBL_APARTMENT_INVENTORY;

    /**
     * @access public
     * @param ServiceLocatorInterface $sm
     * @param string $domain
     */
    public function __construct($sm, $domain = 'DDD\Domain\Apartment\Inventory\RateAvailability'){
        parent::__construct($sm, $domain);
    }

    /**
     * Get rate availability for given range
     * @access public
     *
     * @param int $rateID
     * @param string $startDate
     * @param string $endDate
     * @return \DDD\Domain\Apartment\Rate\Select
     * @author Tigran Petrosyan
     */
    public function getRateInventoryForRange($rateID, $startDate, $endDate){
    	$result = $this->fetchAll(
    		function (Select $select) use($rateID, $startDate, $endDate) {

    			$columns = array(
    				'id' => 'id',
    				'rate_id' => 'rate_id',
    				'date' => 'date',
    				'price' => 'price',
    				'availability' => 'availability',
    				'room_id' => 'room_id',
    				'apartment_id' => 'apartment_id',
    				'is_lock_price' => 'is_lock_price',
    			);

    			$select->join(array('prod_types' => DbTables::TBL_PRODUCT_TYPES), $this->getTable().'.room_id = prod_types.id', array('cubilis_room_id'=>'cubilis_id'), 'LEFT');
    			$select	->columns($columns);
    			$where = new Where();
    			$where->equalTo($this->table . '.rate_id', $rateID);
    			$where->greaterThanOrEqualTo($this->table.'.date', $startDate);
    			$where->lessThanOrEqualTo($this->table.'.date', $endDate);
    			$select->where($where);
    		}
    	);

    	return $result;
    }

	/**
	 * @param int $room_id
	 * @param string $begin_date
	 * @param string $end_date
	 *
	 * @return RateAvailabilityCancel[]|\ArrayObject
	 */
	public function getRateAvWithDateRangAndRoomId($room_id, $begin_date, $end_date) {
        $this->resultSetPrototype->setArrayObjectPrototype(new \DDD\Domain\Apartment\Inventory\RateAvailabilityCancel);
    	return $this->fetchAll(
    		function (Select $select) use($room_id, $begin_date, $end_date) {
                $select->columns([
                    'date' => 'date',
                    'availability' => 'availability',
                    'price' => 'price'
                ]);
    			$select->join(
				    ['prod_rate' => DbTables::TBL_APARTMENT_RATES],
				    $this->getTable() . '.rate_id = prod_rate.id',
                    ['cubilis_rate_id' => 'cubilis_id'],
				    Select::JOIN_LEFT
			    );
                $select->join(
	                ['prod_types' => DbTables::TBL_PRODUCT_TYPES],
	                $this->getTable() . '.room_id = prod_types.id',
	                ['cubilis_room_id' => 'cubilis_id'],
	                Select::JOIN_LEFT
                );
                $select->where
                   ->equalTo($this->getTable() . '.room_id', $room_id)
                   ->greaterThanOrEqualTo($this->getTable() . '.date', $begin_date)
                   ->lessThan($this->getTable() . '.date', $end_date);
    		}
    	);
    }

    /**
     * @param $itemId
     * @param null $from
     * @param null $to
     * @param string $type
     * @return \Zend\Db\ResultSet\ResultSet
     */
    public function getRateAvailabilityByApartmentId($itemId, $from = null, $to = null, $type = 'product')
    {
		if (is_null($from)) {
			$from = date('Y-m-d');
		}

		return $this->fetchAll(
			function (Select $select) use($itemId, $from, $to, $type) {
				$select->columns([
					'price',
					'date',
					'availability',
					'product_id' => 'apartment_id',
				]);

				$select->join(
					['rates' => DbTables::TBL_APARTMENT_RATES],
					$this->getTable() . '.rate_id = rates.id',
					[
						'cubilis_rate_id' => 'cubilis_id',
						'capacity',
						'min_stay',
						'max_stay',
					],
					Select::JOIN_LEFT
				);

				$select->join(
					['rooms' => DbTables::TBL_PRODUCT_TYPES],
					$this->getTable() . '.room_id = rooms.id',
					[
						'cubilis_room_id' => 'cubilis_id',
					],
					Select::JOIN_LEFT
				);

                if ($type == libChannelManager::SYNC_WITH_TYPE) {
                    $select
                        ->where
                        ->equalTo($this->getTable() . '.room_id', $itemId);
                } elseif ($type == libChannelManager::SYNC_WITH_RATE) {
                    $select
                        ->where
                        ->equalTo($this->getTable() . '.rate_id', $itemId);
                } else {
                    $select
                        ->where
                        ->equalTo($this->getTable() . '.apartment_id', $itemId);
                }

				$select
					->where
						->greaterThanOrEqualTo($this->getTable() . '.date', $from)
						->isNotNull('rates.cubilis_id');

				if (!is_null($to)) {
					$select
						->where
							->lessThanOrEqualTo($this->getTable() . '.date', $to);
				}
			}
		);
	}

	public function getRateAvailabilityByRateId($rateId) {
		return $this->fetchAll(
			function (Select $select) use($rateId) {
				$select->columns([
					'price',
					'date',
					'availability',
				]);
			}
		);
	}

	/**
	 * @param int $rateId
	 * @param string $dateFrom
	 * @param string $dateTo
	 *
	 * @return \DDD\Domain\Apartment\Inventory\RateAvailability[]|\ArrayObject|null
	 */
	public function getAvailabilityByRateIdAndDateRange($rateId, $dateFrom, $dateTo) {
		return $this->fetchAll(
			function (Select $select) use($rateId, $dateFrom, $dateTo) {
				$select->columns([
					'availability',
					'is_changed',
					'is_lock_price',
					'date',
				]);
				$select->where
					->equalTo('rate_id', $rateId)
					->greaterThanOrEqualTo('date', $dateFrom)
					->and
					->lessThanOrEqualTo('date', $dateTo);
			}
		);
	}

	/**
	 * @param int $productRateId
	 * @param string $dateFrom
	 * @param string $dateTo
	 * @return RateAvailabilityComplete
	 */
	public function getMinAvail($productRateId, $dateFrom, $dateTo) {
		return $this->fetchOne(
			function (Select $select) use($productRateId, $dateFrom, $dateTo) {
				$select->columns(['availability' => new Expression('MIN(availability)')]);
				$select
					->where
					->equalTo('rate_id', $productRateId)
					->greaterThanOrEqualTo('date', $dateFrom)
					->lessThan('date', $dateTo);
			}
		);
	}

	/**
	 * @param int $apartmentId
	 * @param string $date
	 * @param int $availability
	 * @return bool
	 */
	public function updateAvailabilityByApartmentIdAndDate($apartmentId, $date, $availability)
    {
		return $this->update([
			'availability' => $availability,
			'is_changed' => 1 - $availability,
		], [
			'apartment_id' => $apartmentId,
			'date' => date('Y-m-d', strtotime($date)),
		]);
	}

    /**
     * @param $apartmentId
     * @param $start_date
     * @param $end_date
     * @return \Zend\Db\ResultSet\ResultSet
     */
    public function getOccupancy($apartmentId, $start_date, $end_date)
    {
    	$result = $this->fetchAll(
    		function (Select $select) use($apartmentId, $start_date, $end_date) {
                $select->columns([
					'date'=>'date'
				]);
    			$select->join(array('prod_rate' => DbTables::TBL_APARTMENT_RATES), $this->getTable().'.rate_id = prod_rate.id', array());
                $select->where
                       ->equalTo($this->getTable().'.apartment_id', $apartmentId)
                       ->greaterThanOrEqualTo($this->getTable().'.date', $start_date)
                       ->lessThanOrEqualTo($this->getTable().'.date', $end_date)
                       ->greaterThan($this->getTable().'.availability', 0)
                       ->equalTo('prod_rate.type', RateService::TYPE1);
    		}
    	);
    	return $result;
    }

    /**
     * @param $apartmentId
     * @param $start_date
     * @param $end_date
     * @return array|\ArrayObject|null
     */
    public function getExtremums($apartmentId, $start_date, $end_date)
    {
    	$result = $this->fetchOne(
    		function (Select $select) use($apartmentId, $start_date, $end_date) {
                $select->columns([
					'max_price'=> new Expression('max(price)'),
					'min_price'=> new Expression('min(price)'),
					'max_availability'=> new Expression('max(availability)'),
				]);
    			$select->join(array('prod_rate' => DbTables::TBL_APARTMENT_RATES), $this->getTable().'.rate_id = prod_rate.id', array());
                $select->where
                       ->equalTo($this->getTable().'.apartment_id', $apartmentId)
                       ->greaterThan($this->getTable().'.date', $start_date)
                       ->lessThan($this->getTable().'.date', $end_date);
    		}
    	);
    	return $result;
    }

    /**
     * @param $apartmentId
     * @param $availability
     * @param $dateFrom
     * @param $dateTo
     * @param $weekDays
     * @return int
     */
	public function updateAvailabilityByApartmentId($apartmentId, $availability, $dateFrom, $dateTo, $weekDays, $all = false)
    {
        $isChanged = $all ? null : 0;
        
        $where = $this->getWhereForInventoryRange($apartmentId, $dateFrom, $dateTo, $weekDays, $isChanged, null);

		return $this->update([
			'availability' => $availability,
		], $where);
	}

	/**
	 * @param array $where
	 *
	 * @return \DDD\Domain\Apartment\Inventory\RateAvailability[]|\ArrayObject|null
	 */
	public function getAvailabilityByPredicate($where) {
		$result = $this->fetchAll(
			function (Select $select) use($where) {
				$select->columns([
					'date',
				]);
				$select->join(
					['rates' => DbTables::TBL_APARTMENT_RATES],
					$this->getTable() . '.rate_id = rates.id',
					[
						'rate_id' => 'cubilis_id',
					],
					Select::JOIN_LEFT
				);
				$select->join(
					['rooms' => DbTables::TBL_PRODUCT_TYPES],
					$this->getTable() . '.room_id = rooms.id',
					[
						'room_id' => 'cubilis_id',
					],
					Select::JOIN_LEFT
				);

				$newWhere = [];
				if (count($where)) {
					foreach ($where as $column => $whereItem) {
						$column = $this->getTable() . '.' . $column;
						$newWhere[$column] = $whereItem;
					}
				}

				$select->where(
					$newWhere
				);

				$select
					->where
						->isNotNull('rates.cubilis_id')
						->and
						->isNotNull('rooms.cubilis_id');
			}
		);

		return $result->buffer();
	}

    /**
     * @param $apartmentId
     * @param $dateFrom
     * @param $dateTo
     * @param $weekDays
     * @param int $isChanged
     * @param int $isLockPrice
     * @return Where
     */
    private function getWhereForInventoryRange($apartmentId, $dateFrom, $dateTo, $weekDays, $isChanged = 0, $isLockPrice = 0) {
        $where = new Where();

        $where
            ->equalTo( DbTables::TBL_APARTMENT_INVENTORY . '.apartment_id', $apartmentId)
            ->greaterThanOrEqualTo( DbTables::TBL_APARTMENT_INVENTORY . '.date', $dateFrom)
            ->lessThanOrEqualTo(DbTables::TBL_APARTMENT_INVENTORY . '.date', $dateTo);

        if ($weekDays) {
            $where->expression("weekday(" . DbTables::TBL_APARTMENT_INVENTORY . ".date) in ({$weekDays})", []);
        }

        if (!is_null($isChanged)) {
            $where->equalTo(DbTables::TBL_APARTMENT_INVENTORY . '.is_changed', $isChanged);
        }

        if (!is_null($isLockPrice)) {
            $where->equalTo(DbTables::TBL_APARTMENT_INVENTORY . '.is_lock_price', $isLockPrice);
        }
        return $where;
    }


    /**
     * @param $rateId
     * @param $dateFrom
     * @param $dateTo
     * @param $weekDays
     * @param int $isChanged
     * @param int $isLockPrice
     * @return Where
     */
    private function getRateWhereForInventoryRange($rateId, $dateFrom, $dateTo, $weekDays, $isChanged = 0, $isLockPrice = 0) {
        $where = new Where();

        $where
            ->equalTo( DbTables::TBL_APARTMENT_INVENTORY . '.rate_id', $rateId)
            ->greaterThanOrEqualTo( DbTables::TBL_APARTMENT_INVENTORY . '.date', $dateFrom)
            ->lessThanOrEqualTo(DbTables::TBL_APARTMENT_INVENTORY . '.date', $dateTo);

        if (!is_null($weekDays)) {
            $where->expression("weekday(" . DbTables::TBL_APARTMENT_INVENTORY . ".date) in ({$weekDays})", []);
        }

        if (!is_null($isChanged)) {
            $where->equalTo(DbTables::TBL_APARTMENT_INVENTORY . '.is_changed', $isChanged);
        }

        if (!is_null($isLockPrice)) {
            $where->equalTo(DbTables::TBL_APARTMENT_INVENTORY . '.is_lock_price', $isLockPrice);
        }
        return $where;
    }

	/**
	 * @param int $rateId
	 * @param string $date
	 *
	 * @return RateAvailability|bool
	 */
	public function getByRateIdAndDate($rateId, $date) {
		$result = $this->fetchOne(
			function (Select $select) use($rateId, $date) {
				$select->columns(['rate_id']);
				$select->where([
					'rate_id' => $rateId,
					'date' => $date,
				]);
			}
		);

		return $result;
	}

	/**
	 * @param int $rateId
	 * @param string $date
	 *
	 * @return int
	 */
	public function deleteAvailabilities($rateId, $date) {
		$where = new Where();
		$where
			->lessThan('date', $date)
			->and
			->equalTo('rate_id', $rateId);

		return $this->delete($where);
	}

    public function getAvailabilityByDate($apartelId, $start, $end){
        $result = $this->fetchAll(
    		function (Select $select) use($apartelId, $start, $end) {
                $select->columns([
					'date',
					'availability',
				]);
                $select->where
                       ->equalTo($this->getTable().'.apartment_id', $apartelId)
                       ->greaterThanOrEqualTo($this->getTable().'.date', $start)
                       ->lessThanOrEqualTo($this->getTable().'.date', $end);
    		}
    	);
    	return $result;
    }

    /**
     * @param int $apartmentId
     * @param string $start
     * @param string $end
     * @return bool
     *
     * @author Tigran Ghabuzyan
     */
    public function checkApartmentAvailability($apartmentId, $start, $end)
    {
        $result = $this->fetchOne(
            function (Select $select) use($apartmentId, $start, $end) {
                $select->columns([
                    'date'
                ]);
                $select->join(
                    ['rates' => DbTables::TBL_APARTMENT_RATES],
                    new Expression($this->getTable() . '.rate_id = rates.id AND rates.type = '. RateService::TYPE1 .' AND ' . $this->getTable() . '.apartment_id = ' . $apartmentId),
                    []
                );

                $select->where
                    ->greaterThanOrEqualTo($this->getTable() . '.date', $start)
                    ->lessThan($this->getTable() . '.date', $end)
                    ->equalTo($this->getTable() . '.availability', 0);
                $select->group($this->getTable() . '.date');
            }
        );

        if ($result) {
            return false;
        } else {
            return true;
        }
    }

    public function bookingReservationData($rateId, $arrival, $departure, $guest, $bookNightCount, $checkNightCount){
        $sql = "SELECT sub2.*,
                    p_gen.id as prod_id,
                    p_gen.country_id,
                    p_gen.province_id,
                    p_gen.city_id,
                    p_gen.currency_id,
                    p_gen.address,
                    p_type.id AS room_id,
                    p_gen.`name` AS prod_name,
                    p_gen.bedroom_count,
                    p_rate.capacity,
                    p_rate.capacity AS rate_capacity,
                    p_rate.name AS rate_name,
                    p_rate.is_refundable,
                    p_rate.refundable_before_hours,
                    p_rate.penalty_percent,
                    p_rate.penalty_nights,
                    p_rate.penalty_fixed_amount,
                    p_rate.penalty_type,
                    location1.name AS province_name,
                    location.name AS city_name,
                    p_desc.check_in, p_desc.check_out,
                    location.tot,
                    location.tot_type,
                    location.tot_included,
                    location.tot_additional,
                    location.tot_max_duration,
                    location.vat,
                    location.vat_type,
                    location.vat_included,
                    location.vat_additional,
                    location.vat_max_duration,
                    location.city_tax,
                    location.city_tax_type,
                    location.city_tax_included,
                    location.city_tax_additional,
                    location.city_tax_max_duration,
                    location.sales_tax,
                    location.sales_tax_type,
                    location.sales_tax_included,
                    location.sales_tax_additional,
                    location.sales_tax_max_duration,
                    currency_country.code as country_currency,
                    group_apartel.name as apartel_name,
                    media.img1,
                    currency.code,
                    currency.symbol
                    FROM (
                        SELECT SUM(sub1.price) AS amount_price, sub1.availability, sub1.rate_id as rateId, sub1.apartment_id as accId
                        FROM (
                            SELECT * FROM " . DbTables::TBL_APARTMENT_INVENTORY . " AS av
                            where av.rate_id = ? AND av.`date` >= ? AND av.`date` < ?
                            ORDER BY av.availability
                        ) AS sub1
                    ) as sub2
                INNER JOIN " . DbTables::TBL_APARTMENTS . " as p_gen ON p_gen.id = sub2.accId AND p_gen.status in (5,10)
                INNER JOIN " . DbTables::TBL_PRODUCT_TYPES . " as p_type ON p_type.apartment_id = sub2.accId
                INNER JOIN " . DbTables::TBL_PRODUCT_DESCRIPTIONS . " as p_desc ON p_desc.apartment_id = sub2.accId
                INNER JOIN " . DbTables::TBL_APARTMENT_IMAGES . " AS media ON p_gen.id = media.apartment_id
                INNER JOIN " . DbTables::TBL_APARTMENT_RATES . " as p_rate ON p_rate.id = sub2.rateId AND p_rate.active = 1 AND p_rate.capacity >= ?
                AND p_rate.min_stay <= ? AND p_rate.max_stay >= ? AND p_rate.release_period_start <= ? AND p_rate.release_period_end >= ?
                INNER JOIN " . DbTables::TBL_CITIES . " as city ON city.id = p_gen.city_id
                INNER JOIN " . DbTables::TBL_LOCATION_DETAILS . " as location ON location.id = city.detail_id
                INNER JOIN " . DbTables::TBL_PROVINCES . " as provinces ON provinces.id = p_gen.province_id
                INNER JOIN " . DbTables::TBL_LOCATION_DETAILS . " as location1 ON location1.id = provinces.detail_id
                INNER JOIN " . DbTables::TBL_CURRENCY . " AS currency ON p_gen.currency_id = currency.id
                INNER JOIN " . DbTables::TBL_COUNTRIES . " as countrie ON countrie.id = p_gen.country_id
                LEFT JOIN " . DbTables::TBL_CURRENCY . " AS currency_country ON countrie.currency_id = currency_country.id
                LEFT JOIN " . DbTables::TBL_APARTMENT_GROUP_ITEMS . " AS group_acc ON p_gen.id = group_acc.apartment_id
                LEFT JOIN " . DbTables::TBL_APARTMENT_GROUPS . " AS group_apartel ON group_apartel.id = group_acc.apartment_group_id AND group_apartel.usage_apartel = 1
                WHERE sub2.availability = 1
                GROUP BY p_gen.id";
        $statement = $this->adapter->createStatement($sql, [$rateId, $arrival, $departure, $guest, $bookNightCount, $bookNightCount, $checkNightCount, $checkNightCount]);
        $execute = $statement->execute();
        $result = $execute->current();
    	return $result;
    }

    public function getAvailableRates($apartmentUrl, $city, $guestCount, $arrivalDate, $departureDate) {
        $bookNightCount  = Helper::getDaysFromTwoDate($arrivalDate, $departureDate);
        $checkNightCount = Helper::getDaysFromTwoDate($arrivalDate, date('Y-m-d'));

        $sql = "
            select * from (
                select
                    product_rates.id               AS id,
                    product_rates.name             AS name,
                    product_rates.capacity         AS capacity,
                    product_rates.type             AS type,
                    product_rates.is_refundable    AS is_refundable,
                    product_rates.refundable_before_hours AS refundable_before_hours,
                    product_rates.penalty_percent  AS penalty_percent,
                    product_rates.penalty_nights     AS penalty_nights,
                    product_rates.penalty_fixed_amount   AS penalty_fixed_amount,
                    product_rates.penalty_type     AS penalty_type,
                    avg(rate_av.price)             AS price,
                    min(rate_av.availability)     AS availability,
                    apartments.currency_id         AS currency_id,
                    geo_details.name            AS city_name,
                    currency.code                  AS code,
                    currency.symbol                AS symbol
                from " . DbTables::TBL_APARTMENT_INVENTORY . " as rate_av
                    left join " . DbTables::TBL_APARTMENT_RATES . "    as product_rates on product_rates.id = rate_av.rate_id
                    left join " . DbTables::TBL_APARTMENTS . "       as apartments    on apartments.id = rate_av.apartment_id
                    left join " . DbTables::TBL_CITIES . "           as cities        on cities.id = apartments.city_id
                    left join " . DbTables::TBL_LOCATION_DETAILS . " as geo_details   on geo_details.id = cities.detail_id and lower(geo_details.name) = ?
                    left join " . DbTables::TBL_CURRENCY . "         as currency      on currency.id = apartments.currency_id
                where
                    apartments.url         = ? and
                    product_rates.active   = 1 and
                    product_rates.capacity >= ? and
                    product_rates.min_stay <= ? and
                    product_rates.max_stay >= ? and
                    product_rates.release_period_start <= ? and
                    product_rates.release_period_end >= ? and
                    rate_av.date           >= ? and
                    rate_av.date           < ?
                group by rate_av.rate_id
                order by
                  rate_av.price asc,
                  product_rates.capacity asc
            ) as result where result.availability > 0";
        if(!Helper::isBackofficeUser()) {
            $sql .= " limit 4";
        }
        $statement = $this->adapter->createStatement(
            $sql, [$city, $apartmentUrl, $guestCount, $bookNightCount, $bookNightCount, $checkNightCount, $checkNightCount, $arrivalDate, $departureDate]
        );
        $result = $statement->execute();

        return $result;
    }

    public function getApartmentAvailabilityByDate($apartmentId, $date)
    {
    	$result = $this->fetchOne(
			function (Select $select) use($apartmentId, $date) {
				$select->columns([
					'apartment_id',
					'availability'
				]);
 				$select->join(
                    ['r' => DbTables::TBL_APARTMENT_RATES],
                    new Expression(
                    	$this->getTable() . '.apartment_id = r.apartment_id AND r.type = ' . RateService::TYPE1
                    ),
                    ['type']
                );
				$select->where(
					[
						$this->getTable() . '.apartment_id' => $apartmentId,
						$this->getTable() . '.date'		   => $date
					]
				);
			}
		);

		return $result;
    }

    public function getClosedAv($apartmentId, $startDate, $endDate)
    {
    	$result = $this->fetchOne(
            function (Select $select) use($apartmentId, $startDate, $endDate) {
                $select->columns([
                    'count' => new Expression('COUNT(*)')
                ]);
                $select->join(
                	['prod_rate' => DbTables::TBL_APARTMENT_RATES],
                	$this->getTable().'.rate_id = prod_rate.id',
                	[]
                );

                $select->where->equalTo('prod_rate.type', RateService::TYPE1)
                       ->equalTo($this->getTable() . '.apartment_id', $apartmentId)
                       ->equalTo($this->getTable() . '.availability', 0)
                       ->greaterThanOrEqualTo($this->getTable() . '.date', $startDate)
                       ->lessThan($this->getTable() . '.date', $endDate)
                ;
            }
        );

        return $result ? $result['count'] : 0;
    }

    /**
     * @param $cubilisRateIdDates
     * @param $roomId
     * @return \Zend\Db\Adapter\Driver\ResultInterface
     */
    public function getRateByCubilisRateIdDates($cubilisRateIdDates, $roomId)
    {
        $sql = "SELECT
                    ar.id as rate_id,
                    ar.name as rate_name,
                    ar.capacity,
                    ai.price,
                    ai.date,
                    ai.room_id as room_type_id,
                    ai.apartment_id,
                    ai.availability,
                    ar.active
                FROM
                    " . DbTables::TBL_APARTMENT_INVENTORY . " AS ai
                    LEFT JOIN " . DbTables::TBL_APARTMENT_RATES . " AS ar ON ai.rate_id = ar.id
                WHERE
                    ar.room_id = {$roomId}
                    AND (ar.cubilis_id, ai.`date`) IN ({$cubilisRateIdDates})
                GROUP BY ai.id
                ORDER BY ai.availability ASC, ar.id ASC, ai.date ASC"; // AND ai.availability = 1 AND ar.active = 1
        $statement = $this->adapter->createStatement($sql);
        $result = $statement->execute();

        $this->setEntity(new \ArrayObject());
        $resultSet = clone $this->resultSetPrototype;
        $resultSet->initialize($result);
        return $result;
    }

    /**
     * @param $dates
     * @param $roomTypeId
     * @return \Zend\Db\ResultSet\ResultSet
     */
    public function getRateByParentRateIdDates($dates, $roomTypeId)
    {
        $this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());
        return $this->fetchAll(
            function (Select $select) use($dates, $roomTypeId) {
                $select->columns([
                    'price',
                    'date',
                    'room_type_id' => 'room_id',
                    'apartment_id',
                    'availability',
                ]);
                $select->join(
                    ['rate' => DbTables::TBL_APARTMENT_RATES],
                    $this->getTable() . '.rate_id = rate.id',
                    [
                        'rate_id' => 'id',
                        'rate_name' => 'name',
                        'capacity',
                        'active',
                    ]
                );
                $select->where
                    ->equalTo($this->getTable() . '.room_id', $roomTypeId)
                    ->greaterThanOrEqualTo($this->getTable() . '.date', $dates['date_from'])
                    ->lessThan($this->getTable() . '.date', $dates['date_to'])
                    ->equalTo('rate.type', RateService::TYPE1)
                ;
            }
        );
    }

    /**
     * @param $rateIdDates
     * @return \Zend\Db\Adapter\Driver\ResultInterface
     */
    public function getRateByRateIdDates($rateIdDates)
    {
        $sql = "SELECT
                    ai.rate_id,
                    ai.availability
                FROM
                    " . DbTables::TBL_APARTMENT_INVENTORY . " AS ai
                    INNER JOIN " . DbTables::TBL_APARTMENT_RATES . " AS ar ON ai.rate_id = ar.id
                WHERE
                    (ai.rate_id, ai.`date`) IN ({$rateIdDates})
                    AND ai.availability = 1
                    AND ar.active = 1
                GROUP BY ai.id
                ORDER BY ai.availability ASC, ar.id ASC, ai.date ASC";
        $statement = $this->adapter->createStatement($sql);
        $result = $statement->execute();

        return $result;
    }

    public function getRateDataByRateIdDates($rateId, $dateFrom, $dateTo)
    {
        return $result = $this->fetchAll(
            function (Select $select) use($rateId, $dateFrom, $dateTo) {
                $select->columns([
                    'rate_id',
                    'date',
                    'price',
                    'room_id',
                    'apartment_id',
                ]);
                $select->join(
                    ['rates' => DbTables::TBL_APARTMENT_RATES],
                        $this->getTable() . '.rate_id = rates.id',
                    [
                        'rate_name' => 'name',
                        'capacity' => 'capacity',
                    ]
                );

                $select->where
                    ->equalTo($this->getTable() . '.rate_id', $rateId)
                    ->greaterThanOrEqualTo($this->getTable() . '.date', $dateFrom)
                    ->lessThan($this->getTable() . '.date', $dateTo)
                    ->equalTo($this->getTable() . '.availability', 1);
                $select->order([$this->getTable() . '.rate_id ASC', $this->getTable() . '.date ASC']);
            }
        );
    }

    public function getDateAvListBeforeAfterReservation($apartmentId, $dateFrom, $dateTo)
    {
        $this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());
        return $result = $this->fetchAll(
            function (Select $select) use($apartmentId, $dateFrom, $dateTo) {
                $select->columns([
                    'date',
                    'availability',
                ]);
                $select->join(
                    ['rates' => DbTables::TBL_APARTMENT_RATES],
                        $this->getTable() . '.rate_id = rates.id',
                    []
                );

                $select->where
                    ->equalTo($this->getTable() . '.apartment_id', $apartmentId)
                    ->greaterThanOrEqualTo($this->getTable() . '.date', $dateFrom)
                    ->lessThanOrEqualTo($this->getTable() . '.date', $dateTo)
                    ->equalTo('rates.type', RateService::TYPE1);
                $select->order([$this->getTable() . '.date ASC']);
            }
        );
    }

    public function getAvailabilityByRateDate($rateId, $date) {
        $this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());
        return $this->fetchOne(
            function (Select $select) use($rateId, $date) {
                $select->columns([
                    'availability',
                ]);

                $select->where
                    ->equalTo($this->getTable() . '.rate_id', $rateId)
                    ->equalTo($this->getTable() . '.date', $date);
            }
        );
    }

    public function checkApartmentAvailabilityApartmentDateList($apartmentId, $dates)
    {
        $this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());
        return $result = $this->fetchOne(
            function (Select $select) use($apartmentId, $dates) {
                $select->columns([
                    'id',
                ]);
                $select->join(
                    ['rates' => DbTables::TBL_APARTMENT_RATES],
                    $this->getTable() . '.rate_id = rates.id',
                    []
                );

                $select->where
                    ->equalTo($this->getTable() . '.apartment_id', $apartmentId)
                    ->in($this->getTable() . '.date', $dates)
                    ->equalTo('rates.type', RateService::TYPE1)
                    ->equalTo($this->getTable() . '.availability', 1)
                ;
            }
        );
    }

    /**
     * @param $apartmentId
     * @param $dateFrom
     * @param $dateTo
     * @param $weekDays
     * @param $forceLockPrice
     * @return \Zend\Db\ResultSet\ResultSet
     */
    public function getRateInventoryData($apartmentId, $dateFrom, $dateTo, $weekDays, $forceLockPrice)
    {
        $this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());
        return $this->fetchAll(
            function (Select $select) use($apartmentId, $dateFrom, $dateTo, $weekDays, $forceLockPrice) {
                $select->columns([
                    'id',
                    'price',
                    'date',
                ]);

                $select->where->equalTo('rates.type', RateService::TYPE1);
                $select->where(
                    $this->getWhereForInventoryRange($apartmentId, $dateFrom, $dateTo, $weekDays, null, $forceLockPrice)
                );
            }
        );
    }

    /**
     * @param $price
     * @param $priceType
     * @param $rateId
     * @param $dateFrom
     * @param $dateTo
     * @param $weekDays
     * @param $forceLockPrice
     * @return int
     * @throws \Exception
     */
    public function updateParentRatePriceByRang($price, $priceType, $rateId, $dateFrom, $dateTo, $weekDays, $forceLockPrice)
    {
        $where = $this->getRateWhereForInventoryRange($rateId, $dateFrom, $dateTo, $weekDays, null, $forceLockPrice);

        $newPriceSql = $this->getPriceSqlByType($price, $priceType);

        // if bad situation do nothing
        if (!$newPriceSql) {
            throw new \Exception('Bad data for update price');
        }

        return $this->update([
            'price' => new Expression($newPriceSql),
        ], $where);
    }

    /**
     * @param $price
     * @param $priceType
     * @return string
     */
    private function getPriceSqlByType($price, $priceType)
    {
        $newPriceSql = '';
        switch ($priceType) {
            case 0:
                // Amount
                $newPriceSql = $price;
                break;
            case 1:
                // Percent Less
                $newPriceSql = "price + price*{$price}/100";
                break;
            case 2:
                // Percent More
                $newPriceSql = "price - price*{$price}/100";
                break;
            case 3:
                // Amount Less
                $newPriceSql = "price + {$price}";
                break;
            case 4:
                // Amount More
                $newPriceSql = "price - {$price}";
                break;
        }

        return $newPriceSql;
    }

    /**
     * @param $apartmentId
     * @param $dateFrom
     * @param $dateTo
     * @param $weekDays
     * @param $setLockPrice
     * @param $forceLockPrice
     * @return int
     */
    public function updateLockPriceBit($apartmentId, $dateFrom, $dateTo, $weekDays, $setLockPrice, $forceLockPrice)
    {
        $where = $this->getWhereForInventoryRange($apartmentId, $dateFrom, $dateTo, $weekDays, null, $forceLockPrice);
        return $this->update([
            'is_lock_price' => $setLockPrice,
        ], $where);
    }

    /**
     * @param $apartmentId
     * @param $dateFrom
     * @param $dateTo
     * @param $availability
     * @param $isChanged
     * @return int
     */
    public function updateAvailabilityByApartmentDateRange($apartmentId, $dateFrom, $dateTo, $availability, $isChanged)
    {
        $where = new Where();
        $where
            ->equalTo('apartment_id', $apartmentId)
            ->greaterThanOrEqualTo('date', $dateFrom)
            ->lessThan('date', $dateTo);

        return $this->update([
            'availability' => $availability,
            'is_changed' => $isChanged,
        ], $where);
    }

    /**
     * @param $apartmentId
     * @param $dateFrom
     * @param $dateTo
     * @return \Zend\Db\ResultSet\ResultSet
     */
    public function getAllRateForSyncByApartmentRange($apartmentId, $dateFrom, $dateTo) {
        $this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());
        return $this->fetchAll(
            function (Select $select) use($apartmentId, $dateFrom, $dateTo) {
                $select->columns([
                    'date' => 'date',
                    'availability' => 'availability',
                    'price' => 'price',
                ]);
                $select->join(
                    ['rates' => DbTables::TBL_APARTMENT_RATES],
                    $this->getTable() . '.rate_id = rates.id',
                    ['cubilis_rate_id' => 'cubilis_id']
                );
                $select->join(
                    ['room' => DbTables::TBL_PRODUCT_TYPES],
                    $this->getTable() . '.room_id = room.id',
                    ['cubilis_room_id' => 'cubilis_id']
                );
                $select->where
                    ->isNotNull('rates.cubilis_id')
                    ->isNotNull('room.cubilis_id')
                    ->equalTo($this->getTable() . '.apartment_id', $apartmentId)
                    ->greaterThanOrEqualTo($this->getTable() . '.date', $dateFrom)
                    ->lessThan($this->getTable() . '.date', $dateTo);
            }
        );
    }

    /**
     * @param $apartmentId
     * @param $date
     * @return array|\ArrayObject|null
     */
    public function getAvailabilityByDateBeforeUpdate($apartmentId, $date)
    {
        $this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());
        return $this->fetchOne(
            function (Select $select) use($apartmentId, $date) {
                $select->columns([
                    'availability',
                ]);

                $select->join(
                    ['rates' => DbTables::TBL_APARTMENT_RATES],
                    $this->getTable() . '.rate_id = rates.id',
                    []
                );

                $select->where
                    ->equalTo($this->getTable() . '.apartment_id', $apartmentId)
                    ->equalTo($this->getTable() . '.date', $date)
                    ->equalTo('rates.type', RateService::TYPE1);
            }
        );
    }

    /**
     * @param $apartmentId
     * @param $dateFrom
     * @param $dateTo
     * @return \Zend\Db\ResultSet\ResultSet
     */
    public function getAvailabilityByRangeBeforeUpdate($apartmentId, $dateFrom, $dateTo)
    {
        $this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());
        return $this->fetchAll(
            function (Select $select) use($apartmentId, $dateFrom, $dateTo) {
                $select->columns([
                    'availability',
                    'date'
                ]);

                $select->join(
                    ['rates' => DbTables::TBL_APARTMENT_RATES],
                    $this->getTable() . '.rate_id = rates.id',
                    []
                );

                $select->where
                    ->equalTo($this->getTable() . '.apartment_id', $apartmentId)
                    ->greaterThanOrEqualTo($this->getTable() . '.date', $dateFrom)
                    ->lessThanOrEqualTo($this->getTable() . '.date', $dateTo)
                    ->equalTo('rates.type', RateService::TYPE1);
            }
        );
    }

    /**
     * @param $apartmentList
     * @return \Zend\Db\ResultSet\ResultSet
     */
    public function getApartmentsYearlyAvailability($apartmentList)
    {
        $this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());
        return $this->fetchAll(
            function (Select $select) use($apartmentList) {
                $select->columns([
                    'all_availability' => new Expression('sum(availability)'),
                    'date'
                ]);

                $select->join(
                    ['rates' => DbTables::TBL_APARTMENT_RATES],
                    $this->getTable() . '.rate_id = rates.id',
                    []
                );

                $select->where
                    ->in($this->getTable() . '.apartment_id', $apartmentList)
                    ->equalTo('rates.type', RateService::TYPE1);
                $select->group($this->getTable() . '.date');
            }
        );
    }

    /**
     * @return array|\ArrayObject|null
     */
    public function getMinMaxDate()
    {
        $this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());
        return $this->fetchOne(
            function (Select $select)  {
                $select->columns([
                    'min_date' => new Expression('min(date)'),
                    'max_date' => new Expression('max(date)'),
                ]);
            }
        );
    }

    /**
     * @param $apartmentId
     * @param $dateFrom
     * @param $dateTo
     * @param $weekDays
     * @return int
     */
    public function getPriceAVGRange($apartmentId, $dateFrom, $dateTo, $weekDays)
    {
        $this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());
        $result = $this->fetchOne(
            function (Select $select) use ($apartmentId, $dateFrom, $dateTo, $weekDays) {
                $select->columns([
                    'price_avg' => new Expression('avg(price)')
                ]);

                $select->join(
                    ['rates' => DbTables::TBL_APARTMENT_RATES],
                    $this->getTable() . '.rate_id = rates.id',
                    []
                );

                $select->where
                    ->equalTo($this->getTable() . '.apartment_id', $apartmentId)
                    ->greaterThanOrEqualTo($this->getTable() . '.date', $dateFrom)
                    ->lessThanOrEqualTo($this->getTable() . '.date', $dateTo)
                    ->lessThanOrEqualTo($this->getTable() . '.date', 0)
                    ->equalTo('rates.type', RateService::TYPE1);

                if ($weekDays) {
                    $select->where->expression("weekday(" . $this->getTable() . ".date) in ({$weekDays})", []);
                }
            }
        );

        return $result ? $result['price_avg'] : 0;
    }

    /**
     * @param $apartmentId
     * @param $dateFrom
     * @param $dateTo
     * @param $weekDays
     * @param $price
     * @param $priceType
     * @return int
     */
    public function getPriceAVGRangeByPriceType($apartmentId, $dateFrom, $dateTo, $weekDays, $price, $priceType)
    {
        $newPriceSql = $this->getPriceSqlByType($price, $priceType);
        $this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());
        $result = $this->fetchOne(
            function (Select $select) use ($apartmentId, $dateFrom, $dateTo, $weekDays, $newPriceSql) {
                $select->columns([
                    'price_avg' => new Expression("avg({$newPriceSql})")
                ]);

                $select->join(
                    ['rates' => DbTables::TBL_APARTMENT_RATES],
                    $this->getTable() . '.rate_id = rates.id',
                    []
                );

                $select->where
                    ->equalTo($this->getTable() . '.apartment_id', $apartmentId)
                    ->greaterThanOrEqualTo($this->getTable() . '.date', $dateFrom)
                    ->lessThanOrEqualTo($this->getTable() . '.date', $dateTo)
                    ->equalTo('rates.type', RateService::TYPE1);

                if ($weekDays) {
                    $select->where->expression("weekday(" . $this->getTable() . ".date) in ({$weekDays})", []);
                }
            }
        );
        return $result ? $result['price_avg'] : 0;
    }

}
