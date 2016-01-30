<?php

namespace DDD\Domain\User;

class Vacationdays
{
    protected $id;
    protected $from;
    protected $to;
    protected $total_number;
    protected $comment;
    protected $is_approved;
    protected $user_id;
    protected $type;
    
    
    public function exchangeArray($data)
    {
        $this->id     = (isset($data['id'])) ? $data['id'] : null;
        $this->from = (isset($data['from'])) ? $data['from'] : null;
        $this->to = (isset($data['to'])) ? $data['to'] : null;
        $this->total_number = (isset($data['total_number'])) ? $data['total_number'] : null;
        $this->comment = (isset($data['comment'])) ? $data['comment'] : null;
        $this->is_approved = (isset($data['is_approved'])) ? $data['is_approved'] : null;
        $this->user_id = (isset($data['user_id'])) ? $data['user_id'] : null;
        $this->type = (isset($data['type'])) ? $data['type'] : null;
    }
    
    public function getType() {
            return $this->type;
    }
    
    public function getIs_approved() {
            return $this->is_approved;
    }
    
    public function setIs_approved($val) {
            $this->is_approved = $val;
            return $this;
    }
    
    public function getComment() {
            return $this->comment;
    }
    
    public function setComment($val) {
            $this->comment = $val;
            return $this;
    }
    
    public function getTotal_number() {
            return $this->total_number;
    }
    
    public function setTotal_number($val) {
            $this->total_number = $val;
            return $this;
    }
    
    public function getTo() {
            return $this->to;
    }
    
    public function setTo($val) {
            $this->to = $val;
            return $this;
    }
    
    public function getFrom() {
            return $this->from;
    }
    
    public function setFrom($val) {
            $this->from = $val;
            return $this;
    }

    public function getUserId()
    {
        return $this->user_id;
    }
    
    public function getId() {
            return $this->id;
    }
    
    public function setId($val) {
            $this->id = $val;
            return $this;
    }
}
