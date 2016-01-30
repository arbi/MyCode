<?php
namespace DDD\Dao\ApartmentGroup;

use Library\DbManager\TableGatewayManager;
use Library\Constants\DbTables;

use Zend\Db\Sql\Select;
use Zend\Db\Sql\Expression;
use Zend\Db\Sql\Where;

class BuildingDetails extends TableGatewayManager
{
    protected $table = DbTables::TBL_BUILDING_DETAILS;
    public function __construct($sm, $domain = '\ArrayObject')
    {
        parent::__construct($sm, $domain);
    }
}
