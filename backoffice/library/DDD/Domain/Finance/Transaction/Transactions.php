<?php

namespace DDD\Domain\Finance\Transaction;

class Transactions
{
    protected $id;
    protected $account_id;
    protected $status_id;
    protected $type_id;
    protected $currency_id;
    protected $date;
    protected $amount;
    protected $transactor_type;
    protected $transactor_id;

    public function exchangeArray($data)
    {
        $this->id = (isset($data['id'])) ? $data['id'] : null;
        $this->account_id = (isset($data['account_id'])) ? $data['account_id'] : null;
        $this->status_id = (isset($data['status_id'])) ? $data['status_id'] : null;
        $this->type_id = (isset($data['type_id'])) ? $data['type_id'] : null;
        $this->currency_id = (isset($data['currency_id'])) ? $data['currency_id'] : null;
        $this->date = (isset($data['date'])) ? $data['date'] : null;
        $this->amount = (isset($data['amount'])) ? $data['amount'] : null;
        $this->transactor_type = (isset($data['transactor_type'])) ? $data['transactor_type'] : null;
        $this->transactor_id = (isset($data['transactor_id'])) ? $data['transactor_id'] : null;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getAccountId()
    {
        return $this->account_id;
    }

    public function getStatusId()
    {
        return $this->status_id;
    }

    public function getTypeId()
    {
        return $this->type_id;
    }

    public function getCurrencyId()
    {
        return $this->currency_id;
    }

    public function getAmount()
    {
        return $this->amount;
    }

    public function getDate()
    {
        return $this->date;
    }

    public function getTransactorId()
    {
        return $this->transactor_id;
    }

    public function getTransactorType()
    {
        return $this->transactor_type;
    }
}
