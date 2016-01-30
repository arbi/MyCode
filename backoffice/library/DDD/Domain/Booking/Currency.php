<?php

namespace DDD\Domain\Booking;

class Currency
{
    protected $id;
    protected $code;
    protected $value;
    
    public function exchangeArray($data)
    {
        $this->id     = (isset($data['id'])) ? $data['id'] : null;
        $this->code = (isset($data['code'])) ? $data['code'] : null;
        $this->value = (isset($data['value'])) ? $data['value'] : null;
    }
    
    public function getValue() {
            return $this->value;
    }
    
    public function getCode() {
            return $this->code;
    }
    
    public function getId() {
            return $this->id;
    }

}
