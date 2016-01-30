<?php

namespace DDD\Dao\Finance;

use Zend\Db\Sql\Select;
use Library\DbManager\TableGatewayManager;
use Library\Constants\DbTables;

class MoneyAccount extends TableGatewayManager
{
    protected $table = DbTables::TBL_MONEY_ACCOUNT;

    public function __construct($sm, $domain = 'DDD\Domain\Finance\MoneyAccount')
    {
        parent::__construct($sm, $domain);
    }

    /**
     * @param $id
     * @return array|\ArrayObject|null
     */
    public function getCurrencyId($id)
    {
        $entity = $this->getEntity();
        $this->setEntity(new \ArrayObject());
        $result = $this->fetchOne(function(Select $select) use ($id){
            $select->columns(['currency_id']);
            $select->where->equalTo('id', $id);
        });
        $this->setEntity($entity);
        return $result['currency_id'];
    }
}
