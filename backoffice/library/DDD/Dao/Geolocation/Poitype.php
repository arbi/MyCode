<?php
namespace DDD\Dao\Geolocation;

use Zend\Db\Sql\Select;
use Library\DbManager\TableGatewayManager;
use Library\Constants\DbTables;

class Poitype extends TableGatewayManager
{
    protected $table = DbTables::TBL_POI_TYPE;
    public function __construct($sm)
    {
        parent::__construct($sm, 'DDD\Domain\Geolocation\Poitype');
    }
    
    public function getAllList(){
        $result = $this->fetchAll(function (Select $select) {
                                    $select->columns(array('id'))
                                           ->join(array('utls' => DbTables::TBL_UN_TEXTLINES) , 
                                                                $this->getTable().'.textline_id = utls.id', array('name'=>'en'), 'left')
                                            ->order('utls.en');
                              ;
                      });
        return $result;      
    }
}