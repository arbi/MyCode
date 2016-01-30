<?php

namespace DDD\Dao\User\Schedule;

use Library\DbManager\TableGatewayManager;
use Library\Constants\DbTables;

use Zend\Db\Sql\Select;

class Schedule extends TableGatewayManager
{
    protected $table = DbTables::TBL_USER_SCHEDULE;

    public function __construct($sm, $domain = 'DDD\Domain\User\Schedule\Schedule')
    {
        parent::__construct($sm, $domain);
    }
}
