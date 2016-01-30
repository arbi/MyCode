<?php
namespace DDD\Dao\Translation;

use Zend\Db\Sql\Select;
use Library\DbManager\TableGatewayManager;
use Library\Constants\Constants;
use Library\Constants\DbTables;
use Zend\Db\Sql\Expression;
use Zend\Db\Sql\Where;

class UniversalPages extends TableGatewayManager
{
    protected $table = DbTables::TBL_PAGES;
    
    public function __construct($sm, $domain = 'DDD\Domain\Translation\UniversalPages'){
        parent::__construct($sm, $domain);
    }

}