<?php

namespace DDD\Domain\Geolocation;

class Poitype
{
    protected $id;
    protected $name;
    protected $textline_id;
    
    public function exchangeArray($data)
    {
        $this->id     = (isset($data['id'])) ? $data['id'] : null;
        $this->name = (isset($data['name'])) ? $data['name'] : null;
        $this->textline_id = (isset($data['textline_id'])) ? $data['textline_id'] : null;
    }
    
    public function getTextline_id() {
            return $this->textline_id;
    }
    
    public function setTextline_id($val) {
            $this->textline_id = $val;
            return $this;
    }
    
    public function getName() {
            return $this->name;
    }
    
    public function setName($val) {
            $this->name = $val;
            return $this;
    }
    
    public function getId() {
            return $this->id;
    }
    
    public function setId($val) {
            $this->id = $val;
            return $this;
    }
}
