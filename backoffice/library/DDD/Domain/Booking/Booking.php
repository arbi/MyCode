<?php

namespace DDD\Domain\Booking;

class Booking
{
    /**
     * @var int
     */
    protected $id;

    /**
     * @var string
     */
    protected $resNumber;

    /**
     * @var int
     */
    protected $arrivalStatus;

    /**
     * @var int
     */
    protected $apartmentIdAssigned;

    /**
     * @var string
     */
    protected $dateFrom;

    /**
     * @var string
     */
    protected $dateTo;

    /**
     * @var
     */
    protected $guestCurrencyCode;

    /**
     * @var string
     */
    protected $actualArrivalDate;

    /**
     * @var string
     */
    protected $actualDepartureDate;

    /**
     * @var
     */
    protected $status;

    /**
     * @var int
     */
    protected $apartmentCityId;

    /**
     * @var int
     */
    protected $customerId;

    /**
     * @var
     */
    protected $occupancy;

    /**
     * @var
     */
    protected $apartmentCurrencyCode;

    /**
     * @var int
     */
    protected $timestamp;

    /**
     * @var
     */
    protected $guestBalance;

    /**
     * @var
     */
    protected $guestFirstName;

    /**
     * @var
     */
    protected $guestLastName;

    /**
     * @param $data
     */
    public function exchangeArray($data)
    {
        $this->id                    = (isset($data['id'])) ? $data['id'] : null;
        $this->resNumber             = (isset($data['res_number'])) ? $data['res_number'] : null;
        $this->arrivalStatus         = (isset($data['arrival_status'])) ? $data['arrival_status'] : null;
        $this->apartmentIdAssigned   = (isset($data['apartment_id_assigned'])) ? $data['apartment_id_assigned'] : null;
        $this->dateFrom              = (isset($data['date_from'])) ? $data['date_from'] : null;
        $this->dateTo                = (isset($data['date_to'])) ? $data['date_to'] : null;
        $this->guestCurrencyCode     = (isset($data['guest_currency_code'])) ? $data['guest_currency_code'] : null;
        $this->apartmentCurrencyCode = (isset($data['apartment_currency_code'])) ? $data['apartment_currency_code'] : null;
        $this->actualArrivalDate     = (isset($data['arrival_date'])) ? $data['arrival_date'] : null;
        $this->actualDepartureDate   = (isset($data['departure_date'])) ? $data['departure_date'] : null;
        $this->apartmentCityId       = (isset($data['acc_city_id'])) ? $data['acc_city_id'] : null;
        $this->status                = (isset($data['status'])) ? $data['status'] : null;
        $this->customerId            = (isset($data['customer_id'])) ? $data['customer_id'] : null;
        $this->occupancy             = (isset($data['occupancy'])) ? $data['occupancy'] : null;
        $this->timestamp             = (isset($data['timestamp'])) ? $data['timestamp'] : null;
        $this->guestFirstName        = (isset($data['guest_first_name'])) ? $data['guest_first_name'] : null;
        $this->guestLastName         = (isset($data['guest_last_name'])) ? $data['guest_last_name'] : null;
        $this->partner_id            = (isset($data['partner_id'])) ? $data['partner_id'] : null;
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
    public function getDateTo()
    {
        return $this->dateTo;
    }

    /**
     * @return string
     */
    public function getResNumber()
    {
        return $this->resNumber;
    }

    /**
     * @return int
     */
    public function getTimestamp()
    {
        return $this->timestamp;
    }

    public function getStatus()
    {
    	return $this->status;
    }

    public function getReservationNumber()
    {
    	return $this->resNumber;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param $val
     * @return $this
     */
    public function setId($val)
    {
        $this->id = $val;
        return $this;
    }

    /**
     * @return int
     */
    public function getApartmentIdAssigned()
    {
    	return $this->apartmentIdAssigned;
    }

    /**
     * @return mixed
     */
    public function getCustomerCurrency()
    {
    	return $this->guestCurrencyCode;
    }

    public function getApartmentCurrencyCode()
    {
        return $this->apartmentCurrencyCode;
    }

    /**
     * @return string
     */
    public function getActualArrivalDate()
    {
    	return $this->actualArrivalDate;
    }

    /**
     * @return mixed
     */
    public function getActualDepartureDate()
    {
    	return $this->actualDepartureDate;
    }

    /**
     * @param int $apartmentCityId
     */
    public function setApartmentCityId($apartmentCityId)
    {
        $this->apartmentCityId = $apartmentCityId;
    }

    /**
     * @return int
     */
    public function getApartmentCityId()
    {
        return $this->apartmentCityId;
    }

    /**
     * @return int
     */
    public function getArrivalStatus()
    {
        return $this->arrivalStatus;
    }

    /**
     * @return int
     */
    public function getCustomerId()
    {
        return $this->customerId;
    }

    /**
     * @return int|null
     */
    public function getOccupancy()
    {
        return $this->occupancy;
    }

    /**
     * @return double
     */
    public function getGuestBalance()
    {
        return $this->guestBalance;
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
}
