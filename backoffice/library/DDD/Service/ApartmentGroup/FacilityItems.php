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
class FacilityItems extends ServiceBase
{
    /**
     * @access public
     * @param Int $buildingId Building Id
     * @return ArrayObject
     */
    public function getBuildingFacilities($buildingId)
    {
    	$buildingFacilityItemsDao = $this->getServiceLocator()->get('dao_building_facility_items');
        $result = $buildingFacilityItemsDao->fetchAll(['building_id' => $buildingId]);
        
        $facilities = [];
        
        if($result->count()) {
            foreach($result as $row) {
                array_push($facilities, $row->getFacilityId());
            }
        }
        
        return $facilities;
    }
}
