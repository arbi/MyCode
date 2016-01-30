<?php

namespace DDD\Domain\Geolocation;

class PoiUrl
{

    protected $id;
    protected $province;
    protected $provinceSlug;
    protected $country;
    protected $countrySlug;
    protected $city;
    protected $citySlug;
    protected $poi;
    protected $poiSlug;
    protected $detailId;

    public function exchangeArray($data)
    {
        $this->id           = (isset($data['id'])) ? $data['id'] : null;
        $this->province     = (isset($data['province'])) ? $data['province'] : null;
        $this->provinceSlug = (isset($data['province_slug'])) ? $data['province_slug'] : null;
        $this->country      = (isset($data['country'])) ? $data['country'] : null;
        $this->countrySlug  = (isset($data['country_slug'])) ? $data['country_slug'] : null;
        $this->city         = (isset($data['city'])) ? $data['city'] : null;
        $this->citySlug     = (isset($data['city_slug'])) ? $data['city_slug'] : null;
        $this->poi          = (isset($data['poi'])) ? $data['poi'] : null;
        $this->poiSlug      = (isset($data['poi_slug'])) ? $data['poi_slug'] : null;
        $this->detailId     = (isset($data['detail_id'])) ? $data['detail_id'] : null;
    }

    public function getCity()
    {
        return $this->city;
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

    public function getDetailId()
    {
        return $this->detailId;
    }

    public function getProvinceSlug()
    {
        return $this->provinceSlug;
    }

    public function getCountrySlug()
    {
        return $this->countrySlug;
    }

    public function getCitySlug()
    {
        return $this->citySlug;
    }

    public function getPoi()
    {
        return $this->poi;
    }

    public function getPoiSlug()
    {
        return $this->poiSlug;
    }

}
