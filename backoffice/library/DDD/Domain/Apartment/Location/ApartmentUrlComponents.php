<?php

namespace DDD\Domain\Apartment\Location;

/**
 * Specific domain to use to construct apartment website url
 * @author Tigran Petrosyan
 * @final
 *
 * @package core
 * @subpackage core/domain
 */
final class ApartmentUrlComponents
{

    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $cityName;

    /**
     * @var string
     */
    protected $citySlug;

    /**
     * @var string
     */
    protected $provinceName;

    /**
     * @var string
     */
    protected $provinceSlug;

    /**
     * @var string
     */
    protected $countryName;

    /**
     * @var string
     */
    protected $countrySlug;

    /**
     * @var string
     */
    protected $url;

    /**
     *
     * @param array $data
     */
    public function exchangeArray($data)
    {
        $this->name         = (isset($data['name'])) ? $data['name'] : null;
        $this->cityName     = (isset($data['city'])) ? $data['city'] : null;
        $this->citySlug     = (isset($data['city_slug'])) ? $data['city_slug'] : null;
        $this->provinceName = (isset($data['province'])) ? $data['province'] : null;
        $this->provinceSlug = (isset($data['province_slug'])) ? $data['province_slug'] : null;
        $this->countryName  = (isset($data['country'])) ? $data['country'] : null;
        $this->countrySlug  = (isset($data['country_slug'])) ? $data['country_slug'] : null;
        $this->url          = (isset($data['url'])) ? $data['url'] : null;
    }

    /**
     * Get apartment name
     * @access public
     *
     * @return string name
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Get apartment city name
     * @access public
     *
     * @return string city name
     */
    public function getCityName()
    {
        return $this->cityName;
    }

    /**
     * Get apartment province name
     * @access public
     *
     * @return string province name
     */
    public function getProvinceName()
    {
        return $this->provinceName;
    }

    /**
     * Get apartment country name
     * @access public
     *
     * @return string country name
     */
    public function getCountryName()
    {
        return $this->countryName;
    }

    /**
     * Get apartment country name
     * @access public
     *
     * @return string country name
     */
    public function getUrl()
    {
        return $this->url;
    }

    public function getCitySlug()
    {
        return $this->citySlug;
    }

    public function getProvinceSlug()
    {
        return $this->provinceSlug;
    }

    public function getCountrySlug()
    {
        return $this->countrySlug;
    }

}
