<?php

namespace DDD\Domain\Geolocation;

class Cities
{
    protected $id;
    protected $name;
    protected $province_id;
    protected $detail_id;
    protected $currency;

    /**
     * @access public
     * @var string
     */
    protected $timezone;

    public function exchangeArray($data)
    {
        $this->id     = (isset($data['id'])) ? $data['id'] : null;
        $this->name = (isset($data['name'])) ? $data['name'] : null;
        $this->province_id = (isset($data['province_id'])) ? $data['province_id'] : null;
        $this->detail_id = (isset($data['detail_id'])) ? $data['detail_id'] : null;
        $this->timezone = (isset($data['timezone'])) ? $data['timezone'] : null;
        $this->currency = (isset($data['currency'])) ? $data['currency'] : null;
    }

    public function getCurrency() {
            return $this->currency;
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

    public function getProvince_id() {
            return $this->province_id;
    }

    public function setProvince_id($val) {
            $this->province_id = $val;
            return $this;
    }

    /**
     * @access public
     * @return string
     */
    public function getTimezone() {
    	return $this->timezone;
    }
}
