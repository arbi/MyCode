<?php

namespace DDD\Dao\Venue;

use Library\DbManager\TableGatewayManager;
use Zend\Db\Sql\Select;
use Library\Constants\DbTables;
use Zend\Db\Sql\Where;

class LunchroomOrderArchive extends TableGatewayManager
{
    protected $table   = DbTables::TBL_LUNCHROOM_ORDER_ARCHIVE;

    public function __construct($sm, $domain = '\ArrayObject')
    {
        parent::__construct($sm, $domain);
    }

    /**
     * @param int $chargeId
     * @return \Zend\Db\ResultSet\ResultSet
     */
    public function getItemsByChargeId($chargeId)
    {
        return $this->fetchAll(function(Select $select) use ($chargeId) {
            $select->columns(['item_price', 'item_quantity', 'item_name']);
            $select->where->equalTo('venue_charge_id', $chargeId);
        });
    }
}