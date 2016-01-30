<?php

namespace DDD\Domain\Queue;

class InventorySynchronizationQueue
{
    /**
     * @var int|null $id
     */
    protected $id;

    /**
     * @var int|null $rateId
     */
    protected $rateId;

    /**
     * @var string|null $rateName
     */
    protected $rateName;

    /**
     * @var string|null $date
     */
    protected $date;

    /**
     * @var int|null $attempts
     */
    protected $attempts;

    /**
     * @var string|null $additionDate
     */
    protected $additionDate;

    /**
     * @var string|null $updateDate
     */
    protected $updateDate;

    /**
     * @var int|null $cubilisRoomId
     */
    protected $cubilisRoomId;

    /**
     * @var int|null $cubilisRateId
     */
    protected $cubilisRateId;

    /**
     * @var int|null $availability
     */
    protected $availability;

    /**
     * @var int|null $price
     */
    protected $price;

    /**
     * @var int|null $apartmentId
     */
    protected $apartmentId;

    public function exchangeArray($data)
    {
        $this->id = (isset($data['id'])) ? $data['id'] : null;
        $this->rateId = (isset($data['rate_id'])) ? $data['rate_id'] : null;
        $this->rateName = (isset($data['rate_name'])) ? $data['rate_name'] : null;
        $this->date = (isset($data['date'])) ? $data['date'] : null;
        $this->attempts = (isset($data['attempts'])) ? $data['attempts'] : null;
        $this->additionDate = (isset($data['addition_date'])) ? $data['addition_date'] : null;
        $this->updateDate = (isset($data['update_date'])) ? $data['update_date'] : null;
        $this->availability = (isset($data['availability'])) ? $data['availability'] : null;
        $this->price = (isset($data['price'])) ? $data['price'] : null;
        $this->cubilisRoomId = (isset($data['cubilis_room_id'])) ? $data['cubilis_room_id'] : null;
        $this->cubilisRateId = (isset($data['cubilis_rate_id'])) ? $data['cubilis_rate_id'] : null;
        $this->apartmentId = (isset($data['entity_id'])) ? $data['entity_id'] : null;
    }

    /**
     * @return null|string
     */
    public function getAdditionDate()
    {
        return $this->additionDate;
    }

    /**
     * @return int|null
     */
    public function getApartmentId()
    {
        return $this->apartmentId;
    }

    /**
     * @return int|null
     */
    public function getAttempts()
    {
        return $this->attempts;
    }

    /**
     * @return int|null
     */
    public function getAvailability()
    {
        return $this->availability;
    }

    /**
     * @return int|null
     */
    public function getCubilisRateId()
    {
        return $this->cubilisRateId;
    }

    /**
     * @return int|null
     */
    public function getCubilisRoomId()
    {
        return $this->cubilisRoomId;
    }

    /**
     * @return null|string
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * @return int|null
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return int|null
     */
    public function getPrice()
    {
        return $this->price;
    }

    /**
     * @return int|null
     */
    public function getRateId()
    {
        return $this->rateId;
    }

    /**
     * @return null|string
     */
    public function getRateName()
    {
        return $this->rateName;
    }

    /**
     * @return null|string
     */
    public function getUpdateDate()
    {
        return $this->updateDate;
    }
}
