<?php

namespace DDD\Domain\Apartment\ProductRate;

/**
 * Class Cubilis
 * @package DDD\Domain\Apartment\ProductRate
 */
class Cubilis {
    protected $id;
    protected $apartment_id;
    protected $room_id;
    protected $name;
    protected $capacity;
    protected $active;
    protected $cubilis_id;

    public function exchangeArray($data) {
        $this->id = isset($data['id']) ? $data['id'] : null;
        $this->apartment_id = isset($data['apartment_id']) ? $data['apartment_id'] : null;
        $this->room_id = isset($data['room_id']) ? $data['room_id'] : null;
        $this->capacity = isset($data['capacity']) ? $data['capacity'] : null;
        $this->name = isset($data['name']) ? $data['name'] : null;
        $this->active = isset($data['active']) ? $data['active'] : null;
        $this->cubilis_id = isset($data['cubilis_id']) ? $data['cubilis_id'] : null;
    }

	public function getId() {
		return $this->id;
	}

	public function getApartmentId() {
		return $this->apartment_id;
	}

	public function getRoomId() {
		return $this->room_id;
	}

	public function getName() {
		return $this->name;
	}

	public function getCapacity() {
		return $this->capacity;
	}

	public function getActive() {
		return $this->active;
	}

	public function getCubilisId() {
		return $this->cubilis_id;
	}
}
