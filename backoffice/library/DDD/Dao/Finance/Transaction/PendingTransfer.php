<?php

namespace DDD\Dao\Finance\Transaction;

use Library\Constants\DbTables;
use Library\DbManager\TableGatewayManager;
use Zend\Db\Sql\Expression;
use Zend\Db\Sql\Select;
use Zend\ServiceManager\ServiceLocatorInterface;

class PendingTransfer extends TableGatewayManager
{
    protected $table = DbTables::TBL_PENDING_TRANSFER;

    /**
     * @param ServiceLocatorInterface $sm
     * @param string $domain
     */
    public function __construct($sm, $domain = '\ArrayObject')
    {
        parent::__construct($sm, $domain);
    }

    public function getPendingTransactions()
    {
        return $this->fetchAll(function(Select $select) {
            $select->columns(['id', 'date_created', 'description']);
            $select->join(
                ['account_from' => DbTables::TBL_MONEY_ACCOUNT],
                $this->getTable() . '.money_account_from = account_from.id',
                ['account_from' => 'name'],
                Select::JOIN_LEFT
            );
            $select->join(
                ['account_to' => DbTables::TBL_MONEY_ACCOUNT],
                $this->getTable() . '.money_account_to = account_to.id',
                ['account_to' => 'name'],
                Select::JOIN_LEFT
            );
        });
    }

    /**
     * @return int
     */
    public function getTotalCount()
    {
        $result = $this->fetchOne(function(Select $select) {
            $select->columns(['count' => new Expression('count(*)')]);
        });

        return $result['count'];
    }
}
