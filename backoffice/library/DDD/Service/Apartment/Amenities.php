<?php

namespace DDD\Service\Apartment;

use DDD\Service\ServiceBase;
use Zend\Stdlib\ArrayObject;

/**
 * Service class providing methods to work with apartment amenities
 * @author Tigran Gh.
 * @package core
 * @subpackage core/service
 */
class Amenities extends ServiceBase
{
    /**
     * @access public
     * @return ArrayObject
     */
    public function getAmenitiesList()
    {
    	$apartmentAmenitiesDao = $this->getServiceLocator()->get('dao_apartment_amenities');
        $amenities = $apartmentAmenitiesDao->fetchAll();
        
        return $amenities;
    }
}
