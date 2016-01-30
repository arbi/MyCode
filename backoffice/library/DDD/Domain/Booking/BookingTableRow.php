<?php

namespace DDD\Domain\Booking;

/**
 * Domain class to use in all cases when we need basic information about reservation not all row.
 * For example user's table.
 * @final
 * @category core
 * @package domain
 *
 * @author Tigran Petrosyan
 */
class BookingTableRow
{
    protected $id;
    protected $resNumber;
    protected $affiliateID;
    protected $status;
    protected $reservationDate;
    protected $productName;
    protected $guestFirstName;
    protected $guestLastName;
    protected $arrivalDate;
    protected $departureDate;
    protected $pax;
    protected $rateName;
    protected $guest_balance;
    protected $guestCurrencyCode;
    protected $overbooking;
    protected $locked;
    protected $occupancy;
    protected $arrivalStatus;

    public function exchangeArray($data) {
        $this->id 				= (isset($data['id'])) ? $data['id'] : null;
        $this->resNumber 		= (isset($data['res_number'])) ? $data['res_number'] : null;
        $this->affiliateID 		= (isset($data['partner_id'])) ? $data['partner_id'] : null;
        $this->status 			= (isset($data['status'])) ? $data['status'] : null;
        $this->reservationDate 	= (isset($data['timestamp'])) ? $data['timestamp'] : null;
        $this->productName 		= (isset($data['acc_name'])) ? $data['acc_name'] : null;
        $this->guestFirstName 	= (isset($data['guest_first_name'])) ? $data['guest_first_name'] : null;
        $this->guestLastName 	= (isset($data['guest_last_name'])) ? $data['guest_last_name'] : null;
        $this->arrivalDate 		= (isset($data['date_from'])) ? $data['date_from'] : null;
        $this->departureDate	= (isset($data['date_to'])) ? $data['date_to'] : null;
        $this->pax 				= (isset($data['pax'])) ? $data['pax'] : null;
        $this->rateName 		= (isset($data['rate_name'])) ? $data['rate_name'] : null;
        $this->guest_balance 	= (isset($data['guest_balance'])) ? $data['guest_balance'] : null;
        $this->guestCurrencyCode= (isset($data['guest_currency_code'])) ? $data['guest_currency_code'] : null;
        $this->locked		    = (isset($data['locked'])) ? $data['locked'] : null;
        $this->occupancy	    = (isset($data['occupancy'])) ? $data['occupancy'] : null;
        $this->overbooking      = (isset($data['overbooking_status'])) ? $data['overbooking_status'] : null;
        $this->arrivalStatus    = (isset($data['arrival_status'])) ? $data['arrival_status'] : null;

    }
    public function getId() {
        return $this->id;
    }

    public function getReservationNumber()
    {
    	return $this->resNumber;
    }

    public function getAffiliateID() {
    	return $this->affiliateID;
    }

    public function getStatus() {
    	return $this->status;
    }

    public function getReservationDate() {
    	return $this->reservationDate;
    }

    public function getProductName() {
    	return $this->productName;
    }

    public function getGuestFullName() {
    	return $this->guestFirstName . ' ' . $this->guestLastName;
    }

    public function getArrivalDate() {
    	return $this->arrivalDate;
    }

    public function getDepartureDate() {
    	return $this->departureDate;
    }

    public function getPAX() {
    	return $this->pax;
    }

    public function getRateName() {
    	return $this->rateName;
    }

    public function getGuestBalance() {
    	return $this->guest_balance . ' ' . $this->guestCurrencyCode;
    }

    public function getGuestCurrencyCode() {
    	return $this->guestCurrencyCode;
    }

    public function isLocked()
    {
        return (int) $this->locked;
    }

    public function getOccupancy()
    {
        return $this->occupancy;
    }

    public function getOverbooking() {
    	return $this->overbooking;
    }

    public function getArrivalStatus()
    {
        return $this->arrivalStatus;
    }
}
