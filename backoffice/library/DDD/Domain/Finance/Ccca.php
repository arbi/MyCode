<?php

namespace DDD\Domain\Finance;

class Ccca
{
    protected $id;
    protected $reservationId;
    protected $ccId;
    protected $pageToken;
    protected $status;
    protected $createdDate;
    protected $amount;

    public function exchangeArray($data)
    {
        $this->id            = (isset($data['id'])) ? $data['id'] : null;
        $this->reservationId = (isset($data['reservation_id'])) ? $data['reservation_id'] : null;
        $this->ccId          = (isset($data['cc_id'])) ? $data['cc_id'] : null;
        $this->pageToken     = (isset($data['page_token'])) ? $data['page_token'] : null;
        $this->status        = (isset($data['status'])) ? $data['status'] : null;
        $this->createdDate   = (isset($data['created_date'])) ? $data['created_date'] : null;
        $this->amount        = (isset($data['amount'])) ? $data['amount'] : null;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getReservationId()
    {
        return $this->reservationId;
    }

    public function getCcId()
    {
        return $this->ccId;
    }

    public function getPageToken()
    {
        return $this->pageToken;
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function getCreatedDate()
    {
        return $this->createdDate;
    }

    public function getAmount()
    {
        return $this->amount;
    }
}
