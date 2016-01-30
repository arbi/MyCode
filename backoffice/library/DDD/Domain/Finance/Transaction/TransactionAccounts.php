<?php

namespace DDD\Domain\Finance\Transaction;

class TransactionAccounts
{
    protected $id;
    protected $type;
    protected $holder_id;

    public function exchangeArray($data)
    {
        $this->id = (isset($data['id'])) ? $data['id'] : null;
        $this->type = (isset($data['type'])) ? $data['type'] : null;
        $this->holder_id = (isset($data['holder_id'])) ? $data['holder_id'] : null;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getType()
    {
        return $this->type;
    }

    public function getHolderId()
    {
        return $this->holder_id;
    }
}
