<?php

namespace DDD\Domain\Apartment\Inventory;

/**
 * Class RateAvailabilityComplete
 * @package DDD\Domain\Apartment\Inventory
 */
class RateAvailabilityComplete {

    private $cubilis_rate_id;
    private $cubilis_room_id;

    /**
     * @var int
     */
    private $availability;

    private $capacity;
    private $min_stay;
    private $max_stay;
    private $date;
    private $price;
    protected $product_id;

    public function exchangeArray($data)
    {
        $this->cubilis_rate_id = (isset($data['cubilis_rate_id'])) ? $data['cubilis_rate_id'] : null;
        $this->cubilis_room_id = (isset($data['cubilis_room_id'])) ? $data['cubilis_room_id'] : null;
	    $this->availability = (isset($data['availability'])) ? $data['availability'] : null;
	    $this->capacity = (isset($data['capacity'])) ? $data['capacity'] : null;
	    $this->min_stay = (isset($data['min_stay'])) ? $data['min_stay'] : null;
	    $this->max_stay = (isset($data['max_stay'])) ? $data['max_stay'] : null;
	    $this->date = (isset($data['date'])) ? $data['date'] : null;
	    $this->price = (isset($data['price'])) ? $data['price'] : null;
	    $this->product_id = (isset($data['product_id'])) ? $data['product_id'] : null;
    }

	public function getProductId()
    {
		return $this->product_id;
	}

	public function getMinStay()
    {
		return $this->min_stay;
	}

    /**
     * @return int
     */
    public function getAvailability()
    {
		return $this->availability;
	}

	public function getCapacity()
    {
		return $this->capacity;
	}

	public function getDate()
    {
		return $this->date;
	}

	public function getMaxStay()
    {
		return $this->max_stay;
	}

	public function getPrice()
    {
		return $this->price;
	}

	public function getCubilisRateId()
    {
		return $this->cubilis_rate_id;
	}

	public function getCubilisRoomId()
    {
		return $this->cubilis_room_id;
	}
}
