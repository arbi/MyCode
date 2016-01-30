<?php

namespace DDD\Dao\Finance\Transaction;

use Library\Constants\DbTables;
use Library\DbManager\TableGatewayManager;
use Library\Utility\Debug;
use Zend\Db\Sql\Expression;
use Zend\Db\Sql\Select;
use Zend\Db\Sql\Where;
use Zend\ServiceManager\ServiceLocatorInterface;
use Library\Finance\Base\Account;

class Transactions extends TableGatewayManager
{
    protected $table = DbTables::TBL_TRANSACTIONS;

    /**
     * @param ServiceLocatorInterface $sm
     * @param string $domain
     */
    public function __construct($sm, $domain = '\ArrayObject')
    {
        parent::__construct($sm, $domain);
    }

    /**
     * @param int $accountId
     * @return \Zend\Db\ResultSet\ResultSet|array[]
     */
    public function getMoneyTransactionsSimple($accountId)
    {
        return $this->fetchAll(function (Select $select) use ($accountId) {
            $select->columns(['id', 'date', 'description', 'amount', 'is_verified', 'is_voided']);
            $select->join(
                ['currency' => DbTables::TBL_CURRENCY],
                $this->getTable() . '.currency_id = currency.id',
                ['currency_sign' => 'symbol'],
                Select::JOIN_LEFT
            );
            $select->where([$this->getTable() . '.account_id' => $accountId]);
            $select->order(['date desc']);
        });
    }

    /**
     * @param int $transactionId
     * @return \Zend\Db\ResultSet\ResultSet|bool
     */
    public function getMoneyTransaction($transactionId)
    {
        $driver = $this->getAdapter()->getDriver();
        return $driver->createStatement("
            select
                ga_transactions.id,
                ga_transactions.date,
                ga_transactions.description,
                ga_transactions.amount,
                ga_currency.code as currency,
                ga_transactions.is_verified,
                ga_transactions.is_voided,
                ifnull(
                    ifnull(
                        ga_money_accounts.name,
                        ifnull(ga_booking_partners.partner_name, ga_suppliers.name)
                    ),
                    concat(ga_bo_users.firstname, ' ', ga_bo_users.lastname)
                ) as account,
                ifnull(
                        ifnull(
                                ga_money_accounts.id,
                                ifnull(ga_booking_partners.gid, ga_suppliers.id)
                        ),
                        ga_bo_users.id
                ) as account_entity_id,
                ga_transaction_accounts.type as account_type
            from ga_transactions
                left join ga_currency on ga_currency.id = ga_transactions.currency_id
                left join ga_transaction_accounts on ga_transaction_accounts.id = ga_transactions.account_id
                left join ga_bo_users on ga_bo_users.id = ga_transaction_accounts.holder_id and ga_transaction_accounts.type = " . Account::TYPE_PEOPLE . "
                left join ga_suppliers on ga_suppliers.id = ga_transaction_accounts.holder_id and ga_transaction_accounts.type = " . Account::TYPE_SUPPLIER . "
                left join ga_booking_partners on ga_booking_partners.gid = ga_transaction_accounts.holder_id and ga_transaction_accounts.type = " . Account::TYPE_PARTNER . "
                left join ga_money_accounts on ga_money_accounts.id = ga_transaction_accounts.holder_id and ga_transaction_accounts.type = " . Account::TYPE_MONEY_ACCOUNT . "
            where ga_transactions.id = ?
        ")->execute([$transactionId]);
    }

    /**
     * @param $moneyTransactionId
     * @return \Zend\Db\Adapter\Driver\ResultInterface
     */
    public function getSiblingTransactionsCount($moneyTransactionId)
    {
        $driver = $this->getAdapter()->getDriver();
        $stmt = $driver->createStatement("
            select id from ga_reservation_transactions where money_transaction_id = ?
            union
            select id from ga_expense_transaction where money_transaction_id = ?;
        ");

        $result = $stmt->execute([$moneyTransactionId, $moneyTransactionId]);

        return (int)$result->count();
    }

    /**
     * @param int $moneyTransactionId
     * @return array|\ArrayObject|null
     */
    public function getMoneyAccountByMoneyTransactionId($moneyTransactionId)
    {
        return $this->fetchOne(function (Select $select) use ($moneyTransactionId) {
            $select->columns([]);
            $select->join(
                ['accounts' => DbTables::TBL_TRANSACTION_ACCOUNTS],
                $this->getTable() . '.account_id = accounts.id',
                ['account_id' => 'id'],
                Select::JOIN_LEFT
            );
            $select->join(
                ['money_account' => DbTables::TBL_MONEY_ACCOUNT],
                'money_account.id = accounts.holder_id',
                ['id', 'balance'],
                Select::JOIN_LEFT
            );
            $select->where([$this->getTable() . '.id' => $moneyTransactionId]);
        });
    }

    /**
     * @param int $accountId
     * @return float
     */
    public function calculateMoneyAccountBalance($accountId)
    {
        $result = $this->fetchOne(function (Select $select) use ($accountId) {
            $select->columns(['balance' => new Expression('ifnull(sum(amount), 0)')]);
            $select->where(['account_id' => $accountId]);
        });

        if ($result) {
            return $result['balance'];
        }

        return 0;
    }

    /**
     * @param array $moneyAccountTransactionIds
     * @return \Zend\Db\ResultSet\ResultSet
     */
    public function getMoneyAccountTransactionsByIds($moneyAccountTransactionIds)
    {
        return $this->fetchAll(function(Select $select) use ($moneyAccountTransactionIds) {
           $select->where->in($this->getTable() . '.id', $moneyAccountTransactionIds);
        });
    }

    /**
     * @param array $moneyAccountTransactionIds
     */
    public function deleteByIds($moneyAccountTransactionIds)
    {
        $where = new Where();
        $where->in($this->getTable() . '.id', $moneyAccountTransactionIds);
        $this->delete($where);
    }

}
