<?php

namespace DDD\Domain\Apartment;

class FrontierCard
{
    protected $id;
    protected $name;
    protected $buildingId;
    protected $building;
    protected $unitNumber;
    protected $address;
    protected $curResId;
    protected $curResNum;
    protected $curResGuest;
    protected $bedroomCount;
    protected $primaryWiFiNetwork;
    protected $primaryWiFiPass;
    protected $secondaryWiFiNetwork;
    protected $secondaryWiFiPass;
    protected $apartmentTimezone;
    protected $curResDateFrom;

    public function exchangeArray($data) {
        $this->id                   = (isset($data['id'])) ? $data['id'] : null;
        $this->name                 = (isset($data['name'])) ? $data['name'] : null;
        $this->building             = (isset($data['building'])) ? $data['building'] : null;
        $this->buildingId           = (isset($data['building_id'])) ? $data['building_id'] : null;
        $this->unitNumber           = (isset($data['unit_number'])) ? $data['unit_number'] : null;
        $this->primaryWiFiNetwork   = (isset($data['primary_wifi_network'])) ? $data['primary_wifi_network'] : null;
        $this->primaryWiFiPass      = (isset($data['primary_wifi_pass'])) ? $data['primary_wifi_pass'] : null;
        $this->secondaryWiFiNetwork = (isset($data['secondary_wifi_network'])) ? $data['secondary_wifi_network'] : null;
        $this->secondaryWiFiPass    = (isset($data['secondary_wifi_pass'])) ? $data['secondary_wifi_pass'] : null;
        $this->address              = (isset($data['address'])) ? $data['address'] : null;
        $this->curResId             = (isset($data['cur_res_id'])) ? $data['cur_res_id'] : null;
        $this->curResNum            = (isset($data['cur_res_num'])) ? $data['cur_res_num'] : null;
        $this->bedroomCount         = (isset($data['bedroom_count'])) ? $data['bedroom_count'] : null;
        $this->apartmentTimezone    = (isset($data['timezone'])) ? $data['timezone'] : null;
        $this->curResDateFrom       = (isset($data['cur_res_date_from'])) ? $data['cur_res_date_from'] : null;
        $this->curResGuest          = (isset($data['cur_first_name']) || isset($data['cur_last_name']))
                                ? $data['cur_first_name'] . ' ' . $data['cur_last_name']
                                : null;

    }

    public function getId()
    {
    	return $this->id;
    }

    public function getName()
    {
    	return $this->name;
    }

    public function getBuildingId()
    {
    	return $this->buildingId;
    }

    public function getBuilding()
    {
    	return $this->building;
    }

    public function getUnitNumber()
    {
    	return $this->unitNumber;
    }

    public function getAddress()
    {
    	return $this->address;
    }

    public function getCurResId()
    {
    	return $this->curResId;
    }

    public function getCurResNum()
    {
    	return $this->curResNum;
    }

    public function getCurResGuest()
    {
    	return $this->curResGuest;
    }

    public function getBedroomCount()
    {
        return $this->bedroomCount;
    }

    /**
     * @return mixed
     */
    public function getPrimaryWiFiNetwork()
    {
        return $this->primaryWiFiNetwork;
    }

    /**
     * @return mixed
     */
    public function getPrimaryWiFiPass()
    {
        return $this->primaryWiFiPass;
    }

    /**
     * @return mixed
     */
    public function getSecondaryWiFiNetwork()
    {
        return $this->secondaryWiFiNetwork;
    }

    /**
     * @return mixed
     */
    public function getSecondaryWiFiPass()
    {
        return $this->secondaryWiFiPass;
    }

    public function getApartmentTimezone()
    {
        return $this->apartmentTimezone;
    }

    public function getCurResDateFrom()
    {
        return $this->curResDateFrom;
    }

}