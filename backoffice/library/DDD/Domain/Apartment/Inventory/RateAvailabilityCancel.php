<?php

namespace DDD\Domain\Apartment\Inventory;

class RateAvailabilityCancel
{

    private $id;
   
    private $rateID;
   
    private $date;
    
    private $availability;

    private $price;
    
    private $roomID;

    /**
     * @var int
     */
    private $apartmentId;
    
    private $cubilis_room_id;
    
    private $cubilis_rate_id;

	
    public function exchangeArray($data) {
        $this->id			= (isset($data['id'])) ? $data['id'] : null;
        $this->rateID		= (isset($data['rate_id'])) ? $data['rate_id'] : null;
        $this->date			= (isset($data['date'])) ? $data['date'] : null;
        $this->availability	= (isset($data['availability'])) ? $data['availability'] : null;
        $this->price		= (isset($data['price'])) ? $data['price'] : null;
        $this->roomID		= (isset($data['room_id'])) ? $data['room_id'] : null;
        $this->apartmentId	= (isset($data['apartment_id'])) ? $data['apartment_id'] : null;
        $this->cubilis_room_id		= (isset($data['cubilis_room_id'])) ? $data['cubilis_room_id'] : null;
        $this->cubilis_rate_id		= (isset($data['cubilis_rate_id'])) ? $data['cubilis_rate_id'] : null;
    }
    
	public function getCubilis_rate_id() {
		return $this->cubilis_rate_id;
	}
    
	public function getID() {
		return $this->id;
	}
    
	public function getCubilis_room_id() {
		return $this->cubilis_room_id;
	}

    /**
     * @return int
     */
    public function getApartmentId()
    {
		return $this->apartmentId;
	}

	public function getRateID() {
		return $this->rateID;
	}

	public function getRoomID() {
		return $this->roomID;
	}
	
	public function getDate() {
		return $this->date;
	}
	
	public function getAvailability() {
		return $this->availability;
	}
	
	public function getPrice() {
		return $this->price;
	}
}