<?php

namespace DDD\Domain\Booking;

class ReservationIssueTypes
{
    protected $id;
    protected $title;
    protected $description;
    
    public function exchangeArray($data)
    {
        $this->id           = (isset($data['id'])) ? $data['id'] : null;
        $this->title        = (isset($data['title'])) ? $data['title'] : null;
        $this->description  = (isset($data['description'])) ? $data['description'] : null;
    }
    
    public function getId() {
        return $this->id;
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

    public function setTitle($value) {
        $this->title = $value;
        return $this;
    }

    public function setDescription($value) {
        $this->description = $value;
        return $this;
    }
}
