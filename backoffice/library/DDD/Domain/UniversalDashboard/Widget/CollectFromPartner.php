<?php

namespace DDD\Domain\UniversalDashboard\Widget;

/**
 * Domain class to use in Universal Dashboard "Collect From Partner" widget
 * @final
 * @category core
 * @package domain
 * @subpackage widget
 *
 * @author Tigran Petrosyan
 */
class CollectFromPartner
{
    /**
     * Unique reservation id
     * @var int
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
     * Apartment name
     * @var string
     */
    protected $apartmentName;

    /**
     * Partner id
     * @var int
     */
    protected $partnerId;

    /**
     * Partner name
     * @var string
     */
    protected $partnerName;

    /**
     * Partner balance
     * @var float
     */
    protected $partnerBalance;

    /**
     * currency symbol
     * @var string
     */
    protected $symbol;

    /**
     * currency id
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
        $this->partnerBalance = (isset($data['partner_balance'])) ? $data['partner_balance'] : null;
        $this->symbol = (isset($data['symbol'])) ? $data['symbol'] : null;
        $this->currencyId = (isset($data['currency_id'])) ? $data['currency_id'] : null;
    }

    /**
     * @return string
     */
    public function getReservationNumber()
    {
    	return $this->resNumber;
    }

    /**
     * @return int
     */
    public function getReservationId()
    {
        return $this->resId;
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
    public function getPartnerName()
    {
    	return $this->partnerName;
    }

    /**
     * @return string
     */
    public function getPartnerId()
    {
    	return $this->partnerId;
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
     * @return int
     */
    public function getCurrencyId()
    {
        return $this->currencyId;
    }
}
