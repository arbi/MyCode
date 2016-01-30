<?php

namespace DDD\Dao\Geolocation;

use Library\Utility\Debug;
use Zend\Db\Sql\Expression;
use Zend\Db\Sql\Select;
use Library\DbManager\TableGatewayManager;
use Library\Constants\DbTables;

class City extends TableGatewayManager
{

    protected $table = DbTables::TBL_CITIES;

    public function __construct($sm)
    {
        parent::__construct($sm, 'DDD\Domain\Geolocation\City');
    }

    public function getCountryIDByCityID($city_id)
    {
        $result = $this->fetchOne(function (Select $select) use($city_id) {
            $select->join(array ('province' => DbTables::TBL_PROVINCES), $this->getTable() . '.province_id = province.id')
                ->join(array ('country' => DbTables::TBL_COUNTRIES), 'province.country_id = country.id', array ('id'))
                ->where($this->getTable() . '.id = ' . (int) $city_id);
        });
        return $result;
    }

    public function getCityByCountryId($country_id)
    {
        $result = $this->fetchAll(function (Select $select) use($country_id) {
            $select->columns(array ('id'))
                ->join(array ('province' => DbTables::TBL_PROVINCES), $this->getTable() . '.province_id = province.id', array ())
                ->join(array ('country' => DbTables::TBL_COUNTRIES), 'province.country_id = country.id', array ())
                ->join(array ('details' => DbTables::TBL_LOCATION_DETAILS), $this->getTable() . '.detail_id = details.id', array ('name' => 'name'))
                ->where('country.id = ' . (int) $country_id);
        });
        return $result;
    }

    public function getCityById($cityId)
    {
        $result = $this->fetchOne(function (Select $select) use($cityId) {
            $select->columns(
                ['id']
            );

            $select->join(
                ['details' => DbTables::TBL_LOCATION_DETAILS],
                $this->getTable() . '.detail_id = details.id',
                [
                    'name',
                    'slug'
                ]
            );

            $select->where($this->getTable() . '.id = ' . (int) $cityId);
        });

        return $result;
    }

    /**
     * @return \Zend\Db\ResultSet\ResultSet|array[]
     */
    public function getSearchableCities()
    {
        $this->setEntity(new \ArrayObject());

        return $this->fetchAll(function(Select $select) {
                $select->columns(['id']);
                $select->join(
                    ['details' => DbTables::TBL_LOCATION_DETAILS], $this->getTable() . '.detail_id = details.id', ['name' => 'name'], Select::JOIN_LEFT
                );
                $select->where(['details.is_searchable', 1]);
                $select->order('details.name');
            });
    }

    /**
     * @param $partnerId
     * @return \Zend\Db\ResultSet\ResultSet | \ArrayObject
     */
    public function getCityForPartnerCommission($partnerId)
    {
        $this->setEntity(new \ArrayObject());

        return $this->fetchAll(function(Select $select) use ($partnerId) {
                $select->columns(['id']);
                $select->join(
                    ['details' => DbTables::TBL_LOCATION_DETAILS], $this->getTable() . '.detail_id = details.id', [
                    'name'
                    ], Select::JOIN_LEFT
                );
                $select->join(
                    ['partner_city_commission' => DbTables::TBL_PARTNER_CITY_COMMISSION], new Expression($this->getTable() . '.id = partner_city_commission.city_id AND partner_city_commission.partner_id=' . $partnerId), [], Select::JOIN_LEFT
                );
                $select->where->equalTo('details.is_searchable', 1)
                    ->isNull('partner_city_commission.partner_id')
                ;
                $select->group($this->getTable() . '.id');
                $select->order('details.name');
            });
    }

    /**
     * @return array
     */
    public function getCitiesForSelect()
    {
        $prototype = $this->resultSetPrototype->getArrayObjectPrototype();
        $this->setEntity(new \ArrayObject());

        $result = $this->fetchAll(function(Select $select) {
            $select->columns(['id']);
            $select->join(
                ['details' => DbTables::TBL_LOCATION_DETAILS], $this->getTable() . '.detail_id = details.id', ['name' => 'name'], Select::JOIN_LEFT
            );
            $select->where->isNotNull('name');
            $select->order('details.name');
        });

        $resultArray = ['-- Choose City --'];
        foreach ($result as $row) {
            $resultArray[$row['id']] = $row['name'];
        }
        $this->setEntity($prototype);
        return $resultArray;
    }

}
