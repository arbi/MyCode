<?php

namespace DDD\Domain\Booking;

class ChannelReservation {
    protected $id;
    protected $res_number;
    protected $apartment_id;
    protected $apartment_id_assigned;
    protected $apartment_id_origin;
    protected $customer_id;
    protected $guestEmail;
    protected $guestFirstName;
    protected $guestLastName;
    protected $status;
    protected $date_from;
    protected $date_to;
    protected $funds_confirmed;
    protected $cancelation_date;
    protected $apartment_status;
    protected $channel_res_id;
    protected $reservation_id;
    protected $room_id;
    protected $rate_id;
    protected $i_date_from;
    protected $i_date_to;
    protected $guest_name;
    protected $building_id;

    public function exchangeArray($data)
    {
        $this->id = (isset($data['id'])) ? $data['id'] : null;
        $this->res_number = (isset($data['res_number'])) ? $data['res_number'] : null;
        $this->customer_id = (isset($data['customer_id'])) ? $data['customer_id'] : null;
        $this->guestEmail = (isset($data['guest_email'])) ? $data['guest_email'] : null;
        $this->apartment_id = (isset($data['apartment_id_assigned'])) ? $data['apartment_id_assigned'] : null;
        $this->apartment_id_assigned = (isset($data['apartment_id_assigned'])) ? $data['apartment_id_assigned'] : null;
        $this->apartment_id_origin = (isset($data['apartment_id_origin'])) ? $data['apartment_id_origin'] : null;
        $this->guestFirstName = (isset($data['guest_first_name'])) ? $data['guest_first_name'] : null;
        $this->guestLastName = (isset($data['guest_last_name'])) ? $data['guest_last_name'] : null;
        $this->status = (isset($data['status'])) ? $data['status'] : null;
        $this->date_from = (isset($data['date_from'])) ? $data['date_from'] : null;
        $this->date_to = (isset($data['date_to'])) ? $data['date_to'] : null;
        $this->funds_confirmed = (isset($data['funds_confirmed'])) ? $data['funds_confirmed'] : null;
        $this->cancelation_date = (isset($data['cancelation_date'])) ? $data['cancelation_date'] : null;
        $this->channel_res_id = (isset($data['channel_res_id'])) ? $data['channel_res_id'] : null;
        $this->reservation_id = (isset($data['reservation_id'])) ? $data['reservation_id'] : null;
        $this->room_id = (isset($data['room_id'])) ? $data['room_id'] : null;
        $this->rate_id = (isset($data['rate_id'])) ? $data['rate_id'] : null;
        $this->i_date_from = (isset($data['i_date_from'])) ? $data['i_date_from'] : null;
        $this->i_date_to = (isset($data['i_date_to'])) ? $data['i_date_to'] : null;
        $this->guest_name = (isset($data['guest_name'])) ? $data['guest_name'] : null;
        $this->building_id = (isset($data['building_id'])) ? $data['building_id'] : null;

    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return mixed
     */
    public function getResNumber()
    {
        return $this->res_number;
    }

    /**
     * @return mixed
     */
    public function getApartmentId()
    {
        return $this->apartment_id;
    }

    /**
     * @return mixed
     */
    public function getApartmentIdAssigned()
    {
        return $this->apartment_id_assigned;
    }

    /**
     * @return mixed
     */
    public function getApartmentIdOrigin()
    {
        return $this->apartment_id_origin;
    }

    /**
     * @return mixed
     */
    public function getCustomerId()
    {
        return $this->customer_id;
    }

    /**
     * @return mixed
     */
    public function getGuestEmail()
    {
        return $this->guestEmail;
    }

    /**
     * @return mixed
     */
    public function getGuestFirstName()
    {
        return $this->guestFirstName;
    }

    /**
     * @return mixed
     */
    public function getGuestLastName()
    {
        return $this->guestLastName;
    }

    /**
     * @return mixed
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @return mixed
     */
    public function getDateFrom()
    {
        return $this->date_from;
    }

    /**
     * @return mixed
     */
    public function getDateTo()
    {
        return $this->date_to;
    }

    /**
     * @return mixed
     */
    public function getFundsConfirmed()
    {
        return $this->funds_confirmed;
    }

    /**
     * @return mixed
     */
    public function getCancelationDate()
    {
        return $this->cancelation_date;
    }

    /**
     * @return mixed
     */
    public function getApartmentStatus()
    {
        return $this->apartment_status;
    }

    /**
     * @return mixed
     */
    public function getChannelResId()
    {
        return $this->channel_res_id;
    }

    /**
     * @return mixed
     */
    public function getReservationId()
    {
        return $this->reservation_id;
    }

    /**
     * @return mixed
     */
    public function getRoomId()
    {
        return $this->room_id;
    }

    /**
     * @return mixed
     */
    public function getRateId()
    {
        return $this->rate_id;
    }

    /**
     * @return mixed
     */
    public function getIDateFrom()
    {
        return $this->i_date_from;
    }

    /**
     * @return mixed
     */
    public function getIDateTo()
    {
        return $this->i_date_to;
    }

    /**
     * @return mixed
     */
    public function getGuestName()
    {
        return $this->guest_name;
    }

    /**
     * @return mixed
     */
    public function getBuildingId()
    {
        return $this->building_id;
    }

}
