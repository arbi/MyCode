<?php

namespace DDD\Domain\Booking;

class BookingProfile
{
    protected $id;
    protected $res_number;
    protected $acc_name;
    protected $date_from;
    protected $date_to;
    protected $guestEmail;
    
    public function exchangeArray($data)
    {
        $this->id     = (isset($data['id'])) ? $data['id'] : null;
        $this->res_number = (isset($data['res_number'])) ? $data['res_number'] : null;
        $this->acc_name = (isset($data['acc_name'])) ? $data['acc_name'] : null;
        $this->date_from = (isset($data['date_from'])) ? $data['date_from'] : null;
        $this->date_to = (isset($data['date_to'])) ? $data['date_to'] : null;
        $this->guestEmail = (isset($data['guest_email'])) ? $data['guest_email'] : null;
    }
    
    public function getId() {
        return $this->id;
    }

    public function getReservationNumber()
    {
        return $this->res_number;
    }

    public function getApartmentName() {
        return $this->acc_name;
    }

    public function getDate_from() {
        return $this->date_from;
    }

    public function getDate_to() {
        return $this->date_to;
    }

    public function getGuestEmail() {
        return $this->guestEmail;
    }
}
