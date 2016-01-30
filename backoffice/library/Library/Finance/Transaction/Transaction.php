<?php

namespace Library\Finance\Transaction;

use Library\Authentication\BackofficeAuthenticationService;
use Library\Finance\Base\ITransaction;
use Library\Finance\Base\TransactionBase;

abstract class Transaction extends TransactionBase implements ITransaction
{
    /**
     * @param int|null $moneyTransactionId Used to delete transaction
     * @param array $data
     * @return array
     * @throws \RuntimeException
     */
    public function processGeneralTransaction($moneyTransactionId = null, $data = [])
    {
        $transactionIdList = [];
        /**
         * @var \DDD\Service\Finance\Transaction\BankTransaction $transactionService
         */
        switch ($this->getMode()) {
            case self::MODE_ADD:
                if (!is_null($this->getAccountFrom()) && $this->isMoneyAccount($this->getAccountFrom()->getType())) {
                    $lastMoneyTransactionId = $this->getTransactionsDao()->save([
                        'account_id' => $this->getAccountFrom()->getTransactionAccountId(),
                        'status' => Transaction::STATUS_OK,
                        'type' => $this->getType(),
                        'date' => $this->getTransactionDateFrom(),
                        'is_verified' => $this->getIsVerified(),
                        'currency_id' => $this->getAccountFrom()->getCurrency(),
                        'amount' => $this->getAccountFrom()->getAmount(),
                        'description' => $this->getDescription(),
                    ]);
                    array_push($transactionIdList, $lastMoneyTransactionId);

                    // Update money account balance
                    $this->updateBalance($this->getAccountFrom()->getAccountId(), $this->getAccountFrom()->getAmount());
                }

                if (!is_null($this->getAccountTo()) && $this->isMoneyAccount($this->getAccountTo()->getType())) {
                    $lastMoneyTransactionId = $this->getTransactionsDao()->save([
                        'account_id' => $this->getAccountTo()->getTransactionAccountId(),
                        'status' => Transaction::STATUS_OK,
                        'type' => $this->getType(),
                        'date' => $this->getTransactionDateTo(),
                        'is_verified' => $this->getIsVerified(),
                        'currency_id' => $this->getAccountTo()->getCurrency(),
                        'amount' => $this->getAccountTo()->getAmount(),
                        'description' => $this->getDescription(),
                    ]);

                    array_push($transactionIdList, $lastMoneyTransactionId);

                    // Update money account balance
                    $this->updateBalance($this->getAccountTo()->getAccountId(), $this->getAccountTo()->getAmount());
                }

                break;
            case self::MODE_EDIT:
                // @fixme: to verify transfer transactions we need to pass money transaction id array
                // @fixme: for both direction

                if (count($data)) {
                    $this->getTransactionsDao()->save($data, ['id' => $moneyTransactionId]);
                }

                break;
            case self::MODE_DELETE:
                // @fixme id can be more than one

                // Delete transaction and update money account balance
                $this->deleteTransaction($moneyTransactionId);

                break;
        }

        return $transactionIdList;
    }

    /**
     * @param int $accountFromValue
     * @param int $accountToValue
     */
    public function setAccountIdentity($accountFromValue, $accountToValue)
    {
        $this->setAccount(self::DIRECTION_FROM, $this->accountFromType, $accountFromValue);
        $this->setAccount(self::DIRECTION_TO, $this->accountToType, $accountToValue);
    }

    /**
     * @param int $moneyAccountId
     * @param double $amount
     */
    private function updateBalance($moneyAccountId, $amount)
    {
        $moneyAccountDao = $this->getMoneyAccountsDao();

        $moneyAccount = $moneyAccountDao->fetchOne(['id' => $moneyAccountId]);

        if ($moneyAccount) {
            $moneyAccountDao->update(['balance' => $moneyAccount['balance'] + $amount], ['id' => $moneyAccount['id']]);
        }
    }

    /**
     * Delete transaction and update money account balancec
     *
     * @param int $moneyTransactionId
     */
    private function deleteTransaction($moneyTransactionId)
    {
        $moneyTransactionDao = $this->getTransactionsDao();
        $moneyAccountDao = $this->getMoneyAccountsDao();

        $moneyAccount = $moneyTransactionDao->getMoneyAccountByMoneyTransactionId($moneyTransactionId);

        $this->getTransactionsDao()->delete(['id' => $moneyTransactionId]);

        if ($moneyAccount) {
            $balance = $moneyTransactionDao->calculateMoneyAccountBalance($moneyAccount['account_id']);
            $moneyAccountDao->update(['balance' => $balance], ['id' => $moneyAccount['id']]);
        }
    }

    /**
     * @param int $accountTypeId
     * @return bool
     */
    private function isMoneyAccount($accountTypeId)
    {
        return (
            $this->getAccountTypeById($accountTypeId) == self::ACCOUNT_MONEY_ACCOUNT
        );
    }

    /**
     * @param $transactionId
     * @param $status
     * @return bool
     */
    protected function changeVerifyStatus($transactionId, $status)
    {
        /**
         * @var BackofficeAuthenticationService $auth
         * @var \DDD\Dao\Finance\Transaction\ExpenseTransactions $expenseTransactionDao
         * @var \DDD\Dao\Finance\Transaction\Transactions $transactionDao
         */
        $expenseTransactionDao = $this->getServiceLocator()->get('dao_finance_transaction_expense_transactions');
        $transactionDao = $this->getServiceLocator()->get('dao_finance_transaction_transactions');

        try {
            $transactionDao->beginTransaction();

            // Verify
            $transactionDao->save([
                'is_verified' => $status,
            ], ['id' => $transactionId]);

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
}
