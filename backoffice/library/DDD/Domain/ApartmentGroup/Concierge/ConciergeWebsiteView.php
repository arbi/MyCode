<?php

namespace DDD\Domain\ApartmentGroup\Concierge;

/**
 * Class ConciergeWebsiteView
 * @package DDD\Domain\ApartmentGroup\Concierge
 *
 * @author Tigran Petrosyan
 */
class ConciergeWebsiteView
{
    /**
     * @var string
     */
    protected $guestFirstName;

    /**
     * @var string
     */
    protected $guestLastName;

    /**
     * @var string
     */
    protected $reservationNumber;

    /**
     * @var int
     */
    protected $pax;

    /**
     * @var string
     */
    protected $checkOutDate;

    /**
     * @var string
     */
    protected $apartmentName;

    /**
     * @var string
     */
    protected $unitNumber;

    /**
     * @param $data []
     */
    public function exchangeArray($data)
    {
        $this->guestFirstName  	= (isset($data['guest_first_name'])) ? $data['guest_first_name'] : null;
        $this->guestLastName  	= (isset($data['guest_last_name'])) ? $data['guest_last_name'] : null;
        $this->reservationNumber= (isset($data['res_number'])) ? $data['res_number'] : null;
        $this->pax  		    = (isset($data['pax'])) ? $data['pax'] : null;
        $this->checkOutDate  	= (isset($data['date_to'])) ? $data['date_to'] : null;
        $this->apartmentName  	= (isset($data['apartment_name'])) ? $data['apartment_name'] : null;
        $this->unitNumber       = (isset($data['unit_number'])) ? $data['unit_number'] : null;
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
    public function getCheckOutDate()
    {
        return $this->checkOutDate;
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
    public function getPax()
    {
        return $this->pax;
    }

    /**
     * @return string
     */
    public function getReservationNumber()
    {
        return $this->reservationNumber;
    }

    /**
     * @return string
     */
    public function getUnitNumber()
    {
        return $this->unitNumber;
    }

    /**
     * @return string
     */
    public function getGuestFullName()
    {
        return $this->guestFirstName . " " . $this->guestLastName;
    }

    /**
     * @return string
     */
    public function getApartmentNameWithUnitNumber()
    {
        return $this->apartmentName . " (" . $this->unitNumber . ")";
    }
}
