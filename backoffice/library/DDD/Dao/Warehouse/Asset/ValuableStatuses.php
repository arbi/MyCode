<?php
namespace DDD\Dao\Warehouse\Asset;

use Library\Utility\Debug;
use Zend\Db\Sql\Expression;
use Zend\Db\Sql\Select;
use Library\DbManager\TableGatewayManager;
use Library\Constants\Constants;
use Library\Constants\DbTables;
use Zend\Db\Sql\Where;

class ValuableStatuses extends TableGatewayManager
{
    protected $table = DbTables::TBL_ASSETS_VALUABLE_STATUSES;

    public function __construct($sm)
    {
        parent::__construct($sm, 'DDD\Domain\Warehouse\Assets\ValuableStatuses');
    }

}
