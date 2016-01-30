<?php

namespace DDD\Dao\Location;

use Library\DbManager\TableGatewayManager;
use Library\Constants\DbTables;
use Zend\Db\Sql\Select;

class Province extends TableGatewayManager
{
    protected $table = DbTables::TBL_LOCATION_DETAILS;
    
    public function __construct($sm)
    {
        parent::__construct($sm, 'DDD\Domain\Location\Province');
    }
    
    public function getProvinceIdByCityId($cityId)
    {
        $result = $this->fetchOne(function(Select $select) use($cityId){
            
            $select->join(
                    ['cities' => DbTables::TBL_CITIES],
                    $this->getTable().'.id = cities.province_id',
                    ['id' => 'province_id'],
                    'right'
                    );
            
            $select->where
                    ->equalTo('cities.id', $cityId);
        });
        
        return $result->getId();
    }
}
