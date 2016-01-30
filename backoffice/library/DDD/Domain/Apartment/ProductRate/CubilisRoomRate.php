<?php

namespace DDD\Domain\Apartment\ProductRate;

/**
 * Class Cubilis
 * @package DDD\Domain\Apartment\ProductRate
 */
class CubilisRoomRate {
    protected $apartment_id;
    protected $room_id;
    protected $rate_id;
    protected $rate_name;
	protected $cubilis_room_id;
	protected $cubilis_rate_id;
	protected $rate_active;
	protected $room_active;

    public function exchangeArray($data) {
        $this->apartment_id = isset($data['apartment_id']) ? $data['apartment_id'] : null;
        $this->room_id = isset($data['room_id']) ? $data['room_id'] : null;
        $this->rate_id = isset($data['rate_id']) ? $data['rate_id'] : null;
        $this->rate_name = isset($data['rate_name']) ? $data['rate_name'] : null;
        $this->cubilis_room_id = isset($data['cubilis_room_id']) ? $data['cubilis_room_id'] : null;
        $this->cubilis_rate_id = isset($data['cubilis_rate_id']) ? $data['cubilis_rate_id'] : null;
        $this->rate_active = isset($data['rate_active']) ? $data['rate_active'] : null;
        $this->room_active = isset($data['room_active']) ? $data['room_active'] : null;
    }

	public function getApartmentId() {
		return $this->apartment_id;
	}

	public function getRoomId() {
		return $this->room_id;
	}

	public function getRateId() {
		return $this->rate_id;
	}

	public function getRateName() {
		return $this->rate_name;
	}

	public function getCubilisRoomId() {
		return $this->cubilis_room_id;
	}

	public function getCubilisRateId() {
		return $this->cubilis_rate_id;
	}

	public function getRateActive() {
		return $this->rate_active;
	}

	public function getRoomActive() {
		return $this->room_active;
	}
}
