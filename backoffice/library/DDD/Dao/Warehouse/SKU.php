<?php

namespace DDD\Dao\Warehouse;

use Library\Utility\Debug;
use Zend\Db\Sql\Expression;
use Zend\Db\Sql\Select;
use Library\DbManager\TableGatewayManager;
use Library\Constants\Constants;
use Library\Constants\DbTables;
use Zend\Db\Sql\Where;

class SKU extends TableGatewayManager
{
    protected $table = DbTables::TBL_SKU;

    public function __construct($sm)
    {
        parent::__construct($sm, 'ArrayObject');
    }

    public function getSkuIdByName($sku)
    {
        return  $this->fetchOne(function (Select $select) use($sku) {
            $where = new Where();
            $where->equalTo('name', $sku);
            $select
                ->columns(['id','asset_category_id'])
                ->where($where);
        });
    }
}
