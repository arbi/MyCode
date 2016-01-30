<?php
namespace DDD\Dao\Apartment;

use Library\DbManager\TableGatewayManager;
use Library\Constants\DbTables;
use Zend\Db\Sql\Select;
use Zend\Db\Sql\Expression;

class Cost extends TableGatewayManager
{
    protected $table = DbTables::TBL_EXACT_EXPENSES;

    public function __construct($sm, $domain = 'DDD\Domain\Apartment\Document\Document'){
        parent::__construct($sm, $domain);
    }
}
