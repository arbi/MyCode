<?php

namespace DDD\Dao\Finance\Transaction;

use Library\Constants\DbTables;
use Library\DbManager\TableGatewayManager;
use Zend\ServiceManager\ServiceLocatorInterface;

class DebitTransactions extends TableGatewayManager
{
    protected $table = DbTables::TBL_EXPENSE_TRANSACTIONS;

    /**
     * @param ServiceLocatorInterface $sm
     * @param string $domain
     */
    public function __construct($sm, $domain = '\ArrayObject')
    {
        parent::__construct($sm, $domain);
    }
}
