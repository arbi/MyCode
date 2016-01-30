<?php

namespace DDD\Domain\Textline;

class Location
{
    protected $id;
    protected $name_en;
    protected $name_fr;
    protected $name_de;
    protected $name_it;
    protected $name_ru;
    protected $name_am;
    protected $name_ge;
    
    public function exchangeArray($data)
    {
        $this->id       = (isset($data['id']))      ? $data['id']       : null;
        $this->name_en  = (isset($data['name'])) ? $data['name']  : null;
        $this->name_fr  = (isset($data['name_fr'])) ? $data['name_fr']  : null;
        $this->name_de  = (isset($data['name_de'])) ? $data['name_de']  : null;
        $this->name_it  = (isset($data['name_it'])) ? $data['name_it']  : null;
        $this->name_ru  = (isset($data['name_ru'])) ? $data['name_ru']  : null;
        $this->name_am  = (isset($data['name_am'])) ? $data['name_am']  : null;
        $this->name_ge  = (isset($data['name_ge'])) ? $data['name_ge']  : null;
    }
    
    public function getId(){
        return $this->id;
    }
    
    public function getEn(){
        return $this->name_en;
    }
    
    public function getFr(){
        if ($this->name_fr === '') {
            return $this->getEn();
        }
        return $this->name_fr;
    }
    
    public function getDe(){
        if ($this->name_de === '') {
            return $this->getEn();
        }
        return $this->name_de;
    }
    
    public function getIt(){
        if ($this->name_it === '') {
            return $this->getEn();
        }
        return $this->name_it;
    }
    
    public function getRu(){
        if ($this->name_ru === '') {
            return $this->getEn();
        }
        return $this->name_ru;
    }
    
    public function getAm(){
        if ($this->name_am === '') {
            return $this->getEn();
        }
        return $this->name_am;
    }
    
    public function getGe(){
        if ($this->name_ge === '') {
            return $this->getEn();
        }
        return $this->name_ge;
    }
}

?>