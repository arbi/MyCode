<?php

namespace DDD\Dao\Apartment;

use Library\Constants\Objects;
use Library\DbManager\TableGatewayManager;
use Library\Constants\DbTables;
use Library\Utility\Debug;
use Zend\Db\Sql\Select;
use Zend\Db\Sql\Where;
use Zend\ServiceManager\ServiceLocatorInterface;
use Library\Utility\Helper;
use Zend\Db\Sql\Expression;
use DDD\Service\Apartment\Rate as RateService;

/**
 * DAO class for apartment inventory
 * @author Tigran Petrosyan
 */
class Rate extends TableGatewayManager
{
	/**
	 * @access protected
	 * @var string
	 */
    protected $table = DbTables::TBL_APARTMENT_RATES;

    /**
     * @access public
     * @param ServiceLocatorInterface $sm
     * @param string $domain
     */
    public function __construct($sm, $domain = 'DDD\Domain\Apartment\Rate\Select'){
        parent::__construct($sm, $domain);
    }

    /**
     * Get apartment rates
     * @access public
     *
     * @param int $apartmentId
     * @return \DDD\Domain\Apartment\Rate\Select[]|\ArrayObject|null
     * @author Tigran Petrosyan
     */
    public function getApartmentRates($apartmentId){
    	$result = $this->fetchAll(
    		function (Select $select) use($apartmentId) {

    			$columns = [
    				'id',
    				'name',
                    'type',
                    'week_percent',
                    'weekend_percent',
                    'active'
    			];

            	$select	->columns($columns);

            	$where = new Where();
            	$where->equalTo($this->table . '.apartment_id', $apartmentId);
            	$select->where($where);
            });
        return $result->buffer();
    }

    /**
     * Get apartment master rate ID by room id
     * @access public
     * @param int $roomID Room ID
     * @return int
     */
    public function getApartmentMasterRateIdByRoomId($roomID)
    {
        $this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());

    	$result = $this->fetchOne(
    		function (Select $select) use($roomID) {
    			$columns = array(
    				'id' => 'id'
    			);

    			$select	->columns($columns);

    			$where = new Where();
    			$where->equalTo($this->table . '.room_id', $roomID);
    			$where->equalTo($this->table . '.type', RateService::TYPE1);
    			$where->equalTo($this->table . '.active', 1);
    			$select->where($where);
    		}
    	);

    	return (int)$result['id'];
    }

    public function getRateDetails($rateID)
    {
    	$this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());

    	$result = $this->fetchOne(
    		function (Select $select) use($rateID) {
    			$columns = [
    				'id'                    => 'id',
    				'rate_name'             => 'name',
    				'active'                => 'active',
    				'default_availability'  => 'default_availability',
    				'capacity'              => 'capacity',
    				'weekday_price'         => 'week_price',
    				'current_week_price'    => 'week_price',
    				'weekend_price'         => 'weekend_price',
    				'current_weekend_price' => 'weekend_price',
    				'min_stay'              => 'min_stay',
    				'max_stay'              => 'max_stay',
    				'release_window_start'  => 'release_period_start',
    				'release_window_end'    => 'release_period_end',
    				'is_refundable'         => 'is_refundable',
    				'penalty_type'          => 'penalty_type',
    				'refundable_before_hours' => 'refundable_before_hours',
    				'penalty_percent'       => 'penalty_percent',
    				'penalty_fixed_amount'  => 'penalty_fixed_amount',
    				'penalty_nights'        => 'penalty_nights',
    				'type'                  => 'type',
    				'week_percent'          => 'week_percent',
    				'weekend_percent'       => 'weekend_percent',
    			];

    			$select	->columns($columns);

    			$where = new Where();
    			$where->equalTo($this->table . '.id', $rateID);
    			$select->where($where);
    		}
    	);

    	return $result;
    }

    /**
     * Activate or deactivate given rate
     * @param int $rateID
     * @param int $status
     * @return boolean
     */
    public function changeRateStatus($rateID, $status){
    	$set = array(
    		'active' => $status
    	);
    	$where = array(
    		'id' => $rateID
    	);
    	$result = $this->update($set, $where);

    	return $result;
    }

    /**
     * @param int $rateId
     * @return array|\ArrayObject|null
     */
    public function checkRateAvailabilityApartmentStatus($rateId)
    {
        return $this->fetchOne(function (Select $select) use ($rateId) {
            $select->columns(['id']);
            $select->join(
                ['apartment' => DbTables::TBL_APARTMENTS],
                $this->getTable() . '.apartment_id = apartment.id',
                [],
                Select::JOIN_INNER
            );
            $select->where->equalTo($this->getTable() . '.id', $rateId)
                          ->isNotNull($this->getTable() . '.cubilis_id')
                          ->in('apartment.status', [Objects::PRODUCT_STATUS_LIVEANDSELLIG,
                                                    Objects::PRODUCT_STATUS_SELLINGNOTSEARCHABLE])
            ;

        });
    }
    /**
     * @param int $rateId
     * @return array|\ArrayObject|null
     */
    public function getRatesPolicyData($rateIds)
    {
        $this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());
        return $this->fetchAll(function (Select $select) use ($rateIds) {
            $select->columns([
                                'is_refundable',
                                'refundable_before_hours',
                                'penalty_percent',
                                'penalty_nights',
                                'penalty_fixed_amount',
                                'penalty_type'
                            ]);
            $select->where->in('id', $rateIds);
        });
    }

    public function getRatesPriceByDate($apartmentId, $dates)
    {
        $this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());
        return $this->fetchAll(function (Select $select) use ($apartmentId, $dates) {
            $select->columns([
                    'id' => 'id',
                    'rate_name' => 'name'
            ]);
            $select->join(
                ['inventory' => DbTables::TBL_APARTMENT_INVENTORY],
                $this->getTable() . '.id = inventory.rate_id',
                [
                    'price',
                    'date'
                ],
                Select::JOIN_INNER
            );
            $select->where
                   ->equalTo($this->getTable() . '.apartment_id', $apartmentId)
                   ->equalTo($this->getTable() . '.active', 1);
            if (!empty($dates)) {
                $select->where->in('inventory.date', $dates);
            }
            $select->order(['inventory.date ASC', $this->getTable() . '.id ASC']);
        });
    }

    public function getApartmentParentRate($apartmentId)
    {
        $this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());
        return $this->fetchOne(function (Select $select) use ($apartmentId) {
            $select->columns([
                'id'
            ]);
            $select->where->equalTo('apartment_id', $apartmentId)
                          ->equalTo('type', RateService::TYPE1);
        });
    }

    public function getSameRateType($apartmentId, $rateType, $rateCapacity, $date)
    {
        $this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());
        return $this->fetchOne(function (Select $select) use ($apartmentId, $rateType, $rateCapacity, $date) {
            $select->columns([
                'id',
            ]);
            $select->join(
                ['inventory' => DbTables::TBL_APARTMENT_INVENTORY],
                $this->getTable() . '.id = inventory.rate_id',
                [
                    'has_inventory' => 'id'
                ],
                Select::JOIN_LEFT
            );
            $select->where->equalTo($this->getTable() . '.apartment_id', $apartmentId)
                          ->equalTo($this->getTable() . '.type', $rateType)
                          ->equalTo($this->getTable() . '.active', 1)
                          ->equalTo('inventory.date', $date)
                          ->equalTo($this->getTable() . '.capacity', $rateCapacity)
            ;
            $select->order(['inventory.price ASC', $this->getTable() . '.capacity ASC']);
        });
    }

    /**
     * @param $rateId
     * @return array|\ArrayObject|null
     */
    public function checkRateExistAndActive($rateId)
    {
        $this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());
        return $this->fetchOne(function (Select $select) use ($rateId) {
            $select->columns([
                'id'
            ]);
            $select->where->equalTo($this->getTable() . '.id', $rateId)
                          ->equalTo($this->getTable() . '.active', 1);
        });
    }

    /**
     * @param $date
     * @param $apartmentId
     * @return array|\ArrayObject|null
     */
    public function getParentRateWithInventoryData($date, $apartmentId)
    {
        $this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());
        return $this->fetchOne(function (Select $select) use ($date, $apartmentId) {
            $select->columns([
                'rate_id' => 'id',
                'rate_name' => 'name',
                'capacity' => 'capacity',
                'active' => 'active',
            ]);

            $select->join(
                ['inventory' => DbTables::TBL_APARTMENT_INVENTORY],
                $this->getTable() . '.id = inventory.rate_id',
                [
                    'price',
                    'date',
                    'room_id',
                    'apartment_id',
                    'availability',
                ]
            );

            $select->where->equalTo($this->getTable() . '.apartment_id', $apartmentId)
                          ->equalTo($this->getTable() . '.type', RateService::TYPE1)
                          ->equalTo('inventory.date', $date);
        });
    }

    /**
     * @param $date
     * @param $apartmentId
     * @param $isRefundable
     * @param $capacity
     * @return array|\ArrayObject|null
     */
    public function getSamePolicySameCapacityRate($date, $apartmentId, $isRefundable, $capacity)
    {
        $this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());
        return $this->fetchOne(function (Select $select) use ($apartmentId, $isRefundable, $capacity, $date) {
            $select->columns([
                'rate_id' => 'id',
                'rate_name' => 'name',
                'capacity' => 'capacity',
                'active' => 'active',
            ]);

            $select->join(
                ['inventory' => DbTables::TBL_APARTMENT_INVENTORY],
                $this->getTable() . '.id = inventory.rate_id',
                [
                    'price',
                    'date',
                    'room_id',
                    'apartment_id',
                    'availability',
                ]
            );

            $select->where->equalTo($this->getTable() . '.apartment_id', $apartmentId)
                          ->equalTo($this->getTable() . '.is_refundable', $isRefundable)
                          ->greaterThanOrEqualTo($this->getTable() . '.capacity', $capacity)
                          ->equalTo('inventory.date', $date)
                          ->equalTo($this->getTable() . '.active', 1)
            ;
        });
    }

    /**
     * @param $apartmentId
     * @return array|\ArrayObject|null
     */
    public function getApartmentParentRatePrices($apartmentId)
    {
        $this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());
        return $this->fetchOne(function (Select $select) use ($apartmentId) {
            $select->columns([
                'id',
                'week_price',
                'weekend_price'
            ]);
            $select->where->equalTo('apartment_id', $apartmentId)
                ->equalTo('type', RateService::TYPE1);
        });
    }

    /**
     * @param $rateId
     * @return array|\ArrayObject|null
     */
    public function getRatePrices($rateId)
    {
        $this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());
        return $this->fetchOne(function (Select $select) use ($rateId) {
            $select->columns([
                'week_price',
                'weekend_price'
            ]);
            $select->where->equalTo('id', $rateId);
        });
    }

    /**
     * @param $apartmentId
     * @return $this
     */
    public function getApartmentRatesWithoutParent($apartmentId)
    {
        $this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());
        return $this->fetchAll(function (Select $select) use($apartmentId) {
            $columns = [
                'id',
                'week_percent',
                'weekend_percent',
            ];

            $select	->columns($columns);
            $select->where->equalTo($this->table . '.apartment_id', $apartmentId)
                              ->notEqualTo($this->table . '.type', RateService::TYPE1);
        });
    }

    /**
     * @param $apartmentId
     * @param $weekPrice
     * @param $weekendPrice
     */
    public function updateChildRatePrice ($apartmentId, $weekPrice, $weekendPrice)
    {
        $parentRateType = RateService::TYPE1;
        $sql = "UPDATE {$this->getTable()}
                SET week_price = ROUND(({$weekPrice} + week_percent*{$weekPrice}/100), 2), weekend_price = ROUND(({$weekendPrice} + weekend_percent*{$weekendPrice}/100), 2)
                WHERE apartment_id = {$apartmentId} AND `type` != {$parentRateType}";
        $statement = $this->adapter->driver->createStatement($sql);
        $statement->execute();
    }


    public function checkDuplicateRateName($apartmentId, $rateId, $rateName)
    {
        $rateName = str_replace(' ', '', $rateName);
        $this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());
        $result = $this->fetchAll(
            function (Select $select) use($apartmentId, $rateId, $rateName) {

                $select ->columns([
                    'id',
                ]);

                if ((int)$rateId) {
                    $select->where->notEqualTo($this->getTable() . '.id', $rateId);
                }

                $select->where->expression('REPLACE('.$this->getTable() . '.name, " ", "") = "'.$rateName . '"', []);
                $select->where->equalTo($this->getTable() . '.apartment_id', $apartmentId);
            }
        );
        return $result;
    }

    /**
     * @param $apartmentId
     * @return \Zend\Db\ResultSet\ResultSet
     */
    public function getRatesByApartmentId($apartmentId)
    {
        $previousEntity = $this->getEntity();
        $this->setEntity(new \DDD\Domain\Apartment\ProductRate\CubilisRoomRate());

        $result = $this->fetchAll(
            function (Select $select) use($apartmentId) {
                $select->columns([
                    'rate_id' => 'id',
                    'apartment_id',
                    'room_id',
                    'rate_name' => 'name',
                    'cubilis_rate_id' => 'cubilis_id'
                ]);

                $select->join(
                    ['rooms' => DbTables::TBL_PRODUCT_TYPES],
                    $this->getTable() . '.room_id = rooms.id',
                    [
                        'room_id' => 'id',
                        'cubilis_room_id' => 'cubilis_id',
                    ],
                    Select::JOIN_LEFT
                );

                $select->where
                    ->equalTo($this->getTable() . '.apartment_id', $apartmentId)
                    ->equalTo($this->getTable() . '.active', 1);
            }
        );

        $this->setEntity($previousEntity);
        return $result;
    }

    /**
     * @param $apartmentId
     * @return array|\ArrayObject|null
     */
    public function getMasterRateByApartmentId($apartmentId)
    {
        $previousEntity = $this->getEntity();
        $this->setEntity(new \ArrayObject());

        $result = $this->fetchOne(
            function (Select $select) use($apartmentId) {
                $select->columns([
                    'id',
                ]);

                $select->where([
                    'apartment_id' => $apartmentId,
                    'type'         => RateService::TYPE1
                ]);
            }
        );

        $this->setEntity($previousEntity);
        return $result;
    }

    /**
     * @param int $rateId
     * @return Penalty|null
     */
    public function getRateById($rateId)
    {
        $previousEntity = $this->getEntity();
        $this->setEntity(new \DDD\Domain\Apartment\ProductRate\Penalty());

        $result = $this->fetchOne(
            function (Select $select) use($rateId) {
                $select->columns([
                    'penalty_percent',
                    'penalty_nights',
                    'penalty_fixed_amount',
                    'penalty_type',
                    'is_refundable',
                    'refundable_before_hours',
                    'rate_active' => 'active'
                ]);
                $select->where(['id' => $rateId]);
            }
        );

        $this->setEntity($previousEntity);
        return $result;
    }

    /**
     * @param int $rateId
     * @return Cubilis|null
     */
    public function getCubilisIdByRateId($rateId)
    {
        $previousEntity = $this->getEntity();
        $this->setEntity(new \DDD\Domain\Apartment\Room\Cubilis());

        $result = $this->fetchOne(
            function (Select $select) use($rateId) {
                $select->columns(['cubilis_id']);
                $select->where(['id' => $rateId]);
            }
        );

        $this->setEntity($previousEntity);
        return $result;
    }

    /**
     * @param int $rateId
     * @return CubilisRoomRate|null
     */
    public function getRoomByRateId($rateId)
    {
        $previousEntity = $this->getEntity();
        $this->setEntity(new \DDD\Domain\Apartment\ProductRate\CubilisRoomRate());

        $result = $this->fetchOne(
            function (Select $select) use($rateId) {
                $select->columns([
                    'rate_active' => 'active',
                    'room_id' => 'room_id',
                ]);

                $select->where([$this->getTable() . '.id' => $rateId]);
            }
        );

        $this->setEntity($previousEntity);
        return $result;
    }

    /**
     * @param int|null $apartmentId
     * @return WithStatus[]|\ArrayObject|null
     *
     * @todo refactor this method: change name, complex where part, constants
     */
    public function getAllActiveRatesByApartmentId($apartmentId)
    {
        $previousEntity = $this->getEntity();
        $this->setEntity(new \DDD\Domain\Apartment\ProductRate\WithStatus());

        $result = $this->fetchAll(
            function (Select $select) use($apartmentId) {
                $select->columns([
                    'id',
                    'room_id',
                    'apartment_id',
                    'default_availability',
                    'week_price',
                    'weekend_price',
                    'type',
                    'cubilis_id',
                    'name',
                ]);
                $select->join(
                    DbTables::TBL_APARTMENTS,
                    DbTables::TBL_APARTMENTS . '.id = ' . $this->getTable() . '.apartment_id',
                    ['status']
                );

                is_null($apartmentId)
                    ? $select->where
                    ->equalTo(DbTables::TBL_APARTMENTS . '.status', 5)
                    ->or
                    ->equalTo(DbTables::TBL_APARTMENTS . '.status', 10)
                    : $select->where([
                    $this->getTable() . '.apartment_id' => $apartmentId
                ]);

            }
        );

        $this->setEntity($previousEntity);
        return $result->buffer();
    }

    /**
     * @param int|null $rateId
     * @return WithStatus[]|\ArrayObject|null
     */
    public function getAllActiveRatesByRateId($rateId)
    {
        $previousEntity = $this->getEntity();
        $this->setEntity(new \DDD\Domain\Apartment\ProductRate\WithStatus());

        $result = $this->fetchAll(
            function (Select $select) use($rateId) {
                $select->columns([
                    'id',
                    'room_id',
                    'apartment_id',
                    'default_availability',
                    'week_price',
                    'weekend_price',
                ]);

                is_null($rateId) ?: $select->where([
                    'id' => $rateId
                ]);
            }
        );

        $this->setEntity($previousEntity);
        return $result->buffer();
    }

    /**
     * @param int $rateId
     * @param int $cubilisRateId
     * @return int
     */
    public function updateCubilisLink($rateId, $cubilisRateId)
    {
        return $this->update(['cubilis_id' => $cubilisRateId], ['id' => $rateId]);
    }

    /**
     * @param $roomId
     * @return int
     */
    public function clearCubilisLinks($roomId)
    {
        return $this->update(['cubilis_id' => null], ['room_id' => $roomId]);
    }
}
