<?php

namespace DDD\Domain\Geolocation;

class Provinces
{

    protected $id;
    protected $name;
    protected $provinceShortName;
    protected $country_id;
    protected $detail_id;
    protected $slug;

    public function exchangeArray($data)
    {
        $this->id         = (isset($data['id'])) ? $data['id'] : null;
        $this->name       = (isset($data['name'])) ? $data['name'] : null;
        $this->provinceShortName = (isset($data['short_name'])) ? $data['short_name'] : null;
        $this->country_id = (isset($data['country_id'])) ? $data['country_id'] : null;
        $this->detail_id  = (isset($data['detail_id'])) ? $data['detail_id'] : null;
        $this->slug       = (isset($data['slug'])) ? $data['slug'] : null;
    }

    public function getDetail_id()
    {
        return $this->detail_id;
    }

    public function setDetail_id($val)
    {
        $this->detail_id = $val;
        return $this;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setName($val)
    {
        $this->name = $val;
        return $this;
    }

    public function getId()
    {
        return $this->id;
    }

    public function setId($val)
    {
        $this->id = $val;
        return $this;
    }

    public function getCountry_id()
    {
        return $this->country_id;
    }

    public function setCountry_id($val)
    {
        $this->country_id = $val;
        return $this;
    }

    public function getSlug()
    {
        return $this->slug;
    }

    public function setSlug($slug)
    {
        $this->slug = $slug;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getProvinceShortName()
    {
        return $this->provinceShortName;
    }

}
