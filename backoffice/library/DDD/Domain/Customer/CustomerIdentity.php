<?php

namespace DDD\Domain\Customer;

class CustomerIdentity
{

    protected $id;
    protected $customer_id;
    protected $reservation_id;
    protected $user_id;
    protected $user_name;

    protected $ip_address;
    protected $ip_hostname;
    protected $ip_provider;

    protected $ua_family;
    protected $ua_major;
    protected $ua_minor;
    protected $ua_patch;
    protected $ua_language;

    protected $os_family;
    protected $os_major;
    protected $os_minor;
    protected $os_patch;
    protected $os_patchMinor;

    protected $device_family;
    protected $device_brand;
    protected $device_model;

    protected $geo_city;
    protected $geo_region;
    protected $geo_country;
    protected $geo_location;

    protected $landing_page;
    protected $referer_page;
    protected $referer_host;

    public function exchangeArray($data)
    {
        $this->id             = (isset($data['id'])) ? $data['id'] : null;
        $this->customer_id    = (isset($data['customer_id'])) ? $data['customer_id'] : null;
        $this->reservation_id = (isset($data['reservation_id'])) ? $data['reservation_id'] : null;
        $this->user_id        = (isset($data['user_id'])) ? $data['user_id'] : null;
        $this->user_name      = (isset($data['user_name'])) ? $data['user_name'] : null;
        $this->ip_address     = (isset($data['ip_address'])) ? $data['ip_address'] : null;
        $this->ip_hostname    = (isset($data['ip_hostname'])) ? $data['ip_hostname'] : null;
        $this->ip_provider    = (isset($data['ip_provider'])) ? $data['ip_provider'] : null;
        $this->ua_family      = (isset($data['ua_family'])) ? $data['ua_family'] : null;
        $this->ua_major       = (isset($data['ua_major'])) ? $data['ua_major'] : null;
        $this->ua_minor       = (isset($data['ua_minor'])) ? $data['ua_minor'] : null;
        $this->ua_patch       = (isset($data['ua_patch'])) ? $data['ua_patch'] : null;
        $this->ua_language    = (isset($data['ua_language'])) ? $data['ua_language'] : null;
        $this->os_family      = (isset($data['os_family'])) ? $data['os_family'] : null;
        $this->os_major       = (isset($data['os_major'])) ? $data['os_major'] : null;
        $this->os_minor       = (isset($data['os_minor'])) ? $data['os_minor'] : null;
        $this->os_patch       = (isset($data['os_patch'])) ? $data['os_patch'] : null;
        $this->os_patchMinor  = (isset($data['os_patchMinor'])) ? $data['os_patchMinor'] : null;
        $this->device_family  = (isset($data['device_family'])) ? $data['device_family'] : null;
        $this->device_brand   = (isset($data['device_brand'])) ? $data['device_brand'] : null;
        $this->device_model   = (isset($data['device_model'])) ? $data['device_model'] : null;
        $this->geo_city       = (isset($data['geo_city'])) ? $data['geo_city'] : null;
        $this->geo_region     = (isset($data['geo_region'])) ? $data['geo_region'] : null;
        $this->geo_country    = (isset($data['geo_country'])) ? $data['geo_country'] : null;
        $this->geo_location   = (isset($data['geo_location'])) ? $data['geo_location'] : null;
        $this->landing_page   = (isset($data['landing_page'])) ? $data['landing_page'] : null;
        $this->referer_page   = (isset($data['referer_page'])) ? $data['referer_page'] : null;
        $this->referer_host   = (isset($data['referer_host'])) ? $data['referer_host'] : null;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getCustomerId()
    {
        return $this->customer_id;
    }

    public function getReservationId()
    {
        return $this->reservation_id;
    }

    public function getUserId()
    {
        return $this->user_id;
    }

    public function getUserName()
    {
        return $this->user_name;
    }

    public function getIpAddress()
    {
        return $this->ip_address;
    }

    public function getIpHostname()
    {
        return $this->ip_hostname;
    }

    public function getIpProvider()
    {
        return $this->ip_provider;
    }

    public function getUaFamily()
    {
        return $this->ua_family;
    }

    public function getUaMajor()
    {
        return $this->ua_major;
    }

    public function getUaMinor()
    {
        return $this->ua_minor;
    }

    public function getUaPatch()
    {
        return $this->ua_patch;
    }

    public function getUaLanguage()
    {
        return $this->ua_language;
    }

    public function getOsFamily()
    {
        return $this->os_family;
    }

    public function getOsMajor()
    {
        return $this->os_major;
    }

    public function getOsMinor()
    {
        return $this->os_minor;
    }

    public function getOsPatch()
    {
        return $this->os_patch;
    }

    public function getOsPatchMinor()
    {
        return $this->os_patchMinor;
    }

    public function getDeviceFamily()
    {
        return $this->device_family;
    }

    public function getDeviceBrand()
    {
        return $this->device_brand;
    }

    public function getDeviceModel()
    {
        return $this->device_model;
    }

    public function getGeoCity()
    {
        return $this->geo_city;
    }

    public function getGeoRegion()
    {
        return $this->geo_region;
    }

    public function getGeoCountry()
    {
        return $this->geo_country;
    }

    public function getGeoLocation()
    {
        return $this->geo_location;
    }

    public function getLandingPage()
    {
        return $this->landing_page;
    }

    public function getRefererPage()
    {
        return $this->referer_page;
    }

    public function getRefererHost()
    {
        return $this->referer_host;
    }

}
