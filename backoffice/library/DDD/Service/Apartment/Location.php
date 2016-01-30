<?php

namespace DDD\Service\Apartment;

use DDD\Service\ServiceBase;
use FileManager\Constant\DirectoryStructure;
use DDD\Dao\Apartment\General as ApartmentGeneral;
use DDD\Dao\ApartmentGroup\ApartmentGroupItems;

class Location extends ServiceBase
{
    public function getApartmentLocation($apartmentId)
    {
    	$apartmentLocationDao = $this->getApartmentLocationDao();
    	$location = $apartmentLocationDao->getApartmentLocation($apartmentId);

    	return $location;
    }

    public function saveApartmentLocation($apartmentId, $location)
    {
    	// apartment location data consists from 3 parts - from general table, location table and room table
    	// let's save it step by step

    	// common condition
    	$where = ['apartment_id' => $apartmentId];
        $apartmentGeneralDao = $this->getApartmentGeneralDao();
    	$generalData = [
    		'province_id' => $location['province_id'],
    		'city_id' => $location['city_id'],
    		'address' => $location['address'],
    		'postal_code' => $location['postal_code'],
    		'block' => $location['block'],
    		'floor' => $location['floor'],
    		'unit_number' => $location['unit_number'],
    	];

        if(isset($location['country_id'])) {
            $locationData = $apartmentGeneralDao->fetchOne(['id' => $apartmentId]);

            if($locationData && $locationData['country_id'] == null) { // null - value set then create new apartment
                $generalData['country_id'] = $location['country_id'];
                $countryDao = $this->getServiceLocator()->get('dao_geolocation_countries');
                $countryData = $countryDao->fetchOne(['id' => (int)$location['country_id']]);
                if($countryData && $countryData->getCurrencyId()) {
                    $generalData['currency_id'] = $countryData->getCurrencyId();
                }
            }

        }
    	$geoLocationData = [
    		'x_pos' => $location['longitude'],
    		'y_pos' => $location['latitude'],
    	];

        $apartmentLocationDao = $this->getApartmentLocationDao();
        $currentState = $apartmentLocationDao->getApartmentLocation($apartmentId);

        /* @var $apartmentGroupItemsDao ApartmentGroupItems */
        $apartmentGroupItemsDao = $this->getServiceLocator()->get('dao_apartment_group_apartment_group_items');
        $apartments = $apartmentGroupItemsDao->getApartmentGroupItems($currentState->getBuildingID(), true);

        /* @var $apartmentsGeneralDao ApartmentGeneral */
        $apartmentsGeneralDao = $this->getServiceLocator()->get('dao_apartment_general');

        if (!$currentState->getBuildingID()) {

            $apartmentGroupItemsDao->insert([
                'apartment_group_id' => $location['building'],
                'apartment_id' => $apartmentId
            ]);

            $apartmentsGeneralDao->update(
                [
                    'building_id' => $location['building'],
                    'building_section_id' => $location['building_section']
                ],
                ['id' => $apartmentId]
            );
        } elseif ($currentState->getBuildingID() != $location['building']) {
            foreach ($apartments as $apartment) {
                if ($apartmentId == $apartment->getApartmentId()) {
                    // move to other building
                    $apartmentGroupItemsDao->delete(['id' => $apartment->getId()]);
                    $apartmentGroupItemsDao->insert([
                        'apartment_group_id' => $location['building'],
                        'apartment_id' => $apartmentId
                    ]);

                    $apartmentsGeneralDao->update(
                        [
                            'building_id' => $location['building'],
                            'building_section_id' => $location['building_section']
                        ],
                        ['id' => $apartmentId]
                    );
                }
            }
        } elseif ($currentState->getBuildingID() == $location['building'] && $currentState->getBuildingSectionId() != $location['building_section']) {
            $apartmentsGeneralDao->update(
                [
                    'building_id' => $location['building'],
                    'building_section_id' => $location['building_section']
                ],
                ['id' => $apartmentId]
            );

            // Remove unused spots if change section
            /* @var \DDD\Dao\Apartment\Spots $apartmentSpotsDao */
            $apartmentSpotsDao = $this->getServiceLocator()->get('dao_apartment_spots');
            $apartmentSpotsDao->removeUnusedSpots($apartmentId, $location['building_section']);
        }

    	$resultGeneral = $apartmentGeneralDao->save($generalData, ['id' => $apartmentId]);
    	$resultGeoLocation = $apartmentLocationDao->save($geoLocationData, $where);

    	return $resultGeneral && $resultGeoLocation;
    }

    /**
     * @param string $domain
     * @return \DDD\Dao\Apartment\Location
     */
    public function getApartmentLocationDao($domain = '\DDD\Domain\Apartment\Location\Location')
    {
		return new \DDD\Dao\Apartment\Location($this->getServiceLocator(), $domain);
	}

	/**
	 * @param string $domain
	 * @return \DDD\Dao\Apartment\Room
	 */
	public function getApartmentRoomDao($domain = 'ArrayObject')
    {
		return new \DDD\Dao\Apartment\Room($this->getServiceLocator(), $domain);
	}

	/**
	 * @param string $domain
	 * @return \DDD\Dao\Apartment\General
	 */
	public function getApartmentGeneralDao($domain = 'ArrayObject')
    {
		return new \DDD\Dao\Apartment\General($this->getServiceLocator(), $domain);
	}

	/**
	 * @param string $domain
	 * @return \DDD\Dao\Apartment\Textline
	 */
	public function getApartmentTextlineDao($domain = 'ArrayObject')
    {
		return new \DDD\Dao\Apartment\Textline($this->getServiceLocator(), $domain);
	}
}
