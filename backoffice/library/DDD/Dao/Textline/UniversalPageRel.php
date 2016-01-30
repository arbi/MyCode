<?php

namespace DDD\Dao\Textline;

use Library\DbManager\TableGatewayManager;
use Library\Constants\DbTables;
use Zend\Db\Sql\Select;

class UniversalPageRel extends TableGatewayManager
{
    protected $table = DbTables::TBL_UN_TEXTLINE_PAGE_REL;

    public function __construct($sm, $domain = 'ArrayObject')
    {
        parent::__construct($sm, $domain);
    }
}

?>
