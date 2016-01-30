<?php

namespace DDD\Domain\Geolocation;

class Continents
{
    protected $id;
    protected $en;
    protected $textline_id;
    protected $detail_id;
    
    
    public function exchangeArray($data)
    {
        $this->id     = (isset($data['id'])) ? $data['id'] : null;
        $this->en = (isset($data['en'])) ? $data['en'] : null;
        $this->textline_id = (isset($data['textline_id'])) ? $data['textline_id'] : null;
        $this->detail_id = (isset($data['detail_id'])) ? $data['detail_id'] : null;
    }
    
    public function getDetail_id() {
            return $this->detail_id;
    }
    
    public function setDetail_id($val) {
            $this->detail_id = $val;
            return $this;
    }
    
    public function getEn() {
            return $this->en;
    }
    
    public function setEn($val) {
            $this->en = $val;
            return $this;
    }
    
    public function getId() {
            return $this->id;
    }
    
    public function setId($val) {
            $this->id = $val;
            return $this;
    }
    
    public function getTextline_id() {
            return $this->textline_id;
    }
    
    public function setTextline_id($val) {
            $this->textline_id = $val;
            return $this;
    }
}
