<?php
namespace DDD\Dao\Accommodation;

use DDD\Domain\Apartment\NotifyPerformanceApartments;
use DDD\Domain\Apartment\PerformanceGroupApartments;
use DDD\Service\Booking;
use DDD\Service\Apartment\Rate as RateService;
use DDD\Service\Accommodations as ApartmentService;
use DDD\Service\Warehouse\Asset as AssetService;

use DDD\Service\Translation;
use Library\Constants\Objects;
use Library\DbManager\TableGatewayManager;
use Library\Constants\Constants;
use Library\Utility\Debug;
use Library\Constants\DbTables;

use Zend\Db\Sql\Select;
use Zend\Db\Sql\Expression;

class Accommodations extends TableGatewayManager
{
	/**
	 * @var string
	 */
    protected $table = DbTables::TBL_APARTMENTS;

    public function __construct($sm, $domain = 'DDD\Domain\Accommodation\Accommodations') {
        parent::__construct($sm, $domain);
    }

	public function getAccommodations($query) {
		$result = $this->fetchAll(function (Select $select) use($query) {

            $select->where
                   ->notEqualTo('status', '9')
                   ->and
                   ->like('name', '%'.$query.'%')
                   ->or
                   ->equalTo('id', $query);

			$select->columns(array('id', 'name'))
                   ->order('name')
                   ->limit(10);
		});

		return $result;
	}

    /**
     * @return \ArrayObject|NotifyPerformanceApartments[]
     *
     * @author Tigran Petrosyan
     */
    public function getApartmentsForPerformanceCalculation()
    {
        $this->resultSetPrototype->setArrayObjectPrototype(new NotifyPerformanceApartments());

		$result = $this->fetchAll(function (Select $select) {

            $select->columns([
                'apartment_id'      => 'id',
                'apartment_name'    => 'name',
            ]);

            $select->join(
                ['currency' => DbTables::TBL_CURRENCY],
                $this->getTable() . '.currency_id = currency.id',
                ['currency_code' => 'code']
            );

            $select->join(
                ['pd' => DbTables::TBL_APARTMENTS_DETAILS],
                new Expression($this->getTable() . '.id = pd.apartment_id'),
                []
            );

            $select->where->notIn($this->getTable() . '.status', [
                Objects::PRODUCT_STATUS_DISABLED,
                Objects::PRODUCT_STATUS_LIVE_IN_UNIT
            ]);

            $select->where->equalTo('pd.notify_negative_profit', 1);
		});

		return $result;
	}

    /**
     * @return \ArrayObject|PerformanceGroupApartments[]
     *
     * @author Tigran Petrosyan
     */
    public function getPerformanceGroupsSellingApartments()
    {
        $this->resultSetPrototype->setArrayObjectPrototype(new PerformanceGroupApartments());

		$result = $this->fetchAll(function (Select $select) {

            $select->columns([
                'apartment_id'      => 'id',
                'apartment_name'    => 'name',
            ]);

            $select->join(
                ['apartment_group_items' => DbTables::TBL_APARTMENT_GROUP_ITEMS],
                $this->getTable() . '.id = apartment_group_items.apartment_id',
                []
            );

            $select->join(
                ['apartment_groups' => DbTables::TBL_APARTMENT_GROUPS],
                new Expression('apartment_group_items.apartment_group_id = apartment_groups.id AND apartment_groups.usage_performance_group = 1 AND apartment_groups.active = 1'),
                [
                    'performance_group_id'      => 'id',
                    'performance_group_name'    => 'name'
                ]
            );

            $select->join(
                ['currency' => DbTables::TBL_CURRENCY],
                $this->getTable() . '.currency_id = currency.id',
                ['currency_code' => 'code'],
                Select::JOIN_LEFT
            );

            $select->where->notEqualTo($this->getTable() . '.status', Objects::PRODUCT_STATUS_DISABLED);
            $select->order('performance_group_name ASC');
		});

		return $result;
	}

	public function getForSelect()
    {
		$result = $this->fetchAll(function (Select $select) {

			$select->columns(array('id', 'name'))
				->order('name');
		});

		return $result;
	}

	public function getForAutocomplete($query, $building, $mode)
    {
		$result = $this->fetchAll(function (Select $select) use ($query, $building, $mode) {
			$select->columns(array('id', 'name', 'building_id', 'unit_number'))->order('name');
			$select->join(DbTables::TBL_CITIES, $this->getTable() . '.city_id = ' . DbTables::TBL_CITIES . '.id', array());
			$select->join(DbTables::TBL_PROVINCES, DbTables::TBL_CITIES . '.province_id = ' . DbTables::TBL_PROVINCES . '.id', array());
			$select->join(DbTables::TBL_COUNTRIES, DbTables::TBL_PROVINCES . '.country_id = ' . DbTables::TBL_COUNTRIES . '.id', array());
			$select->join(array("det1" => DbTables::TBL_LOCATION_DETAILS), DbTables::TBL_CITIES . '.detail_id = det1.id', array('city' => 'name'));
			$select->join(array("det2" => DbTables::TBL_LOCATION_DETAILS), DbTables::TBL_COUNTRIES . '.detail_id = det2.id', array('country' => 'name'));
            if($building){
                $select->join(['apartment_group' => DbTables::TBL_APARTMENT_GROUPS], $this->getTable() . '.building_id = apartment_group.id', ['apartment_group' => 'name'], 'LEFT');
            }
			$select->where->like($this->getTable() . '.name', '%' . $query . '%');
            $select->limit(15);
            if (!$mode) {
                $select->where->notEqualTo($this->getTable() . '.status', '9');
            }
		});

		return $result;
	}

	public function getAppartmentFullAddressByID($id)
    {
		$result = $this->fetchOne(function (Select $select) use ($id) {
			$select->columns(array('id', 'name', 'address', 'postal_code', 'unit_number' => 'unit_number'))->order('name');
			$select->join(DbTables::TBL_CITIES, $this->getTable() . '.city_id = ' . DbTables::TBL_CITIES . '.id', array(), 'LEFT');
			$select->join(DbTables::TBL_PROVINCES, DbTables::TBL_CITIES . '.province_id = ' . DbTables::TBL_PROVINCES . '.id', array(), 'LEFT');
			$select->join(DbTables::TBL_COUNTRIES, DbTables::TBL_PROVINCES . '.country_id = ' . DbTables::TBL_COUNTRIES . '.id', array(), 'LEFT');
			$select->join(array("det1" => DbTables::TBL_LOCATION_DETAILS), DbTables::TBL_CITIES . '.detail_id = det1.id', array('city' => 'name'), 'LEFT');
			$select->join(array("det2" => DbTables::TBL_LOCATION_DETAILS), DbTables::TBL_COUNTRIES . '.detail_id = det2.id', array('country' => 'name'), 'LEFT');
			$select->where->equalTo($this->table . '.id', $id);
		});

		return $result;
	}

	public function findByFullAddress($query, $mode)
    {
		$result = $this->fetchAll(function (Select $select) use ($query, $mode) {
			$select->columns(array('id', 'name', 'address', 'postal_code', 'unit_number' => 'unit_number'))->order('name');
			$select->join(DbTables::TBL_CITIES, $this->getTable() . '.city_id = ' . DbTables::TBL_CITIES . '.id', array());
			$select->join(DbTables::TBL_PROVINCES, DbTables::TBL_CITIES . '.province_id = ' . DbTables::TBL_PROVINCES . '.id', array());
			$select->join(DbTables::TBL_COUNTRIES, DbTables::TBL_PROVINCES . '.country_id = ' . DbTables::TBL_COUNTRIES . '.id', array());
			$select->join(array("det1" => DbTables::TBL_LOCATION_DETAILS), DbTables::TBL_CITIES . '.detail_id = det1.id', array('city' => 'name'));
			$select->join(array("det2" => DbTables::TBL_LOCATION_DETAILS), DbTables::TBL_COUNTRIES . '.detail_id = det2.id', array('country' => 'name'));
			$select
                ->where
                ->and->nest
				->like($this->table . '.name', '%' . $query . '%')
				->or
				->like('det1.name', '%' . $query . '%')
				->or
				->like('det2.name', '%' . $query . '%')
				->or
				->like($this->table . '.address', '%' . $query . '%')
				->or
				->like($this->table . '.unit_number', '%' . $query . '%')
				->or
				->like($this->table . '.postal_code', '%' . $query . '%')
                ->unnest();

            if (!$mode) {
                $select
                    ->where->and->nest
                    ->notEqualTo($this->getTable() . '.status', '9');
            }

			$select->group('id');
		});

		return $result;
	}

	/**
	 * Method to get products list with basic information to display in product search results
	 */
	public function getAllAccommodations($where)
    {
		return $this->fetchAll(function (Select $select) use($where) {
			$select->columns([
                "id",
                "status",
                "name",
                "block",
                "create_date",
                "url",
                'unit_number'
            ]);

            $select->join(
                ["ag" => DbTables::TBL_APARTMENT_GROUPS],
                new Expression($this->table.'.building_id = ag.id and ag.usage_building = 1'),
                ['building' => 'name'],
                Select::JOIN_LEFT
            );

            $select->join(
                DbTables::TBL_CITIES,
                $this->getTable() . '.city_id = ' . DbTables::TBL_CITIES . '.id',
                [],
                Select::JOIN_LEFT
            );

			$select->join(
                DbTables::TBL_PROVINCES,
                DbTables::TBL_CITIES . '.province_id = ' . DbTables::TBL_PROVINCES . '.id',
                [],
                Select::JOIN_LEFT
            );

			$select->join(
                DbTables::TBL_COUNTRIES,
                DbTables::TBL_PROVINCES . '.country_id = ' . DbTables::TBL_COUNTRIES . '.id',
                [],
                Select::JOIN_LEFT
            );

			$select->join(
                ["det1" => DbTables::TBL_LOCATION_DETAILS],
                DbTables::TBL_CITIES . '.detail_id = det1.id',
                ['city' => 'name'],
                Select::JOIN_LEFT
            );

            $select->join(
                ["det2" => DbTables::TBL_LOCATION_DETAILS],
                DbTables::TBL_COUNTRIES . '.detail_id = det2.id',
                ['country' => 'name'],
                Select::JOIN_LEFT
            );

			$select->join(
                DbTables::TBL_PRODUCT_STATUSES,
                $this->getTable() . '.status = ' . DbTables::TBL_PRODUCT_STATUSES . '.id',
                ['status_name' => 'short_name'],
                Select::JOIN_LEFT
            );

			if ($where !== null) {
				$select->where($where);
			}

			$select->group('id');
		});
	}

	public function getAccommodationsIn(array $whereIn = [])
    {
		$result = $this->fetchAll(function (Select $select) use($whereIn) {
			$select
				->columns([ 'id', 'name' ])
				->where([ 'status' => 9 ])
				->where->in('id', $whereIn);
		});

		return $result;
	}

    public function getCurrency($id)
    {
		$result = $this->fetchOne(function (Select $select) use($id) {
			$select->where(['id' => $id]);
			$select->columns(['currency_id']);
		});

		return $result;
	}

	public function getAccommodationsForTranlation($txt)
    {
		$result = $this->fetchAll(function (Select $select) use($txt) {

            $select->where
                ->notEqualTo('status', '9')
                ->and
                ->expression(
                    'id NOT IN (' . Constants::TEST_APARTMENT_1 .
                    ', ' . Constants::TEST_APARTMENT_2 . ')',
                    []
                )
                ->and
                ->like('name', '%'.$txt.'%');

			$select->columns(array('id', 'name'))
               ->order('name')
               ->limit(20);
		});

		return $result;
	}

    public function getOccupancyStatistics(
        $queryParams,
        $startDate,
        $endDate,
        $hasDevTestRole = false
    ) {

		return $this->fetchAll(
            function (Select $select) use(
                $queryParams,
                $startDate,
                $endDate,
                $hasDevTestRole
            ) {
    			$select->columns(
                    [
                        'id',
                        'name',
                        'pax'      => 'max_capacity',
                        'bedrooms' => 'bedroom_count'
                    ]
                );

                $select->join(
                    ['det' => DbTables::TBL_APARTMENTS_DETAILS],
                    $this->getTable() . '.id = det.apartment_id',
                    []
                );

                $select->join(
                    ['ap_group' => DbTables::TBL_APARTMENT_GROUPS],
                    new Expression(
                        $this->getTable() .
                        '.building_id = ap_group.id AND ap_group.usage_building = 1'
                    ),
                    ['building' => 'name'],
                    $select::JOIN_LEFT
                );

                $select->join(
                    ['ci' => DbTables::TBL_CITIES],
                    $this->getTable() . '.city_id = ci.id',
                    []
                );

                $select->join(
                    ['geo' => DbTables::TBL_LOCATION_DETAILS],
                    'geo.id = ci.detail_id',
                    ['city_name'=>'name']
                );

                $select->join(
                    ['rates' => DbTables::TBL_APARTMENT_RATES],
                    $this->getTable() . '.id = rates.apartment_id',
                    []
                );

                $select->join(
                    ['av' => DbTables::TBL_APARTMENT_INVENTORY],
                    'rates.id = av.rate_id',
                    [
                        'date',
                        'month'         => new Expression('Month(date)'),
                        'day_count'     => new Expression('DAY(LAST_DAY(date))'),
                        'month_name'    => new Expression('MONTHNAME(date)'),
                        'year'          => new Expression('YEAR(date)'),
                        'availability'  =>'availability'
                    ]
                );

                /**
                 * @todo use IN method instead of expression
                 * @todo use constants instead of 5,10
                 */
                $select
                    ->where
                    ->expression($this->getTable().'.status IN (5,10)', [])
                    ->equalTo('rates.type', RateService::TYPE1)
                    ->equalTo('rates.active', RateService::STATUS_ACTIVE)
                    ->between('av.date', $startDate, $endDate)
                    ->expression(
                        $this->getTable().'.id NOT IN (' .
                        Constants::TEST_APARTMENT_1 . ', ' .
                        Constants::TEST_APARTMENT_2 . ')',
                        []
                    );

                if (isset($queryParams['building_id']) && $queryParams['building_id'] != '') {
                    $select->where->equalTo('ap_group.id', $queryParams['building_id']);
                }

                if (isset($queryParams['apt_location_id']) && $queryParams['apt_location_id'] != '') {
                    $select->where->equalTo($this->getTable() . '.city_id', $queryParams['apt_location_id']);
                }

                if (isset($queryParams['bedroom_count']) && is_numeric($queryParams['bedroom_count'])) {
                    $select->where->equalTo($this->getTable() . '.bedroom_count', $queryParams['bedroom_count']);
                }

                $select->order(['year ASC', 'month ASC','city_name ASC']);

                if (!$hasDevTestRole) {
                    $select->where->notEqualTo(
                        $this->getTable() . '.id',
                        Constants::TEST_APARTMENT_GROUP
                    );
                }
    		}
        );
	}

	/**
	 * @param $apartmentId int
	 * @return \DDD\Domain\Accommodation\Accommodations|null
	 */
	public function getAccById($apartmentId)
    {
        return $this->fetchOne(function (Select $select) use ($apartmentId) {
            $select->columns(['id', 'name', 'currency_id', 'country_id', 'province_id', 'city_id', 'address', 'status', 'building_id']);

            // Currency Code
            $select->join(
                ['currency' => DbTables::TBL_CURRENCY],
                $this->getTable() . '.currency_id = currency.id',
                ['currency_code' => 'code'],
                Select::JOIN_LEFT
            );

            // Province Name
            $select->join(
                ['province' => DbTables::TBL_PROVINCES],
                $this->getTable() . '.province_id = province.id',
                [],
                Select::JOIN_LEFT
            );
            $select->join(
                ['geo' => DbTables::TBL_LOCATION_DETAILS],
                'province.detail_id = geo.id',
                ['province_name' => 'name'],
                Select::JOIN_LEFT
            );

            // City Name
            $select->join(
                ['city' => DbTables::TBL_CITIES],
                $this->getTable() . '.city_id = city.id',
                [],
                Select::JOIN_LEFT
            );
            $select->join(
                ['geo2' => DbTables::TBL_LOCATION_DETAILS],
                'city.detail_id = geo2.id',
                ['city_name' => 'name'],
                Select::JOIN_LEFT
            );
            $select->join(
                ['building_lots' => DbTables::TBL_BUILDING_LOTS],
                $this->getTable() . '.building_section_id = building_lots.building_section_id',
                ['lot_id'],
                Select::JOIN_LEFT
            );

            $select->where([$this->getTable() . '.id' => $apartmentId]);
        });
	}

    public function getAccDetail($id)
    {
		$result = $this->fetchOne(function (Select $select) use($id) {
			$select
				->columns(['id'])
				->join(DbTables::TBL_CURRENCY, $this->getTable() . '.currency_id = ' . DbTables::TBL_CURRENCY . '.id', array('symbol' => 'symbol', 'code' => 'code'), 'LEFT')
				->join(DbTables::TBL_APARTMENTS_DETAILS, $this->getTable() . '.id = ' . DbTables::TBL_APARTMENTS_DETAILS . '.apartment_id', array('monthly_cost' => 'monthly_cost',
                                                                                                                                      'startup_cost' => 'startup_cost'), 'LEFT')
				->where([ $this->getTable() . '.id' => $id ]);
		});
		return $result;
	}

    public function getAccommodationNotInBuilding($accommodations, $building_id)
    {
        $result = $this->fetchAll(function (Select $select) use($accommodations, $building_id) {
            $select->columns(array('name'))
                ->where->
                    expression($this->getTable().'.id IN ('.implode(', ', $accommodations).') and building_id <>'.$building_id.' and building_id <> 0', array());
        });
        return $result;
    }

    /**
     * @param int $apartmentId
     */
    public function getApartmentReceptionEntryTextline($apartmentId)
    {
        $this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());
        $result = $this->fetchOne(function (Select $select) use ($apartmentId) {
            $select->columns(['id']);

            $select
                ->join(
                    ['building_details' => DbTables::TBL_BUILDING_DETAILS],
                    $this->getTable() . '.building_id = building_details.apartment_group_id',
                    [],
                    Select::JOIN_LEFT
                )
                ->join(
                    ['office' => DbTables::TBL_OFFICES],
                    'office.id = building_details.assigned_office_id',
                    ['textline_id'],
                    Select::JOIN_LEFT
                )
                ->join(
                    ['pt' => DbTables::TBL_PRODUCT_TEXTLINES],
                    'office.textline_id = pt.id',
                    ['en_text' => 'en'],
                    Select::JOIN_LEFT
                );

            $select->where
                ->equalTo($this->getTable() . '.id', $apartmentId)
                ->and
                ->notEqualTo($this->getTable() . '.building_id', 0);
        });

        return $result;
    }

    public function getApartmentsListForTeam($teamId)
    {
        $prototype = $this->resultSetPrototype->getArrayObjectPrototype();
        $this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());
        $result = $this->fetchAll(function (Select $select) use ($teamId) {
            $select->columns(['id']);

           $select->join(
           ['frontier_team_apartment' => DbTables::TBL_TEAM_FRONTIER_APARTMENTS],
               $this->getTable().'.id=frontier_team_apartment.apartment_id' ,
               [],
           Select::JOIN_INNER
           );
            $select->join(
                ['teams' => DbTables::TBL_TEAMS],
                'teams.id=frontier_team_apartment.team_id' ,
                ['team_name' => 'name'],
                Select::JOIN_INNER
            );
            if ($teamId > 0) {
                $select->where->notEqualTo('frontier_team_apartment.team_id',$teamId);
            }
        });
        $this->resultSetPrototype->setArrayObjectPrototype($prototype);
        return $result;
    }

    public function getApartmentTimezone($apartmentId)
    {
        $prototype = $this->resultSetPrototype->getArrayObjectPrototype();
        $this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());
        $result = $this->fetchOne(function (Select $select) use ($apartmentId) {
            $select->columns(['name']);

            $select->join(DbTables::TBL_PRODUCT_DESCRIPTIONS, $this->getTable() . '.id = ' . DbTables::TBL_PRODUCT_DESCRIPTIONS . '.apartment_id', array(
                'check_in',
                'check_out',
            ));

            $select->join(
                ['city' => DbTables::TBL_CITIES],
                $this->getTable() . '.city_id = city.id' ,
                ['timezone']
            )->where->equalTo($this->getTable() . '.id', $apartmentId);

        });
        $this->resultSetPrototype->setArrayObjectPrototype($prototype);

        return $result;
    }

    public function getAvailableSpotsInLotForApartmentForDateRangeByPriority(
        $apartmentId,
        $startDate,
        $endDate,
        $spotsAlreadySelectedInSameChargeSession,
        $isSelectedDate,
        $directSpots,
        $today,
        $selectSameSpot = true,
        $preferedSpotIds = false
    ) {
		$prototype = $this->resultSetPrototype->getArrayObjectPrototype();
		$this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());
		$result = $this->fetchAll(function (Select $select) use(
            $apartmentId,
            $startDate,
            $endDate,
            $spotsAlreadySelectedInSameChargeSession,
            $isSelectedDate,
            $directSpots,
            $selectSameSpot,
            $preferedSpotIds,
            $today
        ) {

            $checkAvailability = true;

            if (!$isSelectedDate) {
                if ($today > $endDate) {
                    $checkAvailability = false;
                }
            }

			//if the reservation is in the past do not
			//check the availability
			$select->columns(['id']);
            $select->join(
				['lot_buildings' => DbTables::TBL_BUILDING_LOTS],
				$this->getTable() . '.building_section_id = lot_buildings.building_section_id',
				[],
				Select::JOIN_LEFT
			);
			$select->join(
				['parking_lots' => DbTables::TBL_PARKING_LOTS],
				'lot_buildings.lot_id = parking_lots.id',
				['name'],
				Select::JOIN_LEFT
			);
			$select->join(
				['parking_spots' => DbTables::TBL_PARKING_SPOTS],
				'parking_lots.id = parking_spots.lot_id',
				['unit' , 'price', 'parking_spot_id' => 'id'],
				Select::JOIN_LEFT
			);

			$select->join(
				['parking_inventory' => DbTables::TBL_PARKING_INVENTORY],
				'parking_spots.id = parking_inventory.spot_id',
				['date']
			);

			$select->where([$this->getTable() . '.id' => $apartmentId]);

			if ($checkAvailability) {
				$select->where->greaterThanOrEqualTo('parking_inventory.date', $startDate)
					->where->lessThanOrEqualTo('parking_inventory.date', $endDate)
					->where->equalTo('parking_lots.active', 1);
                if ($directSpots || $selectSameSpot) {
                    $notIn = array_merge($directSpots, $spotsAlreadySelectedInSameChargeSession);
                    if(!empty($notIn)) {
                        $select->where->notIn('parking_spots.id',$notIn);
                    }
                    $select->group('parking_inventory.spot_id')
                       ->having('MIN(`parking_inventory`.`availability`) = 1');
                } else {
                    if (!empty($spotsAlreadySelectedInSameChargeSession)) {
                        $select->where->notIn('parking_spots.id', $spotsAlreadySelectedInSameChargeSession);
                    }
                    $select->where->equalTo('parking_inventory.availability', 1);
                }
			} else {
                $select->where
                    ->greaterThanOrEqualTo('parking_inventory.date', $startDate)
                    ->lessThanOrEqualTo('parking_inventory.date', $endDate);

                if ($directSpots || $selectSameSpot) {
                    $notIn = array_merge($directSpots, $spotsAlreadySelectedInSameChargeSession);
                    if(!empty($notIn)) {
                        $select->where->notIn('parking_spots.id',$notIn);
                    }

                    $select->group('parking_inventory.spot_id');

                } else {
                    if (!empty($spotsAlreadySelectedInSameChargeSession)) {
                        $select->where->notIn('parking_spots.id', $spotsAlreadySelectedInSameChargeSession);
                    }
                }
            }

            if ($preferedSpotIds) {
                $select->where->in('parking_spots.id', $preferedSpotIds);
                $prferedStrings = implode(',', $preferedSpotIds);
                $select->order([new Expression('FIELD (parking_spots.id, '. $prferedStrings .')')]);
                $select->limit(1);
            }
		});
		$this->resultSetPrototype->setArrayObjectPrototype($prototype);
		return $result;
	}

    public function getAparatmentByCityId($cityId)
    {
        $prototype = $this->resultSetPrototype->getArrayObjectPrototype();
		$this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());
        $locationType = AssetService::ENTITY_TYPE_APARTMENT;

        $result = $this->fetchAll(function (Select $select) use ($cityId, $locationType) {
            $select->columns([
                'id',
                'name',
                'address',
                'buildingId'   => 'building_id',
                'cityId'       => 'city_id',
                'locationType' => new Expression("{$locationType}")
            ]);
            $select->where->equalTo('city_id', $cityId);

            $select->where
                ->equalTo('status', ApartmentService::APARTMENT_STATUS_LIVE_AND_SELLING)
                ->or
                ->equalTo('status', ApartmentService::APARTMENT_STATUS_SELLING_NOT_SEARCHABLE);
        });

        $this->resultSetPrototype->setArrayObjectPrototype($prototype);
        return $result;
    }

    public function getApartmentRawData($apartmentId)
    {
        $prototype = $this->resultSetPrototype->getArrayObjectPrototype();
        $this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());
        $locationType = AssetService::ENTITY_TYPE_APARTMENT;

        $result = $this->fetchOne(function (Select $select) use ($apartmentId) {
            $select->columns([
                'id',
                'name',
                'address',
                'building_id',
                'city_id',
            ]);

            $select->where->equalTo('id', $apartmentId);
        });

        $this->resultSetPrototype->setArrayObjectPrototype($prototype);
        return $result;
    }
}
