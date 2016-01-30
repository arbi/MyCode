<?php

namespace Library\Finance;

use Library\Finance\Account;
use Library\Finance\Base\FinanceBase;
use Library\Finance\CreditCard\CreditCard;
use Library\Finance\CreditCard\CreditCardEntity;
use Library\Finance\Process\Expense;
use Library\Finance\Transaction\Transaction;
use Library\Finance\Transaction\Transactor;

class Finance extends FinanceBase
{
    /**
     * @param null|int $customerId
     * @return Account\Customer
     */
    public function getCustomer($customerId = null)
    {
        if (!is_null($customerId) && $customerId <= 0) {
            $customerId = null;
        }

        $customer = new Account\Customer($this->getServiceLocator());
        $customer->setAccountId($customerId);
        $customer->prepare();

        return $customer;
    }

    /**
     * @param null|int $supplierId
     * @return Account\Supplier
     */
    public function getSupplier($supplierId = null)
    {
        if (!is_null($supplierId) && $supplierId <= 0) {
            $supplierId = null;
        }

        $supplier = new Account\Supplier($this->getServiceLocator());
        $supplier->setAccountId($supplierId);
        $supplier->prepare();

        return $supplier;
    }

    /**
     * @param null|int $moneyAccountId
     * @return Account\MoneyAccount
     */
    public function getMoneyAccount($moneyAccountId = null)
    {
        if (!is_null($moneyAccountId) && $moneyAccountId <= 0) {
            $moneyAccountId = null;
        }

        $moneyAccount = new Account\MoneyAccount($this->getServiceLocator());
        $moneyAccount->setAccountId($moneyAccountId);
        $moneyAccount->prepare();

        return $moneyAccount;
    }

    /**
     * @param null|int $expenseId
     * @return Expense\Ticket
     */
    public function getExpense($expenseId = null)
    {
        return new Expense\Ticket($this, $expenseId);
    }
}
