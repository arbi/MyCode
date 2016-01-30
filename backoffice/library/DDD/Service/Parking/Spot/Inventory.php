<?php

namespace DDD\Service\Parking\Spot;

use DDD\Dao\Apartment\Spots;
use DDD\Service\ServiceBase;
use Zend\Db\Sql\Select;
use DDD\Service\Booking\BookingAddon;
use DDD\Dao\Apartment\Details;
use DDD\Service\User;
use DDD\Service\Booking\Charge as ChargeService;

class Inventory extends ServiceBase
{
    const FILL_MARGIN  = 5;
    const IS_AVAILABLE = 1;

    /**
     * @param int $spotId
     * @param string $year
     * @param string $month
     * @return \DDD\Domain\Parking\Spot\Inventory[]|null
     */
    public function getSpotAvailabilityForMonth($spotId, $year, $month)
    {
        $firstDay = date('Y-m-d', strtotime('first day of ' . $year . '-' . $month));
        $lastDay = date('Y-m-d', strtotime('last day of ' . $year . '-' . $month));

        $spotInventoryDao = $this->getServiceLocator()->get('dao_parking_spot_inventory');
        $monthInventory   = $spotInventoryDao->getSpotInventoryForRange($spotId, $firstDay, $lastDay);

        return $monthInventory;
    }

    /**
     * @param string $dateFrom
     * @param string $dateTo
     * @param int $spotId
     * @return void
     */
    public function fillInventory($dateFrom, $dateTo, $spotId)
    {
        /**
         * @var \DDD\Dao\Parking\Spot\Inventory $inventoryDao
         */
        $inventoryDao = $this->getServiceLocator()->get('dao_parking_spot_inventory');
        $dateSeeker = new \DateTime($dateFrom);

        $data = [];

        while ($dateSeeker->format('Y-m-d') <= $dateTo) {
            $date = $dateSeeker->format('Y-m-d');
            $dateSeeker->modify('+1 day');

            array_push($data, [
                'spot_id' => $spotId,
                'date' => $date,
                'availability' => 1,
            ]);
        }

        $inventoryDao->multiInsert($data);
    }

    /**
     * @return \DDD\Domain\Parking\Spot\Inventory[]|null
     */
    public function getEndDates()
    {
        /**
         * @var \DDD\Dao\Parking\Spot\Inventory $inventoryDao
         */
        $inventoryDao = $this->getServiceLocator()->get('dao_parking_spot_inventory');
        return $inventoryDao->getEndDates();
    }

    public function getAvailableSpotsForApartmentForDateRangeByPriority(
        $apartmentId,
        $startDate,
        $endDate,
        $spotsAlreadySelectedInSameChargeSession,
        $isSelectedDate = false
    ) {
        /**
         * @var Spots $apartmentSpotsDao
         */
        $apartmentSpotsDao   = $this->getServiceLocator()->get('dao_apartment_spots');
        $apartmentDao        = $this->getServiceLocator()->get('dao_accommodation_accommodations');
        $apartmentService = $this->getServiceLocator()->get('service_apartment_general');
        $allAvailableSpots   = [];
        $directSpots         = [];
        $selectedDates       = [];
        $allAvailable        = false;
        $selectSameSpot      = true;

        $apartmentTimezone = $apartmentService->getApartmentTimezoneById($apartmentId)['timezone'];
        $datetime = new \DateTime('now');
        $datetime->setTimezone(new \DateTimeZone($apartmentTimezone));
        $dateToday             = $datetime->format('Y-m-d');
        $apartmentPreferSpots = $apartmentSpotsDao->getApartmentSpots($apartmentId);
        foreach ($apartmentPreferSpots as $apartmentPreferSpot) {

            $res = $apartmentSpotsDao->getAvailableSpotsForApartmentForDateRangeByPriority(
                $apartmentPreferSpot['spot_id'],
                $startDate,
                $endDate,
                $spotsAlreadySelectedInSameChargeSession,
                $dateToday,
                $isSelectedDate
            );
            if ($res !== false) {
                array_push($allAvailableSpots,
                    [
                        'id'      => $res['parking_spot_id'],
                        'name'    => $res['unit'] . '(' . $res['name'] . ')',
                        'price'   => $res['price'],
                        'date'    => $res['date']
                    ]
                );
                array_push($directSpots, $res['parking_spot_id']);
            }
        }

        $availableSpotsByLot = $apartmentDao->getAvailableSpotsInLotForApartmentForDateRangeByPriority(
            $apartmentId,
            $startDate,
            $endDate,
            $spotsAlreadySelectedInSameChargeSession,
            $isSelectedDate,
            $directSpots,
            $dateToday,
            $selectSameSpot
        );

        if ($availableSpotsByLot->count()) {
            foreach ($availableSpotsByLot as $availableSpotByLot) {
                array_push(
                    $allAvailableSpots,
                    [
                        'id'      => $availableSpotByLot['parking_spot_id'],
                        'name'    => $availableSpotByLot['unit'] . '(' . $availableSpotByLot['name'] . ')',
                        'price'   => $availableSpotByLot['price'],
                        'date'    => $availableSpotByLot['date']
                    ]
                );
                array_push($directSpots, $availableSpotByLot['parking_spot_id']);
            }
        } elseif (!$allAvailableSpots) {

            $allAvailable = true;
            $availableSpotsByLot = $apartmentDao->getAvailableSpotsInLotForApartmentForDateRangeByPriority(
                $apartmentId,
                $startDate,
                $endDate,
                $spotsAlreadySelectedInSameChargeSession,
                $isSelectedDate,
                [],
                $dateToday,
                false,
                false
            );

            $allAvailableSpots = [];

            foreach ($availableSpotsByLot as $availableSpotByLot) {
                array_push($selectedDates, $availableSpotByLot['date']);
                array_push(
                    $allAvailableSpots,
                    [
                        'id'      => $availableSpotByLot['parking_spot_id'],
                        'name'    => $availableSpotByLot['unit'] . '(' . $availableSpotByLot['name'] . ')',
                        'price'   => $availableSpotByLot['price'],
                        'date'    => $availableSpotByLot['date']
                    ]
                );
            }

            $date1    = date_create($startDate);
            $date2    = date_create($endDate);
            $dateDiff = date_diff($date2, $date1)->d + 1;

            $selectedDates = count(array_unique($selectedDates));

            if ($dateDiff != $selectedDates) {
                return [
                    'allAvailable'   => $allAvailable ,
                    'availableSpots' => []
                ];
            }
        }

        return [
            'allAvailable'   => $allAvailable,
            'availableSpots' => $allAvailableSpots
        ];
    }

    /**
     * @param int $apartmentId
     * @param string $dateFrom
     * @param string $dateTo
     * @return bool|array
     */
    public function getAvailableSpotForApartment($apartmentId, $dateFrom, $dateTo)
    {
        /**
         * @var \DDD\Dao\Apartment\Details $apartmentDetailsDao
         */
         $apartmentDetailsDao = $this->getServiceLocator()->get('dao_apartment_details');
         $apartmentSpotsDao   = $this->getServiceLocator()->get('dao_apartment_spots');

        $prioritySpots  = $apartmentSpotsDao->getApartmentParkingPrioritySpots($apartmentId);
        $availableSpots = $apartmentSpotsDao->getAvailableSpotsForApartment($apartmentId, $dateFrom, $dateTo);

        if ($availableSpots && $availableSpots->count()) {
            $availableSpotsArr = [];

            foreach ($availableSpots as $availableSpot) {
                $availableSpotsArr[$availableSpot['spot_id']] = $availableSpot;
            }

            if ($prioritySpots) {
                foreach ($prioritySpots as $spot) {
                    if ($spot && !empty($availableSpotsArr[$spot])) {
                        return $availableSpotsArr[$spot];
                    }
                }
            }

            // In case that priority spots are not available, take the first available
            return array_shift($availableSpotsArr);
        } else {
            return false;
        }
    }

    public function changeParkingByApartment($resId, $resNumber, $newApartmentId, $startDate, $endDate, $originStartDate)
    {
        /**
         * @var \DDD\Dao\Apartment\Details $apartmentDetailsDao
         * @var \DDD\Service\Booking\Charge $chargeService
         * @var \DDD\Dao\Booking\ChargeDeleted $chargeDeletedDao
         */
        $apartmentService = $this->getServiceLocator()->get('service_apartment_general');
        $apartmentDao        = $this->getServiceLocator()->get('dao_accommodation_accommodations');
        $chargeService       = $this->getServiceLocator()->get('service_booking_charge');
        $taskService         = $this->getServiceLocator()->get('service_task');
        $chargesDao          = $this->getServiceLocator()->get('dao_booking_charge');
        $apartmentSpotsDao   = $this->getServiceLocator()->get('dao_apartment_spots');
        $chargeDeletedDao    = $this->getServiceLocator()->get('dao_booking_charge_deleted');

        $chargesBlocks = [];
        $preferedSpot  = [];

        $apartmentTimezone = $apartmentService->getApartmentTimezoneById($newApartmentId)['timezone'];
        $datetime = new \DateTime('now');
        $datetime->setTimezone(new \DateTimeZone($apartmentTimezone));
        $dateToday             = $datetime->format('Y-m-d');
        $charges = $chargesDao->getChargesByReservationId($resId, 1, BookingAddon::ADDON_TYPE_PARKING);
        // check if the reservation does not have any parking charges do nothing
        if (!$charges->count()) {
            return;
        }

        // check if moving date is the middle of reservation, just reverse parking charges and create task.
        if (strtotime($startDate) != strtotime($originStartDate)) {
            $taskService->createParkingMoveTask($resId, $resNumber, $newApartmentId);
            return;
        }

        // revers params
        $reverseParams = [
            'reservation_id' => $resId,
            'date' 			 => date('Y-m-d H:i:s'),
            'user_id' 		 => User::SYSTEM_USER_ID,
        ];

        $i = 0;
        $preDate = false;
        // 1: First reverse charges
        foreach ($charges as $charge) {

            $removedId = $charge->getId();
            $chargesDao->save(['status' => ChargeService::CHARGE_STATUS_DELETED], ['id' => $removedId]);

            if (!$chargeDeletedDao->fetchOne(['reservation_charge_id' => $removedId])) {
                $reverseParams['reservation_charge_id'] = $removedId;
                $chargeDeletedDao->save($reverseParams);
            }

            if (!$preDate || (strtotime($charge->getReservationNightlyDate()) > strtotime($preDate))) {
                $chargesBlocks[$i][$charge->getReservationNightlyDate()] = $charge;
            } else {
                ++$i;
                $chargesBlocks[$i][$charge->getReservationNightlyDate()] = $charge;
            }

            $preDate = $charge->getReservationNightlyDate();
        }

        $this->changeSpotAvailability($chargesBlocks, 1);

        // update balance
        $chargeService->updateBalance($resId);

        // check new apartment prefered spot ids
        // if it does have just create task
        $newApartmentPreferedSpotId = [];

        $apartmentPreferSpots = $apartmentSpotsDao->getApartmentSpots($newApartmentId);
        if ($apartmentPreferSpots->count()) {
            foreach ($apartmentPreferSpots as $apartmentPreferSpot) {
                array_push($newApartmentPreferedSpotId, $apartmentPreferSpot['spot_id']);
            }
        } else {
            $taskService->createParkingMoveTask($resId, $resNumber, $newApartmentId);
            return;
        }

        // 2: Then check check new availability
        $alreadySelectedSpots = [];
        // SELECT the most prefered spot for charges
        foreach ($chargesBlocks as $key => $charges) {
            $dates     = array_keys($charges);
            $startDate = min($dates);
            $endDate   = max($dates);

            $availableSpot = $apartmentDao->getAvailableSpotsInLotForApartmentForDateRangeByPriority(
                $newApartmentId,
                $startDate,
                $endDate,
                $alreadySelectedSpots,
                false,
                [],
                $dateToday,
                true,
                $newApartmentPreferedSpotId
            );

            $preferedSpot[$key] = false;

            if (!$availableSpot->count()) {
                $taskService->createParkingMoveTask($resId, $resNumber, $newApartmentId);
                return;
            } else {
                foreach ($availableSpot as $row) {
                    $preferedSpot[$key] = $row;
                    array_push($alreadySelectedSpots, $row['parking_spot_id']);
                }
            }
        }

        $spotIds        = [];
        $rateNames      = [];
        $prices         = [];
        $nightDate      = [];
        $nightlyIds     = [];
        $moneyDirection = [];
        $addonstype     = [];
        $commissions    = [];

        foreach ($chargesBlocks as $key => $charges) {
            foreach ($charges as $date => $charge) {
                array_push($spotIds, $preferedSpot[$key]['parking_spot_id']);
                array_push($rateNames, $preferedSpot[$key]['unit'] . '(' .  $preferedSpot[$key]['name'] . ')');
                array_push($prices, $charge->getAcc_amount());
                array_push($nightDate, $date);
                array_push($nightlyIds, $charge->getReservationNightlyId());
                array_push($moneyDirection, $charge->getMoneyDirection());
                array_push($commissions, $charge->getCommission());
                array_push($addonstype, BookingAddon::ADDON_TYPE_PARKING);

                $customerCurrency      = $charge->getCustomer_currency();
                $accommodationCurrency = $charge->getApartmentCurrency();
                $type                  = $charge->getType();
            }
        }

        $parkingChargesData = [
            'reservation_id' 	        => $resId,
            'date' 				        => date('Y-m-d H:i:s'),
            'customerCurrency'          => $customerCurrency,
            'accommodationCurrency' 	=> $accommodationCurrency,
            'apartment_id' 		        => (int)$newApartmentId,
            'type' 				        => $type,
            'spotIds'                   => $spotIds,
            'reservation_nightly_ids'   => $nightlyIds,
            'nightDate'                 => $nightDate,
            'rateNames'                 => $rateNames,
            'entityId'                  => $spotIds,
            'accommodation_amount'      => $prices,
            'addonstype'                => $addonstype,
            'new_addon_money_direction' => $moneyDirection,
            'res_number'                => $resNumber,
            'accId'                     => $newApartmentId,
            'charge_comment'            => '',
            'new_addon_commission'      => $commissions
        ];

        $chargeResult = $chargeService->saveCharge($parkingChargesData, User::SYSTEM_USER_ID);

        if ($chargeResult) {
            // update balance for new charges
            $chargeService->updateBalance($resId);
        }
    }

    private function changeSpotAvailability($parkingCharges, $status)
    {
        $inventoryDao = $this->getServiceLocator()->get('dao_parking_spot_inventory');
        foreach ($parkingCharges as $charges) {
            foreach ($charges as $charge) {
                $inventoryDao->save(
                    ['availability' => $status],
                    [
                        'spot_id' => $charge->getEntityId(),
                        'date'    => $charge->getReservationNightlyDate()
                    ]
                );
            }
        }
    }
}
