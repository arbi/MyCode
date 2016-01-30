<?php

namespace DDD\Service\Apartment;

use DDD\Service\ServiceBase;

/**
 * Service class providing methods to work with apartment details
 * @author Tigran Petrosyan
 * @package core
 * @subpackage core/service
 */
class Details extends ServiceBase
{

    /**
     * @param int $apartmentId
     * @return array
     */
    public function getApartmentDetails($apartmentId)
    {

    	$apartmentDescriptionDao = $this->getApartmentDescriptionDao('ArrayObject');
    	$apartmentDetailsDao = $this->getApartmentDetailsDao('ArrayObject');

    	$descriptionRow = $apartmentDescriptionDao->fetchOne(array('apartment_id' => $apartmentId));
    	$detailsRow = $apartmentDetailsDao->fetchOne(array('apartment_id' => $apartmentId));

    	$data = array();
    	foreach ($descriptionRow as $key => $value) {
    		$data[$key] = $value;
    	}

    	foreach ($detailsRow as $key => $value) {
    		$data[$key] = $value;
    	}

        $data['show_apartment_entry_code'] = $detailsRow['show_apartment_entry_code'];
        return $data;
    }

	public function isApartmentConnectedToCubilis($apartmentId)
    {
		$apartmentDetailsDao = $this->getApartmentDetailsDao('ArrayObject');
		$detailsRow = $apartmentDetailsDao->fetchOne(array('apartment_id' => $apartmentId),['sync_cubilis']);

		return (int)$detailsRow['sync_cubilis'];
	}

    public function saveApartmentDetails($apartmentId, $data)
    {
    	unset($data['save_button']);

        if ($data['cleaning_fee'] < 0) {
            return false;
        }

        $apartmentAmenityItemsDao = $this->getServiceLocator()->get('dao_apartment_amenity_items');
        $apartmentSpotsDao        = $this->getServiceLocator()->get('dao_apartment_spots');
        $apartmentDescriptionDao  = $this->getApartmentDescriptionDao('ArrayObject');
        $apartmentDetailsDao      = $this->getApartmentDetailsDao('ArrayObject');

        $apartmentAmenityItemsDao->deleteWhere(['apartment_id' => $apartmentId]);
        if(!empty($data['amenities'])) {
            foreach($data['amenities'] as $amenityId => $isSet) {
                if($isSet) {
                    $apartmentAmenityItemsDao->save([
                        'amenity_id' => $amenityId,
                        'apartment_id' => $apartmentId
                    ]);
                }
            }
        }
        unset($data['amenities']);

    	$detailsData = [
            'notify_negative_profit'    => $data['notify_negative_profit'],
            'monthly_cost'              => $data['monthly_cost'],
            'startup_cost'              => $data['startup_cost'],
            'show_apartment_entry_code' => $data['show_apartment_entry_code'],
            'cleaning_fee'              => (float)$data['cleaning_fee'],
            'extra_person_fee'          => $data['extra_person_fee'],
    	];

        $apartmentSpotsDao->deleteWhere(['apartment_id' => $apartmentId]);
        if (isset($data['parking_spot_ids'])) {
            foreach ($data['parking_spot_ids'] as $priority => $spotId) {
                $apartmentSpotsDao->save(['apartment_id' => $apartmentId, 'spot_id' => $spotId, 'priority' => $priority]);
            }
        }

        unset($data['notify_negative_profit']);
        unset($data['monthly_cost']);
        unset($data['startup_cost']);
        unset($data['show_apartment_entry_code']);
        unset($data['cleaning_fee']);
        unset($data['extra_person_fee']);
        unset($data['parking_lot_id']);
        unset($data['parking_spot_ids']);

        $apartmentDetailsDao->save($detailsData, ['apartment_id' => $apartmentId]);
        $apartmentDescriptionDao->save($data, ['apartment_id' => $apartmentId]);

    	return true;
    }

    /**
	 * @access private
	 * @param string $domain
	 * @return \DDD\Dao\Apartment\Description
	 */
	private function getApartmentDescriptionDao($domain = 'ArrayObject') {
		return new \DDD\Dao\Apartment\Description($this->getServiceLocator(), $domain);
	}

	/**
	 * @access private
	 * @param string $domain
	 * @return \DDD\Dao\Apartment\Details
	 */
	private function getApartmentDetailsDao($domain = 'ArrayObject') {
		return new \DDD\Dao\Apartment\Details($this->getServiceLocator(), $domain);
	}
}
