<?php

namespace DDD\Dao\Geolocation;

use Zend\Db\Sql\Select;
use Library\DbManager\TableGatewayManager;
use Library\Constants\DbTables;

class Countries extends TableGatewayManager
{
    protected $table = DbTables::TBL_COUNTRIES;

    public function __construct($sm)
    {
        parent::__construct($sm, 'DDD\Domain\Geolocation\Countries');
    }

    /**
     * @return \DDD\Domain\Geolocation\Countries[]|\ArrayObject
     */
    public function getCountriesList(){
        $result = $this->fetchAll(function (Select $select) {
            $select->columns(array('id', 'required_postal_code', 'phone_code'))
                   ->join(
                   ['details' => DbTables::TBL_LOCATION_DETAILS] ,
                   $this->getTable().'.detail_id = details.id',
                   ['name'=>'name', 'iso' => 'iso']
                   )
                   ->where->equalTo('details.is_selling',1)
                   ->where->isNotNull($this->getTable() . '.phone_code');
            $select->order(array('details.name ASC'));
        });

        return $result->buffer();
    }

    public function getParentDetail($id){
    	$result = $this->fetchOne(function (Select $select) use($id) {

                                            $select->join(array('countinent' => DbTables::TBL_CONTINENTS) ,
                                                                $this->getTable().'.continent_id = countinent.id', array(),'left');

                                            $select->join(array('utils' => DbTables::TBL_UN_TEXTLINES) ,
                                                                'countinent.textline_id = utils.id', array('name'=>'en'),'left');
                                            $select->where
                                                   ->equalTo($this->getTable().'.id',$id);
                              });
        return $result;
    }

    public function getCountriesListWithCities(){
    	$result = $this->fetchAll(function (Select $select) {
    		$select->columns(array('id'))
    		->join(array('details' => DbTables::TBL_LOCATION_DETAILS) ,
    				$this->getTable().'.detail_id = details.id', array('name'=>'name'))
    		->join(array('province' => DbTables::TBL_PROVINCES) ,
    						$this->getTable().'.id = province.country_id', array())
    		->join(array('cities' => DbTables::TBL_CITIES) ,
    				'province.id = cities.province_id', array('city_id'=>'id'))
    		->group(array('details.name'))
    		->order(array('details.name ASC'));

    	});
    	return $result;
    }

    /**
     * Get countries and details by country id
     *
     * @return array
     */
    public function getCountriesById(){
        $countries = $this->fetchAll(function (Select $select) {
            $select->columns(array('id'))
                ->join(array('details' => DbTables::TBL_LOCATION_DETAILS) ,
                    $this->getTable().'.detail_id = details.id', array('name'=>'name'))
                ->join(array('province' => DbTables::TBL_PROVINCES) ,
                    $this->getTable().'.id = province.country_id', array())
                ->join(array('cities' => DbTables::TBL_CITIES) ,
                    'province.id = cities.province_id', array('city_id'=>'id'))
                ->group(array('details.name'))
                ->order(array('details.name ASC'));

        });

        $countriesByID = [];
        foreach ($countries as $country) {
            $countriesByID[$country->getId()] = $country;
        }

        return $countriesByID;
    }

    public function getAllActiveCountries()
    {
    	$result = $this->fetchAll(function (Select $select) {
    		$select->columns(array('id', 'detail_id'))
                   ->join(array('details' => DbTables::TBL_LOCATION_DETAILS) ,
                            $this->getTable().'.detail_id = details.id', array('name'=>'name'))
                   ->order(array('details.name ASC'));
    	});
    	return $result;
    }

    public function getCountryById($id)
    {
        return $this->fetchOne(function (Select $select) use($id) {
            $select->columns(
                [
                    'id',
                    'detail_id',
                    'required_postal_code'
                ]
            );

            $select->join(
                ['gd' => DbTables::TBL_LOCATION_DETAILS],
                $this->getTable() . '.detail_id = gd.id',
                [
                    'name',
                    'slug'
                ]
            );

            $select->where->equalTo($this->getTable() . '.id', $id);
        });
    }

    public function getPhoneByCountryId($countryId)
    {
        $tempResult = $this->fetchAll(
            function (Select $select) use($countryId) {
                $select->columns([
                    'id', 'contact_phone'
                ]);
                $select->where
                    ->equalTo($this->getTable() . '.id', $countryId)
                    ->or
                    ->equalTo($this->getTable() . '.id', '213'); // USA phone for default value.
            }
        );
        $result = [];
        foreach ($tempResult as $row) {
            $result[$row->getId()] = $row->getContactPhone();
        }
        if(!empty($result[$countryId])) {
            return $result[$countryId];
        } else {
            unset($result[$countryId]);
            return array_pop($result);
        }
    }
}
