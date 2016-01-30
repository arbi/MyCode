<?php

namespace DDD\Domain\Booking;

class FrontierCard
{
    protected $id;
    protected $bookingStatus;
    protected $resNumber;
    protected $guest;
    protected $apartmentAssigned;
    protected $apartmentAssignedId;
    protected $building;
    protected $buildingId;
    protected $unitNumber;
    protected $guestTravelPhone;
    protected $guestPhone;
    protected $dateFrom;
    protected $dateTo;
    protected $arrivalTime;
    protected $arrivalDate;
    protected $arrivalStatus;
    protected $departureDate;
    protected $occupancy;
    protected $guestBalance;
    protected $housekeepingComments;
    protected $timezone;
    protected $apartmentCheckInTime;
    protected $kiPageStatus;
    protected $kiPageHash;
    protected $groupId;
    protected $cccaVerified;
    protected $parking;
    protected $keyTask;
    protected $guestEmail;

    /**
     * @var int
     */
    protected $cccaPageStatus;

    /**
     * @var string
     */
    protected $cccaPageToken;
    protected $apartmentCurrencyCode;

    public function exchangeArray($data)
    {
        $this->id                   = (isset($data['id'])) ? $data['id'] : null;
        $this->bookingStatus        = (isset($data['status'])) ? $data['status'] : null;
        $this->guest                = (isset($data['guest_first_name']) || isset($data['guest_last_name']))
            ? $data['guest_first_name'] . ' ' . $data['guest_last_name'] : null;
        $this->resNumber            = (isset($data['res_number'])) ? $data['res_number'] : null;
        $this->apartmentAssignedId  = (isset($data['apartment_id_assigned'])) ? $data['apartment_id_assigned'] : null;
        $this->apartmentAssigned    = (isset($data['apartment_assigned'])) ? $data['apartment_assigned'] : null;
        $this->buildingId           = (isset($data['building_id'])) ? $data['building_id'] : null;
        $this->building             = (isset($data['building'])) ? $data['building'] : null;
        $this->unitNumber           = (isset($data['unit_number'])) ? $data['unit_number'] : null;
        $this->guestPhone           = (isset($data['guest_phone'])) ? $data['guest_phone'] : null;
        $this->guestTravelPhone     = (isset($data['guest_travel_phone'])) ? $data['guest_travel_phone'] : null;
        $this->dateFrom             = (isset($data['date_from'])) ? $data['date_from'] : null;
        $this->dateTo               = (isset($data['date_to'])) ? $data['date_to'] : null;
        $this->arrivalTime          = (isset($data['guest_arrival_time'])) ? $data['guest_arrival_time'] : null;
        $this->arrivalStatus        = (isset($data['arrival_status'])) ? $data['arrival_status'] : null;
        $this->arrivalDate          = (isset($data['arrival_date'])) ? $data['arrival_date'] : null;
        $this->departureDate        = (isset($data['departure_date'])) ? $data['departure_date'] : null;
        $this->occupancy            = (isset($data['occupancy'])) ? $data['occupancy'] : null;
        $this->guestBalance         = (isset($data['guest_balance'])) ? $data['guest_balance'] : null;
        $this->timezone             = (isset($data['timezone'])) ? $data['timezone'] : null;
        $this->apartmentCheckInTime = (isset($data['apartment_check_in_time'])) ? $data['apartment_check_in_time'] : null;
        $this->housekeepingComments = (isset($data['housekeeping_comments'])) ? $data['housekeeping_comments'] : null;
        $this->kiPageStatus         = (isset($data['ki_page_status'])) ? $data['ki_page_status'] : null;
        $this->kiPageHash           = (isset($data['ki_page_hash'])) ? $data['ki_page_hash'] : null;
        $this->groupId              = (isset($data['group_id'])) ? $data['group_id'] : null;
        $this->cccaVerified         = (isset($data['ccca_verified'])) ? $data['ccca_verified'] : null;
        $this->parking              = (isset($data['parking'])) ? $data['parking'] : null;
        $this->keyTask              = (isset($data['key_task'])) ? $data['key_task'] : null;
        $this->guestEmail           = (isset($data['guest_email'])) ? $data['guest_email'] : null;
        $this->cccaPageStatus       = (isset($data['ccca_page_status'])) ? $data['ccca_page_status'] : null;
        $this->cccaPageToken        = (isset($data['ccca_page_token'])) ? $data['ccca_page_token'] : null;
        $this->apartmentCurrencyCode  = (isset($data['apartment_currency_code'])) ? $data['apartment_currency_code'] : null;
    }

    /**
     * @param int $cccaPageStatus
     */
    public function setCccaPageStatus($cccaPageStatus)
    {
        $this->cccaPageStatus = $cccaPageStatus;
    }

    /**
     * @return int
     */
    public function getCccaPageStatus()
    {
        return $this->cccaPageStatus;
    }

    /**
     * @param string $cccaPageToken
     */
    public function setCccaPageToken($cccaPageToken)
    {
        $this->cccaPageToken = $cccaPageToken;
    }

    /**
     * @return string
     */
    public function getCccaPageToken()
    {
        return $this->cccaPageToken;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getBookingStatus()
    {
        return $this->bookingStatus;
    }

    public function getGuest()
    {
        return $this->guest;
    }

    public function getResNumber()
    {
        return $this->resNumber;
    }

    public function getApartmentAssigned()
    {
        return $this->apartmentAssigned;
    }

    public function getApartmentAssignedId()
    {
        return $this->apartmentAssignedId;
    }

    public function getBuildingId()
    {
        return $this->buildingId;
    }

    public function getBuilding()
    {
        return $this->building;
    }

    public function getUnitNumber()
    {
        return $this->unitNumber;
    }

    public function getGuestPhone()
    {
        return $this->guestPhone;
    }

    public function getGuestTravelPhone()
    {
        return $this->guestTravelPhone;
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
     * @return mixed
     */
    public function getArrivalTime()
    {
        return $this->arrivalTime;
    }

    /**
     * @return mixed
     */
    public function getArrivalDate()
    {
        return $this->arrivalDate;
    }

    /**
     * @return mixed
     */
    public function getDepartureDate()
    {
        return $this->departureDate;
    }

    /**
     * @return mixed
     */
    public function getOccupancy()
    {
        return $this->occupancy;
    }

    /**
     * @return mixed
     */
    public function getGuestBalance()
    {
        return $this->guestBalance;
    }

    /**
     * @return mixed
     */
    public function getArrivalStatus()
    {
        return $this->arrivalStatus;
    }

    /**
     * @return string | null
     */
    public function getTimezone()
    {
        return $this->timezone;
    }

    public function getApartmentCheckInTime()
    {
        return $this->apartmentCheckInTime;
    }

    /**
     * @return string | null
     */
    public function getHousekeepingComments()
    {
        return $this->housekeepingComments;
    }

    public function getKiPageStatus()
    {
        return $this->kiPageStatus;
    }

    public function getKiPageHash()
    {
        return $this->kiPageHash;
    }

    public function getKiPageGodModeCode()
    {
        return substr(md5($this->kiPageHash), 12, 5);
    }

    public function getCccaVerified()
    {
        return (int)$this->cccaVerified;
    }

    public function getParking()
    {
        return $this->parking;
    }

    public function getGuestEmail()
    {
        return strtolower($this->guestEmail);
    }

    public function getKeyTask()
    {
        return $this->keyTask;
    }

    /**
     * @return mixed
     */
    public function getApartmentCurrencyCode()
    {
        return $this->apartmentCurrencyCode;
    }

}