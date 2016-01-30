<?php

/**
 * Description of BuildingFacilities
 *
 * @author Tigran Gh.
 * 
 * @package core
 * @subpackage core/domain
 */

namespace DDD\Domain\ApartmentGroup;

class BuildingFacilities
{
    /**
     * @access private
     * @var Int
     */
    private $buildingId;
    
    /**
     * @access private
     * @var String
     */
    private $facilityId;
    
    /**
     * @access private
     * @var Int
     */
    private $facilityName;
    
    /**
     * @access private
     * @var String
     */
    private $facilityTid;

    /**
     * 
     * @param array $data
     */
    public function exchangeArray($data)
    {
        $this->buildingId	= (isset($data['building_id'])) ? $data['building_id'] : null;
        $this->facilityId   = (isset($data['facility_id'])) ? $data['facility_id'] : null;
        $this->facilityName  = (isset($data['facility_name'])) ? $data['facility_name'] : null;
        $this->facilityTid   = (isset($data['textline_id'])) ? $data['textline_id'] : null;
    }
    
    /**
     * @access public
     * @return int
     */
	public function getBuildingId()
    {
		return $this->buildingId;
	}

	/**
	 * @access public
	 * @return int
	 */
	public function getFacilityId()
    {
		return $this->facilityId;
	}

	/**
	 * @access public
	 * @return int
	 */
	public function getFacilityName() {
		return $this->facilityName;
	}

	/**
	 * @access public
	 * @return int
	 */
	public function getFacilityTextlineId() {
		return $this->facilityTid;
	}
}
