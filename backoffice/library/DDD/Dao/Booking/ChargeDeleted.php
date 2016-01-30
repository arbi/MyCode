<?php

namespace DDD\Dao\Booking;

use DDD\Service\Booking\BookingAddon;
use Library\Utility\Debug;
use Zend\Db\Sql\Select;
use Library\DbManager\TableGatewayManager;
use Library\Constants\DbTables;
use Zend\Db\Sql\Expression;
use DDD\Domain\Booking\SumTransaction;
use DDD\Service\Booking\Charge as ChargeService;
use Zend\Db\Sql\Predicate\Predicate;
use Zend\Db\Sql\Where;

class ChargeDeleted extends TableGatewayManager
{
    protected $table = DbTables::TBL_CHARGE_DELETED;

    public function __construct($sm, $domain = 'DDD\Domain\Booking\ChargeDeleted') {
        parent::__construct($sm, $domain);
    }
}
