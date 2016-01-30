<?php

namespace DDD\Domain\Apartment\ProductRate;

/**
 * Class Cubilis
 * @package DDD\Domain\Apartment\ProductRate
 */
class WithStatus {
    protected $id;
    protected $apartment_id;
    protected $room_id;
    protected $default_availability;
    protected $week_price;
    protected $weekend_price;
    protected $type;
    protected $cubilis_id;
    protected $name;

    public function exchangeArray($data) {
        $this->id                   = isset($data['id']) ? $data['id'] : null;
        $this->apartment_id         = isset($data['apartment_id']) ? $data['apartment_id'] : null;
        $this->room_id              = isset($data['room_id']) ? $data['room_id'] : null;
        $this->default_availability = isset($data['default_availability']) ? $data['default_availability'] : null;
        $this->week_price           = isset($data['week_price']) ? $data['week_price'] : null;
        $this->weekend_price        = isset($data['weekend_price']) ? $data['weekend_price'] : null;
        $this->type                 = isset($data['type']) ? $data['type'] : null;
        $this->cubilis_id           = isset($data['cubilis_id']) ? $data['cubilis_id'] : null;
        $this->name                 = isset($data['name']) ? $data['name'] : null;
    }

	public function getApartmentId() {
		return $this->apartment_id;
	}

	public function getDefaultAvailability() {
		return $this->default_availability;
	}

	public function getId() {
		return $this->id;
	}

	public function getRoomId() {
		return $this->room_id;
	}

	public function getWeekPrice() {
		return $this->week_price;
	}

	public function getWeekendPrice() {
		return $this->weekend_price;
	}

    public function getType() {
        return $this->type;
    }

    public function getCubilisId() {
        return $this->cubilis_id;
    }

    public function getName() {
        return $this->name;
    }
}
