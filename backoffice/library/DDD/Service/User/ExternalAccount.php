<?php

namespace DDD\Service\User;

use DDD\Service\User as UserBase;
use Zend\Db\Sql\Where;

/**
 * Class    ExternalAccount
 * @package DDD\Service\User\ExternalAccount
 * @author  Harut Grigoryan
 */
class ExternalAccount extends UserBase
{
    /**
     * Statuses
     */
    const EXTERNAL_ACCOUNT_STATUS_ACTIVE   = 1;
    const EXTERNAL_ACCOUNT_STATUS_ARCHIVED = 2;
    /**
     * Account types
     */
    const EXTERNAL_ACCOUNT_TYPE_DIRECT_DEPOSIT = 1;
    const EXTERNAL_ACCOUNT_TYPE_CHECK          = 2;
    const EXTERNAL_ACCOUNT_TYPE_CASH           = 3;
    const EXTERNAL_ACCOUNT_TYPE_COMPANY_CARD   = 4;
    /**
     * Default account separator
     */
    const EXTERNAL_ACCOUNT_IS_DEFAULT = 1;

    /**
     * @param array $params
     * @return mixed
     */
    public function getExternalAccountsByParams($params = [])
    {
        /**
         * @var \DDD\Dao\User\ExternalAccount $externalAccountDao
         */
        $externalAccountDao = $this->getServiceLocator()->get('dao_user_external_account');

        return $externalAccountDao->getExternalAccountsByParams($params);
    }

    /**
     * Get External Accounts by transaction account id
     *
     * @param  $transactionAccountID
     * @return mixed
     */
    public function getByTransactionAccountId($transactionAccountID)
    {
        /**
         * @var \DDD\Dao\User\ExternalAccount $externalAccountDao
         */
        $externalAccountDao = $this->getServiceLocator()->get('dao_user_external_account');

        return $externalAccountDao->getByTransactionAccountId($transactionAccountID);
    }

    /**
     * Get Active External Accounts by transaction account id
     *
     * @param  $transactionAccountID
     * @return mixed
     */
    public function getActiveAccountsByTransactionAccountId($transactionAccountID)
    {
        /**
         * @var \DDD\Dao\User\ExternalAccount $externalAccountDao
         */
        $externalAccountDao = $this->getServiceLocator()->get('dao_user_external_account');

        return $externalAccountDao->getActiveAccountsByTransactionAccountId($transactionAccountID);
    }

    /**
     * Get External Accounts by user id
     *
     * @param  $transactionAccountId
     * @return mixed
     */
    public function getExternalAccountsByTransactionAccountIdAndIdKey($transactionAccountId)
    {
        /**
         * @var \DDD\Dao\User\ExternalAccount $externalAccountDao
         */
        $externalAccountDao = $this->getServiceLocator()->get('dao_user_external_account');
        $externalAccounts   = $externalAccountDao->getByTransactionAccountId($transactionAccountId);

        $externalAccountById = [];
        foreach ($externalAccounts as $externalAccount) {
            $externalAccountById[$externalAccount->getId()] = $externalAccount;
        }

        return $externalAccountById;
    }
}
