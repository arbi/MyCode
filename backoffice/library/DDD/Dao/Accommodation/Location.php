<?php
namespace DDD\Dao\Accommodation;

use Zend\Db\Sql\Select;
use Library\DbManager\TableGatewayManager;
use Library\Constants\DbTables;

class Location extends TableGatewayManager
{
    protected $table = DbTables::TBL_APARTMENT_LOCATIONS;
    public function __construct($sm, $domain = 'DDD\Domain\Accommodation\Location')
    {
        parent::__construct($sm, $domain);
    }
}