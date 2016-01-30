<?php

namespace DDD\Dao\MoneyAccount;

use Zend\Db\Sql\Select;
use Library\DbManager\TableGatewayManager;
use Library\Constants\Constants;
use Library\Constants\DbTables;
use Zend\Db\Sql\Where;

class MoneyAccountUsers extends TableGatewayManager {
    protected $table = DbTables::TBL_MONEY_ACCOUNT_USERS;

    public function __construct($sm) {
        parent::__construct($sm, '\ArrayObject');
    }
}
