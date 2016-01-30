<?php

namespace DDD\Domain\UniversalDashboard\Widget;

/**
 * Domain class to use in Universal Dashboard "Mark As Settled" widget
 * @final
 * @category core
 * @package domain
 * @subpackage widget
 *
 * @author Tigran Petrosyan
 */
class ToBeSettled {
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
     * Departure date
     * @access protected
     * @var string
     */
    protected $departureDate;

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
	 * @var boolean
	 */
	protected $isNoCollection;

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
     * This method called automatically when returning something from DAO.
     * @access public
     *
     * @param array $data
     */
    public function exchangeArray($data) {
        $this->resNumber = (isset($data['res_number'])) ?
            $data['res_number'] : null;
    	$this->status = (isset($data['status'])) ?
            $data['status'] : null;
        $this->departureDate = (isset($data['departure_date'])) ?
            $data['departure_date'] : null;
        $this->apartmentName = (isset($data['apartment_name'])) ?
            $data['apartment_name'] : null;
        $this->guestFirstName = (isset($data['guest_first_name'])) ?
            $data['guest_first_name'] : null;
        $this->guestLastName = (isset($data['guest_last_name'])) ?
            $data['guest_last_name'] : null;
        $this->isNoCollection = (isset($data['is_no_collection'])) ?
            $data['is_no_collection'] : null;
        $this->guestBalance = (isset($data['guest_balance'])) ?
            $data['guest_balance'] : null;
        $this->partnerBalance = (isset($data['partner_balance'])) ?
            $data['partner_balance'] : null;
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
     * @return number
     */
    public function getStatus()
    {
    	return $this->status;
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
    public function getGuestFullName()
    {
    	return $this->guestFirstName . ' ' . $this->guestLastName;
    }

	/**
	 * @return boolean
	 */
	public function isNoCollection()
    {
		return $this->isNoCollection;
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
}

?>
