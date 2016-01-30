<?php

namespace DDD\Service\Finance\Transaction;

use DDD\Dao\Finance\Expense\Expenses;
use DDD\Dao\Finance\Transaction\ExpenseTransactions;
use DDD\Dao\Finance\Transaction\Transactions;
use DDD\Dao\MoneyAccount\MoneyAccount as MoneyAccountsDAO;
use DDD\Service\Currency\CurrencyVault;
use DDD\Service\ServiceBase;

/**
 * Class PurchaseOrderTransaction
 * @package DDD\Service\Finance\Transaction
 */
class PurchaseOrderTransaction extends ServiceBase
{
    /**
     * @param $purchaseOrderTransactionId
     * @param bool $updatePurchaseOrderBalance
     * @return bool
     */
    public function removePurchaseOrderTransaction($purchaseOrderTransactionId, $updatePurchaseOrderBalance = true)
    {
        /**
         * @var MoneyAccountsDAO $moneyAccountsDao
         */
        $moneyTransactionsDao           = new Transactions($this->getServiceLocator(), '\ArrayObject');
        $purchaseOrderTransactionsDao   = new ExpenseTransactions($this->getServiceLocator(), '\ArrayObject');
        $purchaseOrdersDao              = new Expenses($this->getServiceLocator(), '\ArrayObject');
        $moneyAccountsDao               = new MoneyAccountsDAO($this->getServiceLocator(), '\ArrayObject');

        $purchaseOrderTransaction = $purchaseOrderTransactionsDao->fetchOne(
            ['id' => $purchaseOrderTransactionId],
            [
                'money_transaction_id',
                'money_account_id',
                'amount',
                'expense_id',
                'creation_date'
            ]
        );
        $moneyTransactionId = $purchaseOrderTransaction['money_transaction_id'];
        $purchaseOrderTransactionAmount = $purchaseOrderTransaction['amount'];
        $purchaseOrderId = $purchaseOrderTransaction['expense_id'];
        $purchaseOrder = $purchaseOrdersDao->fetchOne(
            ['id' => $purchaseOrderId],
            [
                'ticket_balance',
                'transaction_balance',
                'currency_id'
            ]
        );
        $purchaseOrderTransactionMoneyAccountId = $purchaseOrderTransaction['money_account_id'];
        $moneyAccount = $moneyAccountsDao->fetchOne(
            ['id' => $purchaseOrderTransactionMoneyAccountId],
            [
                'balance',
                'currency_id'
            ]
        );

        // Remove money transaction
        $moneyTransactionsDao->delete([
            'id' => $moneyTransactionId
        ]);

        // Remove purchase order transaction
        $purchaseOrderTransactionsDao->delete([
            'id' => $purchaseOrderTransactionId
        ]);

        // Update money account balance
        $moneyAccountBalance = $moneyAccount['balance'];

        $moneyAccountBalance -= $purchaseOrderTransactionAmount;
        $moneyAccountsDao->update(
            ['balance' => $moneyAccountBalance],
            ['id' => $purchaseOrderTransactionMoneyAccountId]
        );

        if ($updatePurchaseOrderBalance) {
            // Update purchase order balance
            // Update purchase order transaction balance

            if ($purchaseOrder['currency_id'] != $moneyAccount['currency_id']) {
                // PO balance and PO transaction balance are in PO currency
                // We need to convert currencies to correctly recalculate those balances
                // Please note that we need to make conversion by currency rate for that particular date when transaction was made
                $currencyVaultService = new CurrencyVault();
                $purchaseOrderTransactionAmount = $currencyVaultService->convertCurrency(
                    $purchaseOrderTransactionAmount,
                    $moneyAccount['currency_id'],
                    $purchaseOrder['currency_id'],
                    $purchaseOrderTransaction['creation_date']
                );

                $purchaseOrdersDao->update(
                    [
                        'ticket_balance'      => $purchaseOrder['balance'] - $purchaseOrderTransactionAmount,
                        'transaction_balance' => $purchaseOrder['transaction_balance'] - $purchaseOrderTransactionAmount,
                    ],
                    [
                        'id' => $purchaseOrder['id']
                    ]
                );
            }
        }

        return true;
    }

    /**
     * @param $purchaseOrderId
     * @param bool $updatePurchaseOrderBalance
     * @return bool
     */
    public function removePurchaseOrderAllTransactions($purchaseOrderId, $updatePurchaseOrderBalance = true)
    {
        $purchaseOrderTransactionsDao = new ExpenseTransactions($this->getServiceLocator(), '\ArrayObject');

        $purchaseOrderTransactions = $purchaseOrderTransactionsDao->fetchAll(
            ['expense_id' => $purchaseOrderId],
            [
                'id',
                'money_transaction_id'
            ]
        );

        foreach ($purchaseOrderTransactions as $purchaseOrderTransaction) {
            $this->removePurchaseOrderTransaction($purchaseOrderTransaction['id'], $updatePurchaseOrderBalance);
        }

        return true;
    }
} 