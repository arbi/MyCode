<?php

namespace DDD\Domain\UniversalDashboard\Widget;

/**
 * Domain class to use in Universal Dashboard "Pay To Partner" widget
 * @final
 * @category core
 * @package domain
 * @subpackage widget
 *
 * @author Tigran Petrosyan
 */
class PayToPartner {
    /**
     * Unique reservation id
     * @var string
     */
    protected $resId;

    /**
     * Unique reservation number
     * @var string
     */
    protected $resNumber;

    /**
     * Reservation status
     * @var int
     */
    protected $status;

    /*
     * @var int
     */
    protected $apartelId;

    /**
     * Booking date
     * @var string
     */
    protected $bookingDate;

    /**
     * Departure date
     * @var string
     */
    protected $departureDate;

    /**
     * Apartment id
     * @var int
     */
    protected $apartmentId;

    /**
     * Apartment name
     * @var string
     */
    protected $apartmentName;

    /**
     * Partner id
     * @var string
     */
    protected $partnerId;

    /**
     * Partner name
     * @var string
     */
    protected $partnerName;

    /**
     * Customer balance
     * @var float
     */
    protected $guestBalance;

    /**
     * Partner balance
     * @var float
     */
    protected $partnerBalance;

    /**
     * @var string
     */
    protected $symbol;

    /**
     * @var string
     */
    protected $currencyId;

    /**
     * This method called automatically when returning something from DAO.
     * @access public
     *
     * @param array $data
     */
    public function exchangeArray($data) {
        $this->resId = (isset($data['id'])) ? $data['id'] : null;
        $this->resNumber = (isset($data['res_number'])) ? $data['res_number'] : null;
    	$this->status = (isset($data['status'])) ? $data['status'] : null;
    	$this->bookingDate = (isset($data['booking_date'])) ? $data['booking_date'] : null;
        $this->departureDate = (isset($data['departure_date'])) ? $data['departure_date'] : null;
        $this->apartmentName = (isset($data['apartment_name'])) ? $data['apartment_name'] : null;
        $this->partnerId = (isset($data['partner_id'])) ? $data['partner_id'] : null;
        $this->partnerName = (isset($data['partner_name'])) ? $data['partner_name'] : null;
        $this->guestBalance = (isset($data['guest_balance'])) ? $data['guest_balance'] : null;
        $this->partnerBalance = (isset($data['partner_balance'])) ? $data['partner_balance'] : null;
        $this->symbol = (isset($data['symbol'])) ? $data['symbol'] : null;
        $this->currencyId = (isset($data['currency_id'])) ? $data['currency_id'] : null;
        $this->apartelId = (isset($data['apartel_id'])) ? $data['apartel_id'] : null;
        $this->apartmentId = (isset($data['apartment_id'])) ? $data['apartment_id'] : null;
    }

    /**
     * @return string
     */
    public function getReservationId()
    {
    	return $this->resId;
    }

    /**
     * @return string
     */
    public function getReservationNumber()
    {
    	return $this->resNumber;
    }

    /**
     * @return number
     */
    public function getStatus()
    {
    	return $this->status;
    }

    /**
     * @return string
     */
    public function getBookingDate()
    {
    	return $this->bookingDate;
    }

    /**
     * @return string
     */
    public function getDepartureDate()
    {
    	return $this->departureDate;
    }

    /**
     * @return string
     */
    public function getApartmentName()
    {
    	return $this->apartmentName;
    }

    /**
     * @return string
     */
    public function getApartmentId()
    {
    	return $this->apartmentId;
    }

    /**
     * @return string
     */
    public function getPartnerId()
    {
    	return $this->partnerId;
    }

    /**
     * @return string
     */
    public function getPartnerName()
    {
    	return $this->partnerName;
    }

    /**
     * @return boolean
     */
    public function isVirtual()
    {
    	return $this->apartelId ? 1 : 0;
    }

    /**
     * @return boolean
     */
    public function getApartelId()
    {
        return $this->apartelId;
    }

    /**
     * @return number
     */
    public function getGuestBalance()
    {
    	return $this->guestBalance;
    }

    /**
     * @return number
     */
    public function getPartnerBalance()
    {
    	return $this->partnerBalance;
    }

    /**
     * @return string
     */
    public function getSymbol()
    {
        return $this->symbol;
    }

    /**
     * @return string
     */
    public function getCurrencyId()
    {
        return $this->currencyId;
    }
}
