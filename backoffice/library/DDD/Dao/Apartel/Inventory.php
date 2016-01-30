<?php

namespace DDD\Dao\Apartel;

use DDD\Service\Availability;
use Library\Constants\Objects;
use Library\DbManager\TableGatewayManager;
use Library\Constants\DbTables;
use Library\ChannelManager\ChannelManager as libChannelManager;
use Library\Utility\Debug;
use Zend\Db\Sql\Expression;
use Zend\Db\Sql\Select;
use Zend\Db\Sql\Where;
use Zend\ServiceManager\ServiceLocatorInterface;
use DDD\Service\Apartment\Rate as RateService;
USE DDD\Service\Apartel\Inventory as InventoryService;
class Inventory extends TableGatewayManager
{
	/**
	 * @var string
	 */
    protected $table = DbTables::TBL_APARTEL_INVENTORY;
    protected $sm = false;
    /**
     * @param ServiceLocatorInterface $sm
     * @param string $domain
     */
    public function __construct($sm, $domain = '\DDD\Domain\Apartel\Inventory\Inventory')
    {
        $this->sm = $sm;
        parent::__construct($sm, $domain);
    }

    /**
     * @param $itemId
     * @param null $from
     * @param null $to
     * @param string $type
     * @return \Zend\Db\ResultSet\ResultSet
     */
    public function getRateAvailabilityByApartelId($itemId, $from = null, $to = null, $type = 'product')
    {
        $this->resultSetPrototype->setArrayObjectPrototype(new \DDD\Domain\Apartel\Inventory\InventoryForSync());
        if (is_null($from)) {
            $from = date('Y-m-d');
        }

        return $this->fetchAll(
            function (Select $select) use($itemId, $from, $to, $type) {
                $select->columns([
                    'price',
                    'date',
                    'availability',
                    'product_id' => 'apartel_id',
                ]);

                $select->join(
                    ['rates' => DbTables::TBL_APARTEL_RATES],
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
                    ['types' => DbTables::TBL_APARTEL_TYPE],
                    $this->getTable() . '.apartel_type_id = types.id',
                    [
                        'cubilis_type_id' => 'cubilis_id',
                    ],
                    Select::JOIN_LEFT
                );

                if ($type == libChannelManager::SYNC_WITH_TYPE) {
                    $select
                        ->where
                        ->equalTo($this->getTable() . '.apartel_type_id', $itemId);
                } elseif ($type == libChannelManager::SYNC_WITH_RATE) {
                    $select
                        ->where
                        ->equalTo($this->getTable() . '.rate_id', $itemId);
                } else {
                    $select
                        ->where
                        ->equalTo($this->getTable() . '.apartel_id', $itemId);
                }

                $select->where
                        ->greaterThanOrEqualTo($this->getTable() . '.date', $from)
                        ->isNotNull('types.cubilis_id')
                        ->isNotNull('rates.cubilis_id');

                if (!is_null($to)) {
                    $select
                        ->where
                        ->lessThanOrEqualTo($this->getTable() . '.date', $to);
                }
            }
        );
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

    /**
     * @param $cubilisRateIdDates
     * @param $roomTypeId
     * @return \Zend\Db\ResultSet\ResultSetInterface
     */
    public function getRateByCubilisRateIdDates($cubilisRateIdDates, $roomTypeId)
    {
        $sql = "SELECT
                    ar.id as rate_id,
                    ar.name as rate_name,
                    ar.capacity,
                    ai.price,
                    ai.date,
                    ai.apartel_type_id as room_type_id,
                    ai.apartel_id,
                    ai.availability,
                    ar.active
                FROM
                    " . DbTables::TBL_APARTEL_INVENTORY . " AS ai
                    INNER JOIN " . DbTables::TBL_APARTEL_RATES . " AS ar ON ai.rate_id = ar.id
                WHERE
                    ar.apartel_type_id = {$roomTypeId}
                    AND (ar.cubilis_id, ai.`date`) IN ({$cubilisRateIdDates})
                GROUP BY ai.id
                ORDER BY ai.availability ASC, ar.id ASC, ai.date ASC"; // AND ai.availability = 1
        $statement = $this->adapter->createStatement($sql);
        $result = $statement->execute();

        $this->setEntity(new \ArrayObject());
        $resultSet = clone $this->resultSetPrototype;
        $resultSet->initialize($result);
        return $resultSet;
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
                    'room_type_id' => 'apartel_type_id',
                    'apartel_id',
                    'availability',
                ]);
                $select->join(
                    ['rate' => DbTables::TBL_APARTEL_RATES],
                    $this->getTable() . '.rate_id = rate.id',
                    [
                        'rate_id' => 'id',
                        'rate_name' => 'name',
                        'capacity',
                        'active',
                    ]
                );
                $select->where
                    ->equalTo($this->getTable() . '.apartel_type_id', $roomTypeId)
                    ->greaterThanOrEqualTo($this->getTable() . '.date', $dates['date_from'])
                    ->lessThan($this->getTable() . '.date', $dates['date_to'])
                    ->equalTo('rate.type', RateService::TYPE1)
                ;
            }
        );
    }

    /**
     * @param $roomTypeId
     * @param $dateFrom
     * @param $dateTo
     * @return \Zend\Db\ResultSet\ResultSet
     */
    public function getAllRateForSyncByTypeDateRange($roomTypeId, $dateFrom, $dateTo) {
        $this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());
        return $this->fetchAll(
            function (Select $select) use($roomTypeId, $dateFrom, $dateTo) {
                $select->columns([
                    'date' => 'date',
                    'availability' => 'availability',
                    'price' => 'price',
                ]);
                $select->join(
                    ['rates' => DbTables::TBL_APARTEL_RATES],
                    $this->getTable() . '.rate_id = rates.id',
                    ['cubilis_rate_id' => 'cubilis_id']
                );
                $select->join(
                    ['types' => DbTables::TBL_APARTEL_TYPE],
                    $this->getTable() . '.apartel_type_id = types.id',
                    ['cubilis_room_id' => 'cubilis_id']
                );
                $select->where
                    ->isNotNull('rates.cubilis_id')
                    ->isNotNull('types.cubilis_id')
                    ->equalTo($this->getTable() . '.apartel_type_id', $roomTypeId)
                    ->greaterThanOrEqualTo($this->getTable() . '.date', $dateFrom)
                    ->lessThan($this->getTable() . '.date', $dateTo);
            }
        );
    }

    /**
     * @param $rateId
     * @param $date
     * @return array|\ArrayObject|null
     */
    public function getPriceByRateIdDate($rateId, $date)
    {
        $this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());

        return $this->fetchOne(
            function (Select $select) use($rateId, $date) {
                $select->columns([
                    'price',
                ]);

                $select->where
                    ->equalTo($this->getTable() . '.rate_id', $rateId)
                    ->equalTo($this->getTable() . '.date', $date);
            }
        );
    }

    /**
     * @param $apartmentList
     * @param $typeId
     * @return \Zend\Db\Adapter\Driver\ResultInterface
     */
    public function setApartelAvailabilityByApartmentList($typeId, $apartmentList)
    {
        $standardRate = RateService::TYPE1;
        if ($apartmentList) {
            $apartelService = $this->sm->get('service_apartel_general');
            $defaultAvailability = $apartelService->getDefaultAvailabilityByTypeId($typeId);

            $setQuery = " apartmentAll.all_availability ";
            $rand     = rand(InventoryService::MIN_AVAILABILITY_LIMIT, InventoryService::MAX_AVAILABILITY_LIMIT);

            if ($defaultAvailability) {
                $setQuery = "(CASE WHEN apartmentAll.all_availability > " . InventoryService::AVAILABILITY_LIMIT .
                    " THEN " . $rand . " ELSE apartmentAll.all_availability END) ";
            }

            $sql = "UPDATE ga_apartel_inventory AS apartel
                        JOIN
                    (SELECT
                        SUM(availability) AS all_availability,
                            ga_apartment_inventory.date AS date
                    FROM
                        ga_apartment_inventory
                    INNER JOIN ga_apartment_rates AS rates ON ga_apartment_inventory.rate_id = rates.id
                    WHERE
                        ga_apartment_inventory.apartment_id IN ({$apartmentList})
                            AND rates.type = {$standardRate}
                    GROUP BY ga_apartment_inventory.date) AS apartmentAll ON apartel.date = apartmentAll.date
                SET
                    apartel.availability = " . $setQuery . " where apartel.apartel_type_id = {$typeId}";

        } else {
            $sql = "UPDATE ga_apartel_inventory SET availability = 0 where apartel_type_id = {$typeId}";
        }

        $statement = $this->adapter->createStatement($sql);
        return $statement->execute();
    }

    /**
     * @param $typeId
     * @param bool $dateFrom
     * @param bool $dateTo
     * @return \Zend\Db\Adapter\Driver\ResultInterface
     */
    public function setApartelAvailabilityByRoomType($typeId, $dateFrom = false, $dateTo = false)
    {
        $apartelService = $this->sm->get('service_apartel_general');
        $defaultAvailability = $apartelService->getDefaultAvailabilityByTypeId($typeId);

        $setQuery = " apartmentAll.all_availability ";
        $rand     = rand(InventoryService::MIN_AVAILABILITY_LIMIT, InventoryService::MAX_AVAILABILITY_LIMIT);

        if ($defaultAvailability) {
            $setQuery = "(CASE WHEN apartmentAll.all_availability > " . InventoryService::AVAILABILITY_LIMIT .
                " THEN " . $rand . " ELSE apartmentAll.all_availability END) ";
        }

        $standardRate = RateService::TYPE1;
        $sql = "UPDATE ga_apartel_inventory AS inventory
                    JOIN
                (SELECT
                    SUM(availability) AS all_availability,
                        ai.date AS date
                FROM
                    ga_apartment_inventory AS ai
                INNER JOIN ga_rel_apartel_type_apartment AS raa ON raa.apartment_id = ai.apartment_id AND raa.apartel_type_id = {$typeId}
                INNER JOIN ga_apartment_rates AS rates ON ai.rate_id = rates.id
                WHERE
                    rates.type = {$standardRate}";

        if ($dateFrom && $dateTo) {
            $sql .= " AND ai.date >= '{$dateFrom}' AND ai.date < '{$dateTo}'";
        }

        $sql .=" GROUP BY ai.date) AS apartmentAll ON inventory.date = apartmentAll.date
            SET
                inventory.availability = " . $setQuery . "
            WHERE
                inventory.apartel_type_id = {$typeId}";

        if ($dateFrom && $dateTo) {
            $sql .= " AND inventory.date >= '{$dateFrom}' AND inventory.date < '{$dateTo}'";
        }

        $statement = $this->adapter->createStatement($sql);
        return $statement->execute();
    }

    /**
     * @return array|\ArrayObject|null
     */
    public function getMinMaxDate()
    {
        $this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());
        return $this->fetchOne(
            function (Select $select) {
                $select->columns([
                    'min_date' => new Expression('min(date)'),
                    'max_date' => new Expression('max(date)'),
                ]);
            }
        );
    }

    /**
     * @param $rateId
     * @param $startDate
     * @param $endDate
     * @return \Zend\Db\ResultSet\ResultSet
     */
    public function getRateInventoryForRange($rateId, $startDate, $endDate)
    {
        $this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());
        $result = $this->fetchAll(
            function (Select $select) use($rateId, $startDate, $endDate) {

                $columns = array(
                    'id',
                    'rate_id',
                    'date',
                    'price',
                    'availability',
                    'is_lock_price',
                );

                $select	->columns($columns);
                $where = new Where();
                $where->equalTo($this->table . '.rate_id', $rateId);
                $where->greaterThanOrEqualTo($this->table.'.date', $startDate);
                $where->lessThanOrEqualTo($this->table.'.date', $endDate);
                $select->where($where);
            }
        );

        return $result;
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
            ->equalTo( DbTables::TBL_APARTEL_INVENTORY. '.rate_id', $rateId)
            ->greaterThanOrEqualTo( DbTables::TBL_APARTEL_INVENTORY . '.date', $dateFrom)
            ->lessThanOrEqualTo(DbTables::TBL_APARTEL_INVENTORY . '.date', $dateTo);

        if (!is_null($weekDays)) {
            $where->expression("weekday(" . DbTables::TBL_APARTEL_INVENTORY . ".date) in ({$weekDays})", []);
        }

        if (!is_null($isChanged)) {
            $where->equalTo(DbTables::TBL_APARTEL_INVENTORY . '.is_changed', $isChanged);
        }

        if (!is_null($isLockPrice)) {
            $where->equalTo(DbTables::TBL_APARTEL_INVENTORY . '.is_lock_price', $isLockPrice);
        }
        return $where;
    }

    /**
     * @param $roomTypeId
     * @param $dateFrom
     * @param $dateTo
     * @param $weekDays
     * @param $forceLockPrice
     * @return \Zend\Db\ResultSet\ResultSet
     */
    public function getRateInventoryData($roomTypeId, $dateFrom, $dateTo, $weekDays, $forceLockPrice)
    {
        $this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());
        return $this->fetchAll(
            function (Select $select) use($roomTypeId, $dateFrom, $dateTo, $weekDays, $forceLockPrice) {
                $select->columns([
                    'id',
                    'price',
                    'date',
                ]);

                $select->where->equalTo('rates.type', RateService::TYPE1);
                $select->where(
                    $this->getWhereForInventoryRange($roomTypeId, $dateFrom, $dateTo, $weekDays, null, $forceLockPrice)
                );
            }
        );
    }

    /**
     * @param $roomTypeId
     * @param $dateFrom
     * @param $dateTo
     * @param $weekDays
     * @param int $isChanged
     * @param int $isLockPrice
     * @return Where
     */
    private function getWhereForInventoryRange($roomTypeId, $dateFrom, $dateTo, $weekDays, $isChanged = 0, $isLockPrice = 0) {
        $where = new Where();

        $where
            ->equalTo( DbTables::TBL_APARTEL_INVENTORY . '.apartel_type_id', $roomTypeId)
            ->greaterThanOrEqualTo( DbTables::TBL_APARTEL_INVENTORY . '.date', $dateFrom)
            ->lessThanOrEqualTo(DbTables::TBL_APARTEL_INVENTORY . '.date', $dateTo);

        if ($weekDays) {
            $where->expression("weekday(" . DbTables::TBL_APARTEL_INVENTORY . ".date) in ({$weekDays})", []);
        }

        if (!is_null($isChanged)) {
            $where->equalTo(DbTables::TBL_APARTEL_INVENTORY . '.is_changed', $isChanged);
        }

        if (!is_null($isLockPrice)) {
            $where->equalTo(DbTables::TBL_APARTEL_INVENTORY . '.is_lock_price', $isLockPrice);
        }
        return $where;
    }

    /**
     * @param $roomTypeId
     * @param $dateFrom
     * @param $dateTo
     * @param $weekDays
     * @param $setLockPrice
     * @param $forceLockPrice
     * @return int
     */
    public function updateLockPriceBit($roomTypeId, $dateFrom, $dateTo, $weekDays, $setLockPrice, $forceLockPrice)
    {
        $where = $this->getWhereForInventoryRange($roomTypeId, $dateFrom, $dateTo, $weekDays, null, $forceLockPrice);
        return $this->update([
            'is_lock_price' => $setLockPrice,
        ], $where);
    }

    /**
     * @param $roomTypeId
     * @param $dateFrom
     * @param $dateTo
     * @param $weekDays
     * @return int
     */
    public function getPriceAVGRange($roomTypeId, $dateFrom, $dateTo, $weekDays)
    {
        $this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());
        $result = $this->fetchOne(
            function (Select $select) use ($roomTypeId, $dateFrom, $dateTo, $weekDays) {
                $select->columns([
                    'price_avg' => new Expression('avg(price)')
                ]);

                $select->join(
                    ['rates' => DbTables::TBL_APARTEL_RATES],
                    $this->getTable() . '.rate_id = rates.id',
                    []
                );

                $select->where
                    ->equalTo($this->getTable() . '.apartel_type_id', $roomTypeId)
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
                    ['rates' => DbTables::TBL_APARTEL_RATES],
                    $this->getTable() . '.rate_id = rates.id',
                    []
                );

                $select->where
                    ->equalTo($this->getTable() . '.apartel_type_id', $apartmentId)
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
