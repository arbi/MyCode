<?php

namespace DDD\Domain\UniversalDashboard\Widget;

/**
 * Class OverbookingReservation
 * @package DDD\Domain\UniversalDashboard\Widget
 *
 * @author Tigran Petrosyan
 */
class OverbookingReservation {

    /**
     * Unique reservation id
     * @var int
     */
    protected $reservationId;

    /**
     * Unique reservation number
     * @var string
     */
    protected $reservationNumber;

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
    protected $cityName;

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

    public function exchangeArray($data)
    {
        $this->reservationId 	= (isset($data['id'])) ? $data['id'] : null;
        $this->reservationNumber= (isset($data['res_number'])) ? $data['res_number'] : null;
        $this->arrivalDate 		= (isset($data['arrival_date'])) ? $data['arrival_date'] : null;
        $this->cityName	        = (isset($data['city_name'])) ? $data['city_name'] : null;
        $this->apartmentName	= (isset($data['apartment_name'])) ? $data['apartment_name'] : null;
        $this->guestFirstName   = (isset($data['guest_first_name'])) ? $data['guest_first_name'] : null;
        $this->guestLastName 	= (isset($data['guest_last_name'])) ? $data['guest_last_name'] : null;
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
    public function getApartmentName()
    {
        return $this->apartmentName;
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
    public function getCityName()
    {
        return $this->cityName;
    }

    /**
     * @return string
     */
    public function getGuestFirstName()
    {
        return $this->guestFirstName;
    }

    /**
     * @return string
     */
    public function getGuestLastName()
    {
        return $this->guestLastName;
    }

    /**
     * @return int
     */
    public function getReservationId()
    {
        return $this->reservationId;
    }

    /**
     * @return string
     */
    public function getReservationNumber()
    {
        return $this->reservationNumber;
    }
}
