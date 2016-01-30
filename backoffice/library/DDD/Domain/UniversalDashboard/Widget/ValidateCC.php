<?php

namespace DDD\Domain\UniversalDashboard\Widget;

/**
 * Domain class to use in Universal Dashboard "Validate CC" widget
 * @final
 * @category core
 * @package domain
 * @subpackage widget
 *
 * @author Tigran Petrosyan
 */
class ValidateCC {
    /**
     * Unique reservation number
     * @var string
     */
    protected $resNumber;

    /**
     * Booking date
     * @var string
     */
    protected $bookingDate;

    /**
     * Arrival date
     * @access protected
     * @var string
     */
    protected $arrivalDate;

    /**
     * Apartment name
     * @var string
     */
    protected $apartmentName;

    /**
     * Guest first name
     * @var string
     */
    protected $guestFirstName;

    /**
     * Guest last name
     * @var string
     */
    protected $guestLastName;

    /**
     * Customer balance
     * @var float
     */
    protected $guestBalance;

    /**
     * Last reservation agent's full name
     * @var string
     */
    protected $lastAgent;

    /**
     * currency symbol
     * @var string
     */
    protected $symbol;

    /**
     * This method called automatically when returning something from DAO.
     * @access public
     *
     * @param array $data
     */
    public function exchangeArray($data) {
        $this->resNumber = (isset($data['res_number'])) ?
            $data['res_number'] : null;
    	$this->bookingDate = (isset($data['booking_date'])) ?
            $data['booking_date'] : null;
        $this->arrivalDate = (isset($data['arrival_date'])) ?
            $data['arrival_date'] : null;
        $this->apartmentName = (isset($data['apartment_name'])) ?
            $data['apartment_name'] : null;
        $this->guestFirstName = (isset($data['guest_first_name'])) ?
            $data['guest_first_name'] : null;
        $this->guestLastName = (isset($data['guest_last_name'])) ?
            $data['guest_last_name'] : null;
        $this->guestBalance = (isset($data['guest_balance'])) ?
            $data['guest_balance'] : null;
        $this->lastAgent = (isset($data['last_agent_fullname'])) ?
            $data['last_agent_fullname'] : null;
        $this->symbol = (isset($data['symbol'])) ?
            $data['symbol'] : null;
    }

    /**
     * @return string
     */
    public function getReservationNumber()
    {
    	return $this->resNumber;
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
    public function getArrivalDate()
    {
    	return $this->arrivalDate;
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
    public function getGuestFullName()
    {
    	return $this->guestFirstName . ' ' . $this->guestLastName;
    }

    /**
     * @return number
     */
    public function getGuestBalance()
    {
    	return $this->guestBalance;
    }

    /**
     * @return string
     */
    public function getLastAgentFullName()
    {
    	return $this->lastAgent;
    }

    /**
     * @return string
     */
    public function getSymbol()
    {
        return $this->symbol;
    }
}

?>
