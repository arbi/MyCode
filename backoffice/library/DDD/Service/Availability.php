<?php

namespace DDD\Service;

use DDD\Service\Apartel\General;
use DDD\Dao\Apartment\Inventory;
use DDD\Dao\Apartment\Rate;
use DDD\Dao\Booking\Booking as BookingForAV;
use DDD\Service\Booking\BookingTicket;
use DDD\Dao\Booking\ReservationNightly;
use DDD\Service\Reservation\Main as ReservationMain;
use Library\ActionLogger\Logger;
use Library\Constants\Objects;

/**
 * Class Availability
 * @package DDD\Service
 */

class Availability extends ServiceBase
{
    const AVAILABILITY_MINUS = 1;
    const AVAILABILITY_PLUS = 2;

    /**
     * @param $reservationId
     * @param $availability
     * @param bool $updateChannel
     * @return array
     */
    public function updateAvailabilityAllPartForApartment($reservationId, $availability, $updateChannel = false)
    {
        /**
         * @var BookingForAV $bookingDao
         */
        $bookingDao = $this->getServiceLocator()->get('dao_booking_booking');

        // get reservation data
        $reservationData = $bookingDao->getReservationDataForAvailability($reservationId);

        if (!$reservationData) {
            return ['status' => 'error', 'msg' => 'Bed situation on change Availability'];
        }

        if ($reservationData['overbooking'] == BookingTicket::OVERBOOKING_STATUS_OVERBOOKED) {
            return ['status' => 'error', 'msg' => 'Overbooking'];
        }

        // for new reservation
        $dateFrom = $reservationData['date_from'];
        $dateTo = $reservationData['date_to'];
        $apartmentId = $reservationData['apartment_id'];
        // update apartel by apartment and date range
        return $this->updateAvailabilityForApartmentDateRange($apartmentId, $dateFrom, $dateTo, $availability, $updateChannel);

    }

    /**
     * @param $forAvailabilityUpdate
     * @param $reservationId
     * @return array|bool
     */
    public function updateAvailabilityApartmentByNight($forAvailabilityUpdate, $reservationId)
    {
        if (empty($forAvailabilityUpdate)) {
            return ['status' => 'error', 'msg' => 'Bed situation on change Availability'];
        }

        /**
         * @var BookingForAV $bookingDao
         */
        $bookingDao = $this->getServiceLocator()->get('dao_booking_booking');

        // get reservation data
        $reservationData = $bookingDao->getReservationDataForAvailability($reservationId);

        if (!$reservationData) {
            return ['status' => 'error', 'msg' => 'Bed situation on change Availability'];
        }

        if ($reservationData['overbooking'] == BookingTicket::OVERBOOKING_STATUS_OVERBOOKED) {
            return ['status' => 'error', 'msg' => 'Overbooking'];
        }

        // for new reservation
        $apartmentId = $reservationData['apartment_id'];

        foreach ($forAvailabilityUpdate as $row) {
            $dateFrom = $row['date'];
            $dateTo = date('Y-m-d', strtotime('+1 day', strtotime($row['date'])));

            // update
            $this->updateAvailabilityForApartmentDateRange($apartmentId, $dateFrom, $dateTo, $row['availability'], true);
        }
        return ['status' => 'success'];
    }

    /**
     * @param $apartmentId
     * @param $dateFrom
     * @param $dateTo
     * @param $availability
     * @param bool $updateChannel
     * @return array
     */
    public function updateAvailabilityForApartmentDateRange($apartmentId, $dateFrom, $dateTo, $availability, $updateChannel = false)
    {

        /**
         * @var \DDD\Dao\Apartment\Inventory $inventoryDao
         */
        $inventoryDao = $this->getServiceLocator()->get('dao_apartment_inventory');

        // update availability our system
        $isChanged = 1 - $availability;
        $inventoryDao->updateAvailabilityByApartmentDateRange($apartmentId, $dateFrom, $dateTo, $availability, $isChanged);

        // update channel
        if ($updateChannel) {
            $this->updateAvailabilityChannelByDateRangeForApartment($apartmentId, $dateFrom, $dateTo);
        }

        return ['status' => 'success'];
    }

    /**
     * @param $apartmentId
     * @param $dateFrom
     * @param $dateTo
     * @return bool
     */
    public function updateAvailabilityChannelByDateRangeForApartment($apartmentId, $dateFrom, $dateTo)
    {
        /** @var \DDD\Service\Queue\InventorySynchronizationQueue $syncService */
        $syncService = $this->getServiceLocator()->get('service_queue_inventory_synchronization_queue');
        // send queue
        $syncService->push($apartmentId, $dateFrom, $dateTo);

        return true;
    }

    /**
     * @param $reservationId
     * @return bool
     */
    public function updateChannelByReservationId($reservationId)
    {
        /**
         * @var BookingForAV $bookingDao
         */
        $bookingDao = $this->getServiceLocator()->get('dao_booking_booking');

        // get reservation data
        $reservationData = $bookingDao->getReservationDataForAvailability($reservationId);

        if (!$reservationData) {
            return false;
        }

        // for new reservation
        $dateFrom = $reservationData['date_from'];
        $dateTo = $reservationData['date_to'];
        $apartmentId = $reservationData['apartment_id'];

        // update availability to channel for apartment
        $this->updateAvailabilityChannelByDateRangeForApartment($apartmentId, $dateFrom, $dateTo);

        // update availability to channel for apartel
        $this->updateAvailabilityForApartelByApartmentDateRange($apartmentId, $dateFrom, $dateTo, true);

        return true;
    }

    /**
     * @param $reservationId
     * @param bool $updateChannel
     * @return array
     */
    public function updateAvailabilityAllPartForApartel($reservationId, $updateChannel = false)
    {

        /**
         * @var BookingForAV $bookingDao
         */
        $bookingDao = $this->getServiceLocator()->get('dao_booking_booking');

        // get reservation data
        $reservationData = $bookingDao->getReservationDataForAvailability($reservationId);

        if (!$reservationData) {
            return ['status' => 'error', 'msg' => 'Bed situation on change Availability'];
        }

        if ($reservationData['overbooking'] == BookingTicket::OVERBOOKING_STATUS_OVERBOOKED) {
            return ['status' => 'error', 'msg' => 'Overbooking'];
        }

        // for new reservation
        $dateFrom = $reservationData['date_from'];
        $dateTo = $reservationData['date_to'];
        $apartmentId = $reservationData['apartment_id'];

        // update apartel by apartment and date range
        return $this->updateAvailabilityForApartelByApartmentDateRange($apartmentId, $dateFrom, $dateTo, $updateChannel);
    }

    /**
     * @param $forAvailabilityUpdate
     * @param $reservationId
     * @return array|bool
     */
    public function updateAvailabilityApartelByNight($forAvailabilityUpdate, $reservationId)
    {
        if (empty($forAvailabilityUpdate)) {
            return false;
        }

        /**
         * @var BookingForAV $bookingDao
         */
        $bookingDao = $this->getServiceLocator()->get('dao_booking_booking');

        // get reservation data
        $reservationData = $bookingDao->getReservationDataForAvailability($reservationId);

        if (!$reservationData) {
            return ['status' => 'error', 'msg' => 'Bed situation on change Availability'];
        }

        if ($reservationData['overbooking'] == BookingTicket::OVERBOOKING_STATUS_OVERBOOKED) {
            return ['status' => 'error', 'msg' => 'Overbooking'];
        }

        // for new reservation
        $apartmentId = $reservationData['apartment_id'];

        foreach ($forAvailabilityUpdate as $row) {
            $dateFrom = $row['date'];
            $dateTo = date('Y-m-d', strtotime('+1 day', strtotime($row['date'])));

            // update
            $this->updateAvailabilityForApartelByApartmentDateRange($apartmentId, $dateFrom, $dateTo, $reservationId, true);
        }
    }

    /**
     * @param $apartmentId
     * @param $dateFrom
     * @param $dateTo
     * @param bool $updateChannel
     * @return array
     */
    public function updateAvailabilityForApartelByApartmentDateRange($apartmentId, $dateFrom, $dateTo, $updateChannel = false)
    {
        /**
         * @var \DDD\Dao\Apartel\RelTypeApartment $relApartelRoomTypeApartment
         * @var \DDD\Dao\Apartel\Inventory $inventoryDao
         */
        $relApartelRoomTypeApartment = $this->getServiceLocator()->get('dao_apartel_rel_type_apartment');
        $inventoryDao = $this->getServiceLocator()->get('dao_apartel_inventory');

        // get apartel rooms for this apartment
        $apartelRoomTypes = $relApartelRoomTypeApartment->getApartelRoomTypeByApartment($apartmentId);
        foreach ($apartelRoomTypes as $roomType) {
            $roomTypeId = $roomType['apartel_type_id'];
            // update availability our system
            $inventoryDao->setApartelAvailabilityByRoomType($roomTypeId, $dateFrom, $dateTo);

            // update channel
            if ($updateChannel) {
                $this->updateAvailabilityChannelByDateRangeForApartel($roomTypeId, $dateFrom, $dateTo);
            }
        }

        return ['status' => 'success'];
    }

    /**
     * @param $apartmentId
     * @param $dateFrom
     * @param $dateTo
     * @param bool $updateChannel
     * @return array
     */
    public function updateAvailabilityWithQueueForApartelByApartmentDateRange($apartmentId, $dateFrom, $dateTo, $updateChannel = false)
    {
        /**
         * @var \DDD\Dao\Apartel\RelTypeApartment $relApartelRoomTypeApartment
         * @var \DDD\Dao\Apartel\Inventory $inventoryDao
         *
         */
        $relApartelRoomTypeApartment = $this->getServiceLocator()->get('dao_apartel_rel_type_apartment');
        $inventoryDao = $this->getServiceLocator()->get('dao_apartel_inventory');

        // get apartel rooms for this apartment
        $apartelRoomTypes = $relApartelRoomTypeApartment->getApartelRoomTypeByApartment($apartmentId);
        foreach ($apartelRoomTypes as $roomType) {
            $roomTypeId = $roomType['apartel_type_id'];
            // update availability our system
            $inventoryDao->setApartelAvailabilityByRoomType($roomTypeId, $dateFrom, $dateTo);

            // update channel
            if ($updateChannel) {
                $this->updateAvailabilityChannelByDateRangeForApartel($roomTypeId, $dateFrom, date('Y-m-d',  strtotime('-1 day', strtotime($dateTo))));
            }
        }

        return ['status' => 'success'];
    }

    /**
     * @param $roomTypeId
     * @param $dateFrom
     * @param $dateTo
     * @return bool
     */
    public function updateAvailabilityChannelByDateRangeForApartel($roomTypeId, $dateFrom, $dateTo)
    {
        /** @var \DDD\Service\Queue\InventorySynchronizationQueue $syncService */
        $syncService = $this->getServiceLocator()->get('service_queue_inventory_synchronization_queue');

        // send queue
        $syncService->push($roomTypeId, $dateFrom, $dateTo, [], $syncService::ENTITY_TYPE_APARTEL);

        return true;
    }
}
