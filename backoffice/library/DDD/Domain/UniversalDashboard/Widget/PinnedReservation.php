<?php

namespace DDD\Domain\UniversalDashboard\Widget;

class PinnedReservation {

    protected $id;
	protected $userId;
    protected $resNum;
    protected $guestFirstName;
    protected $guestLastName;
    protected $accName;

    public function exchangeArray($data) {
        $this->id = (isset($data['id'])) ?
        	$data['id'] : null;
    	$this->userId = (isset($data['user_id'])) ?
    		$data['user_id'] : null;
        $this->resNum = (isset($data['res_number'])) ?
        	$data['res_number'] : null;
        $this->guestFirstName = (isset($data['guest_first_name'])) ?
        	$data['guest_first_name'] : null;
        $this->guestLastName = (isset($data['guest_last_name'])) ?
        	$data['guest_last_name'] : null;
        $this->accName = (isset($data['acc_name'])) ?
        	$data['acc_name'] : null;
    }

	public function getId() {
		return $this->id;
	}

	public function setId($id) {
		$this->id = $id;
		return $this;
	}

	public function getUserId() {
		return $this->userId;
	}

	public function setUserId($userId) {
		$this->userId = $userId;
		return $this;
	}

	public function getResNum() {
		return $this->resNum;
	}

	public function setResNum($resNum) {
		$this->resNum = $resNum;
		return $this;
	}

	public function getGuestFirstName() {
		return $this->guestFirstName;
	}

	public function getGuestLastName() {
		return $this->guestLastName;
	}

	public function getApartmentName() {
		return $this->accName;
	}
}
