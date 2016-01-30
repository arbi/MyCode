<?php

namespace DDD\Domain\Booking;

class BlackList
{
    protected $id;
    protected $hash;
    protected $type;
    protected $reservation_id;
    
    public function exchangeArray($data)
    {
        $this->id = (isset($data['id'])) ? $data['id'] : null;
        $this->hash = (isset($data['hash'])) ? $data['hash'] : null;
        $this->type = (isset($data['type'])) ? $data['type'] : null;
        $this->reservation_id = (isset($data['reservation_id'])) ? $data['reservation_id'] : null;
    }
    
    public function getId() {
        return $this->id;
    }

    public function getHash() {
        return $this->hash;
    }

    public function getType() {
        return $this->type;
    }

    public function getReservationId() {
        return $this->reservation_id;
    }
}
