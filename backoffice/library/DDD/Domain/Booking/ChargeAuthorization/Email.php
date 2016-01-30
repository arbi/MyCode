<?php

namespace DDD\Domain\Booking\ChargeAuthorization;

/**
 * Class Email
 * @package DDD\Domain\Booking\ChargeAuthorization
 *
 * @author Tigran Petrosyan
 */
class Email
{
    /**
     * @var string
     */
    protected $reservationNumber;

    /**
     * @var string
     */
    protected $apartmentName;

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
    protected $guestEmail;

    /**
     * @var string
     */
    protected $cccaPageToken;

    /**
     * @var string
     */
    protected $phone1;

    /**
     * @var string
     */
    protected $phone2;

    /**
     * @var integer
     */
    protected $partnerId;

    /**
     * @var string
     */
    protected $dateFrom;

    /**
     * @param $data
     */
    public function exchangeArray($data)
    {
        $this->reservationNumber = (isset($data['reservation_number'])) ? $data['reservation_number'] : null;
        $this->apartmentName     = (isset($data['apartment_name'])) ? $data['apartment_name'] : null;
        $this->guestFirstName    = (isset($data['guest_first_name'])) ? $data['guest_first_name'] : null;
        $this->guestLastName     = (isset($data['guest_last_name'])) ? $data['guest_last_name'] : null;
        $this->guestEmail        = (isset($data['guest_email'])) ? $data['guest_email'] : null;
        $this->cccaPageToken     = (isset($data['ccca_page_token'])) ? $data['ccca_page_token'] : null;
        $this->phone1            = (isset($data['phone1'])) ? $data['phone1'] : null;
        $this->phone2            = (isset($data['phone2'])) ? $data['phone2'] : null;
        $this->partnerId         = (isset($data['partner_id'])) ? $data['partner_id'] : null;
        $this->dateFrom          = (isset($data['date_from'])) ? $data['date_from'] : null;
    }

    /**
     * @return string
     */
    public function getDateFrom()
    {
        return $this->dateFrom;
    }

    /**
     * @return string
     */
    public function getPhone2()
    {
        return $this->phone2;
    }

    /**
     * @return string
     */
    public function getPhone1()
    {
        return $this->phone1;
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
    public function getCccaPageToken()
    {
        return $this->cccaPageToken;
    }

    /**
     * @return string
     */
    public function getGuestEmail()
    {
        return $this->guestEmail;
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
     * @return integer
     */
    public function getPartnerId()
    {
        return $this->partnerId;
    }

    /**
     * @return string
     */
    public function getReservationNumber()
    {
        return $this->reservationNumber;
    }
}
