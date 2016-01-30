<?php

namespace DDD\Dao\Finance\Transaction;

use Library\Constants\DbTables;
use Library\DbManager\TableGatewayManager;
use Zend\Db\Sql\Expression;
use Zend\Db\Sql\Select;
use Zend\Db\Sql\Where;
use Zend\ServiceManager\ServiceLocatorInterface;
use Library\Finance\Base\Account;

class TransferTransactions extends TableGatewayManager
{
    protected $table = DbTables::TBL_TRANSFER_TRANSACTIONS;

    /**
     * @param ServiceLocatorInterface $sm
     * @param string $domain
     */
    public function __construct($sm, $domain = '\ArrayObject')
    {
        parent::__construct($sm, $domain);
    }

    /**
     * @param int $moneyTransactionid
     * @return \Zend\Db\Adapter\Driver\ResultInterface|array[]
     */
    public function getTransferByMoneyTransactionId($moneyTransactionid)
    {
        $driver = $this->getAdapter()->getDriver();
        $stmt = $driver->createStatement("
            select
                ga_transfer_transactions.id,
                ga_bo_users.id as creator_id,
                concat(ga_bo_users.firstname, ' ', ga_bo_users.lastname) as creator,
                ga_transfer_transactions.amount_from,
                ga_transfer_transactions.amount_to,
                ga_transfer_transactions.transaction_date_from,
                ga_transfer_transactions.transaction_date_to,
                currency_from.code as currency_from,
                currency_to.code as currency_to,
                ga_transfer_transactions.description,
                ifnull(
                        ifnull(
                                ga_money_accounts_from.name,
                                ifnull(ga_booking_partners_from.partner_name, ga_suppliers_from.name)
                        ),
                        concat(ga_bo_users_from.firstname, ' ', ga_bo_users_from.lastname)
                ) as account_from,
                ifnull(
                        ifnull(
                                ga_money_accounts_from.id,
                                ifnull(ga_booking_partners_from.gid, ga_suppliers_from.id)
                        ),
                        ga_bo_users_from.id
                ) as account_from_entity_id,
                ifnull(
                        ifnull(
                                ga_money_accounts_to.name,
                                ifnull(ga_booking_partners_to.partner_name, ga_suppliers_to.name)
                        ),
                        concat(ga_bo_users_to.firstname, ' ', ga_bo_users_to.lastname)
                ) as account_to,
                ifnull(
                        ifnull(
                                ga_money_accounts_to.id,
                                ifnull(ga_booking_partners_to.gid, ga_suppliers_to.id)
                        ),
                        ga_bo_users_to.id
                ) as account_to_entity_id,
                ga_transaction_accounts_from.type as account_from_type,
                ga_transaction_accounts_to.type as account_to_type
            from ga_transfer_transactions
                left join ga_bo_users on ga_bo_users.id = ga_transfer_transactions.creator_id

                left join ga_transaction_accounts as ga_transaction_accounts_from on ga_transaction_accounts_from.id = ga_transfer_transactions.account_id_from
                left join ga_bo_users as ga_bo_users_from on ga_bo_users_from.id = ga_transaction_accounts_from.holder_id and ga_transaction_accounts_from.type = " . Account::TYPE_PEOPLE . "
                left join ga_suppliers as ga_suppliers_from on ga_suppliers_from.id = ga_transaction_accounts_from.holder_id and ga_transaction_accounts_from.type = " . Account::TYPE_SUPPLIER . "
                left join ga_booking_partners as ga_booking_partners_from on ga_booking_partners_from.gid = ga_transaction_accounts_from.holder_id and ga_transaction_accounts_from.type = " . Account::TYPE_PARTNER . "
                left join ga_money_accounts as ga_money_accounts_from on ga_money_accounts_from.id = ga_transaction_accounts_from.holder_id and ga_transaction_accounts_from.type = " . Account::TYPE_MONEY_ACCOUNT . "
                left join ga_currency as currency_from on currency_from.id = ifnull(ga_money_accounts_from.currency_id, null)

                left join ga_transaction_accounts as ga_transaction_accounts_to on ga_transaction_accounts_to.id = ga_transfer_transactions.account_id_to
                left join ga_bo_users as ga_bo_users_to on ga_bo_users_to.id = ga_transaction_accounts_to.holder_id and ga_transaction_accounts_to.type = " . Account::TYPE_PEOPLE . "
                left join ga_suppliers as ga_suppliers_to on ga_suppliers_to.id = ga_transaction_accounts_to.holder_id and ga_transaction_accounts_to.type = " . Account::TYPE_SUPPLIER . "
                left join ga_booking_partners as ga_booking_partners_to on ga_booking_partners_to.gid = ga_transaction_accounts_to.holder_id and ga_transaction_accounts_to.type = " . Account::TYPE_PARTNER . "
                left join ga_money_accounts as ga_money_accounts_to on ga_money_accounts_to.id = ga_transaction_accounts_to.holder_id and ga_transaction_accounts_to.type = " . Account::TYPE_MONEY_ACCOUNT . "
                left join ga_currency as currency_to on currency_to.id = ifnull(ga_money_accounts_to.currency_id, null)
            where ga_transfer_transactions.money_transaction_id_1 = ? or ga_transfer_transactions.money_transaction_id_2 = ?;
        ");

        return $stmt->execute([$moneyTransactionid, $moneyTransactionid]);
    }

    /**
     * @param $transactionId
     * @return array|\ArrayObject|null
     */
    public function getTransferByTransaction($transactionId)
    {
        return $this->fetchOne(function (Select $select) use ($transactionId) {
            $where = new Where();
            $where
                ->equalTo('money_transaction_id_1', $transactionId)
                ->or
                ->equalTo('money_transaction_id_2', $transactionId);

            $select
                ->where($where)
                ->columns([
                    'id',
                    'money_transaction_id_1',
                    'money_transaction_id_2',
                    'amount_from',
                    'amount_to'
                ])
                ->join(
                    ['ta1' => DbTables::TBL_TRANSACTION_ACCOUNTS],
                    new Expression($this->getTable() . '.account_id_from = ta1.id AND ta1.type = ' . Account::TYPE_MONEY_ACCOUNT),
                    [],
                    Select::JOIN_LEFT
                )
                ->join(
                    ['ma1' => DbTables::TBL_MONEY_ACCOUNT],
                    'ta1.holder_id = ma1.id',
                    ['money_account_id_from' => 'id', 'account_currency_from' => 'currency_id'],
                    Select::JOIN_LEFT
                )
                ->join(
                    ['ta2' => DbTables::TBL_TRANSACTION_ACCOUNTS],
                    new Expression($this->getTable() . '.account_id_to = ta2.id AND ta2.type = ' . Account::TYPE_MONEY_ACCOUNT),
                    [],
                    Select::JOIN_LEFT
                )
                ->join(
                    ['ma2' => DbTables::TBL_MONEY_ACCOUNT],
                    'ta2.holder_id = ma2.id',
                    ['money_account_id_to' => 'id', 'account_currency_to' => 'currency_id'],
                    Select::JOIN_LEFT
                );
        });
    }
}
