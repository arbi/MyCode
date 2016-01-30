<?php

namespace DDD\Domain\GeoliteCountry;

class GeoliteCountry
{
    protected $code;
     public function exchangeArray($data)
    {
        $this->code     = (isset($data['code'])) ? $data['code'] : null;
    }
    
    public function getCode() {
            return $this->code;
    }
    
}