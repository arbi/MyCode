<?php

namespace DDD\Domain\User;

class VacationRequest
{
    protected $id;
    protected $from;
    protected $to;
    protected $total_number;
    protected $comment;
    protected $is_approved;
    protected $firstname;
    protected $lastname;
    protected $user_id;
    protected $type;
    protected $manager_id;
    protected $userId;
    protected $vacation_days;
    protected $sickDays;

    public function exchangeArray($data)
    {
        $this->id            = (isset($data['id'])) ? $data['id'] : null;
        $this->from          = (isset($data['from'])) ? $data['from'] : null;
        $this->to            = (isset($data['to'])) ? $data['to'] : null;
        $this->total_number  = (isset($data['total_number'])) ? $data['total_number'] : null;
        $this->comment       = (isset($data['comment'])) ? $data['comment'] : null;
        $this->is_approved   = (isset($data['is_approved'])) ? $data['is_approved'] : null;
        $this->firstname     = (isset($data['firstname'])) ? $data['firstname'] : null;
        $this->lastname      = (isset($data['lastname'])) ? $data['lastname'] : null;
        $this->user_id       = (isset($data['user_id'])) ? $data['user_id'] : null;
        $this->type          = (isset($data['type'])) ? $data['type'] : null;
        $this->manager_id    = (isset($data['manager_id'])) ? $data['manager_id'] : null;
        $this->userId        = (isset($data['userId'])) ? $data['userId'] : null;
        $this->vacation_days = (isset($data['vacation_days'])) ? $data['vacation_days'] : null;
        $this->sickDays      = (isset($data['sick_days'])) ? $data['sick_days'] : null;
    }

    public function getUserId()
    {
        return $this->userId;
    }

    public function getManager_id()
    {
        return $this->manager_id;
    }

    public function getType()
    {
        return $this->type;
    }

    public function getUser_id()
    {
        return $this->user_id;
    }

    public function setUser_id($val)
    {
        $this->user_id = $val;
        return $this;
    }

    public function getFirstName()
    {
        return $this->firstname;
    }

    public function setFirstName($val)
    {
        $this->firstname = $val;
        return $this;
    }

    public function getLastName()
    {
        return $this->lastname;
    }

    public function setLastName($val)
    {
        $this->lastname = $val;
        return $this;
    }

    public function getIs_approved()
    {
        return $this->is_approved;
    }

    public function setIs_approved($val)
    {
        $this->is_approved = $val;
        return $this;
    }

    public function getComment()
    {
        return $this->comment;
    }

    public function setComment($val)
    {
        $this->comment = $val;
        return $this;
    }

    public function getTotal_number()
    {
        return $this->total_number;
    }

    public function setTotal_number($val)
    {
        $this->total_number = $val;
        return $this;
    }

    public function getTo()
    {
        return $this->to;
    }

    public function setTo($val)
    {
        $this->to = $val;
        return $this;
    }

    public function getFrom()
    {
        return $this->from;
    }

    public function setFrom($val)
    {
        $this->from = $val;
        return $this;
    }

    public function getId()
    {
        return $this->id;
    }

    public function setId($val)
    {
        $this->id = $val;
        return $this;
    }

    public function getVacation_days()
    {
        return $this->vacation_days;
    }

    public function getSickDays()
    {
        return $this->sickDays;
    }

    public function getFullName()
    {
        return $this->firstname . ' ' . $this->lastname;
    }
}
