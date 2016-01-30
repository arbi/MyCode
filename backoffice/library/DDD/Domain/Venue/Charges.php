<?php

namespace DDD\Domain\Venue;


class Charges
{
    protected $id;
    protected $venueId;
    protected $creatorId;
    protected $dateCreatedServer;
    protected $dateCreatedClient;
    protected $status;
    protected $orderStatus;
    protected $description;
    protected $chargedUserId;
    protected $amount;
    protected $isArchived;

    protected $venueName;
    protected $currencyId;
    protected $currencyCode;

    public function exchangeArray($data)
    {
        $this->id                   = (isset($data['id'])) ? $data['id'] : null;
        $this->venueId              = (isset($data['venue_id'])) ? $data['venue_id'] : null;
        $this->creatorId            = (isset($data['creator_id'])) ? $data['creator_id'] : null;
        $this->dateCreatedServer    = (isset($data['date_created_server'])) ? $data['date_created_server'] : null;
        $this->dateCreatedClient    = (isset($data['date_created_client'])) ? $data['date_created_client'] : null;
        $this->status               = (isset($data['status'])) ? $data['status'] : null;
        $this->orderStatus          = (isset($data['order_status'])) ? $data['order_status'] : null;
        $this->description          = (isset($data['description'])) ? $data['description'] : null;
        $this->chargedUserId        = (isset($data['charged_user_id'])) ? $data['charged_user_id'] : null;
        $this->amount               = (isset($data['amount'])) ? $data['amount'] : null;
        $this->isArchived           = (isset($data['is_archived'])) ? $data['is_archived'] : null;

        $this->venueName            = (isset($data['venue_name'])) ? $data['venue_name'] : null;
        $this->currencyId           = (isset($data['currency_id'])) ? $data['currency_id'] : null;
        $this->currencyCode         = (isset($data['currency_code'])) ? $data['currency_code'] : null;
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
    public function getVenueId()
    {
        return $this->venueId;
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
    public function getDateCreatedServer()
    {
        return $this->dateCreatedServer;
    }

    /**
     * @return mixed
     */
    public function getDateCreatedClient()
    {
        return $this->dateCreatedClient;
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
    public function getOrderStatus()
    {
        return $this->orderStatus;
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
    public function getChargedUserId()
    {
        return $this->chargedUserId;
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
    public function getIsArchived()
    {
        return $this->isArchived;
    }

    /**
     * @return mixed
     */
    public function getVenueName()
    {
        return $this->venueName;
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
    public function getCurrencyCode()
    {
        return $this->currencyCode;
    }
}
