<?php

namespace DDD\Domain\Parking;

final class General
{
    /**
     * @var int|null $id
     */
    private $id;

    /**
     * @var string|null $name
     */
    private $name;

    /**
     * @var boolean $active
     */
    private $active;

    /**
     * @var boolean $virtual
     */
    private $virtual;

    /**
     * @var int|null $countryId
     */
    private $countryId;

    /**
     * @var int|null $provinceId
     */
    private $provinceId;

    /**
     * @var int|null $cityId
     */
    private $cityId;

    /**
     * @var string|null $address
     */
    private $address;

    /**
     * @var string|null $country
     */
    private $country;

    /**
     * @var string|null $city
     */
    private $city;

    /**
     * @var string|null $currency
     */
    private $currency;

    /**
     * @var int|null $lockId
     */
    private $lockId;

    /**
     * @var int|null $directionTextlineId
     */
    private $directionTextlineId;

    /**
     * @var string|null $parkingPermit
     */
    private $parkingPermit;

	/**
     *
     * @param array $data
     */
    public function exchangeArray($data) {
        $this->id                   = (isset($data['id'])) ? $data['id'] : null;
        $this->name                 = (isset($data['name'])) ? $data['name'] : null;
        $this->active               = (isset($data['active'])) ? $data['active'] : true;
        $this->virtual              = (isset($data['is_virtual'])) ? $data['is_virtual'] : false;
        $this->countryId            = (isset($data['country_id'])) ? $data['country_id'] : null;
        $this->country              = (isset($data['country'])) ? $data['country'] : null;
        $this->currency             = (isset($data['currency'])) ? $data['currency'] : null;
        $this->provinceId           = (isset($data['province_id'])) ? $data['province_id'] : null;
        $this->cityId               = (isset($data['city_id'])) ? $data['city_id'] : null;
        $this->city                 = (isset($data['city'])) ? $data['city'] : null;
        $this->address              = (isset($data['address'])) ? $data['address'] : null;
        $this->lockId               = (isset($data['lock_id'])) ? $data['lock_id'] : null;
        $this->directionTextlineId  = (isset($data['direction_textline_id'])) ? $data['direction_textline_id'] : null;
        $this->parkingPermit        = (isset($data['parking_permit'])) ? $data['parking_permit'] : null;
    }

    /**
     * @return int|null
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string|null
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return boolean
     */
    public function isVirtual()
    {
        return $this->virtual;
    }

    /**
     * @return boolean
     */
    public function isActive()
    {
        return $this->active;
    }

    /**
     * @return int|null
     */
    public function getLockId()
    {
        return $this->lockId;
    }

    /**
     * @return int|null
     */
    public function getDirectionTextlineId()
    {
        return $this->directionTextlineId;
    }

    /**
     * @return null|string
     */
    public function getParkingPermit()
    {
        return $this->parkingPermit;
    }

    /**
     * @return int|null
     */
    public function getCountryId()
    {
        return $this->countryId;
    }

    /**
     * @return null|string
     */
    public function getCountry()
    {
        return $this->country;
    }

    /**
     * @return null|string
     */
    public function getCity()
    {
        return $this->city;
    }

    /**
     * @return null|string
     */
    public function getCurrency()
    {
        return $this->currency;
    }

    /**
     * @return int|null
     */
    public function getProvinceId()
    {
        return $this->provinceId;
    }

    /**
     * @return int|null
     */
    public function getCityId()
    {
        return $this->cityId;
    }

    /**
     * @return null|string
     */
    public function getAddress()
    {
        return $this->address;
    }
}