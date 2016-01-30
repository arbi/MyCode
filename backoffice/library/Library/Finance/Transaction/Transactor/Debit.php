<?php

namespace Library\Finance\Transaction\Transactor;

use DDD\Dao\Finance\Expense\Expenses;
use Library\Finance\Base\Account;
use Library\Finance\Finance;
use Library\Finance\Transaction\Transaction;
use Library\Utility\Currency;

class Debit extends Transaction
{
    /**
     * @var array
     */
    protected $expenses = [];

    /**
     * @return bool
     * @throws \Exception
     */
    public function process()
    {
        /**
         * @var Expenses $expenseDao
         */
        $expenseDao = $this->getServiceLocator()->get('dao_finance_expense_expenses');
        $expenseDao->setEntity(new \ArrayObject());
        $finance = new Finance($this->getServiceLocator());
        $expenseList = $this->getExpenses();

        if (!count($expenseList)) {
            throw new \Exception('No expense attached.');
        }

        $transactionIdList = parent::processGeneralTransaction();

        if (count($transactionIdList) > 1) {
            throw new \RuntimeException('It is impossible to store more than one transaction id for expense transaction.');
        } else {
            $moneyTransactionId = array_shift($transactionIdList);
        }

        foreach ($expenseList as $expense) {
            $expenseData = $expenseDao->fetchOne(['id' => $expense['id']]);

            $expenseTicket = $finance->getExpense($expense['id']);
            $expenseTicket->prepare(
                $this->getExpenseTicketData($expenseData, $expense['amount'])
            );
            $expenseTicket->addTransaction(
                $this->getTransactionData($moneyTransactionId, $expense['amount'])
            );

            $expenseTicket->save();
        }

        // Set as verified transaction
        $this->changeVerifyStatus($moneyTransactionId, self::IS_VERIFIED);

        return $moneyTransactionId;
    }

    /**
     * @param array $expenseData
     * @param float $newTransactionAmount
     * @return array
     */
    private function getExpenseTicketData($expenseData, $newTransactionAmount)
    {
        $currencyUtility = new Currency($this->getServiceLocator()->get('dao_currency_currency'));
        $newTransactionCurrencyId = $this->getAccountFrom()->getCurrency();

        if ($newTransactionCurrencyId != $expenseData['currency_id']) {
            $newTransactionAmount = $currencyUtility->convert($newTransactionAmount, (int)$newTransactionCurrencyId, (int)$expenseData['currency_id']);
        }

        return [
            'isChanged' => false,
            'currencyId' => $expenseData['currency_id'],
            'managerId' => $expenseData['manager_id'],
            'purpose' => $expenseData['purpose'],
            'expectedCompletionDate' => date('Y-m-d', strtotime($expenseData['expected_completion_date_start'])) . ' - ' . date('Y-m-d', strtotime($expenseData['expected_completion_date_end'])),
            'limit'  => $expenseData['limit'],
            'title'  => $expenseData['title'],
            'balance' => [
                'ticket' => $expenseData['ticket_balance'] - $newTransactionAmount,
                'deposit' => $expenseData['deposit_balance'],
                'transaction' => $expenseData['transaction_balance'] - $newTransactionAmount,
                'item' => $expenseData['item_balance'],
            ],
        ];
    }

    /**
     * @param int $moneyTransactionId
     * @param float $amount
     * @return array
     */
    private function getTransactionData($moneyTransactionId, $amount)
    {
        return [
            'moneyTransactionId' => $moneyTransactionId,
            'accountFrom' => ['id' => $this->getAccountFrom()->getAccountId()],
            'accountTo' => [
                'id' => $this->getAccountTo()->getAccountId(),
                'type' => Account::TYPE_SUPPLIER,
                'transactionAccountId' => $this->getAccountTo()->getTransactionAccountId(),
            ],
            'amount' => abs($amount),
            'isRefund' => $amount > 0 ? 0 : 1,
            'tmpId' => null,
        ];
    }

    /**
     * @param int $id
     * @param float $amount
     * @throws \Exception
     */
    public function setExpense($id, $amount)
    {
        if (!ctype_digit($id)) {
            throw new \Exception('Expense id is in bad format.');
        }

        if (!is_numeric($amount)) {
            throw new \Exception('Expense transaction amount is in bad format.');
        }

        array_push($this->expenses, [
            'id' => $id,
            'amount' => $amount,
        ]);
    }

    /**
     * @return array
     */
    public function getExpenses()
    {
        return $this->expenses;
    }
}
