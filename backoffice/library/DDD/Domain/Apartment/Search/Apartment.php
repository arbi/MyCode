<?php

namespace DDD\Domain\Apartment\Search;

/**
 * Specific domain to use in product autocomplete
 * @author Tigran Petrosyan
 * @final
 *
 * @package core
 * @subpackage core/domain
 */
final class Apartment
{
    protected $id;
    protected $status;
    protected $url;
    protected $name;
    protected $city;
    protected $status_name;
    protected $create_date;
    protected $building;
    protected $block;
    protected $unitNumber;

    /**
     *
     * @param array $data
     */
    public function exchangeArray($data) {
        $this->id			= (isset($data['id'])) ? $data['id'] : null;
        $this->status       = (isset($data['status'])) ? $data['status'] : null;
        $this->url			= (isset($data['url'])) ? $data['url'] : null;
        $this->name 		= (isset($data['name'])) ? $data['name'] : null;
        $this->city         = (isset($data['city'])) ? $data['city'] : null;
        $this->building     = (isset($data['building'])) ? $data['building'] : null;
        $this->status_name  = (isset($data['status_name'])) ? $data['status_name'] : null;
        $this->create_date  = (isset($data['create_date'])) ? $data['create_date'] : null;
        $this->block        = (isset($data['block'])) ? $data['block'] : null;
        $this->unitNumber   = (isset($data['unit_number'])) ? $data['unit_number'] : 121212;
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
     * Get product ID
     * @access public
     *
     * @return int
     */
	public function getUrl() {
		return $this->url;
	}

    /**
     * Get product Status
     * @access public
     *
     * @return int
     */
	public function getStatus() {
		return $this->status;
	}

	/**
	 * Get Product name
	 * @access public
	 *
	 * @return string Product name
	 */
	public function getName() {
		return $this->name;
	}

    /**
	 * Get Country name
	 * @access public
	 *
	 * @return string Country name
	 */
	public function getCountry() {
		return $this->country;
	}

    /**
	 * Get Province name
	 * @access public
	 *
	 * @return string Province name
	 */
	public function getProvince() {
		return $this->province;
	}

    /**
	 * Get City name
	 * @access public
	 *
	 * @return string City name
	 */
	public function getCity() {
		return $this->city;
	}

    /**
	 * Get Status name
	 * @access public
	 *
	 * @return string Status name
	 */
	public function getStatusName() {
		return $this->status_name;
	}

    /**
	 * Get Creation date
	 * @access public
	 *
	 * @return string Creation date
	 */
	public function getCreatedDate() {
		return $this->create_date;
	}

    /**
	 * Get Building
	 * @access public
	 *
	 * @return string Building
	 */
	public function getBuilding() {
		return $this->building;
	}

    public function getBlock() {
        return $this->block;
    }

    public function getUnitNumber() {
        return $this->unitNumber;
    }
}
