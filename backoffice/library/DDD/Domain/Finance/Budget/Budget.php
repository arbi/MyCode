<?php

namespace DDD\Domain\Finance\Budget;

class Budget
{
    protected $id;
    protected $name;
    protected $categoryId;
    protected $from;
    protected $to;
    protected $amount;
    protected $balance;
    protected $description;
    protected $statusId;
    protected $inactive;
    protected $frozen;
    protected $userId;

    public function exchangeArray($data)
    {
        $this->id          = (isset($data['id'])) ? $data['id'] : null;
        $this->name        = (isset($data['name'])) ? $data['name'] : null;
        $this->categoryId  = (isset($data['category_id'])) ? $data['category_id'] : null;
        $this->from        = (isset($data['from'])) ? $data['from'] : null;
        $this->to          = (isset($data['to'])) ? $data['to'] : null;
        $this->amount      = (isset($data['amount'])) ? $data['amount'] : null;
        $this->balance     = (isset($data['balance'])) ? $data['balance'] : null;
        $this->description = (isset($data['description'])) ? $data['description'] : null;
        $this->statusId    = (isset($data['status_id'])) ? $data['status_id'] : null;
        $this->inactive    = (isset($data['inactive'])) ? $data['inactive'] : null;
        $this->frozen      = (isset($data['frozen'])) ? $data['inactive'] : null;
        $this->userId      = (isset($data['user_id'])) ? $data['user_id'] : null;
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
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return mixed
     */
    public function getCategoryId()
    {
        return $this->categoryId;
    }

    /**
     * @return mixed
     */
    public function getFrom()
    {
        return $this->from;
    }

    /**
     * @return mixed
     */
    public function getTo()
    {
        return $this->to;
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
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @return mixed
     */
    public function getStatusId()
    {
        return $this->statusId;
    }

    /**
     * @return mixed
     */
    public function getInactive()
    {
        return $this->inactive;
    }

    /**
     * @return mixed
     */
    public function getFrozen()
    {
        return $this->frozen;
    }

    /**
     * @return mixed
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * @return mixed
     */
    public function getBalance()
    {
        return $this->balance;
    }
}
