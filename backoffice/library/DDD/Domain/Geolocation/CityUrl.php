<?php

namespace DDD\Domain\Geolocation;

class CityUrl
{

    protected $id;
    protected $province;
    protected $provinceSlug;
    protected $city;
    protected $citySlug;
    protected $country;

    public function exchangeArray($data)
    {
        $this->id           = (isset($data['id'])) ? $data['id'] : null;
        $this->province     = (isset($data['province'])) ? $data['province'] : null;
        $this->provinceSlug = (isset($data['province_slug'])) ? $data['province_slug'] : null;
        $this->city         = (isset($data['city'])) ? $data['city'] : null;
        $this->citySlug     = (isset($data['city_slug'])) ? $data['city_slug'] : null;
        $this->country      = (isset($data['country'])) ? $data['country'] : null;
    }

    public function getCountry()
    {
        return $this->country;
    }

    public function getProvince()
    {
        return $this->province;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getProvinceSlug()
    {
        return $this->provinceSlug;
    }

    public function getCity()
    {
        return $this->city;
    }

    public function getCitySlug()
    {
        return $this->citySlug;
    }
}
