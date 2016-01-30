<?php

namespace DDD\Domain\Accommodation;

/**
 * Specific domain to use in product autocomplete
 * @author Tigran Petrosyan
 * @final
 *
 * @package core
 * @subpackage core/domain
 */
final class ProductAutocomplete
{
	/**
	 * @var int
	 */
    protected $id;

    /**
     * @var string product name
     */
    protected $name;

    /**
     * @var string city name
     */
    protected $cityName;

    /**
     * @var string country name
     */
    protected $countryName;
    /**
     * @var string apartment group name
     */
    protected $apartmentGroup;
    /**
     * @var string building id
     */
    protected $buildingId;

    protected $unitNumber;


    /**
     *
     * @param array $data
     */
    public function exchangeArray($data) {
        $this->id			= (isset($data['id'])) ? $data['id'] : null;
        $this->name 		= (isset($data['name'])) ? $data['name'] : null;
        $this->cityName 	= (isset($data['city'])) ? $data['city'] : null;
        $this->countryName	= (isset($data['country'])) ? $data['country'] : null;
        $this->buildingId	= (isset($data['building_id'])) ? $data['building_id'] : null;
        $this->apartmentGroup	= (isset($data['apartment_group'])) ? $data['apartment_group'] : null;
        $this->unitNumber = (isset($data['unit_number'])) ? $data['unit_number'] : null;
    }

    /**
     * Get product ID
     * @access public
     *
     * @return int
     */
	public function getId() {
		return $this->id;
	}

	/**
	 * Get product name
	 * @access public
	 *
	 * @return string product name
	 */
	public function getName() {
		return $this->name;
	}

	/**
	 * Get product city name
	 * @access public
	 *
	 * @return string city name
	 */
	public function getCityName() {
		return $this->cityName;
	}

	/**
	 * Get product country name
	 * @access public
	 *
	 * @return string country name
	 */
	public function getCountryName() {
		return $this->countryName;
	}
    /**
     *
     * @return string
     */
    public function getApartmentGroup() {
        return $this->apartmentGroup;
    }

    /**
     * Get building id
     * @access public
     *
     * @return int
     */
    public function getBuildingId() {
        return $this->buildingId;
    }

    public function getUnitNumber ()
    {
        return $this->unitNumber;
    }
}