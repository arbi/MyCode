<?php

namespace Library\Finance\Transaction\Transactor;

use DDD\Service\Booking\BankTransaction;
use Library\Finance\Exception\MethodNotDefinedException;
use Library\Finance\Transaction\Transaction;

class Reservation extends Transaction
{
    /**
     * @var int $transactorId
     */
    protected $transactorId = null;

    /**
     * @var int $status
     */
    protected $status = BankTransaction::BANK_TRANSACTION_STATUS_PENDING;


    /**
     * @return bool
     */
    public function process()
    {
        $dao = $this->getDao();
        $transactionIdList = parent::processGeneralTransaction();

        if (count($transactionIdList) > 1) {
            throw new \RuntimeException('It is impossible to store more than one transaction id for expense transaction.');
        } else {
            // Why array_shift is used because it returns first element of array or null if array is empty
            $moneyTransactionId = array_shift($transactionIdList);
        }

        $dao->save(['money_transaction_id' => $moneyTransactionId], ['id' => $this->getTransactorId()]);

        $this->setMinorTransferId($dao->lastInsertValue);

        return $moneyTransactionId;
    }

    /**
     * @param int $transactorId
     */
    public function setTransactorId($transactorId)
    {
        $this->transactorId = $transactorId;
    }

    /**
     * @return int
     * @throws MethodNotDefinedException
     */
    public function getTransactorId()
    {
        if (is_null($this->transactorId)) {
            throw new MethodNotDefinedException('Transactor is not defined.');
        }

        return $this->transactorId;
    }

    /**
     * @todo: Money direction should be defined depends on transaction direction
     * @param double $amount
     */
    public function setAmount($amount)
    {
        $this->setAmountFrom($amount);
        $this->setAmountTo($amount);
    }

    /**
     * @param int $status
     */
    public function setStatus($status)
    {
        $this->status = $status;

        if ($status == BankTransaction::BANK_TRANSACTION_STATUS_APPROVED) {
            $this->setIsVerified();
        }
    }

    /**
     * @return int
     */
    public function getStatus()
    {
        return $this->status;
    }
}
