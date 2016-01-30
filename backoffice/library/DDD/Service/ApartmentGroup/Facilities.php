<?php

namespace DDD\Service\ApartmentGroup;

use DDD\Service\ServiceBase;
use Zend\Stdlib\ArrayObject;

/**
 * Service class providing methods to work with apartment amenities
 * @author Tigran Gh.
 * @package core
 * @subpackage core/service
 */
class Facilities extends ServiceBase
{
    /**
     * @access public
     * @return ArrayObject
     */
    public function getFacilitiesList()
    {
    	$buildingFacilitiesDao = $this->getServiceLocator()->get('dao_building_facilities');
        $facilities = $buildingFacilitiesDao->fetchAll();
        
        return $facilities;
    }
}
