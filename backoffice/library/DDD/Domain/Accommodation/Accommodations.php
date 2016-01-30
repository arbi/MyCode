<?php

namespace DDD\Domain\Accommodation;

class Accommodations
{
    protected $id;
    protected $name;
    protected $currency_id;
    protected $currency_code;
    protected $country_id;
    protected $province_id;
    protected $city_id;
    protected $address;
    protected $province_name;
    protected $city_name;
    protected $buildingId;
    protected $building;
    protected $notifyNegativeProfit;
    protected $status;
    protected $lotId;

    public function exchangeArray($data)
    {
        $this->id  = (isset($data['id'])) ? $data['id'] : null;
        $this->name = (isset($data['name'])) ? $data['name'] : null;
        $this->currency_id = (isset($data['currency_id'])) ? $data['currency_id'] : null;
        $this->currency_code = (isset($data['currency_code'])) ? $data['currency_code'] : null;
        $this->country_id = (isset($data['country_id'])) ? $data['country_id'] : null;
        $this->province_id = (isset($data['province_id'])) ? $data['province_id'] : null;
	    $this->province_name = (isset($data['province_name'])) ? $data['province_name'] : null;
        $this->city_id = (isset($data['city_id'])) ? $data['city_id'] : null;
	    $this->city_name = (isset($data['city_name'])) ? $data['city_name'] : null;
        $this->address = (isset($data['address'])) ? $data['address'] : null;
        $this->notifyNegativeProfit = (isset($data['notify_negative_profit'])) ? $data['notify_negative_profit'] : null;
        $this->buildingId = (isset($data['building_id'])) ? $data['building_id'] : null;
        $this->building = (isset($data['building'])) ? $data['building'] : null;
        $this->status = (isset($data['status'])) ? $data['status'] : null;
        $this->lotId = (isset($data['lot_id'])) ? $data['lot_id'] : null;
    }

	public function getLotId() {
		return $this->lotId;
	}

	public function setId ($id) {
		$this->id = $id;
	}

	public function getStatus () {
		return $this->status;
	}

	public function getId () {
		return $this->id;
	}

	public function setName ($name) {
		$this->name = $name;
	}

	public function getName () {
		return $this->name;
	}

	public function setCurrencyId ($name) {
		$this->currency_id = $name;
	}

	public function getCurrencyId () {
		return $this->currency_id;
	}

	public function getCurrencyCode () {
		return $this->currency_code;
	}

	public function getCountryId () {
		return $this->country_id;
	}

	public function getProvinceId () {
		return $this->province_id;
	}

	public function getProvinceName () {
		return $this->province_name;
	}

	public function getCityId () {
		return $this->city_id;
	}

	public function getCityName () {
		return $this->city_name;
	}

	public function getAddress () {
		return $this->address;
	}

	public function getBuildingId () {
		return $this->buildingId;
	}

	public function getBuilding () {
		return $this->building;
	}

	public function getNotifyNegativeProfit () {
		return $this->notifyNegativeProfit;
	}
}
