<?php

namespace DDD\Domain\Apartment\Room;

/**
 * Class Cubilis
 * @package DDD\Domain\Apartment\Room
 */
class Cubilis {
    protected $id;
    protected $apartment_id;
    protected $name;
	protected $active;
	protected $cubilis_id;
	protected $max_capacity;

    public function exchangeArray($data) {
        $this->id = isset($data['id']) ? $data['id'] : null;
        $this->apartment_id = isset($data['apartment_id']) ? $data['apartment_id'] : null;
        $this->name = isset($data['name']) ? $data['name'] : null;
	    $this->active = isset($data['active']) ? $data['active'] : null;
	    $this->cubilis_id = isset($data['cubilis_id']) ? $data['cubilis_id'] : null;
        $this->max_capacity = isset($data['max_capacity']) ? $data['max_capacity'] : null;
    }

	public function getId() {
		return $this->id;
	}

	public function getApartmentId() {
		return $this->apartment_id;
	}

	public function getName() {
		return $this->name;
	}

	public function getActive() {
		return $this->active;
	}

	public function getCubilisId() {
		return $this->cubilis_id;
	}

	public function getMaxCapacity() {
		return $this->max_capacity;
	}
}
