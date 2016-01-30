<?php

namespace DDD\Dao\Finance\Transaction;

use Library\Constants\DbTables;
use Library\DbManager\TableGatewayManager;
use Zend\ServiceManager\ServiceLocatorInterface;

class TransactionTypes extends TableGatewayManager
{
    protected $table = DbTables::TBL_TRANSACTION_TYPES;

    /**
     * @param ServiceLocatorInterface $sm
     * @param string $domain
     */
    public function __construct($sm, $domain = 'DDD\Domain\Finance\Transaction\TransactionTypes')
    {
        parent::__construct($sm, $domain);
    }
}
