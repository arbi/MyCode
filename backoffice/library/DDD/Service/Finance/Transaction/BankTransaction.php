<?php

namespace DDD\Service\Finance\Transaction;

use DDD\Dao\Booking\ChargeTransaction;
use DDD\Dao\Finance\Transaction\ExpenseTransactions;
use DDD\Dao\Finance\Transaction\TransactionAccounts;
use DDD\Dao\Finance\Transaction\Transactions;
use DDD\Dao\Finance\Transaction\TransferTransactions;
use DDD\Service\Finance\Expense\Expenses;
use DDD\Service\ServiceBase;
use Library\ActionLogger\Logger;
use Library\Authentication\BackofficeAuthenticationService;
use Library\Finance\Base\Account;
use Library\Finance\Base\TransactionBase;
use Zend\Db\Sql\Expression;

class BankTransaction extends ServiceBase
{
    /**
     * @return \Zend\Db\ResultSet\ResultSet|array[]
     */
    public function getAllTransactions()
    {
        /**
         * @var Transactions $transactionsDao
         */
        $transactionsDao = $this->getServiceLocator()->get('dao_finance_transaction_transactions');

        return $transactionsDao->fetchAll();
    }

    /**
     * @param int $moneyAccountId
     * @return \Zend\Db\ResultSet\ResultSet|array[]
     */
    public function getMoneyAccountTransactions($moneyAccountId)
    {
        /**
         * @var Transactions $transactionsDao
         * @var TransactionAccounts $transactionAccountDao
         */
        $transactionsDao = $this->getServiceLocator()->get('dao_finance_transaction_transactions');
        $transactionAccountDao = $this->getServiceLocator()->get('dao_finance_transaction_transaction_accounts');
        $accountId = $transactionAccountDao->getAccountIdByHolderAndType($moneyAccountId, Account::TYPE_MONEY_ACCOUNT);

        return $transactionsDao->getMoneyTransactionsSimple($accountId);
    }

    /**
     * @param int $transactionId
     * @return array|array[]
     */
    public function getMoneyAccountTransactionDetails($transactionId)
    {
        /**
         * @var Transactions $transactionDao
         * @var ExpenseTransactions $expenseTransactionDao
         * @var TransferTransactions $transferDao
         * @var ChargeTransaction $reservationDao
         */
        $transactionDao = $this->getServiceLocator()->get('dao_finance_transaction_transactions');
        $expenseTransactionDao = $this->getServiceLocator()->get('dao_finance_transaction_expense_transactions');
        $transferDao = $this->getServiceLocator()->get('dao_finance_transaction_transfer_transactions');
        $reservationDao = $this->getServiceLocator()->get('dao_booking_change_transaction');

        $moneyTransaction = $transactionDao->getMoneyTransaction($transactionId);
        $expenseTransaction = $expenseTransactionDao->getTransactionByMoneyTransactionid($transactionId);
        $transferTransaction = $transferDao->getTransferByMoneyTransactionId($transactionId);
        $reservationTransaction = $reservationDao->getTransactionsByMoneyTransactionId($transactionId);

        return [
            'moneyTransaction' => $moneyTransaction,
            'transferTransaction' => $transferTransaction,
            'reservationTransaction' => $reservationTransaction,
            'expenseTransaction' => $expenseTransaction,
        ];
    }

    /**
    * @param $transactionId
    * @param $status
    * @return bool
    */
   public function changeVerifyStatus($transactionId, $status)
   {
       /** @var \DDD\Dao\Finance\Transaction\ExpenseTransactions $expenseTransactionDao */
       $expenseTransactionDao = $this->getServiceLocator()->get('dao_finance_transaction_expense_transactions');
       /** @var \DDD\Dao\Finance\Transaction\Transactions $transactionDao */
       $transactionDao = $this->getServiceLocator()->get('dao_finance_transaction_transactions');
       /** @var Logger $logger */
       $logger = $this->getServiceLocator()->get('ActionLogger');

       try {
           $transactionDao->beginTransaction();

           // Verify
           $transactionDao->save([
               'is_verified' => $status,
           ], ['id' => $transactionId]);

           $logger->save(Logger::MODULE_MONEY_ACCOUNT, $transactionId, Logger::ACTION_VERIFY_TRANSACTION, $status);

           // Save Verifier
           $verifierId = null;
           if ($status == TransactionBase::IS_VERIFIED) {
               $auth = $this->getServiceLocator()->get('library_backoffice_auth');
               $verifierId = $auth->getIdentity()->id;
           }

           $expenseTransactionDao->save([
               'verifier_id' => $verifierId,
           ], ['money_transaction_id' => $transactionId]);

           $transactionDao->commitTransaction();

           return true;
       } catch (\Exception $ex) {
           $transactionDao->rollbackTransaction();
       }

       return false;
   }

    /**
     * @param $transactionId
     * @throws \RuntimeException
     * @return bool
     */
    public function voidTransaction($transactionId)
    {
        /** @var \DDD\Dao\Finance\Transaction\Transactions $transactionDao */
        $transactionDao = $this->getServiceLocator()->get('dao_finance_transaction_transactions');
        /** @var TransferTransactions $transferDao */
        $transferDao = $this->getServiceLocator()->get('dao_finance_transaction_transfer_transactions');
        /** @var \DDD\Dao\MoneyAccount\MoneyAccount $moneyAccountDao */
        $moneyAccountDao = $this->getServiceLocator()->get('dao_money_account_money_account');
        /** @var Logger $logger */
        $logger = $this->getServiceLocator()->get('ActionLogger');

        $transferTransaction = $transferDao->getTransferByTransaction($transactionId);

        if (!($transferTransaction && !empty($transferTransaction['money_account_id_to']))) {
            throw new \RuntimeException('Only Transfer and Credit transfer types can be voided from here. Other transaction types should be voided from the PO or Reservation ticket. ');
        } else {
            try {
                $transferDao->beginTransaction();

                // Void transfer
                $transferDao->save(['is_voided' => 1], ['id' => $transactionId]);

                // Void transaction 1
                $transactionDao->save(['is_voided' => 1], ['id' => $transferTransaction['money_transaction_id_1']]);

                // Void transaction 2
                $transactionDao->save(['is_voided' => 1], ['id' => $transferTransaction['money_transaction_id_2']]);

                // Fix transaction 2nd account balance
                $moneyAccountDao->save(
                    ['balance' => new Expression('balance - (' . $transferTransaction['amount_to'] . ')')],
                    ['id' => $transferTransaction['money_account_id_to']]
                );

                // Fix transaction 1st account balance if it's money account
                if (!empty($transferTransaction['money_account_id_from'])) {
                    $moneyAccountDao->save(
                        ['balance' => new Expression('balance - (' . $transferTransaction['amount_from'] . ')')],
                        ['id' => $transferTransaction['money_account_id_from']]
                    );
                }

                $logger->save(Logger::MODULE_MONEY_ACCOUNT, $transferTransaction['money_transaction_id_1'], Logger::ACTION_VOID_TRANSACTION);
                $logger->save(Logger::MODULE_MONEY_ACCOUNT, $transferTransaction['money_transaction_id_2'], Logger::ACTION_VOID_TRANSACTION);
                $transferDao->commitTransaction();
                return true;
            } catch (\Exception $e) {
                $transferDao->rollbackTransaction();
                throw new \RuntimeException('Voiding transaction failed.');
            }
        }
    }
}
