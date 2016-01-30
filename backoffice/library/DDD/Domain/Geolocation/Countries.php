<?php

namespace DDD\Domain\Geolocation;

class Countries
{

    protected $id;
    protected $name;
    protected $detail_id;
    protected $currency_id;
    protected $contactPhone;
    protected $required_postal_code;
    protected $slug;
    protected $phoneCode;
    protected $iso;

    public function exchangeArray($data)
    {
        $this->id                   = (isset($data['id'])) ? $data['id'] : null;
        $this->name                 = (isset($data['name'])) ? $data['name'] : null;
        $this->detail_id            = (isset($data['detail_id'])) ? $data['detail_id'] : null;
        $this->currency_id          = (isset($data['currency_id'])) ? $data['currency_id'] : null;
        $this->contactPhone         = (isset($data['contact_phone'])) ? $data['contact_phone'] : null;
        $this->required_postal_code = (isset($data['required_postal_code'])) ? $data['required_postal_code'] : null;
        $this->slug                 = (isset($data['slug'])) ? $data['slug'] : null;
        $this->phoneCode            = (isset($data['phone_code'])) ? $data['phone_code'] : null;
        $this->iso                  = (isset($data['iso'])) ? $data['iso'] : null;
    }

    public function getCurrencyId()
    {
        return $this->currency_id;
    }

    public function getContactPhone()
    {
        return $this->contactPhone;
    }

    public function getRequiredPostalCode()
    {
        return $this->required_postal_code;
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

    public function getSlug()
    {
        return $this->slug;
    }

    public function setSlug($slug)
    {
        $this->slug = $slug;
        return $this;
    }

    public function getPhonecode()
    {
        return $this->phoneCode;
    }

    public function getIso()
    {
        return $this->iso;
    }

}
