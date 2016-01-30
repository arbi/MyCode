<?php

namespace DDD\Domain\Parking;

final class Spot
{
    /**
     * @var int $id
     */
    private $id;

    /**
     * @var int $lotId
     */
    private $lotId;

    /**
     * @var string|null $unit
     */
    private $unit;

    /**
     * @var float|null $price
     */
    private $price;

    /**
     * @var string|null $color
     */
    private $color;

    private $permitId;
    private $lotName;

	/**
     *
     * @param array $data
     */
    public function exchangeArray($data)
    {
        $this->id         = $data['id'];
        $this->lotId      = $data['lot_id'];
        $this->unit       = (isset($data['unit'])) ? $data['unit'] : null;
        $this->price      = (isset($data['price'])) ? $data['price'] : null;
        $this->permitId   = (isset($data['permit_id'])) ? $data['permit_id'] : null;
        $this->lotName    = (isset($data['lot_name'])) ? $data['lot_name'] : null;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return int
     */
    public function getLotId()
    {
        return $this->lotId;
    }

    /**
     * @return null|string
     */
    public function getUnit()
    {
        return $this->unit;
    }

    /**
     * @return float|null
     */
    public function getPrice()
    {
        return $this->price;
    }

    /**
     * @return null|string
     */
    public function getColor()
    {
        return $this->color;
    }

    public function getPermitId()
    {
        return $this->permitId;
    }

    public function getLotName()
    {
        return $this->lotName;
    }
}
