<?php

namespace DDD\Domain\Booking;

class ResId {
    protected $id;
    protected $res_number;
    protected $apartment_id;
    protected $apartment_id_assigned;
    protected $apartment_id_origin;
    protected $customer_id;
    protected $guestEmail;
    protected $guestFirstName;
    protected $guestLastName;
    protected $status;
    protected $date_from;
    protected $date_to;
    protected $funds_confirmed;
    protected $cancelation_date;
    protected $apartment_status;

    public function exchangeArray($data)
    {
        $this->id = (isset($data['id'])) ? $data['id'] : null;
        $this->res_number = (isset($data['res_number'])) ? $data['res_number'] : null;
        $this->customer_id = (isset($data['customer_id'])) ? $data['customer_id'] : null;
        $this->guestEmail = (isset($data['guest_email'])) ? $data['guest_email'] : null;
        $this->apartment_id = (isset($data['apartment_id_assigned'])) ? $data['apartment_id_assigned'] : null;
        $this->apartment_id_assigned = (isset($data['apartment_id_assigned'])) ? $data['apartment_id_assigned'] : null;
        $this->apartment_id_origin = (isset($data['apartment_id_origin'])) ? $data['apartment_id_origin'] : null;
        $this->guestFirstName = (isset($data['guest_first_name'])) ? $data['guest_first_name'] : null;
        $this->guestLastName = (isset($data['guest_last_name'])) ? $data['guest_last_name'] : null;
        $this->status = (isset($data['status'])) ? $data['status'] : null;
        $this->date_from = (isset($data['date_from'])) ? $data['date_from'] : null;
        $this->date_to = (isset($data['date_to'])) ? $data['date_to'] : null;
        $this->funds_confirmed = (isset($data['funds_confirmed'])) ? $data['funds_confirmed'] : null;
        $this->cancelation_date = (isset($data['cancelation_date'])) ? $data['cancelation_date'] : null;
        $this->apartment_status = (isset($data['apartment_status'])) ? $data['apartment_status'] : null;
    }

    public function getGuestEmail() {
        return $this->guestEmail;
    }

    public function getCustomerId() {
        return $this->customer_id;
    }

    public function getApartmentStatus() {
        return $this->apartment_status;
    }

    public function getCancelationDate() {
        return $this->cancelation_date;
    }

	public function getResNumber() {
		return $this->res_number;
	}

	public function getFundsConfirmed() {
		return $this->funds_confirmed;
	}

    public function getId() {
        return $this->id;
    }

	public function getApartmentId() {
		return $this->apartment_id;
	}

	public function getApartmentIdAssigned() {
		return $this->apartment_id_assigned;
	}

	public function getApartmentIdOrigin() {
		return $this->apartment_id_origin;
	}

	public function getDateFrom() {
		return $this->date_from;
	}

	public function getDateTo() {
		return $this->date_to;
	}

	public function getGuestFirstName() {
		return $this->guestFirstName;
	}

	public function getGuestLastName() {
		return $this->guestLastName;
	}

	public function getStatus() {
		return $this->status;
	}
}
