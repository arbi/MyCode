<?php

namespace DDD\Domain\Apartment\Details;

class Sync
{
    private $id;
    private $apartment_id;
    private $cubilis_id;
    private $cubilis_us;
    private $cubilis_pass;
    private $sync_cubilis;

    public function exchangeArray($data) {
        $this->id = (isset($data['id'])) ? $data['id'] : null;
        $this->apartment_id = (isset($data['apartment_id'])) ? $data['apartment_id'] : null;
        $this->cubilis_id = (isset($data['cubilis_id'])) ? $data['cubilis_id'] : null;
        $this->cubilis_us = (isset($data['cubilis_us'])) ? $data['cubilis_us'] : null;
        $this->cubilis_pass = (isset($data['cubilis_pass'])) ? $data['cubilis_pass'] : null;
        $this->sync_cubilis = (isset($data['sync_cubilis'])) ? $data['sync_cubilis'] : null;
    }

	public function getID() {
		return $this->id;
	}

	public function getApartmentId() {
		return $this->apartment_id;
	}

	public function getCubilisId() {
		return $this->cubilis_id;
	}

	public function getCubilisUs() {
		return $this->cubilis_us;
	}

	public function getCubilisPass() {
		return $this->cubilis_pass;
	}

	public function getSync_cubilis() {
		return $this->sync_cubilis;
	}

}
