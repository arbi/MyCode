<?php

namespace DDD\Service;

use DDD\Service\ServiceBase;
use Library\Utility\Debug;
use Zend\Validator\Date;
use Zend\I18n\Validator\Int;

class GroupInventory extends ServiceBase
{
    public function getOptions($hasDevTestRole = false)
    {
        $dao = new \DDD\Dao\ApartmentGroup\ApartmentGroup(
            $this->getServiceLocator(),
            'DDD\Domain\ApartmentGroup\ForSelect'
        );

        $groups = $dao->getAllGroups($hasDevTestRole);
        $params['groups'] = $groups;

        return $params;
    }

    /**
     * @param $apartmentGroupId
     * @param $from
     * @param $to
     * @param int $roomCount
     * @param $sort
     * @param $roomType
     * @return array
     */
    public function composeGroupAvailabilityForDateRange($apartmentGroupId, $from, $to, $roomCount = -1, $sort, $roomType)
    {
        /**
         * @var \DDD\Domain\Apartment\Inventory\GroupInventory[] $resultSet
         * @var \DDD\Dao\Booking\Booking $reservationDao
         */
        $dateValidator = new Date(['format' => 'Y-m-d']);

    	if ($dateValidator->isValid($from) &&  $dateValidator->isValid($to)) {
            $apartmentService = $this->getServiceLocator()->get('service_apartment_general');
            $apartmentDao     = new \DDD\Dao\Apartment\General(
                $this->getServiceLocator(),
                'DDD\Domain\Apartment\Inventory\GroupInventory'
            );

            $reservationDao = $this->getServiceLocator()->get('dao_booking_booking');
            $overbookings = $reservationDao->getApartmentGroupOverbookingsForDateRange($apartmentGroupId, $from, $to, $roomCount, $roomType);

            $result = [];
            $dates  = [];
            $existingDates = [];

    		$resultSet = $apartmentDao->getGroupAvailabilityForDateRange($apartmentGroupId, $from, $to, $roomCount, $sort, $roomType);

    		foreach ($resultSet as $row) {
                $apartmentId = $row->getId();
                $date        = $row->getDate();

        		if (!in_array($date, $existingDates)) {
                    array_push($existingDates, $date);
                    array_push($dates, [
                        'raw' => $date,
                        'dayOfWeek' => date('D', strtotime($date)),
                        'day' => date('j', strtotime($date)),
                        'month' => date('n', strtotime($date)),
                    ]);
                }
        		if (isset($result[$apartmentId])) {

        			$result[$apartmentId][$date] = [
                        'av'               => $row->getAvailability(),
                        'reservation_data' => $row->getReservationData(),
        			];

        		} else {
                    $balcony = !is_null($row->getAmenityId()) ? ' (B)' : '';
                    $bedroom = $row->getBedroom_count() . $balcony;

        			$result[$apartmentId] = [
        				'id'			=> $row->getId(),
        				'name'			=> $row->getName(),
                        'floor'         => $row->getFloor(),
                        'block'         => $row->getBlock(),
                        'bedroom'		=> $bedroom,
                        'bathroom'		=> $row->getBathroom_count(),
                        'max_capacity'	=> $row->getMax_capacity(),
                        'unit_number'   => $row->getUnit_number(),
        				'building_name' => $row->getBuildingName(),
        				'links'			=> $apartmentService->getWebsiteLink($row->getId()),
        				$row->getDate()	=> [
        					'av' => $row->getAvailability(),
        					'reservation_data' => $row->getReservationData(),
        				],
        			];
        		}
        	}

        	if (!empty($result)) {
        		$result = array_values($result);
            }

        	sort($dates);

        	return ['list' => $result, 'days' => $dates, 'overbookings' => $overbookings];
        } else {
        	return ['list' => [], 'days' => [], 'overbookings' => []];
        }
    }
}
