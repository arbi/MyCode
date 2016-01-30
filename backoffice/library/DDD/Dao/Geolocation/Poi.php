<?php

namespace DDD\Dao\Geolocation;

use Zend\Db\Sql\Select;
use Library\DbManager\TableGatewayManager;
use Library\Constants\Constants;
use Library\Constants\DbTables;

class Poi extends TableGatewayManager
{
    protected $table = DbTables::TBL_POI;
    public function __construct($sm, $domain = 'DDD\Domain\Geolocation\Poi')
    {
        parent::__construct($sm, $domain);
    }

    public function getAllPoisByCityID($id, $isSearchable = false)
    {
        $result = $this->fetchAll(function (Select $select) use($id, $isSearchable) {
            $select->columns([
                'id',
                'detail_id',
                'ws_show_right_column'
            ]);

            $select->join(
                ['gd' => DbTables::TBL_LOCATION_DETAILS],
                $this->getTable() . '.detail_id = gd.id',
                [
                    'name',
                    'slug'
                ]
            );

            $select->where
                ->equalTo($this->getTable() . '.city_id', $id);

            if ($isSearchable) {
                $select->where->equalTo('gd.is_searchable', 1);
            }

            $select->order('name');
        });

        return $result;
    }

    public function getProcvincCountryCityByPoiId($id)
    {
        return $this->fetchOne(function (Select $select) use($id) {
            $select->join(
                ['poi_details' => DbTables::TBL_LOCATION_DETAILS],
                $this->getTable() . '.detail_id = poi_details.id',
                [
                    'poi'       => 'name',
                    'poi_slug'  => 'slug'
                ]
            );

            $select->join(
                ['city' => DbTables::TBL_CITIES],
                $this->getTable() . '.city_id = city.id',
                []
            );

            $select->join(
                ['city_details' => DbTables::TBL_LOCATION_DETAILS],
                'city.detail_id = city_details.id',
                [
                    'city'      => 'name',
                    'city_slug' => 'slug'
                ]
            );

            $select->join(
                ['province' => DbTables::TBL_PROVINCES],
                'city.province_id = province.id',
                []
            );

            $select->join(
                ['province_details' => DbTables::TBL_LOCATION_DETAILS],
                'province.detail_id = province_details.id',
                [
                    'province'      => 'name',
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
                [
                    'country'       => 'name',
                    'country_slug'  => 'slug'
                ]
            );

            $select->where
                ->equalTo($this->getTable() . '.id', $id);
        });
    }

    public function getPoiList($city)
    {
        return $this->fetchAll(function (Select $select) use($city) {
            $select->columns(['id', 'type_id']);

            $select->join(
                ['details' => DbTables::TBL_LOCATION_DETAILS],
                $this->getTable() . '.detail_id = details.id',
                [
                    'poi_name' => 'name',
                    'poi_slug' => 'slug',
                    'name'
                ]
            );

            $select->join(
                ['pt' => DbTables::TBL_POI_TYPE],
                $this->getTable() . '.type_id = pt.id',
                []
            );

            $select->join(
                ['textline' => DbTables::TBL_UN_TEXTLINES],
                'pt.textline_id = textline.id',
                ['poi_type_name' => 'en']
            );

            $select->where(
                [
                    $this->getTable() . '.city_id' => $city,
                    'details.is_searchable' => 1,
                ]
            );

            $select->order(
                [
                    $this->getTable() . '.type_id' => 'ASC',
                    'details.name' => 'ASC'
                ]
            );
        });
    }

    public function getPoiDataByName($cityId, $poiName)
    {
        return $this->fetchOne(function (Select $select) use($cityId, $poiName) {
            $select->columns([]);

            $select->join(
                ['details' => DbTables::TBL_LOCATION_DETAILS],
                $this->getTable() . '.detail_id = details.id',
                [
                    'latitude',
                    'longitude',
                    'cover_image',
                    'description' => 'information_text',
                    'poi_name'    => 'name',
                    'detail_id'   => 'id'
                ]
            );

            $select->where
                ->equalTo($this->getTable() . '.city_id', $cityId)
                ->expression('LOWER(details.name) = ?', array ($poiName));
        });
    }

    public function getPoiDataBySlug($cityId, $poiSlug)
    {
        return $this->fetchOne(function (Select $select) use($cityId, $poiSlug) {
            $select->columns([
                'ws_show_right_column'
            ]);

            $select->join(
                ['details' => DbTables::TBL_LOCATION_DETAILS],
                $this->getTable() . '.detail_id = details.id',
                [
                    'latitude',
                    'longitude',
                    'cover_image',
                    'description' => 'information_text',
                    'poi_name'    => 'name',
                    'detail_id'   => 'id'
                ]
            );

            $select->where
                ->equalTo($this->getTable() . '.city_id', $cityId)
                ->expression('LOWER(details.slug) = ?', array ($poiSlug));
        });
    }

}
