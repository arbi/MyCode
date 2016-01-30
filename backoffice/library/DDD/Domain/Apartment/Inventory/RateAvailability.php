<?php

namespace DDD\Domain\Apartment\Inventory;

/**
 * Apartment Rate Availability Domain class to hold rate availability and price for single day
 * @author Tigran Petrosyan
 * @final
 *
 * @package core
 * @subpackage core/domain
 */
final class RateAvailability {
    private $id;
    private $rateID;
    private $date;

    /**
     * @var int
     */
    private $availability;

    private $price;
    private $roomID;

    /**
     * @var int
     */
    private $apartmentId;
    private $cubilis_room_id;
    private $is_changed;
    private $is_lock_price;

    public function exchangeArray($data)
    {
        $this->id			= (isset($data['id'])) ? $data['id'] : null;
        $this->rateID		= (isset($data['rate_id'])) ? $data['rate_id'] : null;
        $this->date			= (isset($data['date'])) ? $data['date'] : null;
        $this->availability	= (isset($data['availability'])) ? $data['availability'] : null;
        $this->price		= (isset($data['price'])) ? $data['price'] : null;
        $this->roomID		= (isset($data['room_id'])) ? $data['room_id'] : null;
        $this->apartmentId	= (isset($data['apartment_id'])) ? $data['apartment_id'] : null;
        $this->cubilis_room_id = (isset($data['cubilis_room_id'])) ? $data['cubilis_room_id'] : null;
        $this->is_changed	   = (isset($data['is_changed'])) ? $data['is_changed'] : null;
        $this->is_lock_price	   = (isset($data['is_lock_price'])) ? $data['is_lock_price'] : null;
    }

	public function getID()
    {
		return $this->id;
	}

	public function getCubilis_room_id()
    {
		return $this->cubilis_room_id;
	}

    /**
     * @return int
     */
    public function getApartmentId()
    {
		return $this->apartmentId;
	}

	public function getRateID()
    {
		return $this->rateID;
	}

	public function getRoomID()
    {
		return $this->roomID;
	}

	public function getDate()
    {
		return $this->date;
	}

    /**
     * @return int
     */
    public function getAvailability()
    {
		return $this->availability;
	}

	public function getPrice()
    {
		return $this->price;
	}

	public function setPrice($value)
    {
		$this->price = $value;
	}

	public function getIsChanged()
    {
		return $this->is_changed;
	}

	public function getIsLockPrice()
    {
		return $this->is_lock_price;
	}
}
