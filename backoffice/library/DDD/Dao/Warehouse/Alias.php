<?php

namespace DDD\Dao\Warehouse;
use Library\Constants\DbTables;
use Library\DbManager\TableGatewayManager;

class Alias extends TableGatewayManager
{
    protected $table = DbTables::TBL_ASSET_CATEGORY_ALIASES;

    public function __construct($sm)
    {
        parent::__construct($sm, 'ArrayObject');
    }
}
