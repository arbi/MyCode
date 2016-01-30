<?php

namespace DDD\Dao\Apartel;

use DDD\Service\Apartment\Rate as ApartmentRate;
use DDD\Service\Apartment\Review;
use Library\Constants\Objects;
use Library\DbManager\TableGatewayManager;
use Library\Constants\DbTables;
use Library\Utility\Debug;
use Zend\Db\Sql\Expression;
use Zend\Db\Sql\Select;
use Zend\ServiceManager\ServiceLocatorInterface;

class RelTypeApartment extends TableGatewayManager
{
	/**
	 * @var string
	 */
    protected $table = DbTables::TBL_APARTEL_REL_TYPE_APARTMENT;

    /**
     * @param ServiceLocatorInterface $sm
     * @param string $domain
     */
    public function __construct($sm, $domain = '\DDD\Domain\Apartel\RelTypeApartment\RelTypeApartment')
    {
        parent::__construct($sm, $domain);
    }

    /**
     * @param $apartelTypeId
     * @return int
     */
    public function getAvailabilityForApartelType($apartelTypeId)
    {
        $this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());
        $result = $this->fetchOne( function (Select $select) use($apartelTypeId) {
                $select->columns([
                    'count' => new Expression('count(*)'),
                ]);

                $select->join(
                    ['apartment' => DbTables::TBL_APARTMENTS],
                    $this->getTable() . '.apartment_id = apartment.id',
                    []
                );

                $select->where->equalTo($this->getTable() . '.apartel_type_id', $apartelTypeId)
                    ->in('apartment.status', [Objects::PRODUCT_STATUS_LIVEANDSELLIG, Objects::PRODUCT_STATUS_SELLINGNOTSEARCHABLE])
                ;
            }
        );

        return $result ? $result['count'] : 0;

    }

    /**
     * @param $apartelTypeId
     * @return array|\ArrayObject|null
     */
    public function getApartmentDetails($apartelTypeId)
    {
        $this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());
        return $this->fetchOne( function (Select $select) use($apartelTypeId) {
                $select->columns([]);
                $select->join(
                    ['apartment' => DbTables::TBL_APARTMENTS],
                    $this->getTable() . '.apartment_id = apartment.id',
                    [
                        'capacity' => new Expression('max(apartment.max_capacity)'),
                        'currency_id',
                    ]
                );
                $select->join(
                    ['currency' => DbTables::TBL_CURRENCY],
                    'currency.id = apartment.currency_id',
                    [
                        'code'
                    ]
                );

                $select->where->equalTo($this->getTable() . '.apartel_type_id', $apartelTypeId);
            }
        );
    }

    /**
     * @param $typeId
     * @param $dates
     * @param int $capacity
     * @param $apartmentListUsed
     * @param $building
     * @return \Zend\Db\ResultSet\ResultSet
     */
    public function getAvailabilityApartmentList($typeId, $dates, $capacity = 1, $apartmentListUsed, $building)
    {
        $this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());
        return $this->fetchAll(function(Select $select) use($typeId, $dates, $capacity, $apartmentListUsed, $building) {
            $select->columns(['apartment_id']);

            $select->join(
                ['apartment' => DbTables::TBL_APARTMENTS],
                new Expression($this->getTable() . '.apartment_id = apartment.id' .
                    ($building ? ' AND apartment.building_id = ' . $building : '' )),
                []
            );

            $select->join(
                ['apartment_inventory' => DbTables::TBL_APARTMENT_INVENTORY],
                $this->getTable() . '.apartment_id = apartment_inventory.apartment_id',
                [
                    'availability'
                ]
            );

            $select->join(
                ['apartment_rate' => DbTables::TBL_APARTMENT_RATES],
                'apartment_inventory.rate_id = apartment_rate.id',
                [
                    'rate_id' => 'id'
                ]
            );

            // apartel is connected to cubilis
            $select->where
                    ->equalTo($this->getTable() . '.apartel_type_id', $typeId)
                    ->equalTo('apartment_rate.type', ApartmentRate::TYPE1)
                    ->greaterThanOrEqualTo('apartment.max_capacity', $capacity)
//                    ->equalTo('apartment_inventory.availability', 1)
                    ->in('apartment.status', [Objects::PRODUCT_STATUS_LIVEANDSELLIG, Objects::PRODUCT_STATUS_SELLINGNOTSEARCHABLE])
                    ->greaterThanOrEqualTo('apartment_inventory.date', $dates['date_from'])
                    ->lessThan('apartment_inventory.date', $dates['date_to']);
            if (!empty($apartmentListUsed)) {
                $select->where->notIn($this->getTable() . '.apartment_id', $apartmentListUsed);
            }
            $select->order('apartment.max_capacity');
        });
    }

    /**
     * @param $apartelTypeId
     * @return array|\ArrayObject|null
     */
    public function getApartmentByTypeId($apartelTypeId)
    {
        $this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());
        return $this->fetchOne( function (Select $select) use($apartelTypeId) {
            $select->columns(['apartment_id']);
            $select->where->equalTo($this->getTable() . '.apartel_type_id', $apartelTypeId);
        });
    }

    /**
     * @param $typeId
     * @param $dateFrom
     * @param $dateTo
     * @return \Zend\Db\ResultSet\ResultSet
     */
    public function getApartelTypeAvailabilityByTypeIdDates($typeId, $dateFrom, $dateTo)
    {
        $this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());
        return $this->fetchAll(function(Select $select) use($typeId, $dateFrom, $dateTo) {
            $select->columns([
                'count_availability' => new Expression('COUNT(id)')
            ]);

            $select->join(
                ['apartment_inventory' => DbTables::TBL_APARTMENT_INVENTORY],
                $this->getTable() . '.apartment_id = apartment_inventory.apartment_id',
                [
                    'date'
                ]
            );

            $select->join(
                ['apartment_rate' => DbTables::TBL_APARTMENT_RATES],
                'apartment_inventory.rate_id = apartment_rate.id',
                []
            );

            $select->where
                ->equalTo($this->getTable() . '.apartel_type_id', $typeId)
                ->equalTo('apartment_rate.type', ApartmentRate::TYPE1)
                ->greaterThanOrEqualTo('apartment_inventory.date', $dateFrom)
                ->lessThan('apartment_inventory.date', $dateTo)
                ->equalTo('apartment_inventory.availability', 1);
            $select->group('apartment_inventory.date');
            $select->order('apartment_inventory.date');
        });
    }

    /**
     * @param $apartmentId
     * @return \Zend\Db\ResultSet\ResultSet
     */
    public function checkFromApartel($apartmentId)
    {
        $this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());
        return $this->fetchAll(function(Select $select) use($apartmentId) {
            $select->columns([
                'apartel_type_id'
            ]);

            $select->where
                   ->equalTo('apartment_id', $apartmentId);
            $select->group('apartel_type_id');
        });
    }

    /**
     * @param $apartmentId
     * @return \Zend\Db\ResultSet\ResultSet
     */
    public function getApartelRoomTypeByApartment($apartmentId)
    {
        $this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());
        return $this->fetchAll(function(Select $select) use($apartmentId) {
            $select->columns([
                'apartel_type_id'
            ]);

            $select->where
                ->equalTo('apartment_id', $apartmentId);
            $select->group('apartel_type_id');
        });
    }

    /**
     * @param $apartelId
     * @param $apartmentId
     * @return array|\ArrayObject|null
     */
    public function getRoomType($apartelId, $apartmentId)
    {
        $this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());
        return $this->fetchOne(function(Select $select) use($apartelId, $apartmentId) {
            $select->columns([
                'apartel_type_id'
            ]);

            $select->join(
                ['apartel_type' => DbTables::TBL_APARTEL_TYPE],
                $this->getTable() . '.apartel_type_id = apartel_type.id',
                []
            );

            $select->where
                ->equalTo($this->getTable() . '.apartment_id', $apartmentId)
                ->equalTo('apartel_type.apartel_id', $apartelId)
            ;
        });
    }

    /**
     * @param $apartelTypeId
     * @return array|\ArrayObject|null
     */
    public function getApartmentListByTypeId($apartelTypeId)
    {
        $this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());
        return $this->fetchAll( function (Select $select) use($apartelTypeId) {
            $select->columns(['apartment_id']);
            $select->where->equalTo($this->getTable() . '.apartel_type_id', $apartelTypeId);
        });
    }

    /**
     * @param $apartelId
     * @param $typeId
     * @return \Zend\Db\ResultSet\ResultSet
     */
    public function getApartelUsedApartment($apartelId, $typeId)
    {
        $this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());
        return $this->fetchAll(function(Select $select) use($apartelId, $typeId) {
            $select->columns([
                'apartment_id'
            ]);

            $select->join(
                ['apartel_type' => DbTables::TBL_APARTEL_TYPE],
                $this->getTable() . '.apartel_type_id = apartel_type.id',
                []
            );

            $select->where->equalTo('apartel_type.apartel_id', $apartelId)
                          ->notEqualTo($this->getTable() . '.apartel_type_id', $typeId)
            ;
        });
    }

    /**
     * @param $apartelId
     * @return \Zend\Db\ResultSet\ResultSet
     */
    public function getReviewForWebsite($apartelId, $count = 10, $offset = 0)
    {
        $this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());

        $result = $this->fetchAll(function (Select $select) use ($apartelId, $count, $offset) {
            $select->columns([]);

            $select->join(
                ['apartel_type' => DbTables::TBL_APARTEL_TYPE],
                $this->getTable() . '.apartel_type_id = apartel_type.id',
                []
            )->join(
                ['review' => DbTables::TBL_PRODUCT_REVIEWS],
                $this->getTable() . '.apartment_id = review.apartment_id',
                [
                    'user_name',
                    'city',
                    'score',
                    'liked',
                    'date',
                    'country_id'
                ]
            );

            $select->where
                ->equalTo('apartel_type.apartel_id', $apartelId)
                ->notEqualTo('review.liked', '')
                ->equalTo('review.status', '3')
                ->notIn('review.city', Review::$notUsedCityList)
                ->greaterThan('review.score', 2)
//                ->expression('review.date >= DATE_SUB(NOW(),INTERVAL 1 YEAR)', [])
            ;

            $select->order('review.date DESC')
                ->limit($count)
                ->offset($offset);

            $select->quantifier(new Expression('SQL_CALC_FOUND_ROWS'));
        });

        $statement = $this->adapter->query('SELECT FOUND_ROWS() as total');
        $resultCount  = $statement->execute();
        $row = $resultCount->current();
        $total = $row['total'];

        return  [
            'result' => $result->toArray(),
            'total'  => $total
        ];
    }

    /**
     * @param $apartelId
     * @return \Zend\Db\ResultSet\ResultSet
     */
    public function getReviewAVGScoreForYear($apartelId)
    {
        $this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());
        $result = $this->fetchOne(function (Select $select) use ($apartelId) {
            $select->columns([]);

            $select->join(
                ['apartel_type' => DbTables::TBL_APARTEL_TYPE],
                $this->getTable() . '.apartel_type_id = apartel_type.id',
                []
            )->join(
                ['review' => DbTables::TBL_PRODUCT_REVIEWS],
                $this->getTable() . '.apartment_id = review.apartment_id',
                [
                    'avg_score' => new Expression('AVG(score)')
                ]
            );

            $select->where
                ->equalTo('apartel_type.apartel_id', $apartelId)
                ->notEqualTo('review.liked', '')
                ->equalTo('review.status', '3')
                ->greaterThan('review.score', 2)
                ->expression('review.date >= DATE_SUB(NOW(),INTERVAL 1 YEAR)', [])
            ;
            $select->group($this->getTable() . '.id');
        });

        return $result ? round($result['avg_score'], 2) : 0;
    }
}
