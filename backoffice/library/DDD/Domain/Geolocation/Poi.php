<?php

namespace DDD\Domain\Geolocation;

class Poi
{
    protected $id;
    protected $name;
    protected $city_id;
    protected $detail_id;
    protected $type_id;
    protected $wsShowRightColumn;

    
    public function exchangeArray($data)
    {
        $this->id     = (isset($data['id'])) ? $data['id'] : null;
        $this->name = (isset($data['name'])) ? $data['name'] : null;
        $this->city_id = (isset($data['city_id'])) ? $data['city_id'] : null;
        $this->detail_id = (isset($data['detail_id'])) ? $data['detail_id'] : null;
        $this->type_id = (isset($data['type_id'])) ? $data['type_id'] : null;
        $this->wsShowRightColumn = (isset($data['ws_show_right_column'])) ? $data['ws_show_right_column'] : null;
    }
    
    public function getType_id() {
            return $this->type_id;
    }
    
    public function setType_id($val) {
            $this->type_id = $val;
            return $this;
    }
    
    public function getDetail_id() {
            return $this->detail_id;
    }
    
    public function setDetail_id($val) {
            $this->detail_id = $val;
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
    
    public function getCity_id() {
            return $this->city_id;
    }
    
    public function setCity_id($val) {
            $this->city_id = $val;
            return $this;
    }

    public function getWsShowRightColumn()
    {
        return $this->wsShowRightColumn;
    }
}
