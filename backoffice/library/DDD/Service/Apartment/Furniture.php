<?php

namespace DDD\Service\Apartment;

use DDD\Service\ServiceBase;
use Zend\Stdlib\ArrayObject;

/**
 * Service class providing methods to work with apartment furniture
 * @author Tigran Petrosyan
 * @package core
 * @subpackage core/service
 */
class Furniture extends ServiceBase
{
	/**
	 * @access public
	 * @param int $apartmentId
	 * @return ArrayObject
	 */
    public function getApartmentFurnitureList($apartmentId) {
    	$dao = $this->getApartmentFurnitureDao();
    	$furnitureList = $dao->getApartmentFurnitureList($apartmentId);
    	
    	return $furnitureList;
    }

    /**
     * @access public
     * @return ArrayObject
     */
    public function getFurnitureTypes() {
    	$dao = $this->getFurnitureTypeDao();
    	$types = $dao->getTypes();
    	 
    	return $types;
    }
    
    /**
     * @param array $data
     * @return boolean
     */
    public function addFurniture($data) {
    	$dao = $this->getApartmentFurnitureDao();
    	
    	$result = $dao->save($data, false);
    	 
    	return $result;
    }
    
    public function removeFurniture($id) {
    	$dao = $this->getApartmentFurnitureDao();
    	
    	$result = $dao->delete(array('id' => $id));
    	
    	return $result;
    }
    
	/**
	 * @access private
	 * @param string $domain
	 * @return \DDD\Dao\Apartment\Furniture
	 */
	private function getApartmentFurnitureDao($domain = 'ArrayObject') {
		return new \DDD\Dao\Apartment\Furniture($this->getServiceLocator(), $domain);
	}
	
	/**
	 * @access private
	 * @param string $domain
	 * @return \DDD\Dao\Apartment\FurnitureType
	 */
	private function getFurnitureTypeDao($domain = 'ArrayObject') {
		return new \DDD\Dao\Apartment\FurnitureType($this->getServiceLocator(), $domain);
	}
}
