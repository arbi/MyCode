<?php

namespace DDD\Dao\Apartel;

use Library\DbManager\TableGatewayManager;
use Library\Constants\DbTables;
use DDD\Service\Apartment\Rate as ApartmentRateService;
use Zend\Db\Sql\Select;
use Zend\ServiceManager\ServiceLocatorInterface;

class Rate extends TableGatewayManager
{
	/**
	 * @var string
	 */
    protected $table = DbTables::TBL_APARTEL_RATES;

    /**
     * @param ServiceLocatorInterface $sm
     * @param string $domain
     */
    public function __construct($sm, $domain = '\DDD\Domain\Apartel\Rate\Rate')
    {
        parent::__construct($sm, $domain);
    }

    /**
     * @param $apartelId
     * @return \Zend\Db\ResultSet\ResultSet
     */
    public function getAllRatesByApartelId($apartelId)
    {
        $this->resultSetPrototype->setArrayObjectPrototype(new \DDD\Domain\Apartel\Rate\Rate());
        return $this->fetchAll(
            function (Select $select) use($apartelId) {
                $select->columns([
                    'id',
                    'apartel_type_id',
                    'apartel_id',
                    'week_price',
                    'weekend_price',
                    'type',
                    'name',
                    'cubilis_id',
                ]);

                if ($apartelId) {
                    $select->where->equalTo($this->getTable() . '.apartel_id', $apartelId);
                }
            }
        );
    }

    /**
     * @param $rateId
     * @return array|\ArrayObject|null
     */
    public function getRateDetails($rateId)
    {
        $this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());
        $result = $this->fetchOne(
            function (Select $select) use($rateId) {
                $columns = [
                    'rate_id'               => 'id',
                    'rate_name'             => 'name',
                    'active'                => 'active',
                    'capacity'              => 'capacity',
                    'week_price'            => 'week_price',
                    'weekend_price'         => 'weekend_price',
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
                $select->where->equalTo('id', $rateId);
            }
        );

        return $result;
    }

    /**
     * @param $typeId
     * @param $date
     * @return int
     */
    public function getAvailabilityByTypeIdDate($typeId, $date)
    {
        $this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());
        $result = $this->fetchOne( function (Select $select) use($typeId, $date) {
                $select->columns([]);

                $select->join(
                    ['inventory' => DbTables::TBL_APARTEL_INVENTORY],
                    $this->getTable() . '.id = inventory.rate_id',
                    [
                        'availability'
                   ]
                );

                $select->where
                       ->equalTo($this->getTable() . '.apartel_type_id', $typeId)
                       ->equalTo($this->getTable() . '.type', ApartmentRateService::TYPE1)
                       ->equalTo('inventory.date', $date)
                ;
            }
        );

        return $result ? $result['availability'] : 0;
    }

    /**
     * @param $typeId
     * @return \Zend\Db\ResultSet\ResultSet
     */
    public function getAllAvailabilityByTypeId($typeId)
    {
        $this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());
        return $result = $this->fetchAll( function (Select $select) use($typeId) {
                $select->columns([]);
                $select->join(
                    ['inventory' => DbTables::TBL_APARTEL_INVENTORY],
                    $this->getTable() . '.id = inventory.rate_id',
                    [
                        'availability',
                        'date',
                   ]
                );

                $select->where
                       ->equalTo($this->getTable() . '.apartel_type_id', $typeId)
                       ->equalTo($this->getTable() . '.type', ApartmentRateService::TYPE1)
                ;
            }
        );
    }

    /**
     * @param $rateIds
     * @return \Zend\Db\ResultSet\ResultSet
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

    /**
     * @param $typeId
     * @return array
     */
    public function getApartelParentRatePrices($typeId)
    {
        $this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());
        $result = $this->fetchOne(function (Select $select) use ($typeId) {
            $select->columns([
                'week_price',
                'weekend_price',
            ]);
            $select->where->equalTo($this->getTable() . '.type', ApartmentRateService::TYPE1)
                          ->equalTo($this->getTable() . '.apartel_type_id', $typeId);
        });

        $weekPrice = $weekendPrice = 0;
        if ($result) {
            $weekPrice = $result['week_price'];
            $weekendPrice = $result['weekend_price'];
        }

        return [
            'week_price' => $weekPrice,
            'weekend_price' => $weekendPrice,
        ];
    }

    /**
     * @param $typeId
     * @param $weekPrice
     * @param $weekendPrice
     */
    public function updateChildRatePrice ($typeId, $weekPrice, $weekendPrice)
    {
        $parentRateType = ApartmentRateService::TYPE1;
        $sql = "UPDATE {$this->getTable()}
                SET week_price = ROUND(({$weekPrice} + week_percent*{$weekPrice}/100), 2), weekend_price = ROUND(({$weekendPrice} + weekend_percent*{$weekendPrice}/100), 2)
                WHERE apartel_type_id = {$typeId} AND `type` != {$parentRateType}";
        $statement = $this->adapter->driver->createStatement($sql);
        $statement->execute();
    }

    /**
     * @param $date
     * @param $roomTypeId
     * @param $isRefundable
     * @param $capacity
     * @return array|\ArrayObject|null
     */
    public function getSamePolicySameCapacityRate($date, $roomTypeId, $isRefundable, $capacity)
    {
        $this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());
        return $this->fetchOne(function (Select $select) use ($roomTypeId, $isRefundable, $capacity, $date) {
            $select->columns([
                'rate_id' => 'id',
                'rate_name' => 'name',
                'capacity' => 'capacity',
                'active' => 'active',
            ]);

            $select->join(
                ['inventory' => DbTables::TBL_APARTEL_INVENTORY],
                $this->getTable() . '.id = inventory.rate_id',
                [
                    'price',
                    'date',
                    'room_id' => 'apartel_type_id',
                    'apartel_id',
                    'availability',
                ]
            );

            $select->where->equalTo($this->getTable() . '.apartel_type_id', $roomTypeId)
                ->equalTo($this->getTable() . '.is_refundable', $isRefundable)
                ->greaterThanOrEqualTo($this->getTable() . '.capacity', $capacity)
                ->equalTo('inventory.date', $date)
                ->equalTo($this->getTable() . '.active', 1)
            ;
        });
    }

    /**
     * @param $date
     * @param $roomTypeId
     * @return array|\ArrayObject|null
     */
    public function getParentRateWithInventoryData($date, $roomTypeId)
    {
        $this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());
        return $this->fetchOne(function (Select $select) use ($date, $roomTypeId) {
            $select->columns([
                'rate_id' => 'id',
                'rate_name' => 'name',
                'capacity' => 'capacity',
                'active' => 'active',
            ]);

            $select->join(
                ['inventory' => DbTables::TBL_APARTEL_INVENTORY],
                $this->getTable() . '.id = inventory.rate_id',
                [
                    'price',
                    'date',
                    'room_id',
                    'apartel_id',
                    'availability',
                ]
            );

            $select->where->equalTo($this->getTable() . '.apartel_type_id', $roomTypeId)
                ->equalTo($this->getTable() . '.type', ApartmentRateService::TYPE1)
                ->equalTo('inventory.date', $date);
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
     * @param $roomTypeId
     * @return array|\ArrayObject|null
     */
    public function getRoomTypeParentRate($roomTypeId)
    {
        $this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());
        return $this->fetchOne(function (Select $select) use ($roomTypeId) {
            $select->columns([
                'id'
            ]);
            $select->where->equalTo('apartel_type_id', $roomTypeId)
                ->equalTo('type', ApartmentRateService::TYPE1);
        });
    }

    /**
     * @param $roomTypeId
     * @return $this
     */
    public function getCubilisSyncApartelRates($roomTypeId)
    {
        $this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());

        $result = $this->fetchAll(
            function (Select $select) use($roomTypeId) {

                $select	->columns([
                    'id',
                    'name',
                ]);

                $select->join(
                    ['roomType' => DbTables::TBL_APARTEL_TYPE],
                    $this->getTable() . '.apartel_type_id = roomType.id',
                    [],
                    Select::JOIN_LEFT
                );

                $select->join(
                    ['apartel' => DbTables::TBL_APARTELS],
                    $this->getTable() . '.apartel_id = apartel.id',
                    [],
                    Select::JOIN_LEFT
                );

                $select->where
                    ->equalTo($this->getTable() . '.apartel_type_id', $roomTypeId)
                    ->notEqualTo($this->getTable() . '.active', 0)
                    ->isNotNull($this->getTable() . '.cubilis_id')
                    ->isNotNull('roomType.cubilis_id')
                    ->notEqualTo('apartel.sync_cubilis', 0);
            });

        return $result->buffer();
    }

    /**
     * @param $typeId
     * @return \Zend\Db\ResultSet\ResultSet
     */
    public function getRoomTypeRates($typeId)
    {
        $this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());
        return $result = $this->fetchAll( function (Select $select) use($typeId) {
            $select->columns([
                'id',
                'name',
                'type',
                'week_percent',
                'weekend_percent'
            ]);
            $select->where
                ->equalTo($this->getTable() . '.apartel_type_id', $typeId);

        }
        );
    }


    /**
     * @param $roomTypeId
     * @return $this
     */
    public function getRoomTypeRatesWithoutParent($roomTypeId)
    {
        $this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());
        return $this->fetchAll(function (Select $select) use($roomTypeId) {
            $columns = [
                'id',
                'week_percent',
                'weekend_percent',
            ];

            $select	->columns($columns);
            $select->where->equalTo('apartel_type_id', $roomTypeId)
                ->notEqualTo('type', ApartmentRateService::TYPE1);
        });
    }

    /**
     * @param $roomTypeId
     * @param $dates
     * @return \Zend\Db\ResultSet\ResultSet
     */
    public function getRatesPriceByDate($roomTypeId, $dates)
    {
        $this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());
        return $this->fetchAll(function (Select $select) use ($roomTypeId, $dates) {
            $select->columns([
                'id' => 'id',
                'rate_name' => 'name'
            ]);
            $select->join(
                ['inventory' => DbTables::TBL_APARTEL_INVENTORY],
                $this->getTable() . '.id = inventory.rate_id',
                [
                    'price',
                    'date'
                ],
                Select::JOIN_INNER
            );
            $select->where
                ->equalTo($this->getTable() . '.apartel_type_id', $roomTypeId)
                ->equalTo($this->getTable() . '.active', 1);
            if (!empty($dates)) {
                $select->where->in('inventory.date', $dates);
            }
            $select->order(['inventory.date ASC', $this->getTable() . '.id ASC']);
        });
    }
}
