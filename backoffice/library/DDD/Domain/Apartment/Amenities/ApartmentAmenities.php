<?php

/**
 * Description of ApartmentAmenities
 *
 * @author Tigran Gh.
 * 
 * @package core
 * @subpackage core/domain
 */

namespace DDD\Domain\Apartment\Amenities;

class ApartmentAmenities {
    
    /**
     * @access private
     * @var Int
     */
    private $apartmentId;
    
    /**
     * @access private
     * @var String
     */
    private $amenityId;
    
    /**
     * @access private
     * @var String
     */
    private $amenityName;
    
    /**
     * @access private
     * @var String
     */
    private $amenityTid;

    /**
     * 
     * @param array $data
     */
    public function exchangeArray($data) {
        $this->apartmentId	= (isset($data['apartment_id'])) ? $data['apartment_id'] : null;
        $this->amenityId    = (isset($data['amenity_id'])) ? $data['amenity_id'] : null;
        $this->amenityName  = (isset($data['amenity_name'])) ? $data['amenity_name'] : null;
        $this->amenityTid   = (isset($data['textline_id'])) ? $data['textline_id'] : null;
    }
    
    /**
     * @access public
     * @return int
     */
	public function getApartmentId() {
		return $this->apartmentId;
	}

	/**
	 * @access public
	 * @return int
	 */
	public function getAmenityId() {
		return $this->amenityId;
	}

	/**
	 * @access public
	 * @return int
	 */
	public function getAmenityName() {
		return $this->amenityName;
	}

	/**
	 * @access public
	 * @return int
	 */
	public function getAmenityTextlineId() {
		return $this->amenityTid;
	}
}
