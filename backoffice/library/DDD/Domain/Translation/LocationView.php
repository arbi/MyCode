<?php

namespace DDD\Domain\Translation;

class LocationView
{
    protected $id;
    protected $tx_2;
    protected $name;
    protected $type;
    protected $count;
    protected $country;
    protected $provinces;
    protected $city;
    protected $poi;
    protected $name_en;


    public function exchangeArray($data)
    {
        $this->id        = (isset($data['id'])) ? $data['id'] : null;
        $this->type      = (isset($data['type'])) ? $data['type'] : null;
        $this->count     = (isset($data['count'])) ? $data['count'] : null;
        $this->tx_2      = (isset($data['tx_2'])) ? $data['tx_2'] : null;
        $this->name      = (isset($data['name'])) ? $data['name'] : null;
        $this->country   = (isset($data['country'])) ? $data['country'] : null;
        $this->provinces = (isset($data['provinces'])) ? $data['provinces'] : null;
        $this->city      = (isset($data['city'])) ? $data['city'] : null;
        $this->poi       = (isset($data['poi'])) ? $data['poi'] : null;
        $this->name      = (isset($data['name'])) ? $data['name'] : null;
    }

    public function getName_en() {
            return $this->name_en;
    }

    public function getPoi() {
            return $this->poi;
    }

    public function getCity() {
            return $this->city;
    }

    public function getProvinces() {
            return $this->provinces;
    }

    public function getCountry() {
            return $this->country;
    }

    public function getName() {
            return $this->name;
    }

    public function getTx_2() {
            return $this->tx_2;
    }

    public function getCount() {
            return $this->count;
    }

    public function getId() {
            return $this->id;
    }

    public function getType() {
            return $this->type;
    }
}