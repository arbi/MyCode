<?php

namespace DDD\Domain\Apartment\Rate;

class CublistRate
{
	
    private $id;
    private $date;
    private $cubilis_id;
    private $ra_date;
    private $ra_price;
    private $ra_availability;
    private $cubilis_room_id;
   
    public function exchangeArray($data) {
        $this->id	= (isset($data['id'])) ? $data['id'] : null;
        $this->date = (isset($data['date'])) ? $data['date'] : null;
        $this->cubilis_id = (isset($data['cubilis_id'])) ? $data['cubilis_id'] : null;
        $this->ra_date = (isset($data['ra_date'])) ? $data['ra_date'] : null;
        $this->ra_price = (isset($data['ra_price'])) ? $data['ra_price'] : null;
        $this->ra_availability = (isset($data['ra_availability'])) ? $data['ra_availability'] : null;
        $this->cubilis_room_id = (isset($data['cubilis_room_id'])) ? $data['cubilis_room_id'] : null;
    }
    
	public function getCubilis_room_id()
    {
		return $this->cubilis_room_id;
	}
    
	public function getRa_availability()
    {
		return $this->ra_availability;
	}
    
	public function getRa_price() {
		return $this->ra_price;
	}
    
	public function getRa_date() {
		return $this->ra_date;
	}
    
	public function getCubilis_id() {
		return $this->cubilis_id;
	}
    
	public function getId() {
		return $this->id;
	}
	
	public function getDate() {
		return $this->date;
	}
}