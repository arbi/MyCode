<?php

namespace DDD\Domain\Booking;

class UserProfileReservation
{
    protected $id;

    public function exchangeArray($data)
    {
        $this->id = (isset($data['id'])) ? $data['id'] : null;
    }
}

?>
