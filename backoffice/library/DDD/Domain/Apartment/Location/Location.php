<?php

namespace DDD\Domain\Apartment\Location;

/**
 * Apartment Location Domain class
 * @author Tigran Petrosyan
 * @final
 *
 * @package core
 * @subpackage core/domain
 */
final class Location
{
    private $id;
    private $y_pos;
    private $x_pos;
    private $descriptionTextlineID;
    private $directionsTextlineID;
    private $descriptionTextEnglish;
    private $directionsTextEnglish;
    private $countryID;
    private $provinceID;
    private $cityID;
    private $address;
    private $postalCode;
    private $buildingID;
    private $building;
    private $block;
    private $floor;
    private $unitNumber;
    private $map_attachment;
    private $buildingSectionId;

    /**
     * @var int
     */
    private $apartmentId;

    public function exchangeArray($data) {
        $this->id							= (isset($data['id'])) ? $data['id'] : null;
        $this->apartmentId    				= (isset($data['apartment_id'])) ? $data['apartment_id'] : null;
        $this->y_pos					    = (isset($data['y_pos'])) ? $data['y_pos'] : null;
        $this->x_pos 					    = (isset($data['x_pos'])) ? $data['x_pos'] : null;
        $this->descriptionTextEnglish		= (isset($data['description_en'])) ? $data['description_en'] : null;
        $this->directionsTextEnglish		= (isset($data['directions_en'])) ? $data['directions_en'] : null;
        $this->descriptionTextlineID		= (isset($data['description_textline'])) ? $data['description_textline'] : null;
        $this->directionsTextlineID			= (isset($data['directions_textline'])) ? $data['directions_textline'] : null;
        $this->countryID					= (isset($data['country_id'])) ? $data['country_id'] : null;
        $this->provinceID					= (isset($data['province_id'])) ? $data['province_id'] : null;
        $this->cityID						= (isset($data['city_id'])) ? $data['city_id'] : null;
        $this->address						= (isset($data['address'])) ? $data['address'] : null;
        $this->postalCode					= (isset($data['postal_code'])) ? $data['postal_code'] : null;
        $this->buildingID					= (isset($data['building_id'])) ? $data['building_id'] : null;
        $this->building 					= (isset($data['building'])) ? $data['building'] : ' No Building ';
        $this->block    					= (isset($data['block'])) ? $data['block'] : null;
        $this->floor						= (isset($data['floor'])) ? $data['floor'] : null;
        $this->unitNumber					= (isset($data['unit_number'])) ? $data['unit_number'] : null;
        $this->map_attachment				= (isset($data['map_attachment'])) ? $data['map_attachment'] : null;
        $this->buildingSectionId   		    = (isset($data['building_section_id'])) ? $data['building_section_id'] : null;
    }

    public function getBuildingSectionId() {
        return $this->buildingSectionId;
    }

    public function getDescriptionTextlineID() {
        return $this->descriptionTextlineID;
    }

    public function getDirectionsTextlineID() {
        return $this->directionsTextlineID;
    }

	public function getMapAttachment() {
		return $this->map_attachment;
	}

	public function getID() {
		return $this->id;
	}

    public function getBlock() {
        return $this->block;
    }

	public function getY_pos() {
		return $this->y_pos;
	}

	public function getX_pos() {
		return $this->x_pos;
	}

	public function getDescriptionText() {
		return $this->descriptionTextEnglish;
	}

	public function getDirectionsText() {
		return $this->directionsTextEnglish;
	}

	public function getCountryID() {
		return $this->countryID;
	}

	public function getProvinceID() {
		return $this->provinceID;
	}

	public function getCityID() {
		return $this->cityID;
	}

	public function getAddress() {
		return $this->address;
	}

	public function getPostalCode() {
		return $this->postalCode;
	}

    public function getBuildingID() {
        return $this->buildingID;
    }

    public function getBuilding() {
        return $this->building;
    }

	public function getFloor() {
		return $this->floor;
	}

	public function getUnitNumber() {
		return $this->unitNumber;
	}

	public function setCountryID($countryID) {
		$this->countryID = $countryID;
	}

	public function setProvinceID($provinceID) {
		$this->provinceID = $provinceID;
	}

	public function setCityID($cityID) {
		$this->cityID = $cityID;
	}

	public function setPostalCode($postalCode) {
		$this->postalCode = $postalCode;
	}

	public function setBuildingID($buildingID) {
		$this->buildingID = $buildingID;
	}

	public function setFloor($floor) {
		$this->floor = $floor;
	}

	public function setUnitNumber($unitNumber) {
		$this->unitNumber = $unitNumber;
	}

    /**
     * @return int
     */
    public function getApartmentId()
    {
		return $this->apartmentId;
	}
}
