<?php

namespace DDD\Dao\Finance;

use Library\DbManager\TableGatewayManager;
use Library\Constants\DbTables;
use Zend\Db\Sql\Select;

class Bank extends TableGatewayManager
{
    protected $table = DbTables::TBL_BANK;

    public function __construct($sm, $domain = 'DDD\Domain\Finance\Bank')
    {
        parent::__construct($sm, $domain);
    }

    /**
     * @param int $bankId
     * @return \DDD\Domain\Finance\Bank|bool
     */
    public function getBankById($bankId)
    {
        return $this->fetchOne(function (Select $select) use ($bankId) {
            $select->where(['id' => $bankId]);
            $select->order('name');
        });
    }
}
