<?php

namespace Library\Finance\Transaction\Transactor;

use Library\Finance\Transaction\Transaction;

class Expense extends Transaction
{
    const STATUS_VOID = 0;
    const STATUS_NORMAL = 1;
    const STATUS_VERIFIED = 2;

    /**
     * @var int
     */
    protected $expenseId;

    /**
     * @var int
     */
    protected $transactionId;

    /**
     * @var int
     */
    protected $transactionAccountId;

    /**
     * @var int
     */
    protected $moneyTransactionId = false;

    /**
     * @var array
     */
    protected $transactionData = [];

    /**
     * @var array
     */
    protected $moneyTransactionData = [];

    /**
     * @var bool
     */
    protected $isVerified = false;

    /**
     * @var bool
     */
    protected $isVoid = false;

    /**
     * @var bool
     */
    protected $verifierId;

    /**
     * @var int
     */
    protected $isVirtual = 0;

    /**
     * @return bool
     */
    public function process()
    {
        // Prepare data and do relevant changes for transaction
        $this->preGeneralTransaction();

        // General transaction (Money Transaction) should be created only for non virtual expense transactions otherwise
        // money transaction already exists and there is no need to create it twice
        if (!$this->getIsVirtual()) {
            // Process money transaction
            if ($this->getIsRefund()) {
                $this->getAccountTo()->forgetDirection();
            }

            $transactionIdList = parent::processGeneralTransaction($this->getMoneyTransactionId(), $this->moneyTransactionData);

            if (count($transactionIdList) > 1) {
                throw new \RuntimeException('It is impossible to store more than one transaction id for expense transaction.');
            } else {
                $this->setMoneyTransactionId(
                    array_shift($transactionIdList)
                );
            }
        }

        // Finish expense transaction
        return $this->postGeneralTransaction();
    }

    /**
     * @return array
     */
    private function preGeneralTransaction()
    {
        $dao = $this->getDao();

        switch ($this->getMode()) {
            case self::MODE_ADD:
                $moneyAccountId = (
                    $this->getIsRefund()
                        ? $this->getAccountTo()->getAccountId()
                        : $this->getAccountFrom()->getAccountId()
                );

                $this->transactionData = [
                    'money_account_id' => $moneyAccountId,
                    'amount' => $this->getAccountFrom()->getAmount(),
                    'transaction_date' => $this->getTransactionDate(),
                    'account_to_id' => $this->getTransactionAccountId(),
                ];

                break;
            case self::MODE_EDIT:
                // Money transaction id required for modification purpose
                if (!$this->getMoneyTransactionId()) {
                    $this->setMoneyTransactionId(
                        $this->getMoneyTransactionIdByTransactionid($this->getTransactionId())
                    );
                }

                if ($this->getIsVoid()) {
                    $moneyTransactionDao = $this->getTransactionsDao();
                    $siblingCount = $moneyTransactionDao->getSiblingTransactionsCount($this->getMoneyTransactionId());

                    $this->transactionData['status'] = self::STATUS_VOID;
                    $this->transactionData['verifier_id'] = $this->getVerifierId();

                    // If One to One connected po transaction
                    if ($siblingCount == 1) {
                        // Remove money transaction
                        $this->setMode(self::MODE_DELETE);
                    } else {
                        $this->transactionData['money_transaction_id'] = null;
                    }

                    $dao->save($this->transactionData, ['id' => $this->getTransactionId()]);
                }

                if ($this->getIsVerified()) {
                    $this->transactionData['status'] = self::STATUS_VERIFIED;
                    $this->moneyTransactionData['is_verified'] = self::IS_VERIFIED;
                }

                break;
        }
    }

    /**
     * @return bool|int
     */
    private function postGeneralTransaction()
    {
        $dao = $this->getDao();
        $moneyTransactionId = -1;

        switch ($this->getMode()) {
            case self::MODE_ADD:
                $this->transactionData['creator_id'] = $this->getUserId();
                $this->transactionData['is_refund'] = $this->getIsRefund();
                $this->transactionData['expense_id'] = $this->getExpenseId();
                $this->transactionData['creation_date'] = $this->getCreationDate();
                $this->transactionData['creation_date'] = $this->getCreationDate();
                $this->transactionData['money_transaction_id'] = $this->getMoneyTransactionId();

                $dao->save($this->transactionData);
                $moneyTransactionId = $dao->lastInsertValue;
                $this->setTransactionId($moneyTransactionId);

                break;
            case self::MODE_EDIT:
                if ($this->getIsVerified()) {
                    $this->transactionData['verifier_id'] = $this->getVerifierId();
                }

                $dao->save($this->transactionData, ['id' => $this->getTransactionId()]);
                $moneyTransactionId = $this->getTransactionId();

                break;
            case self::MODE_DELETE:
                // do nothing

                break;
        }

        // Set as verified transaction
        $this->changeVerifyStatus($moneyTransactionId, self::IS_VERIFIED);

        return $this->getTransactionId();
    }

    /**
     * @param int $transactionId
     * @return int|null
     */
    private function getMoneyTransactionIdByTransactionid($transactionId)
    {
        $dao = $this->getDao();
        $expenseTransaction = $dao->fetchOne(['id' => $transactionId], ['money_transaction_id']);

        if ($expenseTransaction) {
            return $expenseTransaction['money_transaction_id'];
        } else {
            null;
        }
    }

    /**
     * It is a universal method to set an amount for transaction where there is not strict direction.
     * When we registering an expense we only know the one direction - we transfered a money to someone
     * or got a refund from someone. We don't know the exact amount for example ebay got but we know
     * what amount and in what currency we transfered.
     *
     * @param double $amount
     */
    public function setAmount($amount)
    {
        $this->getAccountFrom()->setAmount($amount);
        $this->getAccountTo()->setAmount($amount);
    }

    /**
     * @return int
     */
    public function getTransactionId()
    {
        return $this->transactionId;
    }

    /**
     * @param int $transactionId
     */
    public function setTransactionId($transactionId)
    {
        $this->transactionId = $transactionId;
    }

    /**
     * @return int
     */
    public function getTransactionAccountId()
    {
        return $this->transactionAccountId;
    }

    /**
     * @param int $transactionAccountId
     */
    public function setTransactionAccountId($transactionAccountId)
    {
        $this->transactionAccountId = $transactionAccountId;
    }

    /**
     * @return int|bool
     */
    public function getMoneyTransactionId()
    {
        return $this->moneyTransactionId;
    }

    /**
     * @param int $moneyTransactionId
     */
    public function setMoneyTransactionId($moneyTransactionId)
    {
        $this->moneyTransactionId = $moneyTransactionId;
    }

    /**
     * @param int $expenseId
     */
    public function setExpenseId($expenseId)
    {
        $this->expenseId = $expenseId;
    }

    /**
     * @return int
     */
    public function getExpenseId()
    {
        return $this->expenseId;
    }

    /**
     * @param bool|int $isVerified
     */
    public function setIsVerified($isVerified = true)
    {
        $this->isVerified = $isVerified;
    }

    /**
     * @return int
     */
    public function getIsVerified()
    {
        return $this->isVerified ? 1 : 0;
    }

    /**
     * @param bool|int $isVoid
     */
    public function setIsVoid($isVoid = true)
    {
        $this->isVoid = $isVoid;
    }

    /**
     * @return int
     */
    public function getIsVoid()
    {
        return $this->isVoid ? 1 : 0;
    }

    /**
     * @param int $verifierId
     */
    public function setVerifierId($verifierId)
    {
        $this->verifierId = $verifierId;
    }

    /**
     * @return int
     */
    public function getVerifierId()
    {
        return $this->verifierId;
    }

    /**
     * @return int
     */
    public function getIsVirtual()
    {
        return $this->isVirtual;
    }

    /**
     * @param int $isVirtual
     */
    public function setIsVirtual($isVirtual = 1)
    {
        $this->isVirtual = $isVirtual;
    }
}
