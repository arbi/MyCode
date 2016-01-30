<?php

namespace DDD\Domain\Booking;

class BookingQueue
{
    protected $id;
    protected $reservationId;
    protected $error_status;
    protected $error;

    public function exchangeArray($data) {
        $this->id            = (isset($data['id'])) ? $data['id'] : null;
        $this->reservationId = (isset($data['reservation_id'])) ? $data['reservation_id'] : null;
        $this->error_status  = (isset($data['error_status'])) ? $data['error_status'] : null;
        $this->error         = (isset($data['error'])) ? $data['error'] : null;
    }

    public function getId() {
        return $this->id;
    }

    public function setId($id) {
        $this->id = $id;
        return $this;
    }

    public function getReservationId() {
        return $this->reservationId;
    }

    public function setReservationId($reservationId) {
        $this->reservationId = $reservationId;
        return $this;
    }

    public function getErrorStatus() {
        return $this->error_status;
    }

    public function setErrorStatus($error_status) {
        $this->error_status = $error_status;
        return $this;
    }

    public function getError() {
        return $this->error;
    }

    public function setError($error) {
        $this->error = $error;
        return $this;
    }
}

?>
