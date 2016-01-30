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
class AmenityItems extends ServiceBase
{
    const AMENITY_BALCONY = 3;
    /**
     * @access public
     * @param Int $aptId Apartment Id
     * @return ArrayObject
     */
    public function getApartmentAmenities($aptId)
    {
    	$apartmentAmenityItemsDao = $this->getServiceLocator()->get('dao_apartment_amenity_items');
        $result = $apartmentAmenityItemsDao->fetchAll(['apartment_id' => $aptId]);

        $amenities = [];

        if($result->count()) {
            foreach($result as $row) {
                array_push($amenities, $row->getAmenityId());
            }
        }

        return $amenities;
    }
}
