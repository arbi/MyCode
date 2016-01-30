<?php
namespace DDD\Dao\Apartment;

use DDD\Service\Booking as BookingService;
use DDD\Service\Booking;
use JsonSchema\Constraints\Object;

use Library\Utility\Debug;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\Db\Sql\Select;
use Zend\Db\Sql\Sql;
use Zend\Db\Sql\Expression;
use Zend\Db\Sql\Where;
use Zend\Stdlib\ArrayObject;

use Library\DbManager\TableGatewayManager;
use Library\Constants\DbTables;
use Library\Utility\Helper;
use Library\Constants\Objects;
use Library\Constants\Constants;
use DDD\Domain\UniversalDashboard\Widget\SuspendedApartments as SuspendedApartments;
use DDD\Service\Apartment\Rate as RateService;
use DDD\Service\Booking\BookingTicket;
use DDD\Service\Apartment\AmenityItems;
use DDD\Service\Accommodations as AccommodationService;
use DDD\Service\Task;

/**
 * DAO class for apartment genearl information
 * @author Tigran Petrosyan
 */
class General extends TableGatewayManager
{
	/**
	 * @access protected
	 * @var string
	 */
    protected $table = DbTables::TBL_APARTMENTS;

    /**
     * @access protected
     * @var string
     */
    protected $textlineTable = DbTables::TBL_PRODUCT_TEXTLINES;

    /**
     * @access public
     * @param ServiceLocatorInterface $sm
     * @param string $domain
     */
    public function __construct($sm, $domain = 'ArrayObject')
    {
        parent::__construct($sm, $domain);
    }

    public function getAllApartmentsIdsAndTimezones()
    {
        $prototype = $this->getEntity();
        $this->setEntity(new \ArrayObject());

        $result = $this->fetchAll(function (Select $select) {
            $select->columns(['id','name']);
            $select->join(
                DbTables::TBL_CITIES,
                DbTables::TBL_CITIES . '.id = ' . $this->getTable() . '.city_id',
                ['timezone']
            );
            $select->join(DbTables::TBL_PRODUCT_DESCRIPTIONS, $this->getTable() . '.id = ' . DbTables::TBL_PRODUCT_DESCRIPTIONS . '.apartment_id', array(
                'check_in',
                'check_out',
            ));
            $select->where->notEqualTo($this->getTable() . '.status', Objects::PRODUCT_STATUS_DISABLED);
        });
        $this->setEntity($prototype);
        return $result;
    }

    /**
     * @return \Zend\Db\ResultSet\ResultSet
     */
    public function getAllApartmentsIdsAndTimezonesThatHaveExtraInspectionEnabled()
    {
        $prototype = $this->getEntity();
        $this->setEntity(new \ArrayObject());

        $result = $this->fetchAll(function (Select $select) {
                $select->columns(['id','name']);
                $select->join(
                        ['team_frontier_apartments' => DbTables::TBL_TEAM_FRONTIER_APARTMENTS],
                        new Expression($this->getTable() . '.id = team_frontier_apartments.apartment_id'),
                []
                )
                ->join(
                            ['teams' => DbTables::TBL_TEAMS],
                            new Expression('team_frontier_apartments.team_id = teams.id'),
                    ['team_id' => 'id']
                    )
                ->join(
                        DbTables::TBL_CITIES,
                        DbTables::TBL_CITIES . '.id = ' . $this->getTable() . '.city_id',
                ['timezone']
                )
                ->join(DbTables::TBL_PRODUCT_DESCRIPTIONS, $this->getTable() . '.id = ' . DbTables::TBL_PRODUCT_DESCRIPTIONS . '.apartment_id', array(
                        'check_in',
                        'check_out',
                    ));
            $select->where->notEqualTo($this->getTable() . '.status', Objects::PRODUCT_STATUS_DISABLED)
                          ->equalTo('teams.extra_inspection', Task::TASK_EXTRA_INSPECTION);
         });
        $this->setEntity($prototype);
         return $result;
     }



    public function getApartmentTimezoneById($apartmentId)
    {
        $prototype = $this->getEntity();
        $this->setEntity(new \ArrayObject());

        $result = $this->fetchOne(function (Select $select) use ($apartmentId) {
            $select->columns(['id']);
            $select->join(
                DbTables::TBL_CITIES,
                DbTables::TBL_CITIES . '.id = ' . $this->getTable() . '.city_id',
                ['timezone']
            );
            $select->where->notEqualTo($this->getTable() . '.id', $apartmentId);
        });
        $this->setEntity($prototype);
        return $result;
    }

    public function getOpenNextMonthAvailability($apartmentId)
    {
        $prototype = $this->getEntity();
        $this->setEntity(new \ArrayObject());

        $result = $this->fetchOne(function (Select $select) use ($apartmentId) {
            $select->columns(['open_next_month_availability']);
            $select->where->equalTo($this->getTable() . '.id', $apartmentId);
        });

        $this->setEntity($prototype);
        return $result;
    }

    public function getInfoForDetailsController($apartmentId)
    {
        $prototype = $this->getEntity();
        $this->setEntity(new \ArrayObject());

        $result = $this->fetchOne(function (Select $select) use ($apartmentId) {
            $select->columns(['lock_id']);
            $select->where(['id' => $apartmentId]);
        });

        $this->setEntity($prototype);

        return $result;
    }

    /**
     * @param int $apartmentId
     * @return int
     */
    public function getStatusID($apartmentId)
    {
    	$this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());

    	return $this->fetchOne(function (Select $select) use ($apartmentId) {
    		$select->columns(['status']);
    		$select->where(['id' => $apartmentId]);
    	});
    }

    public function getLockId($apartmentId)
    {
        $this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());

        return $this->fetchOne(function (Select $select) use ($apartmentId) {
            $select->columns(['lock_id']);
            $select->where(['id' => $apartmentId]);
        });
    }

    /**
     * @param $countryId
     * @param int $selectedId
     * @return \Zend\Db\ResultSet\ResultSet
     */
    public function getApartmentsForCountryForSelect($countryId = false, $selectedId = 0)
    {
        $prototype = $this->resultSetPrototype->getArrayObjectPrototype();
        $this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());

        $result = $this->fetchAll(function (Select $select) use ($countryId, $selectedId) {
            $where = new Where();
            $nestedWhere = new Where();
            $nestedWhere
                ->notIn('id', [Constants::TEST_APARTMENT_1, Constants::TEST_APARTMENT_2])
                ->AND
                ->notEqualTo('status', AccommodationService::APARTMENT_STATUS_DISABLED);

            if ($selectedId) {
                $where
                    ->NEST
                    ->equalTo($this->getTable() . '.id', $selectedId)
                    ->orPredicate($nestedWhere)
                    ->UNNEST;
            } else {
                $where->addPredicate($nestedWhere);
            }

            $select->columns(['id', 'name']);

            if ($countryId) {
                $where->equalTo('country_id', $countryId);
            }
            $select->where($where);
        });
        $this->resultSetPrototype->setArrayObjectPrototype($prototype);

        return $result;
    }

    public function getAllApartmentsWithLock($lockId)
    {
        $prototype = $this->resultSetPrototype->getArrayObjectPrototype();
        $this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());
        $result = $this->fetchAll(function (Select $select) use ($lockId) {
            $select->columns(['id', 'name']);
            $select->where(['lock_id' => $lockId]);
        });
        $this->resultSetPrototype->setArrayObjectPrototype($prototype);

        return $result;
    }

	public function getCurrency($apartmentId)
    {
		$this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());

		return $this->fetchOne(function (Select $select) use ($apartmentId) {
			$select->columns([]);
			$select->join(
				DbTables::TBL_CURRENCY,
				DbTables::TBL_CURRENCY . '.id = ' . $this->getTable() . '.currency_id',
				['code', 'symbol']
			);
			$select->where([$this->getTable() . '.id' => $apartmentId]);
		});
	}

    /**
     * @param int $apartmentId
     * @return int
     */
    public function getReviewScore($apartmentId)
    {
        $prototype = $this->resultSetPrototype->getArrayObjectPrototype();
    	$this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());

    	$result =  $this->fetchOne(function (Select $select) use ($apartmentId) {
    		$select->columns(['score']);
    		$select->where(['id' => $apartmentId]);
    	});
        $this->resultSetPrototype->setArrayObjectPrototype($prototype);
        return $result;
    }

    /**
     * Get apartment general info
     * @access public
     *
     * @param int $apartmentId
     * @return ArrayObject
     */
    public function getApartmentGeneralInfo($apartmentId)
    {
    	$this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());

    	$result = $this->fetchOne(function (Select $select) use ($apartmentId) {
    		$select->columns(
                [
                    'id',
                    'name',
                    'status',
                    'currency_id',
                    'create_date',
                    'max_capacity',
                    'square_meters',
                    'bedroom_count',
                    'bathroom_count',
                    'room_count',
                    'unit_number'
                ]
            );

    		// general policy
    		$select->join(
                ['description' => DbTables::TBL_PRODUCT_DESCRIPTIONS],
                $this->table . '.id = description.apartment_id',
                [
    				'check_in',
    				'check_out',
                    'primary_wifi_network',
                    'primary_wifi_pass',
    				'general_descr'
                ]
            );

            $select->join(
                ['city' => DbTables::TBL_CITIES],
                $this->table . '.city_id = city.id',
                ['timezone'],
                Select::JOIN_LEFT
            );

            $select->join(
                ['location_details' => DbTables::TBL_LOCATION_DETAILS],
                'city.detail_id = location_details.id',
                ['city_name' => 'name'],
                Select::JOIN_LEFT
            );

    		// description textlines from apartment textlines table
    		$select->join(
                ['textline1' => $this->textlineTable],
                'description.general_descr = textline1.id',
                ['general_description' => 'en']
            );

            // join for country number
            $select->join(
                ['country' => DbTables::TBL_COUNTRIES],
                $this->table . '.country_id = country.id',
                ['contact_phone'],
                Select::JOIN_LEFT
            );

    		$select->where->equalTo($this->table . '.id', $apartmentId);
    	});

    	return $result;
    }

    /**
     * Get apartment location details such as country, province, city
     * @access public
     *
     * @param int $apartmentId
     * @return \DDD\Domain\Apartment\Location\ApartmentUrlComponents
     */
    public function getApartmentUrlComponents($apartmentId)
    {
        $result = $this->fetchOne(function (Select $select) use ($apartmentId) {
            $select->columns(
                ['url']
            );

            $select->join(
                DbTables::TBL_CITIES,
                $this->table . '.city_id = ' . DbTables::TBL_CITIES . '.id',
                []
            );

            $select->join(
                DbTables::TBL_PROVINCES,
                DbTables::TBL_CITIES . '.province_id = ' . DbTables::TBL_PROVINCES . '.id',
                []
            );

            $select->join(
                DbTables::TBL_COUNTRIES,
                DbTables::TBL_PROVINCES . '.country_id = ' . DbTables::TBL_COUNTRIES . '.id',
                []
            );

            $select->join(
                ["det1" => DbTables::TBL_LOCATION_DETAILS],
                DbTables::TBL_CITIES . '.detail_id = det1.id',
                [
                    'city'      => 'name',
                    'city_slug' => 'slug'
                ]
            );

            $select->join(
                ["det2" => DbTables::TBL_LOCATION_DETAILS],
                DbTables::TBL_COUNTRIES . '.detail_id = det2.id',
                [
                    'country'      => 'name',
                    'country_slug' => 'slug'
                ]
            );

            $select->join(
                ["det3" => DbTables::TBL_LOCATION_DETAILS],
                DbTables::TBL_PROVINCES . '.detail_id = det3.id',
                [
                    'province'      => 'name',
                    'province_slug' => 'slug'
                ]
            );

            $select->where
                ->equalTo($this->table . '.id', $apartmentId)
                ->in($this->table . '.status',
                    [
                        Objects::PRODUCT_STATUS_LIVEANDSELLIG,
                        Objects::PRODUCT_STATUS_SELLINGNOTSEARCHABLE,
                        Objects::PRODUCT_STATUS_REVIEW
                    ]
            );
        });

        return $result;
    }

    /**
     * @param $apartmentGroupId
     * @param $from
     * @param $to
     * @param $roomCount
     * @param string $sort
     * @return \Zend\Db\ResultSet\ResultSet
     */
    public function getGroupAvailabilityForDateRange($apartmentGroupId, $from, $to, $roomCount, $sort = 'max_capacity', $roomType)
    {
        return $this->fetchAll(function (Select $select) use ($apartmentGroupId, $from, $to, $roomCount, $sort, $roomType) {
        	$generalColumns = [
        		'id',
        		'name',
        		'reservation_data' => new Expression('(
                    SELECT CONCAT(res_number, "_", ki_viewed, "_", date_from, "_", date_to, "_", apartel_id, "_", occupancy, "_", guest_balance, "_", locked, "_", overbooking_status, "_", channel_res_id) as reservation_data
                    FROM ' . DbTables::TBL_BOOKINGS. ' as bookings
                    WHERE bookings.`apartment_id_assigned` = `product_acc`.`apartment_id`
                        AND bookings.`date_from` <= `product_rate_av`.`date`
                        AND bookings.`date_to` > `product_rate_av`.`date`
                        AND `product_rate_av`.`availability` = 0 AND `bookings`.`status` = ' . Booking::BOOKING_STATUS_BOOKED . '
                        AND `bookings`.`overbooking_status` != ' . BookingTicket::OVERBOOKING_STATUS_OVERBOOKED . '
                        LIMIT 1
                )'),
                'block',
                'max_capacity' 	=> 'max_capacity',
        		'floor' 		=> 'floor',
        		'bedroom_count' => 'bedroom_count',
        		'bathroom_count'=> 'bathroom_count',
        		'unit_number' 	=> 'unit_number',
                'building_name' => new Expression('(
                    select ga_apartment_groups2.name from ga_apartment_group_items as ga_apartment_group_items2
                        left join ga_apartment_groups as ga_apartment_groups2 on ga_apartment_groups2.id = ga_apartment_group_items2.apartment_group_id
                    where ga_apartment_group_items2.apartment_id = ga_apartments.id and ga_apartment_groups2.usage_building = 1
                    limit 1
                )'),
          	];

            $rateColumns = [
        		'availability'  => 'availability',
        		'date'			=> 'date',
        	];

    		$select->columns($generalColumns)
                ->join(['product_acc' => DbTables::TBL_APARTMENT_GROUP_ITEMS], $this->getTable().'.id = product_acc.apartment_id', [])
                ->join(['product_rate' => DbTables::TBL_APARTMENT_RATES], $this->getTable().'.id = product_rate.apartment_id', [])
                ->join(['product_rate_av' => DbTables::TBL_APARTMENT_INVENTORY], 'product_rate.id = product_rate_av.rate_id', $rateColumns)
                ->join(
                    ['amenity' => DbTables::TBL_APARTMENT_AMENITY_ITEMS],
                    new Expression($this->getTable().'.id = amenity.apartment_id AND amenity_id=' . AmenityItems::AMENITY_BALCONY),
                    ['amenity_id'],
                    Select::JOIN_LEFT
            );

            if ($roomType > 0) {
                $select->join(
                    ['rel_apartment_room_type' => DbTables::TBL_APARTEL_REL_TYPE_APARTMENT],
                    new Expression($this->getTable() . '.id = rel_apartment_room_type.apartment_id AND rel_apartment_room_type.apartel_type_id = ' . $roomType),
                    [],
                    Select::JOIN_INNER
                );
            }

    		$select->where
                ->equalTo('product_acc.apartment_group_id', $apartmentGroupId)
                ->greaterThanOrEqualTo('product_rate_av.date', $from)
                ->lessThanOrEqualTo('product_rate_av.date', $to)
                ->equalTo('product_rate.type', RateService::TYPE1);

    		if ($roomCount != -1) {
    			$select->where->equalTo($this->getTable() . '.bedroom_count', $roomCount);
    		}

            $sortingColumns = ['max_capacity DESC', 'bedroom_count DESC', 'score ASC', 'name ASC'];

            if (!is_null($sort)) {
                switch ($sort) {
                    case 'max_capacity':
                        break;
                    case 'bedroom_count':
                        array_shift($sortingColumns);

                        break;
                    case 'building_name':
                        array_unshift($sortingColumns, 'building_name DESC');

                        break;
                }
            }
            $select->order($sortingColumns);
    	});
    }

    public function isLiveAndSelling($apartmentId)
    {
       	$result = $this->fetchOne(function (Select $select) use ($apartmentId) {
    		$select->columns(array('name',
                                   'currency_id',
                                   'country_id',
                                   'province_id',
                                   'city_id',
                                   'address',
                                   'max_capacity'
                                  ));
    		$select->join(array('prod_desc' => DbTables::TBL_PRODUCT_DESCRIPTIONS), $this->table . '.id = prod_desc.apartment_id',
                          array(
                                'general_descr'
                         ), 'LEFT');

    		$select->join(array('prod_rates' => DbTables::TBL_APARTMENT_RATES), new Expression($this->table . '.id = prod_rates.apartment_id AND prod_rates.active = 1'),
                          array(
                                'rate_id'=>'id',
                         ), 'LEFT');

    		$select->join(array('prod_images' => DbTables::TBL_APARTMENT_IMAGES), $this->table . '.id = prod_images.apartment_id',
                          array(
                                'img1',
                         ), 'LEFT');

    		$select->join(array('prod_location' => DbTables::TBL_APARTMENT_LOCATIONS), $this->table . '.id = prod_location.apartment_id',
                          array(
                                'x_pos',
                                'y_pos',
                         ), 'LEFT');

    		// description textlines from apartment textlines table
    		$select->join(array('textline1' => $this->textlineTable), 'prod_desc.general_descr = textline1.id',
                          array('general_description' => 'en'), 'LEFT');
    		$select->where->equalTo($this->table . '.id', $apartmentId);
    	});

    	return $result;
    }

    /**
     * @param string $apartelTitle
     * @param string $city
     * @return \DDD\Domain\Apartment\General
     */
    public function getApartmentGeneralByTitle($apartelTitle, $city)
    {
    	$result = $this->fetchOne(function (Select $select) use ($apartelTitle,  $city) {
    		$select->columns(
    			[
                    'aprtment_id'=>'id',
                    'name',
                    'status',
                    'address',
                    'currency_id',
                    'city_id',
                    'country_id',
                    'province_id',
                    'score',
                    'max_capacity',
                    'square_meters',
                    'bedroom_count',
                    'bathroom_count',
                ]
            );
    		$select->join(['city' => DbTables::TBL_CITIES], $this->table . '.city_id = city.id', ['timezone']);
    		$select->join(
    			['geo_details' => DbTables::TBL_LOCATION_DETAILS],
                new Expression("geo_details.id = city.detail_id AND LOWER(geo_details.name) = '".$city."'"),
             	['city_name'=>'name']
            );
    		$select->join(
    			['media' => DbTables::TBL_APARTMENT_IMAGES],
    			$this->table . '.id = media.apartment_id',
    			['*']
    		);
    		$select->join(
    			['prod_type' => DbTables::TBL_PRODUCT_TYPES],
    			$this->table . '.id = prod_type.apartment_id',
    			['room_id' =>'id']
    		);
    		$select->join(
    			['location' => DbTables::TBL_APARTMENT_LOCATIONS],
    			$this->table . '.id = location.apartment_id',
    			['x_pos', 'y_pos']
    		);
        	$select->join(
        		['prod_descr' => DbTables::TBL_PRODUCT_DESCRIPTIONS],
        		$this->table . '.id = prod_descr.apartment_id',
        		['general_descr','check_in', 'check_out']
        	);
            $select->join(
            	['textline1' => DbTables::TBL_PRODUCT_TEXTLINES],
            	'prod_descr.general_descr = textline1.id',
            	['general_description' => 'en']
            );
    		$select->join(
    			['prod_acc' => DbTables::TBL_APARTMENT_GROUP_ITEMS],
    			$this->table . '.id = prod_acc.apartment_id',
    			[],
    			'LEFT'
    		);
    		$select->join(
    			['prod_group' => DbTables::TBL_APARTMENT_GROUPS],
    			new Expression('prod_group.id = prod_acc.apartment_group_id AND prod_group.usage_apartel = 1'),
                ['apartel_name'=>'name'],
                'LEFT'
            );
    		$select->join(
    			['prod_rate' => DbTables::TBL_APARTMENT_RATES],
    			new Expression($this->table . '.id = prod_rate.apartment_id AND prod_rate.active = 1'),
                [
	                'price_avg' => new Expression('MIN(week_price)'),
	                'minstay'   => new Expression('MIN(min_stay)'),
	                'maxstay'   => new Expression('MAX(max_stay)'),
	                'windowmin' => new Expression('MAX(release_period_start)'),
	                'windowmax' => new Expression('MAX(release_period_end)'),
                ],
                'LEFT'
            );
            $select->join(
            	['currency' => DbTables::TBL_CURRENCY],
            	$this->table .'.currency_id = currency.id', ['code', 'symbol']);

            $select->where->equalTo($this->table . '.url', $apartelTitle)->in(
        		$this->table . '.status',
        		[
        			Objects::PRODUCT_STATUS_LIVEANDSELLIG,
        			Objects::PRODUCT_STATUS_SELLINGNOTSEARCHABLE,
        			Objects::PRODUCT_STATUS_REVIEW
        		]
            );

            $select->group($this->table . '.id');
    	});

    	return $result;
    }

    /**
     * @param string $apartelTitle
     * @param string $slug
     * @return \DDD\Domain\Apartment\General
     */
    public function getApartmentGeneralBySlug($apartelTitle, $slug)
    {
    	$result = $this->fetchOne(function (Select $select) use ($apartelTitle,  $slug) {
    		$select
                ->columns(
                    [
                        'aprtment_id'=>'id',
                        'name',
                        'status',
                        'address',
                        'currency_id',
                        'city_id',
                        'country_id',
                        'province_id',
                        'score',
                        'max_capacity',
                        'square_meters',
                        'bedroom_count',
                        'bathroom_count',
                    ]
                )
                ->join(
                    ['city' => DbTables::TBL_CITIES],
                    $this->table . '.city_id = city.id',
                    ['timezone']
                )
                ->join(
                    ['geo_details' => DbTables::TBL_LOCATION_DETAILS],
                    new Expression("geo_details.id = city.detail_id AND LOWER(geo_details.slug) = '".$slug."'"),
                    [
                        'city_name' => 'name',
                        'city_slug' => 'slug'
                    ]
                )
                ->join(
                    ['media' => DbTables::TBL_APARTMENT_IMAGES],
                    $this->table . '.id = media.apartment_id',
                    ['*']
                )
                ->join(
                    ['prod_type' => DbTables::TBL_PRODUCT_TYPES],
                    $this->table . '.id = prod_type.apartment_id',
                    ['room_id' =>'id']
                )
                ->join(
                    ['location' => DbTables::TBL_APARTMENT_LOCATIONS],
                    $this->table . '.id = location.apartment_id',
                    ['x_pos', 'y_pos']
                )
                ->join(
                    ['prod_descr' => DbTables::TBL_PRODUCT_DESCRIPTIONS],
                    $this->table . '.id = prod_descr.apartment_id',
                    ['general_descr','check_in', 'check_out']
                )
                ->join(
                    ['textline1' => DbTables::TBL_PRODUCT_TEXTLINES],
                    'prod_descr.general_descr = textline1.id',
                    ['general_description' => 'en']
                )
                ->join(
                    ['prod_acc' => DbTables::TBL_APARTMENT_GROUP_ITEMS],
                    $this->table . '.id = prod_acc.apartment_id',
                    [],
                    'LEFT'
                )
                ->join(
                    ['prod_group' => DbTables::TBL_APARTMENT_GROUPS],
                    new Expression('prod_group.id = prod_acc.apartment_group_id AND prod_group.usage_apartel = 1'),
                    ['apartel_name'=>'name'],
                    'LEFT'
                )
                ->join(
                    ['prod_rate' => DbTables::TBL_APARTMENT_RATES],
                    new Expression($this->table . '.id = prod_rate.apartment_id AND prod_rate.active = 1'),
                    [
                        'price_avg' => new Expression('MIN(week_price)'),
                        'minstay'   => new Expression('MIN(min_stay)'),
                        'maxstay'   => new Expression('MAX(max_stay)'),
                        'windowmin' => new Expression('MAX(release_period_start)'),
                        'windowmax' => new Expression('MAX(release_period_end)'),
                    ],
                    'LEFT'
                )
                ->join(
                    ['currency' => DbTables::TBL_CURRENCY],
                    $this->table .'.currency_id = currency.id', ['code', 'symbol']
                );

            $select->where->equalTo($this->table . '.url', $apartelTitle)->in(
        		$this->table . '.status',
        		[
        			Objects::PRODUCT_STATUS_LIVEANDSELLIG,
        			Objects::PRODUCT_STATUS_SELLINGNOTSEARCHABLE,
        			Objects::PRODUCT_STATUS_REVIEW
        		]
            );

            $select->group($this->table . '.id');
    	});

    	return $result;
    }

    /**
     * @param Int $apartmentId
     * @return array|\ArrayObject|null
     */
    public function getApartmentDataForReservationMove($apartmentId)
    {
        $this->resultSetPrototype->setArrayObjectPrototype(new ArrayObject());

        $result = $this->fetchOne(function (Select $select) use ($apartmentId) {
            $select->columns([]);

            $select->join(
                [ 'city' => DbTables::TBL_CITIES ],
                $this->table . '.city_id = city.id', ['timezone']
            );

            $select->join(
                ['prod_descr' => DbTables::TBL_PRODUCT_DESCRIPTIONS],
                $this->table . '.id = prod_descr.apartment_id',
                [
                    'check_in',
                ]
            );

            $select->where->equalTo($this->table . '.id', $apartmentId);
        });

        return $result;
    }

    /**
     * @param $city
     * @param $arrival
     * @param $departure
     * @param $guest
     * @param $pageItemCount
     * @param $offset
     * @param $bedrooms
     * @return array
     */
    public function getApartmentsByCityDate($city, $arrival, $departure, $guest, $pageItemCount, $offset, $bedrooms)
    {
        $bookNightCount  = Helper::getDaysFromTwoDate($arrival, $departure);
        $checkNightCount = Helper::getDaysFromTwoDate($arrival, date('Y-m-d'));
        $bedroomsSqlPart = '';

        if (count($bedrooms) > 0 && count($bedrooms) < 12) {
            $bedroomsSqlPart = 'AND apartment.bedroom_count in (' . implode(',', $bedrooms) . ')';
        }

        $sql = "
                SELECT SQL_CALC_FOUND_ROWS sub2.*, MIN(sub2.price_avg) as price_min, MAX(sub2.price_avg) as price_max FROM (
                    SELECT sub1.*, avg(price_av) as price_avg, MIN(availability) as availability_min FROM  (
                        SELECT
                        apartment.id AS prod_id,
                        apartment.name AS prod_name,
                        apartment.url AS url,
                        apartment.score AS score,
                        apartment.max_capacity as capacity,
                        apartment.city_id,
                        apartment.country_id,
                        media.img1 AS img1,
                        apartment.bedroom_count AS bedroom_count,
                        apartment.square_meters AS square_meters,
                        apartment.address AS address,
                        (rate_av.availability) AS availability,
                        (rate_av.price) as price_av,
                        rates.id as rate_id,
                        apartment.currency_id,
                        rates.name as rate_name,
                        det1.slug as slug,
                        currency.code, currency.symbol
                        FROM " . DbTables::TBL_APARTMENTS . " as apartment
                        INNER JOIN " . DbTables::TBL_CITIES . " AS city ON apartment.city_id = city.id
                        INNER JOIN " . DbTables::TBL_LOCATION_DETAILS . " AS det1 ON city.detail_id = det1.id
                        INNER JOIN " . DbTables::TBL_APARTMENT_IMAGES . " AS media ON apartment.id = media.apartment_id
                        INNER JOIN " . DbTables::TBL_APARTMENT_RATES . " AS rates ON apartment.id = rates.apartment_id
                        INNER JOIN " . DbTables::TBL_CURRENCY . " as currency ON apartment.currency_id = currency.id
                        AND rates.active = 1
                        AND rates.min_stay <= ?
                        AND rates.max_stay >= ?
                        AND rates.release_period_start <= ?
                        AND rates.release_period_end >= ?
                        INNER JOIN " . DbTables::TBL_APARTMENT_INVENTORY . " AS rate_av ON rates.id = rate_av.rate_id
                        WHERE det1.slug = ?
                        AND apartment.status = " . Objects::PRODUCT_STATUS_LIVEANDSELLIG . "
                        AND rates.capacity >= ?
                        AND rate_av.date >= ?
                        AND rate_av.date < ?
                        {$bedroomsSqlPart}
                        ORDER BY apartment.id ASC, rate_av.availability ASC
                    ) as sub1 group by sub1.rate_id
                )  as sub2 WHERE sub2.availability_min = 1
                GROUP BY sub2.prod_id ORDER BY sub2.score DESC, sub2.price_avg ASC, sub2.capacity ASC, sub2.prod_name ASC
                LIMIT {$pageItemCount} OFFSET {$offset}";

        $statement = $this->adapter->createStatement($sql, [
            $bookNightCount,
            $bookNightCount,
            $checkNightCount,
            $checkNightCount,
            $city,
            $guest,
            $arrival,
            $departure
        ]);

        $result         = $statement->execute();
        $statementCount = $this->adapter->query('SELECT FOUND_ROWS() as total');
        $resultCount    = $statementCount->execute();
        $rowCount       = $resultCount->current();
        $total          = $rowCount['total'];

    	return [
            'result' => $result,
            'total'  => $total
        ];
    }

    /**
     * @param $city
     * @param $guest
     * @param $pageItemCount
     * @param $offset
     * @param $getAll
     * @param $bedrooms
     * @return array
     */
    public function getApartmentsCity($city, $guest, $pageItemCount, $offset, $getAll, $bedrooms)
    {
        $bedroomsSqlPart = '';

        if (count($bedrooms) > 0 && count($bedrooms) < 12) {
            $bedroomsSqlPart = 'AND apartment.bedroom_count IN (' . implode(',', $bedrooms) . ')';
        }

        $sql = "SELECT SQL_CALC_FOUND_ROWS
                    apartment.id AS prod_id,
                    apartment.name AS prod_name,
                    apartment.url AS url,
                    apartment.score AS score,
                    apartment.city_id,
                    apartment.country_id,
                    media.img1 AS img1,
                    apartment.bedroom_count AS bedroom_count,
                    apartment.square_meters AS square_meters,
                    apartment.address AS address,
                    MIN(inventory.price) as price_min,
                    apartment.currency_id,
                    currency.code,
                    currency.symbol,
                    apartment.max_capacity as capacity,
                    rates.name as rate_name,
                    det1.slug as slug
                    FROM  " . DbTables::TBL_APARTMENTS .  " as apartment
                    INNER JOIN " . DbTables::TBL_CITIES . " AS city ON apartment.city_id = city.id
                    INNER JOIN " . DbTables::TBL_LOCATION_DETAILS . " AS det1 ON city.detail_id = det1.id
                    INNER JOIN " . DbTables::TBL_APARTMENT_IMAGES . " AS media ON apartment.id = media.apartment_id
                    INNER JOIN " . DbTables::TBL_APARTMENT_RATES . " AS rates ON apartment.id = rates.apartment_id AND rates.active = 1
                    INNER JOIN " . DbTables::TBL_CURRENCY . " as currency ON apartment.currency_id = currency.id
                    INNER JOIN " . DbTables::TBL_APARTMENT_INVENTORY . " as inventory ON rates.id = inventory.rate_id
                    WHERE det1.slug = ?
                    AND apartment.max_capacity >= ?
                    AND apartment.status = " . Objects::PRODUCT_STATUS_LIVEANDSELLIG . "
                    {$bedroomsSqlPart}
                    GROUP BY apartment.id ORDER BY apartment.score DESC, inventory.price ASC";
        if (!$getAll) {
            $sql .= " LIMIT " . $pageItemCount . " OFFSET ".$offset;
        }

        $statement = $this->adapter->createStatement($sql, [$city, $guest]);
        $result = $statement->execute();

        $statementCount = $this->adapter->query('SELECT FOUND_ROWS() as total');
        $resultCount = $statementCount->execute();
        $rowCount = $resultCount->current();
        $total = $rowCount['total'];

        return [
            'result' => $result,
            'total' => $total,
        ];
    }

    /**
     * @param $apartel
     * @param $arrival
     * @param $departure
     * @param $guest
     * @param $pageItemCount
     * @param $offset
     * @param $bedrooms
     * @return array
     */
    public function getApartmentsByApartelDate($apartel, $arrival, $departure, $guest, $pageItemCount, $offset, $bedrooms)
    {
        $bookNightCount  = Helper::getDaysFromTwoDate($arrival, $departure);
        $checkNightCount = Helper::getDaysFromTwoDate($arrival, date('Y-m-d'));
        $bedroomsSqlPart = '';

        if (count($bedrooms) > 0 && count($bedrooms) < 12) {
            $bedroomsSqlPart = 'AND apartment.bedroom_count in (' . implode(',', $bedrooms) . ')';
        }

        $sql = "
                SELECT SQL_CALC_FOUND_ROWS sub2.*, MIN(sub2.price_avg) as price_min, MAX(sub2.price_avg) as price_max FROM (
                    SELECT sub1.*, avg(price_av) as price_avg, MIN(availability) as availability_min FROM  (
                        SELECT
                              apartment.id            AS prod_id,
                              apartment.name          AS prod_name,
                              apartment.url           AS url,
                              apartment.score         AS score,
                              apartment.max_capacity  AS capacity,
                              apartment.city_id,
                              apartment.country_id,
                              media.img1              AS img1,
                              apartment.bedroom_count AS bedroom_count,
                              apartment.square_meters AS square_meters,
                              apartment.address       AS address,
                              (rate_av.availability)  AS availability,
                              (rate_av.price)         AS price_av,
                              rates.id                AS rate_id,
                              apartment.currency_id,
                              rates.name              AS rate_name,
                              currency.code,
                              currency.symbol,
                              det1.slug as slug,
                              apartel.apartment_group_id
                            FROM " . DbTables::TBL_APARTEL_REL_TYPE_APARTMENT . " AS rel_apartel
                              INNER JOIN " . DbTables::TBL_APARTEL_TYPE . " AS apartel_type ON rel_apartel.apartel_type_id = apartel_type.id
                              INNER JOIN " . DbTables::TBL_APARTELS . " AS apartel ON apartel_type.apartel_id = apartel.id
                              INNER JOIN " . DbTables::TBL_APARTMENTS . " AS apartment ON rel_apartel.apartment_id = apartment.id
                              INNER JOIN " . DbTables::TBL_CITIES . " AS city ON apartment.city_id = city.id
                              INNER JOIN " . DbTables::TBL_APARTMENT_IMAGES . " AS media ON apartment.id = media.apartment_id
                              INNER JOIN " . DbTables::TBL_APARTMENT_RATES . " AS rates ON apartment.id = rates.apartment_id
                                                                        AND rates.active = 1
                                                                        AND rates.min_stay <= ?
                                                                        AND rates.max_stay >= ?
                                                                        AND rates.release_period_start <= ?
                                                                        AND rates.release_period_end >= ?
                              INNER JOIN ga_apartment_inventory AS rate_av ON rates.id = rate_av.rate_id
                              INNER JOIN " . DbTables::TBL_CURRENCY . " as currency ON apartment.currency_id = currency.id
                              INNER JOIN " . DbTables::TBL_LOCATION_DETAILS . " AS det1 ON city.detail_id = det1.id
                            WHERE apartel.slug = ? AND apartment.status = " . Objects::PRODUCT_STATUS_LIVEANDSELLIG . "
                                  AND rates.capacity >= ?
                                  AND rate_av.date >= ?
                                  AND rate_av.date < ?
                                  {$bedroomsSqlPart}
                            ORDER BY apartment.id ASC, rate_av.availability ASC
                    ) as sub1 GROUP BY sub1.rate_id
                )  as sub2  WHERE sub2.availability_min = 1
                GROUP BY sub2.prod_id
                ORDER BY sub2.score DESC, sub2.price_avg ASC, sub2.prod_name ASC
                LIMIT {$pageItemCount} OFFSET {$offset}
        ";

        $statement = $this->adapter->createStatement($sql, [
            $bookNightCount,
            $bookNightCount,
            $checkNightCount,
            $checkNightCount,
            $apartel,
            $guest,
            $arrival,
            $departure
        ]);

        $result         = $statement->execute();
        $statementCount = $this->adapter->query('SELECT FOUND_ROWS() as total');
        $resultCount    = $statementCount->execute();
        $rowCount       = $resultCount->current();
        $total          = $rowCount['total'];

    	return [
            'result' => $result,
            'total'  => $total
        ];
    }


    /**
     * @param $apartel
     * @param $guest
     * @param $pageItemCount
     * @param $offset
     * @param $getAll
     * @param $bedrooms
     * @return array
     */
    public function getApartmentsApartel($apartel, $guest, $pageItemCount, $offset, $getAll, $bedrooms)
    {
        $bedroomsSqlPart = '';

        if (count($bedrooms) > 0 && count($bedrooms) < 12) {
            $bedroomsSqlPart = 'AND apartment.bedroom_count IN (' . implode(',', $bedrooms) . ')';
        }

        $sql = "SELECT SQL_CALC_FOUND_ROWS
                    apartment.id AS prod_id,
                    apartment.name AS prod_name,
                    apartment.url AS url,
                    apartment.score AS score,
                    apartment.city_id,
                    apartment.country_id,
                    media.img1 AS img1,
                    apartment.bedroom_count AS bedroom_count,
                    apartment.square_meters AS square_meters,
                    apartment.address AS address,
                    MIN(inventory.price) as price_min,
                    apartment.currency_id,
                    currency.code,
                    currency.symbol,
                    apartment.max_capacity as capacity,
                    rates.name as rate_name,
                    det1.slug as slug,
                    apartel.apartment_group_id
                    FROM " . DbTables::TBL_APARTEL_REL_TYPE_APARTMENT . " AS rel_apartel
                    INNER JOIN " . DbTables::TBL_APARTEL_TYPE . " AS apartel_type ON rel_apartel.apartel_type_id = apartel_type.id
                    INNER JOIN " . DbTables::TBL_APARTELS . " AS apartel ON apartel_type.apartel_id = apartel.id
                    INNER JOIN " . DbTables::TBL_APARTMENTS . " AS apartment ON rel_apartel.apartment_id = apartment.id
                    INNER JOIN " . DbTables::TBL_CITIES . " AS city ON apartment.city_id = city.id
                    INNER JOIN " . DbTables::TBL_LOCATION_DETAILS . " AS det1 ON city.detail_id = det1.id
                    INNER JOIN " . DbTables::TBL_APARTMENT_IMAGES . " AS media ON apartment.id = media.apartment_id
                    INNER JOIN " . DbTables::TBL_APARTMENT_RATES . " AS rates ON apartment.id = rates.apartment_id AND rates.active = 1
                    INNER JOIN " . DbTables::TBL_CURRENCY . " as currency ON apartment.currency_id = currency.id
                    INNER JOIN " . DbTables::TBL_APARTMENT_INVENTORY . " as inventory ON rates.id = inventory.rate_id
                    WHERE apartel.slug = ?
                    AND apartment.max_capacity >= ?
                    AND apartment.status = " . Objects::PRODUCT_STATUS_LIVEANDSELLIG . "
                    {$bedroomsSqlPart}
                    GROUP BY apartment.id ORDER BY apartment.score DESC, inventory.price ASC";
        if (!$getAll) {
            $sql .= " LIMIT " . $pageItemCount . " OFFSET ".$offset;
        }

        $statement = $this->adapter->createStatement($sql, [$apartel, $guest]);
        $result = $statement->execute();

        $statementCount = $this->adapter->query('SELECT FOUND_ROWS() as total');
        $resultCount = $statementCount->execute();
        $rowCount = $resultCount->current();
        $total = $rowCount['total'];

        return [
            'result' => $result,
            'total' => $total,
        ];
    }

    /**
     * @param String $txt
     * @return \Library\DbManager\Ambigous
     */
    public function getApartmentSearch($txt, $onlyLiveAndSelling = true)
    {
        $result = $this->fetchAll(function (Select $select) use ($txt, $onlyLiveAndSelling) { //
            $select
                ->columns([
                    'id', 'name', 'url'
                ])
                ->join(
                    ['city' => DbTables::TBL_CITIES],
                    $this->table .'.city_id = city.id',
                    []
                )
                ->join(
                    ['city_details' => DbTables::TBL_LOCATION_DETAILS],
                    'city_details.id = city.detail_id',
                    ['location_name' => 'name', 'location_slug' => 'slug']
                );
            if (FALSE !== $txt) {
                $select->where
                    ->like($this->table .'.name', '%' . $txt . '%');
            }

            if ($onlyLiveAndSelling) {
                $select->where
                    ->equalTo('status', Objects::PRODUCT_STATUS_LIVEANDSELLIG);
            }
            if (FALSE !== $txt) {
                $select->limit(10);
            }
    	});
    	return $result;
    }

    /**
     * @param Int $city
     * @return \Library\DbManager\Ambigous
     */
    public function getProdByCityRandom($city)
    {
         $result = $this->fetchAll(function (Select $select) use ($city) {
            $select->columns(array('id', 'name', 'url'));
            $select->join(array('img' => DbTables::TBL_APARTMENT_IMAGES), $this->table .'.id = img.apartment_id', array('img1'));
            $select->where->equalTo('city_id', $city)
                          ->equalTo('status', Objects::PRODUCT_STATUS_LIVEANDSELLIG)
                          ->expression('1=1 order by rand() limit 4', array());
    	});
    	return $result;
    }

    /**
     * @param String $apartelTitle
     * @param String $city
     * @return array|\ArrayObject|null
     */
    public function getBreadcrupDataByCityApartment($apartelTitle, $city)
    {
    	$result = $this->fetchOne(function (Select $select) use ($apartelTitle,  $city) {
    		$select->columns(array('name'));
    		$select->join(array('city' => DbTables::TBL_CITIES), $this->table . '.city_id = city.id', array())
                   ->join(array('province' => DbTables::TBL_PROVINCES),   'city.province_id = province.id', array())
                   ->join(array('d_p' => DbTables::TBL_LOCATION_DETAILS), 'province.detail_id = d_p.id', array('prov_name' => 'name'))
                   ->join(array('d_c' => DbTables::TBL_LOCATION_DETAILS), 'city.detail_id = d_c.id', array('city_name' => 'name'));
            $select->where->equalTo($this->table . '.url', $apartelTitle)
                   ->in($this->table . '.status', [Objects::PRODUCT_STATUS_LIVEANDSELLIG, Objects::PRODUCT_STATUS_SELLINGNOTSEARCHABLE])
                   ->equalTo('d_c.name', $city);
    	});

    	return $result;
    }

    public function checkApartmentSlug($slug, $apartmentId)
    {
    	$result = $this->fetchOne(function (Select $select) use ($slug, $apartmentId) {
    		$select->columns(array('id'));
    		$select->where->equalTo('url', $slug);

            if ($apartmentId) {
                $select->where->notEqualTo('id', $apartmentId);
            }
        });

    	return $result;
    }

    /**
     * @return \Library\DbManager\Ambigous
     */
    public function getApartmentsInRegistrationProcess()
    {
    	$result = $this->fetchAll(function (Select $select) {
    		$select->columns(array('id', 'name', 'create_date'));
            $select->join(DbTables::TBL_PRODUCT_STATUSES, $this->getTable() . '.status = ' . DbTables::TBL_PRODUCT_STATUSES . '.id', array('status' => 'name'), 'LEFT');
			$select->join(DbTables::TBL_CITIES, $this->getTable() . '.city_id = ' . DbTables::TBL_CITIES . '.id', array(), 'LEFT');
			$select->join(array("det1" => DbTables::TBL_LOCATION_DETAILS), DbTables::TBL_CITIES . '.detail_id = det1.id', array('city' => 'name'), 'LEFT');
    		$select->where->expression(
                        $this->getTable() . '.status = ' . Objects::PRODUCT_STATUS_SANDBOX
                        . ' OR ' . $this->getTable() . '.status = ' . Objects::PRODUCT_STATUS_REGISTRATION
                        . ' OR ' . $this->getTable() . '.status = ' . Objects::PRODUCT_STATUS_REVIEW,
                        array()
            );
    	});
    	return $result;
    }

    /**
     * @return int
     */
    public function getApartmentsInRegistrationProcessCount()
    {
        $this->resultSetPrototype->setArrayObjectPrototype(new ArrayObject);
    	$result = $this->fetchOne(function (Select $select) {
    		$select->columns(['count' => new Expression('COUNT(*)')]);
    		$select->where->expression(
                        $this->getTable() . '.status = ' . Objects::PRODUCT_STATUS_SANDBOX
                        . ' OR ' . $this->getTable() . '.status = ' . Objects::PRODUCT_STATUS_REGISTRATION
                        . ' OR ' . $this->getTable() . '.status = ' . Objects::PRODUCT_STATUS_REVIEW,
                        array()
            );
    	});
    	return $result['count'];
    }

    /**
     * @return \ArrayObject|SuspendedApartments[]
     *
     * @author Tigran Petrosyan
     */
    public function getSuspendedApartments()
    {
        $this->resultSetPrototype->setArrayObjectPrototype(new \DDD\Domain\UniversalDashboard\Widget\SuspendedApartments());

        $result = $this->fetchAll(function (Select $select) {
            $select->columns([
                'id',
                'name',
                'create_date',
                'address'
            ]);

            $select->join(
                DbTables::TBL_CITIES,
                $this->getTable() . '.city_id = ' . DbTables::TBL_CITIES . '.id',
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
                DbTables::TBL_COUNTRIES,
                $this->getTable() . '.country_id = ' . DbTables::TBL_COUNTRIES . '.id',
                [],
                Select::JOIN_LEFT
            );
            $select->join(
                ["det2" => DbTables::TBL_LOCATION_DETAILS],
                DbTables::TBL_COUNTRIES . '.detail_id = det2.id',
                ['country' => 'name'],
                Select::JOIN_LEFT
            );

            $select->where->equalTo($this->getTable() . '.status', Objects::PRODUCT_STATUS_SUSPENDED);
        });

        return $result;
    }

    /**
     * @return int
     * @author Tigran Ghabuzyan
     */
    public function getSuspendedApartmentsCount()
    {
        $this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());

        $result = $this->fetchOne(function (Select $select) {
            $select->columns(['count' => new Expression('COUNT(*)')]);

            $select->join(
                DbTables::TBL_CITIES,
                $this->getTable() . '.city_id = ' . DbTables::TBL_CITIES . '.id',
                [],
                Select::JOIN_LEFT
            );
            $select->join(
                ["det1" => DbTables::TBL_LOCATION_DETAILS],
                DbTables::TBL_CITIES . '.detail_id = det1.id',
                [],
                Select::JOIN_LEFT
            );

            $select->join(
                DbTables::TBL_COUNTRIES,
                $this->getTable() . '.country_id = ' . DbTables::TBL_COUNTRIES . '.id',
                [],
                Select::JOIN_LEFT
            );
            $select->join(
                ["det2" => DbTables::TBL_LOCATION_DETAILS],
                DbTables::TBL_COUNTRIES . '.detail_id = det2.id',
                [],
                Select::JOIN_LEFT
            );

            $select->where->equalTo($this->getTable() . '.status', Objects::PRODUCT_STATUS_SUSPENDED);
        });

        return $result['count'];
    }

    /**
     * @param Int $apartmentId
     * @return array|\ArrayObject|null
     */
    public function getMaxCapacity($apartmentId)
    {
        $this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());
        $result = $this->fetchOne(function (Select $select) use ($apartmentId) {
    		$select->columns(['max_capacity']);
    		$select->where->equalTo('id', $apartmentId);
    	});
    	return $result;
    }

    /**
     * @param Int $apartmentId
     * @param String $dateFrom
     * @param String $dateTo
     * @param Int $pax
     * @return \Zend\Db\Adapter\Driver\ResultInterface
     */
    public function getPossibleMoveDestinations($apartmentId, $dateFrom, $dateTo, $rateOccupancy)
    {
        //dates count in period
        $dateCount = floor((strtotime($dateTo) - strtotime($dateFrom)) / 3600 / 24);

        $this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());

        $sql = 'SELECT ga.`id` apartment_id, gag.`name` apartel, ga.`name` apartment
                    FROM `' . DbTables::TBL_APARTMENTS . '` ga
                        LEFT JOIN `' . DbTables::TBL_APARTMENT_GROUP_ITEMS . '` gagi
                            ON ga.`id` = gagi.`apartment_id`
                        INNER JOIN `' . DbTables::TBL_APARTMENT_GROUPS . '` gag
                            ON gagi.`apartment_group_id` = gag.`id` AND gag.`usage_apartel` = 1 AND gag.`active` = 1
                        INNER JOIN `' . DbTables::TBL_APARTMENT_GROUP_ITEMS . '` gagi2
                            ON gagi.`apartment_group_id` = gagi2.`apartment_group_id` AND gagi2.`apartment_id` = ' . $apartmentId . '
                        INNER JOIN `' . DbTables::TBL_APARTMENT_RATES . '` gpr
                            ON gpr.`apartment_id` = ga.`id`AND gpr.`type` = ' . RateService::TYPE1 . '
                        LEFT JOIN `' . DbTables::TBL_APARTMENT_INVENTORY . '` grv
                            ON grv.`rate_id` = gpr.`id` AND grv.`date` >= "' . $dateFrom . '" AND `date` < "' . $dateTo . '" AND grv.`availability` = 1
                    WHERE
                        date IS NOT NULL
                        AND (ga.`status` = ' . Objects::PRODUCT_STATUS_LIVEANDSELLIG . ' OR ga.`status` = ' . Objects::PRODUCT_STATUS_SELLINGNOTSEARCHABLE . ')
                        AND ga.`max_capacity` >= ' . $rateOccupancy . '
                    GROUP BY apartel, apartment
                    HAVING count(grv.`date`) = ' . $dateCount . '
                    ORDER BY apartel, apartment';
        $result = $this->adapter->query($sql)->execute();
        return $result;
    }

    /**
     * @param string $query
     * @param stdClass $user
     * @param int $limit
     * @return \DDD\Domain\Apartment\FrontierCard[]
     */
    public function getFrontierCardList($query, $user, $limit)
    {
        $this->resultSetPrototype->setArrayObjectPrototype(new \DDD\Domain\Apartment\FrontierCard());
        // Execute for search purposes only
        if (!$query) {
            return false;
        }

        return $this->select(function (Select $select) use ($query, $user, $limit) {
            $columns = [
                'id', 'name', 'unit_number'
            ];

            $where = new Where();
            $nestedWhere = new Where();
            $nestedWhere
                ->like($this->getTable() . '.name', '%' . $query . '%')
                ->or
                ->like($this->getTable() . '.address', '%' . $query . '%')
                ->or
                ->equalTo($this->getTable() . '.unit_number', $query);
            $where
                ->addPredicate($nestedWhere)
                ->in($this->table . '.status', [Objects::PRODUCT_STATUS_LIVEANDSELLIG, Objects::PRODUCT_STATUS_SELLINGNOTSEARCHABLE]);

            $select
                ->columns($columns)
                ->join(
                    ['group_item' => DbTables::TBL_APARTMENT_GROUP_ITEMS],
                    $this->getTable() . '.id = group_item.apartment_id',
                    []
                )->join(
                    ['cda' => DbTables::TBL_CONCIERGE_DASHBOARD_ACCESS],
                    new Expression('group_item.apartment_group_id = cda.apartment_group_id AND cda.user_id = ' . $user->id),
                    []
                )->join(
                    ['buildings' => DbTables::TBL_APARTMENT_GROUPS],
                    $this->getTable() . '.building_id = buildings.id',
                    ['building' => 'name'],
                    Select::JOIN_LEFT
                )
                ->where($where);
            if ($limit) {
                $select->limit($limit);
            }
        });
    }

    /**
     * @param int $id
     * @return \DDD\Domain\Apartment\FrontierCard
     */
    public function getTheCard($id)
    {
        $this->resultSetPrototype->setArrayObjectPrototype(new \DDD\Domain\Apartment\FrontierCard());
        // Execute for search purposes only
        if (!$id || !is_numeric($id)) {
            return false;
        }

        // get current date-time by apartment timezone
        $apartmentTimezoneResult =  $this->fetchOne(function (Select $select) use ($id) {
            $select->columns([]);
            $select->join(
                DbTables::TBL_CITIES,
                DbTables::TBL_CITIES . '.id = ' . $this->getTable() . '.city_id',
                ['timezone']
            );
            $select->where->equalTo($this->getTable() . '.id', $id);
        });

        $apartmentTimezone = $apartmentTimezoneResult->getApartmentTimezone();
        $datetime = new \DateTime('now');
        $datetime->setTimezone(new \DateTimeZone($apartmentTimezone));
        $dateTimeToday = $datetime->format('Y-m-d H:i:s');
        return $this->fetchOne(function (Select $select) use ($id , $dateTimeToday) {
            $columns = [
                'id', 'name', 'unit_number', 'address', 'building_id', 'bedroom_count'
            ];

            $where = new Where();
            $where
                ->equalTo($this->getTable() . '.id', $id)
                ->in($this->table . '.status', [Objects::PRODUCT_STATUS_LIVEANDSELLIG, Objects::PRODUCT_STATUS_SELLINGNOTSEARCHABLE]);

            $select
                ->columns($columns)
                ->join(
                    ['apartment_description' => DbTables::TBL_PRODUCT_DESCRIPTIONS],
                    $this->getTable() . '.id = apartment_description.apartment_id',
                    ['primary_wifi_network', 'primary_wifi_pass', 'secondary_wifi_network', 'secondary_wifi_pass', 'check_in', 'check_out'],
                    Select::JOIN_LEFT
                )
                ->join(
                    ['buildings' => DbTables::TBL_APARTMENT_GROUPS],
                    $this->getTable() . '.building_id = buildings.id',
                    ['building' => 'name'],
                    Select::JOIN_LEFT
                )
                ->join(
                    ['reservations' => DbTables::TBL_BOOKINGS],
                    new Expression($this->getTable() . '.id = reservations.apartment_id_assigned' .
                        ' AND ' . 'CONCAT(reservations.date_from," ",apartment_description.check_in) <= ' . "'".$dateTimeToday."'" .
                        ' AND ' . 'CONCAT(reservations.date_to," ",apartment_description.check_out) >= ' . "'".$dateTimeToday."'" .
                        ' AND ' . 'reservations.status = '.BookingService::BOOKING_STATUS_BOOKED
                    ),
                    [
                        'cur_first_name'    => 'guest_first_name',
                        'cur_last_name'     => 'guest_last_name',
                        'cur_res_num'       => 'res_number',
                        'cur_res_id'        => 'id',
                        'cur_res_date_from' => 'date_from'
                    ],
                    Select::JOIN_LEFT
                )
                ->where($where);
        });
    }

    /*
     * Use in OmniBox Search
     * @param string $query
     * @param int $limit
     */
    public function getApartmentsForOmnibox($query, $limit)
    {
        $result = $this->fetchAll(function (Select $select) use($query, $limit) {

            $select->where
                ->notEqualTo('status', '9')
                ->and
                ->like('name', '%'.$query.'%')
                ->or
                ->equalTo('id', $query);

            $select
                ->columns(['id', 'name'])
                ->order('name')
                ->limit($limit);
        });

        $out = [];

        if (count($result)) {
            foreach ($result as $item) {
                array_push($out, [
                    'id' => $item['id'],
                    'text' => $item['name'],
                    'label' => 'apartment'
                ]);
            }
        }

        return $out;
    }

    /**
     * @param $apartmentId
     * @return array
     */
    public function getBuildingFacilitiesByApartmentId($apartmentId)
    {
        $result =  $this->fetchAll(function (Select $select) use($apartmentId) {
           $select->columns([]);
            $select->join(
                ['bfi' => DbTables::TBL_BUILDING_FACILITY_ITEMS],
                new Expression($this->getTable() . '.building_id = bfi.building_id'),
                ['facility_id']
            );

            $select->where([$this->getTable() . '.id' => $apartmentId]);

        });

        $resultArray = [];
        foreach($result as $row) {
            $resultArray[] = $row['facility_id'];
        }
       return $resultArray;
    }
}
