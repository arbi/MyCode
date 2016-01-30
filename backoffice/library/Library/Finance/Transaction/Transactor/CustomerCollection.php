<?php

namespace Library\Finance\Transaction\Transactor;

use DDD\Dao\Booking\ChargeTransaction;
use Library\Finance\Transaction\Transaction;

class CustomerCollection extends Transaction
{
    /**
     * @var array
     */
    protected $transactions = [];

    /**
     * @return bool
     * @throws \Exception
     */
    public function process()
    {
        /**
         * @var ChargeTransaction $reservationTransactionDao
         */
        $reservationTransactionDao = $this->getDao();
        $virtualTransactionIdList = $this->getTransactions();

        if (!count($virtualTransactionIdList)) {
            throw new \Exception('No transactions attached.');
        }

        $transactionIdList = parent::processGeneralTransaction();

        if (count($transactionIdList) > 1) {
            throw new \RuntimeException('It is impossible to store more than one transaction id for reservation transaction.');
        } else {
            $moneyTransactionId = array_shift($transactionIdList);
        }

        foreach ($virtualTransactionIdList as $virtualTransactionId) {
            $reservationTransactionDao->save(['money_transaction_id' => $moneyTransactionId], ['id' => $virtualTransactionId]);
        }

        return $moneyTransactionId;
    }

    /**
     * @return array
     */
    public function getTransactions()
    {
        return $this->transactions;
    }

    /**
     * @param array $transactionIdList
     * @return array
     */
    public function setTransactions($transactionIdList)
    {
        $this->transactions = $transactionIdList;
    }
}
