<?php

namespace DDD\Dao\Finance\Transaction;

use Library\Constants\DbTables;
use Library\DbManager\TableGatewayManager;
use Zend\ServiceManager\ServiceLocatorInterface;

class TransactionAccounts extends TableGatewayManager
{
    protected $table = DbTables::TBL_TRANSACTION_ACCOUNTS;

    /**
     * @param ServiceLocatorInterface $sm
     * @param string $domain
     */
    public function __construct($sm, $domain = 'DDD\Domain\Finance\Transaction\TransactionAccounts')
    {
        parent::__construct($sm, $domain);
    }

    /**
     * @param int $holderId
     * @param int $type
     *
     * @return int
     * @throws \Exception
     */
    public function getAccountIdByHolderAndType($holderId, $type)
    {
        $result =  $this->fetchOne([
            'holder_id' => $holderId,
            'type' => $type,
        ], ['id']);

        if (!$result) {
            throw new \Exception('Cannot find account by holder and type.');
        }

        return $result->getId();
    }
}
