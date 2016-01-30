<?php

namespace DDD\Domain\Parking\Spot;

class Inventory
{
    /**
     * @var int|null $id
     */
    private $id;

    /**
     * @var int|null $spotId
     */
    private $spotId;

    /**
     * @var string|null $date
     */
    private $date;

    /**
     * @var int|null $availability
     */
    private $availability;

    /**
     * @var float|null $price
     */
    private $price;

	/**
     * @param array $data
     */
    public function exchangeArray($data)
    {
        $this->id             = (isset($data['id'])) ? $data['id'] : null;
        $this->spotId         = (isset($data['spot_id'])) ? $data['spot_id'] : null;
        $this->date           = (isset($data['date'])) ? $data['date'] : null;
        $this->price          = (isset($data['price'])) ? $data['price'] : null;
        $this->availability   = (isset($data['availability'])) ? $data['availability'] : null;
    }

    /**
     * @return int|null
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return int
     */
    public function getSpotId()
    {
        return $this->spotId;
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
    public function getAvailability()
    {
        return $this->availability;
    }

    /**
     * @return float|null
     */
    public function getPrice()
    {
        return $this->price;
    }
}