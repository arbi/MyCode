<?php

namespace DDD\Dao\Finance\Transaction;

use Library\Constants\DbTables;
use Library\DbManager\TableGatewayManager;
use Library\Finance\Base\Account;
use Zend\Db\Sql\Expression;
use Zend\Db\Sql\Select;
use Zend\Db\Sql\Where;
use Zend\ServiceManager\ServiceLocatorInterface;

class ExpenseTransactions extends TableGatewayManager
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

    /**
     * @param Where $where
     * @return \Zend\Db\ResultSet\ResultSet|array[]
     */
    public function getTransactions(Where $where)
    {
        return $this->fetchAll(function(Select $select) use ($where) {
            $select->columns([
                '*',
                'items' => new Expression('(
                    select group_concat(id) from ' . DbTables::TBL_EXPENSE_ITEM . "
                    where transaction_id = {$this->getTable()}.id
                )"),
                'account_to' => new Expression('
                    ifnull(
                        ifnull(partner.partner_name, supplier.name),
                        concat(people.firstname, " ", people.lastname)
                    )
                '),
            ]);
            $select->join(
                ['users' => DbTables::TBL_BACKOFFICE_USERS],
                $this->getTable() . '.creator_id = users.id',
                ['creator_name' => new Expression('concat(users.firstname, " ", users.lastname)')],
                Select::JOIN_LEFT
            );
            $select->join(
                ['users2' => DbTables::TBL_BACKOFFICE_USERS],
                $this->getTable() . '.verifier_id = users2.id',
                ['verifier' => new Expression('concat(users2.firstname, " ", users2.lastname)')],
                Select::JOIN_LEFT
            );
            $select->join(
                ['accounts' => DbTables::TBL_TRANSACTION_ACCOUNTS],
                $this->getTable() . '.account_to_id = accounts.id',
                ['account_to_type' => 'type'],
                Select::JOIN_LEFT
            );
            $select->join(
                ['people' => DbTables::TBL_BACKOFFICE_USERS],
                new Expression('people.id = accounts.holder_id and type = ' . Account::TYPE_PEOPLE),
                [],
                Select::JOIN_LEFT
            );
            $select->join(
                ['supplier' => DbTables::TBL_SUPPLIERS],
                new Expression('supplier.id = accounts.holder_id and type = ' . Account::TYPE_SUPPLIER),
                [],
                Select::JOIN_LEFT
            );
            $select->join(
                ['partner' => DbTables::TBL_BOOKING_PARTNERS],
                new Expression('partner.gid = accounts.holder_id and type = ' . Account::TYPE_PARTNER),
                [],
                Select::JOIN_LEFT
            );
            $select->join(
                ['money_transactions' => DbTables::TBL_TRANSACTIONS],
                $this->getTable() . '.money_transaction_id = money_transactions.id',
                ['is_verified'],
                Select::JOIN_LEFT
            );
            $select->join(
                ['money_accounts' => DbTables::TBL_MONEY_ACCOUNT],
                $this->getTable() . '.money_account_id = money_accounts.id',
                ['account_from' => 'name'],
                Select::JOIN_LEFT
            );
            $select->join(
                ['currency' => DbTables::TBL_CURRENCY],
                'money_accounts.currency_id = currency.id',
                [
                    'currency' => 'code',
                    'transaction_currency_id' => 'id',
                ],
                Select::JOIN_LEFT
            );
            $select->join(
                ['expense' => DbTables::TBL_EXPENSES],
                $this->getTable() . '.expense_id = expense.id',
                [
                    'finance_status',
                    'po_currency_id' => 'currency_id',
                ],
                Select::JOIN_LEFT
            );
            $select->join(
                ['bank' => DbTables::TBL_BANK],
                'money_accounts.bank_id = bank.id',
                ['bank' => 'name'],
                Select::JOIN_LEFT
            );
            $select->where($where);
            $select->order(['creation_date DESC', 'id DESC']);
        })->buffer();
    }

    /**
     * @param int $moneyTransactionId
     * @return int
     */
    public function getCountOfExpenseTransactionWithSameMoneyTransactionId($moneyTransactionId)
    {
        $entity = $this->getEntity();
        $this->setEntity(new \ArrayObject());
        $result = $this->fetchOne(function (Select $select) use ($moneyTransactionId) {
            $select->columns(['count' => new Expression('COUNT(*)')]);
            $select->where->equalTo($this->getTable() . '.money_transaction_id', $moneyTransactionId);
        });
        $this->setEntity($entity);
        return $result['count'];
    }


    /**
     * @param int $moneyTransactionid
     * @return \Zend\Db\Adapter\Driver\ResultInterface|array[]
     */
    public function getTransactionByMoneyTransactionid($moneyTransactionid)
    {
        $driver = $this->getAdapter()->getDriver();
        $stmt = $driver->createStatement("
            select
                ga_expense_transaction.id,
                ga_expense_transaction.transaction_date,
                ga_expense_transaction.amount,
                ga_currency.code as currency,
                account_from.name as account_from,
                account_from.id as account_from_entity_id,
                ifnull(
                    ifnull(
                        ga_money_accounts.name,
                        ifnull(ga_booking_partners.partner_name, ga_suppliers.name)
                    ),
                    concat(ga_bo_users.firstname, ' ', ga_bo_users.lastname)
                ) as account_to,
                ifnull(
                        ifnull(
                                ga_money_accounts.id,
                                ifnull(ga_booking_partners.gid, ga_suppliers.id)
                        ),
                        ga_bo_users.id
                ) as account_to_entity_id,
                ga_expense_transaction.is_refund,
                ga_transaction_accounts.type as account_to_type,
                ga_expense.id as purchase_order_id
            from ga_expense_transaction
                left join ga_money_accounts as account_from on account_from.id = ga_expense_transaction.money_account_id
                left join ga_currency as ga_currency on ga_currency.id = account_from.currency_id
                left join ga_transaction_accounts on ga_transaction_accounts.id = ga_expense_transaction.account_to_id
                left join ga_bo_users on ga_bo_users.id = ga_transaction_accounts.holder_id and ga_transaction_accounts.type = " . Account::TYPE_PEOPLE . "
                left join ga_suppliers on ga_suppliers.id = ga_transaction_accounts.holder_id and ga_transaction_accounts.type = " . Account::TYPE_SUPPLIER . "
                left join ga_booking_partners on ga_booking_partners.gid = ga_transaction_accounts.holder_id and ga_transaction_accounts.type = " . Account::TYPE_PARTNER . "
                left join ga_money_accounts on ga_money_accounts.id = ga_transaction_accounts.holder_id and ga_transaction_accounts.type = " . Account::TYPE_MONEY_ACCOUNT . "
                left join ga_expense on ga_expense_transaction.expense_id = ga_expense.id
            where ga_expense_transaction.money_transaction_id = ?;
        ");

        return $stmt->execute([$moneyTransactionid]);
    }

    /**
     * @param int $expenseId
     * @return int
     */
    public function getTransactionCount($expenseId)
    {
        $result = $this->fetchOne(function (Select $select) use ($expenseId) {
            $select->columns(['count' => new Expression('count(*)')]);
            $select->where(['expense_id' => $expenseId]);
        });

        return $result['count'];
    }

    /**
     * @param int $expenseId
     * @return int
     */
    public function getActiveTransactionCount($expenseId)
    {
        $result = $this->fetchOne(function (Select $select) use ($expenseId) {
            $select->columns(['count' => new Expression('count(*)')]);
            $select->where(['expense_id' => $expenseId]);
            $select->where->notEqualTo('status', 0); // TODO: find and replace 0 to voided
        });

        return $result['count'];
    }

    /**
     * @param int $expenseTransactionId
     * @return bool|array
     */
    public function getTransactionDetails($expenseTransactionId)
    {
        return $this->fetchOne(function (Select $select) use ($expenseTransactionId) {
            $select->columns(['money_transaction_id', 'amount', 'creation_date', 'is_refund', 'expense_id', 'money_account_id', 'account_to_id']);
            $select->join(
                ['money_account' => DbTables::TBL_MONEY_ACCOUNT],
                $this->getTable() . '.money_account_id = money_account.id',
                ['currency_id', 'name'],
                Select::JOIN_LEFT
            );
            $select->join(
                ['expense' => DbTables::TBL_EXPENSES],
                $this->getTable() . '.expense_id = expense.id',
                ['expense_currency_id' => 'currency_id', 'ticket_balance', 'transaction_balance'],
                Select::JOIN_LEFT
            );

            $select->where([$this->getTable() . '.id' => $expenseTransactionId]);
        });
    }

    public function updateMoneyTransactions($toBeCombinedMoneyAccountTransactionIds, $combinedMoneyTransactionId)
    {
        $where = new Where();
        $where->in($this->getTable() . '.money_transaction_id', $toBeCombinedMoneyAccountTransactionIds);
        $this->save(['money_transaction_id' => $combinedMoneyTransactionId], $where);
    }

    public function getExpenseIds($moneyTransactionId)
    {
        return $this->fetchAll(function (Select $select) use ($moneyTransactionId) {
            $select->columns(['expense_id']);
            $select->where->equalTo('money_transaction_id',$moneyTransactionId);
        });
    }

    /**
     * @param $expenseTransactionId
     * @return array|\ArrayObject|null
     */
    public function getTransaction($expenseTransactionId)
    {
        $this->setEntity(new \ArrayObject());
        return $this->fetchOne(function (Select $select) use ($expenseTransactionId) {
            $select->columns(['money_transaction_id']);
            $select->where->equalTo('id',$expenseTransactionId);
        });
    }

    /**
     * @param $expenseTransactionId
     * @return array|\ArrayObject|null
     */
    public function getTransactionInfoWithAdditionalInfo($expenseTransactionId)
    {
        $prototype = $this->getEntity();
        $this->setEntity(new \ArrayObject());
        $result = $this->fetchOne(function (Select $select) use ($expenseTransactionId){
            $select->columns(['creation_date', 'amount', 'is_refund']);
            $select->join(
                ['money_accounts' => DbTables::TBL_MONEY_ACCOUNT],
                $this->getTable() . '.money_account_id = money_accounts.id',
                [],
                Select::JOIN_INNER
            );
            $select->join(
                ['currency' => DbTables::TBL_CURRENCY],
                'money_accounts.currency_id = currency.id',
                ['currency_code_transaction' => 'code'],
                Select::JOIN_INNER
            );
            $select->join(
                ['expense' => DbTables::TBL_EXPENSES],
                $this->getTable() . '.expense_id = expense.id',
                [
                    'expense_id' => 'id',
                    'expense_transaction_balance' => 'transaction_balance',
                    'expense_ticket_balance' => 'ticket_balance'
                ],
                Select::JOIN_INNER
            );
            $select->join(
                ['currency2' => DbTables::TBL_CURRENCY],
                'expense.currency_id = currency2.id',
                ['currency_code_expense' => 'code'],
                Select::JOIN_INNER
            );
            $select->where->equalTo($this->getTable() . '.id', $expenseTransactionId);
        });
        $this->setEntity($prototype);
        return $result;
    }
}
