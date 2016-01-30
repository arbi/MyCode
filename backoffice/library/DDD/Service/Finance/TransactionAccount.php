<?php

namespace DDD\Service\Finance;

use DDD\Dao\Finance\Transaction\TransactionAccounts;
use DDD\Service\ServiceBase;
use Library\Finance\Base\Account;
use Zend\Db\Adapter\Adapter;

class TransactionAccount extends ServiceBase
{
    public function getAccountsByAutocomplete($keyword)
    {
        /**
         * @var Adapter $dbAdapter
         */
        $dbAdapter = $this->getServiceLocator()->get('dbadapter');
        $accountPeople = Account::TYPE_PEOPLE;
        $accountSupplier = Account::TYPE_SUPPLIER;
        $accountAffiliate = Account::TYPE_PARTNER;

        $statement = $dbAdapter->createStatement("
          select * from (
                select
                    ga_transaction_accounts.id as unique_id,
                    ifnull(
                            ifnull(ga_booking_partners.gid, ga_suppliers.id),
                            ga_bo_users.id
                    ) as account_id,
                    ifnull(
                            ifnull(ga_booking_partners.partner_name, ga_suppliers.name),
                            concat(ga_bo_users.firstname, ' ', ga_bo_users.lastname)
                    ) as name,
                    ga_transaction_accounts.type as type,
                    case when ga_booking_partners.gid is null then
                        (case when ga_suppliers.id is null then 'People' else 'External' end)
                    else 'Partner' end as label
                from ga_transaction_accounts
                    left join ga_bo_users on ga_bo_users.id = ga_transaction_accounts.holder_id and ga_transaction_accounts.type = {$accountPeople}
                    left join ga_suppliers on ga_suppliers.id = ga_transaction_accounts.holder_id and ga_transaction_accounts.type = {$accountSupplier}
                    left join ga_booking_partners on ga_booking_partners.gid = ga_transaction_accounts.holder_id and ga_transaction_accounts.type = {$accountAffiliate}
            ) as selection
            where name like '%{$keyword}%';
        ");

        $result = $statement->execute();
        $resultList = [];

        if ($result->count()) {
            foreach ($result as $account) {
                $resultList[] = $account;
            }
        }

        return $resultList;
    }

    /**
     * @param int $identityId
     * @param int $type
     * @return int
     */
    public function getTransactionAccountIdByIdentity($identityId, $type)
    {
        $transactionAccountDao = $this->getTransactionAccountDao();
        return $transactionAccountDao->getAccountIdByHolderAndType($identityId, $type);
    }

    /**
     * @return TransactionAccounts
     */
    private function getTransactionAccountDao()
    {
        return $this->getServiceLocator()->get('dao_finance_transaction_transaction_accounts');
    }
}
