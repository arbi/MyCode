<?php

namespace DDD\Domain\UniversalDashboard\Widget;

/**
 * Domain class to use in Universal Dashboard "Key Instructions Not Viewed Reservations" widget
 * @final
 * @category core
 * @package domain
 * @subpackage widget
 *
 * @author Tigran Petrosyan
 */
class KINotViewed {
    /**
     * Unique reservation number
     * @var string
     */
    protected $resNumber;

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
     * Last reservation agent's country
     * @var string
     */
    protected $country;

    /**
     * Last reservation agent's full name
     * @var string
     */
    protected $lastAgent;

    /**
     * This method called automatically when returning something from DAO.
     * @access public
     *
     * @param array $data
     */
    public function exchangeArray($data)
    {
        $this->resNumber 		= (isset($data['res_number'])) ? $data['res_number'] : null;
        $this->arrivalDate 		= (isset($data['arrival_date'])) ? $data['arrival_date'] : null;
        $this->apartmentName	= (isset($data['apartment_name'])) ? $data['apartment_name'] : null;
        $this->guestFirstName   = (isset($data['guest_first_name'])) ? $data['guest_first_name'] : null;
        $this->guestLastName 	= (isset($data['guest_last_name'])) ? $data['guest_last_name'] : null;
        $this->country 		    = (isset($data['country'])) ? $data['country'] : null;
        $this->lastAgent 		= (isset($data['last_agent_fullname'])) ? $data['last_agent_fullname'] : null;
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
     * @return string
     */
    public function getGuestCountry()
    {
        return $this->country;
    }

    /**
     * @return string
     */
    public function getLastAgentFullName()
    {
    	return $this->lastAgent;
    }
}
