<?php

namespace Library\IpInfo\Entity;

/**
 * Class IdentityInformation
 * @package Library\IpInfo\Entity
 *
 * @author Tigran Petrosyan
 */
class IdentityInformation
{
    /**
     * Example: US
     * @var string
     */
    private $country;

    /**
     * Example: California
     * @var string
     */
    private $region;

    /**
     * Example: Mountain View
     * @var string
     */
    private $city;

    /**
     * Example: google-public-dns-a.google.com
     * @var string
     */
    private $hostName;

    /**
     * Example: 37.3860,-122.0838
     * @var string
     */
    private $location;

    /**
     * Example: AS15169 Google Inc.
     * @var string
     */
    private $provider;

    /**
     * @param string $city
     */
    public function setCity($city)
    {
        $this->city = $city;
    }

    /**
     * @return string
     */
    public function getCity()
    {
        return $this->city;
    }

    /**
     * @param string $country
     */
    public function setCountry($country)
    {
        $this->country = $country;
    }

    /**
     * @return string
     */
    public function getCountry()
    {
        return $this->country;
    }

    /**
     * @param string $hostName
     */
    public function setHostName($hostName)
    {
        $this->hostName = $hostName;
    }

    /**
     * @return string
     */
    public function getHostName()
    {
        return $this->hostName;
    }

    /**
     * @param string $location
     */
    public function setLocation($location)
    {
        $this->location = $location;
    }

    /**
     * @return string
     */
    public function getLocation()
    {
        return $this->location;
    }

    /**
     * @param string $provider
     */
    public function setProvider($provider)
    {
        $this->provider = $provider;
    }

    /**
     * @return string
     */
    public function getProvider()
    {
        return $this->provider;
    }

    /**
     * @param string $region
     */
    public function setRegion($region)
    {
        $this->region = $region;
    }

    /**
     * @return string
     */
    public function getRegion()
    {
        return $this->region;
    }
} 