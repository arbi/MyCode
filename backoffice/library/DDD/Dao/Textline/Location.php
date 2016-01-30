<?php

namespace DDD\Dao\Textline;

use Library\DbManager\TableGatewayManager;
use Library\Constants\DbTables;
use Zend\Db\Sql\Select;

class Location extends TableGatewayManager
{
    protected $table = DbTables::TBL_LOCATION_DETAILS;
    
    public function __construct($sm)
    {
        parent::__construct($sm, 'DDD\Domain\Textline\Location');
    }

    /**
     * @param int $id
     * @return \DDD\Domain\Textline\Location
     */
    public function getCityNameById($id)
    {
        $result = $this->fetchOne(function(Select $select) use($id){
            $select->columns(array(
                'name'
            ));

            $select->join(
                array('cities' => DbTables::TBL_CITIES),
                $this->getTable().'.id = cities.detail_id',
                array()
            );
            
            $select->where
                    ->equalTo('cities.id', $id);
        });
        return $result;
    }

    /**
     * @param int $id
     * @return \DDD\Dao\Textline\Location
     */
    public function getProvinceNameById($id)
    {
        $result = $this->fetchOne(function(Select $select) use($id){
            $select->columns(array(
                'name'
            ));
            
            $select->join(
                array('province' => DbTables::TBL_PROVINCES),
                $this->getTable().'.id = province.detail_id',
                array()
            );
            
            $select->where
                    ->equalTo('province.id', $id);
        });
        
        return $result;
    }

    /**
     * @param int $id
     * @return \DDD\Dao\Textline\Location
     */
    public function getCountryNameById($id)
    {
        $result = $this->fetchOne(function(Select $select) use($id){
            $select->columns(array(
                'name'
            ));
            
            $select->join(
                array('countries' => DbTables::TBL_COUNTRIES),
                $this->getTable().'.id = countries.detail_id',
                array()
            );
            
            $select->where
                    ->equalTo('countries.id', $id);
        });
        
        return $result;
    }
    
}

?>