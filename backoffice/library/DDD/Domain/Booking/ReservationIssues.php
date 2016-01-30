<?php

namespace DDD\Domain\Booking;

class ReservationIssues
{
    protected $id;
    protected $reservation_id;
    protected $reservation_number;
    protected $date_from;
    protected $partner_id;
    protected $partner_name;
    protected $partner_ref;
    protected $issue_type_id;
    protected $date_of_detection;
    protected $title;
    protected $description;
    
    public function exchangeArray($data)
    {
        $this->id                   = (isset($data['id'])) ? $data['id'] : null;
        $this->reservation_id       = (isset($data['reservation_id'])) ? $data['reservation_id'] : null;
        $this->reservation_number   = (isset($data['reservation_number'])) ? $data['reservation_number'] : null;
        $this->date_from            = (isset($data['date_from'])) ? $data['date_from'] : null;
        $this->partner_id           = (isset($data['partner_id'])) ? $data['partner_id'] : null;
        $this->partner_name         = (isset($data['partner_name'])) ? $data['partner_name'] : null;
        $this->partner_ref          = (isset($data['partner_ref'])) ? $data['partner_ref'] : null;
        $this->issue_type_id        = (isset($data['issue_type_id'])) ? $data['issue_type_id'] : null;
        $this->date_of_detection    = (isset($data['date_of_detection'])) ? $data['date_of_detection'] : null;
        $this->title                = (isset($data['title'])) ? $data['title'] : null;
        $this->description          = (isset($data['description'])) ? $data['description'] : null;
    }
    
    public function getId() {
        return $this->id;
    }

    public function getReservationId() {
        return $this->reservation_id;
    }

    public function getReservationNumber()
    {
        return $this->reservation_number;
    }

    public function getIssueTypeId() {
        return $this->issue_type_id;
    }

    public function getDateOfDetection() {
        return $this->date_of_detection;
    }

    public function getDateFrom() {
        return $this->date_from;
    }

    public function getPartnerId() {
        return $this->partner_id;
    }

    public function getPartnerName() {
        return $this->partner_name;
    }

    public function getPartnerRef() {
        return $this->partner_ref;
    }
    
    public function getTitle() {
        return $this->title;
    }

    public function getDescription() {
        return $this->description;
    }

    public function setId($value) {
        $this->id = $value;
        return $this;
    }

    public function setReservationId($value) {
        $this->reservation_id = $value;
        return $this;
    }

    public function setReservationNumber($value) {
        $this->reservation_number = $value;
        return $this;
    }

    public function setIssueTypeId($value) {
        $this->issue_type_id = $value;
        return $this;
    }

    public function setDateOfDetection($value) {
        $this->date_of_detection = $value;
        return $this;
    }

    public function setDateFrom($value) {
        $this->date_from = $value;
        return $this;
    }
    
    public function setTitle($value) {
        $this->title = $value;
        return $this;
    }

    public function setDescription($value) {
        $this->description = $value;
        return $this;
    }
}
