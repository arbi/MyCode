<?php

namespace DDD\Domain\Finance\Espm;

class Espm
{
    protected $id;
    protected $amount;
    protected $currencyId;
    protected $accountId;
    protected $status;
    protected $type;
    protected $reason;
    protected $creatorId;
    protected $createdDate;
    protected $date;
    protected $isArchived;


    public function exchangeArray($data)
    {
        $this->id = (isset($data['id'])) ? $data['id'] : null;
        $this->amount = (isset($data['amount'])) ? $data['amount'] : null;
        $this->currencyId = (isset($data['currency_id'])) ? $data['currency_id'] : null;
        $this->accountId = (isset($data['account_id'])) ? $data['account_id'] : null;
        $this->status = (isset($data['status'])) ? $data['status'] : null;
        $this->type = (isset($data['type'])) ? $data['type'] : null;
        $this->reason = (isset($data['reason'])) ? $data['reason'] : null;
        $this->creatorId = (isset($data['creator_id'])) ? $data['creator_id'] : null;
        $this->createdDate = (isset($data['created_date'])) ? $data['created_date'] : null;
        $this->date = (isset($data['date'])) ? $data['date'] : null;
        $this->isArchived = (isset($data['is_archived'])) ? $data['is_archived'] : null;
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return mixed
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * @return mixed
     */
    public function getCurrencyId()
    {
        return $this->currencyId;
    }

    /**
     * @return mixed
     */
    public function getAccountId()
    {
        return $this->accountId;
    }

    /**
     * @return mixed
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @return mixed
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return mixed
     */
    public function getReason()
    {
        return $this->reason;
    }

    /**
     * @return mixed
     */
    public function getCreatorId()
    {
        return $this->creatorId;
    }

    /**
     * @return mixed
     */
    public function getCreatedDate()
    {
        return $this->createdDate;
    }

    /**
     * @return mixed
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * @return mixed
     */
    public function getIsArchived()
    {
        return $this->isArchived;
    }


}
