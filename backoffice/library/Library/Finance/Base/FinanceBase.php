<?php

namespace Library\Finance\Base;

use Library\Finance\Account\Customer;
use Library\Finance\CreditCard\CreditCardEntity;
use Library\Finance\Transaction\Transaction;
use Zend\ServiceManager\ServiceLocatorAwareTrait;
use Library\Finance\Transaction\Transactor;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * @method bool isMoneyAccountToMoneyAccount($accountFrom, $accountTo)
 *
 * @method bool isMoneyAccountToSupplier($accountFrom, $accountTo)
 * @method bool isMoneyAccountToPartner($accountFrom, $accountTo)
 * @method bool isMoneyAccountToCustomer($accountFrom, $accountTo)
 * @method bool isMoneyAccountToPeople($accountFrom, $accountTo)
 *
 * @method bool isPartnerToMoneyAccount($accountFrom, $accountTo)
 * @method bool isCustomerToMoneyAccount($accountFrom, $accountTo)
 * @method bool isSupplierToMoneyAccount($accountFrom, $accountTo)
 * @method bool isPeopleToMoneyAccount($accountFrom, $accountTo)
 */
abstract class FinanceBase
{
    use ServiceLocatorAwareTrait;

    /**
     * @var Transaction $transaction
     */
    protected $transaction;

    /**
     * @var Customer $customer
     */
    protected $customer;

    /**
     * @var array|CreditCardEntity[] $creditCards
     */
    protected $creditCards = [];

    /**
     * @param ServiceLocatorInterface $serviceLocator
     */
    public function __construct(ServiceLocatorInterface $serviceLocator)
    {
        $this->setServiceLocator($serviceLocator);
    }

    /**
     * Match accounts and detect right way to make transaction.
     *
     * @param string $accountFrom
     * @param string $accountTo
     *
     * @return Transaction
     * @throws \RuntimeException
     * @throws \Exception
     */
    protected function detectTransactionType($accountFrom, $accountTo)
    {
        do {
            // Supplier, MoneyAccount to MoneyAccount
            if (
                $this->isSupplierToMoneyAccount($accountFrom, $accountTo) ||
                $this->isMoneyAccountToMoneyAccount($accountFrom, $accountTo)
            ) {
                $transaction = new Transactor\Transfer($accountFrom, $accountTo);

                break;
            }

            // MoneyAccount to Supplier, People, Partner (Affiliate)
            if (
                $this->isMoneyAccountToSupplier($accountFrom, $accountTo) ||
                $this->isMoneyAccountToPeople($accountFrom, $accountTo) ||
                $this->isMoneyAccountToPartner($accountFrom, $accountTo)
            ) {
                $transaction = new Transactor\Expense($accountFrom, $accountTo);

                break;
            }

            throw new \RuntimeException("from {$accountFrom} to {$accountTo} is a not handled situation.");
        } while (false);

        return $transaction;
    }

    /**
     * Understand transaction possibility.
     *
     * @param string $method
     * @param array $arguments
     *
     * @return bool
     * @throws \InvalidArgumentException
     * @throws \BadMethodCallException
     */
    public function __call($method, $arguments)
    {
        if (count($arguments) > 1) {
            if (strpos($method, 'is') === 0) {
                $accountFromTo = substr($method, 2);

                if (strpos($accountFromTo, 'To')) {
                    list($accountFrom, $accountTo) = explode('To', $accountFromTo);

                    $availableAccounts = Transaction::getAvailableAccounts();

                    if (in_array($accountFrom, $availableAccounts) && in_array($accountTo, $availableAccounts)) {
                        $needleFrom = $arguments[0];
                        $needleTo = $arguments[1];

                        return (
                            $accountFrom == $needleFrom && $accountTo == $needleTo
                        );
                    }
                }
            }

            throw new \BadMethodCallException('Method not defined.');
        } else {
            throw new \InvalidArgumentException('Required argument is missing.');
        }
    }
}
