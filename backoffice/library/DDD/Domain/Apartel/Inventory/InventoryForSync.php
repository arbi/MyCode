<?php

namespace DDD\Domain\Apartel\Inventory;

class InventoryForSync
{
    protected $id;
    protected $cubilis_rate_id;
    protected $cubilis_type_id;
    protected $availability;
    protected $capacity;
    protected $min_stay;
    protected $max_stay;
    protected $date;
    protected $price;
    protected $product_id;

    public function exchangeArray($data)
    {
        $this->id = (isset($data['id'])) ? $data['id'] : null;
        $this->cubilis_rate_id = (isset($data['cubilis_rate_id'])) ? $data['cubilis_rate_id'] : null;
        $this->cubilis_type_id = (isset($data['cubilis_type_id'])) ? $data['cubilis_type_id'] : null;
        $this->availability = (isset($data['availability'])) ? $data['availability'] : null;
        $this->capacity = (isset($data['capacity'])) ? $data['capacity'] : null;
        $this->min_stay = (isset($data['min_stay'])) ? $data['min_stay'] : null;
        $this->max_stay = (isset($data['max_stay'])) ? $data['max_stay'] : null;
        $this->date = (isset($data['date'])) ? $data['date'] : null;
        $this->price = (isset($data['price'])) ? $data['price'] : null;
        $this->product_id = (isset($data['product_id'])) ? $data['product_id'] : null;
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
    public function getCapacity()
    {
        return $this->capacity;
    }

    /**
     * @return mixed
     */
    public function getCubilisRateId()
    {
        return $this->cubilis_rate_id;
    }

    /**
     * @return mixed
     */
    public function getCubilisRoomId()
    {
        return $this->cubilis_type_id;
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
    public function getMaxStay()
    {
        return $this->max_stay;
    }

    /**
     * @return mixed
     */
    public function getMinStay()
    {
        return $this->min_stay;
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
    public function getProductId()
    {
        return $this->product_id;
    }

}
