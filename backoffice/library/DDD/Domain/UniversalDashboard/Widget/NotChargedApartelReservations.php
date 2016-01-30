<?php

namespace DDD\Domain\UniversalDashboard\Widget;

/**
 * Class NotChargedApartelReservations
 * @package DDD\Domain\UniversalDashboard\Widget
 *
 * @author Tigran Petrosyan
 */
class NotChargedApartelReservations
{
    /**
     * @var int
     */
    protected $reservationId;

    /**
     * @var string
     */
    protected $reservationNumber;

    /**
     * @var string
     */
    protected $apartelName;

    /**
     * @var string
     */
    protected $apartmentName;

    /**
     * @var string
     */
    protected $guestFullName;

    /**
     * @var string
     */
    protected $checkInDate;

    /**
     * @var string
     */
    protected $checkOutDate;

    /**
     * @param $data []
     */
    public function exchangeArray($data)
    {
        $this->reservationId        = (isset($data['reservation_id'])) ? $data['reservation_id'] : null;
        $this->reservationNumber    = (isset($data['reservation_number'])) ? $data['reservation_number'] : null;
        $this->apartelName          = (isset($data['apartel_name'])) ? $data['apartel_name'] : null;
        $this->apartmentName        = (isset($data['apartment_name'])) ? $data['apartment_name'] : null;
        $this->guestFullName        = (isset($data['guest_full_name'])) ? $data['guest_full_name'] : null;
        $this->checkInDate          = (isset($data['checkin_date'])) ? $data['checkin_date'] : null;
        $this->checkOutDate         = (isset($data['checkout_date'])) ? $data['checkout_date'] : null;
    }

    /**
     * @return string
     */
    public function getApartelName()
    {
        return $this->apartelName;
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
    public function getCheckInDate()
    {
        return $this->checkInDate;
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
    public function getGuestFullName()
    {
        return $this->guestFullName;
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
