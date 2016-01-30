<?php

namespace DDD\Dao\Geolocation;

use Zend\Db\Sql\Select;
use Library\DbManager\TableGatewayManager;
use Library\Constants\DbTables;
use Library\Constants\Objects;

class Cities extends TableGatewayManager
{

    protected $table = DbTables::TBL_CITIES;

    public function __construct($sm, $domain = 'DDD\Domain\Geolocation\Cities')
    {
        parent::__construct($sm, $domain);
    }

    public function getAllCitiesByProvinceID($id)
    {
        $result = $this->fetchAll(function (Select $select) use($id) {
            $select->join(array ('gd' => DbTables::TBL_LOCATION_DETAILS), $this->getTable() . '.detail_id = gd.id', array ('name'));
            $select->where
                ->equalTo($this->getTable() . '.province_id', $id);
            $select->columns(array ('id', 'detail_id'))
                ->order('name');
        });
        return $result;
    }

    public function getProcCountByCityId($id)
    {
        $result = $this->fetchOne(function (Select $select) use($id) {
            $select->join(
                ['city_details' => DbTables::TBL_LOCATION_DETAILS],
                $this->getTable() . '.detail_id = city_details.id',
                [
                    'city' => 'name',
                    'city_slug' => 'slug',
                ]
            );

            $select->join(
                ['province' => DbTables::TBL_PROVINCES],
                $this->getTable() . '.province_id = province.id',
                []
            );

            $select->join(
                ['province_details' => DbTables::TBL_LOCATION_DETAILS],
                'province.detail_id = province_details.id',
                [
                    'province' => 'name',
                    'province_slug' => 'slug'

                ]
            );

            $select->join(
                ['country' => DbTables::TBL_COUNTRIES],
                'province.country_id = country.id',
                []
            );

            $select->join(
                ['country_details' => DbTables::TBL_LOCATION_DETAILS],
                'country.detail_id = country_details.id',
                ['country' => 'name']
            );

            $select->where
                ->equalTo($this->getTable() . '.id', $id);
        });

        return $result;
    }

    public function getCityByProvNameCityName($cityName, $provinceName)
    {
        $result = $this->fetchOne(function (Select $select) use($cityName, $provinceName) {
            $select->columns(array ('id', 'detail_id', 'timezone'));
            $select->join(array ('p' => DbTables::TBL_PROVINCES), $this->getTable() . '.province_id = p.id', array ())
                ->join(array ('d_p' => DbTables::TBL_LOCATION_DETAILS), 'p.detail_id = d_p.id', array ())
                ->join(array ('d_c' => DbTables::TBL_LOCATION_DETAILS), $this->getTable() . '.detail_id = d_c.id', array (
                    'city_name' => 'name'
            ));
            $select->where
                ->equalTo('d_p.name', $provinceName)
                ->equalTo('d_c.name', $cityName);
        });
        return $result;
    }

    public function getCityByProvinceViaSlug($citySlug, $provinceSlug)
    {
        $result = $this->fetchOne(function (Select $select) use($citySlug, $provinceSlug) {
            $select->columns(
                [
                    'id',
                    'detail_id',
                    'timezone'
                ]
            );

            $select
                ->join(
                    ['province' => DbTables::TBL_PROVINCES],
                    $this->getTable() . '.province_id = province.id',
                    ['province_short_name' => 'short_name'])
                ->join(
                    ['province_details' => DbTables::TBL_LOCATION_DETAILS],
                    'province.detail_id = province_details.id',
                    [])
                ->join(
                    ['city_details' => DbTables::TBL_LOCATION_DETAILS],
                    $this->getTable() . '.detail_id = city_details.id',
                    ['city_name' => 'name']);

            $select->where
                ->equalTo('province_details.slug', $provinceSlug)
                ->equalTo('city_details.slug', $citySlug);
        });

        return $result;
    }

    public function getCityForLocation()
    {
        $result = $this->fetchAll(function (Select $select) {
            $select->columns(
                ['id']
            );

            $select->join(
                ['provinces' => DbTables::TBL_PROVINCES],
                $this->table . '.province_id = provinces.id',
                [
                    'country_id'
                ]
            );

            $select->join(
                ['city_details' => DbTables::TBL_LOCATION_DETAILS],
                $this->table . '.detail_id = city_details.id',
                [
                    'detail_id' => 'id',
                    'city_url'  => 'slug',
                    'city_name' => 'name',
                    'cover_image'
                ]
            );

            $select->join(
                ['province_details' => DbTables::TBL_LOCATION_DETAILS],
                'provinces.detail_id = province_details.id',
                [
                    'province_url' => 'slug'
                ]
            );

            $select->where
                ->equalTo('city_details.is_searchable', 1);

            $select->order([$this->table . '.ordering' => 'ASC']);

            $select->group($this->table . '.id');
        });

        return $result;
    }

    public function getBreadcrupDataByCity($cityName)
    {
        $result = $this->fetchOne(function (Select $select) use($cityName) {
            $select->columns(array ());
            $select->join(array ('p' => DbTables::TBL_PROVINCES), $this->getTable() . '.province_id = p.id', array ())
                ->join(array ('d_p' => DbTables::TBL_LOCATION_DETAILS), 'p.detail_id = d_p.id', array ('prov_name' => 'name'))
                ->join(array ('d_c' => DbTables::TBL_LOCATION_DETAILS), $this->getTable() . '.detail_id = d_c.id', array ('city_name' => 'name'));
            $select->where
                ->equalTo('d_c.name', $cityName);
        });
        return $result;
    }

    public function getCityDataAndCurrency($detailsId)
    {
        $result = $this->fetchOne(function (Select $select) use($detailsId) {
            $select->columns(array ('timezone'));
            $select->join(
                ['province' => DbTables::TBL_PROVINCES], $this->getTable() . '.province_id = province.id', [], 'LEFT'
            );
            $select->join(
                ['country' => DbTables::TBL_COUNTRIES], 'province.country_id = country.id', [], 'LEFT'
            );
            $select->join(
                ['currency' => DbTables::TBL_CURRENCY], 'country.currency_id = currency.id', ['currency' => 'code'], 'LEFT'
            );

            $select->where
                ->equalTo($this->getTable() . '.detail_id', $detailsId);
        });
        return $result;
    }

    /**
     * @return \DDD\Domain\Geolocation\Cities[]
     */
    public function getBoUserCities()
    {
        $result = $this->fetchAll(function (Select $select) {
            $select
                ->columns(['id'])
                ->join(
                    ['users' => DbTables::TBL_BACKOFFICE_USERS],
                    $this->getTable() . '.id = users.city_id',
                    [],
                    Select::JOIN_INNER
                )
                ->join(
                    ['details' => DbTables::TBL_LOCATION_DETAILS],
                    $this->getTable() . '.detail_id = details.id',
                    ['name'],
                    Select::JOIN_INNER
                )
                ->where('users.disabled = 0')
                ->group($this->getTable() . '.id')
                ->order('details.name ASC');
        });
        return $result;
    }
}
