<?php

namespace DDD\Domain\Finance\Expense;

/**
 * Class Expenses
 * @package DDD\Domain\Finance\Expense
 */
class Expenses
{
    protected $id;
    protected $status;
    protected $account_id;
    protected $account_reference;
    protected $creator_id;
    protected $manager_id;
    protected $currency_id;
    protected $date_created;
    protected $purpose;
    protected $ticket_balance;
    protected $transaction_balance;
    protected $deposit_balance;
    protected $budgetId;

    public function exchangeArray($data)
    {
        $this->id                    = (isset($data['id'])) ? $data['id'] : null;
        $this->status                = (isset($data['status'])) ? $data['status'] : null;
        $this->account_id            = (isset($data['account_id'])) ? $data['account_id'] : null;
        $this->account_reference     = (isset($data['account_reference'])) ? $data['account_reference'] : null;
        $this->creator_id            = (isset($data['creator_id'])) ? $data['creator_id'] : null;
        $this->manager_id            = (isset($data['manager_id'])) ? $data['manager_id'] : null;
        $this->currency_id           = (isset($data['currency_id'])) ? $data['currency_id'] : null;
        $this->date_created          = (isset($data['date_created'])) ? $data['date_created'] : null;
        $this->purpose               = (isset($data['purpose'])) ? $data['purpose'] : null;
        $this->ticket_balance        = (isset($data['ticket_balance'])) ? $data['ticket_balance'] : null;
        $this->transaction_balance   = (isset($data['transaction_balance'])) ? $data['transaction_balance'] : null;
        $this->deposit_balance       = (isset($data['deposit_balance'])) ? $data['deposit_balance'] : null;
        $this->budgetId              = (isset($data['budget_id'])) ? $data['budget_id'] : null;
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function getCreatorId()
    {
        return $this->creator_id;
    }

    public function getCurrencyId()
    {
        return $this->currency_id;
    }

    public function getDateCreated()
    {
        return $this->date_created;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getManagerId()
    {
        return $this->manager_id;
    }

    public function getAccountId()
    {
        return $this->account_id;
    }

    public function getAccountReference()
    {
        return $this->account_reference;
    }

    public function getDepositBalance()
    {
        return $this->deposit_balance;
    }

    public function getPurpose()
    {
        return $this->purpose;
    }

    public function getTicketBalance()
    {
        return $this->ticket_balance;
    }

    public function getTransactionBalance()
    {
        return $this->transaction_balance;
    }

    /**
     * @return mixed
     */
    public function getBudgetId()
    {
        return $this->budgetId;
    }
}
