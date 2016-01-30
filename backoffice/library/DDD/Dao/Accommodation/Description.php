<?php
namespace DDD\Dao\Accommodation;

use Zend\Db\Sql\Select;
use Library\DbManager\TableGatewayManager;
use Library\Constants\DbTables;

class Description extends TableGatewayManager
{
    protected $table = DbTables::TBL_PRODUCT_DESCRIPTIONS;
    public function __construct($sm, $domain = 'DDD\Domain\Accommodation\Description')
    {
        parent::__construct($sm, $domain);
    }
}