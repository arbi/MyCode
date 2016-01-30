<?php

namespace DDD\Domain\Booking;

class DoorCode
{
    protected $id;
    protected $pin;
    protected $acc_city_id;
    protected $date_from;
    protected $date_to;

    public function exchangeArray($data)
    {
        $this->id          = (isset($data['id'])) ? $data['id'] : null;
        $this->date_from   = (isset($data['date_from'])) ? $data['date_from'] : null;
        $this->date_to     = (isset($data['date_to'])) ? $data['date_to'] : null;
        $this->acc_city_id = (isset($data['acc_city_id'])) ? $data['acc_city_id'] : null;
        $this->pin         = (isset($data['pin'])) ? $data['pin'] : null;
    }

    public function getPin() {
        return $this->pin;
    }

    public function getApartmentCityId()
    {
        return $this->acc_city_id;
    }

    public function getDateTo() {
        return $this->date_to;
    }

    public function getDateFrom() {
        return $this->date_from;
    }

    public function getId() {
        return $this->id;
    }
}

?>
