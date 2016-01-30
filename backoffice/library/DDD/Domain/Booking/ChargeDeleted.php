<?php

namespace DDD\Domain\Booking;

class ChargeDeleted
{
    protected $id;
    protected $reservation_id;
    protected $reservation_charge_id;
    protected $user_id;
    protected $date;
    protected $comment;
    
    public function exchangeArray($data) {
        $this->id = (isset($data['id'])) ? $data['id'] : null;
        $this->reservation_id = (isset($data['reservation_id'])) ? $data['reservation_id'] : null;
        $this->reservation_charge_id = (isset($data['reservation_charge_id'])) ? $data['reservation_charge_id'] : null;
        $this->user_id = (isset($data['user_id'])) ? $data['user_id'] : null;
        $this->date = (isset($data['date'])) ? $data['date'] : null;
        $this->comment = (isset($data['comment'])) ? $data['comment'] : null;
    }

    /**
     * @return mixed
     */
    public function getComment()
    {
        return $this->comment;
    }

    /**
     * @return mixed
     */
    public function getDate()
    {
        return $this->date;
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
    public function getReservationChargeId()
    {
        return $this->reservation_charge_id;
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
    public function getUserId()
    {
        return $this->user_id;
    }

}
