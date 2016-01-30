<?php

namespace DDD\Domain\Apartel\Inventory;

class Inventory
{
    protected $id;
    protected $apartel_id;
    protected $apartel_type_id;
    protected $rate_id;
    protected $date;
    protected $availability;
    protected $price;
    protected $is_changed;
    protected $is_booked;

    public function exchangeArray($data)
    {
        $this->id = (isset($data['id'])) ? $data['id'] : null;
        $this->apartel_id = (isset($data['apartel_id'])) ? $data['apartel_id'] : null;
        $this->apartel_type_id = (isset($data['apartel_type_id'])) ? $data['apartel_type_id'] : null;
        $this->rate_id = (isset($data['rate_id'])) ? $data['rate_id'] : null;
        $this->date = (isset($data['date'])) ? $data['date'] : null;
        $this->availability = (isset($data['availability'])) ? $data['availability'] : null;
        $this->price = (isset($data['price'])) ? $data['price'] : null;
        $this->is_changed = (isset($data['is_changed'])) ? $data['is_changed'] : null;
        $this->is_booked = (isset($data['is_booked'])) ? $data['is_booked'] : null;
    }

    /**
     * @return mixed
     */
    public function getApartelId()
    {
        return $this->apartel_id;
    }

    /**
     * @return mixed
     */
    public function getApartelTypeId()
    {
        return $this->apartel_type_id;
    }

    /**
     * @return mixed
     */
    public function getAvailability()
    {
        return $this->availability;
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
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return mixed
     */
    public function getIsBooked()
    {
        return $this->is_booked;
    }

    /**
     * @return mixed
     */
    public function getIsChanged()
    {
        return $this->is_changed;
    }

    /**
     * @return mixed
     */
    public function getPrice()
    {
        return $this->price;
    }

    /**
     * @return mixed
     */
    public function getRateId()
    {
        return $this->rate_id;
    }
}
