<?php

namespace DDD\Domain\Apartment\Statistics;

class ForBasicDataBooking
{
    protected $id;
    protected $apartmentCurrencyCode;
    protected $status;
    protected $date_to;
    protected $date_from;
    protected $res_number;
    protected $balance;
    protected $check_charged;
    protected $commission;
    protected $transaction_fee_percent;
    protected $price;
    protected $guestEmail;

    public function exchangeArray($data)
    {
        $this->id                      = (isset($data['id'])) ? $data['id'] : null;
        $this->apartmentCurrencyCode   = (isset($data['apartment_currency_code'])) ? $data['apartment_currency_code'] : null;
        $this->status 				   = (isset($data['status'])) ? $data['status'] : null;
        $this->date_to 			       = (isset($data['date_to'])) ? $data['date_to'] : null;
        $this->date_from 			   = (isset($data['date_from'])) ? $data['date_from'] : null;
        $this->res_number 			   = (isset($data['res_number'])) ? $data['res_number'] : null;
        $this->balance 				   = (isset($data['balance'])) ? $data['balance'] : null;
        $this->check_charged 		   = (isset($data['check_charged'])) ? $data['check_charged'] : null;
        $this->commission 			   = (isset($data['commission'])) ? $data['commission'] : null;
        $this->transaction_fee_percent = (isset($data['transaction_fee_percent'])) ? $data['transaction_fee_percent'] : null;
        $this->price 				   = (isset($data['price'])) ? $data['price'] : null;
        $this->guestEmail 			   = (isset($data['guest_email'])) ? $data['guest_email'] : null;
    }

	public function getPrice () {
		return $this->price;
	}

	public function getTransaction_fee_percent () {
		return $this->transaction_fee_percent;
	}

	public function getCommission () {
		return $this->commission;
	}

	public function getCheck_charged () {
		return $this->check_charged;
	}

	public function getBalance () {
		return $this->balance;
	}

	public function getReservationNumber()
    {
		return $this->res_number;
	}

	public function getDate_from () {
		return $this->date_from;
	}

	public function getDate_to () {
		return $this->date_to;
	}

	public function getStatus () {
		return $this->status;
	}

	public function getId () {
		return $this->id;
	}

	public function getApartmentCurrencyCode() {
		return $this->apartmentCurrencyCode;
	}

	public function getGuestEmail() {
		return $this->guestEmail;
	}

}
