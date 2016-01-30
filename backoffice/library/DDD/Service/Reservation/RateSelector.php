<?php

namespace DDD\Service\Reservation;

use DDD\Service\ServiceBase;
use DDD\Service\Team\Team as TeamService;
use Library\ActionLogger\Logger;
use Library\Constants\TextConstants;

/**
 * Class RateSelector
 * @package DDD\Service\Reservation
 */
class RateSelector extends ServiceBase
{
    /**
     * @param $reservationId
     * @param $date
     * @param bool $isGetInfo
     * @param $isApartel
     * @return array|\ArrayObject|null
     */
    public function getSelectorRate($reservationId, $date, $isGetInfo = false, $isApartel = false)
    {
        /**
         * @var \DDD\Dao\Apartment\Rate $rateApartmentDao
         * @var \DDD\Dao\Apartel\Rate $rateApartelDao
         * @var \DDD\Dao\Booking\Booking $bookingDao
         * @var Logger $logger
         */

        $rateApartmentDao = $this->getServiceLocator()->get('dao_apartment_rate');
        $rateApartelDao = $this->getServiceLocator()->get('dao_apartel_rate');
        $bookingDao = $this->getServiceLocator()->get('dao_booking_booking');
        $logger = $this->getServiceLocator()->get('ActionLogger');

        // get reservation data
        $reservationData = $bookingDao->getBookingDataForRateSelector($reservationId);

        // get the same rate by policy and capacity
        if ($isApartel) {
            $getSameRate = $rateApartelDao->getSamePolicySameCapacityRate($date, $reservationData['room_id'], $reservationData['is_refundable'], $reservationData['capacity']);
            $getSameRate['apartment_id'] = $reservationData['apartment_id'];
        } else {
            $getSameRate = $rateApartmentDao->getSamePolicySameCapacityRate($date, $reservationData['apartment_id'], $reservationData['is_refundable'], $reservationData['capacity']);
        }

        if ($getSameRate && isset($getSameRate['rate_id']) && $getSameRate['rate_id']) {
            $rate = $getSameRate;
        } else {
            // get parent rate
            if ($isApartel) {
                $parentRate = $rateApartelDao->getParentRateWithInventoryData($date, $reservationData['room_id']);
                $parentRate['apartment_id'] = $reservationData['apartment_id'];
            } else {
                $parentRate = $rateApartmentDao->getParentRateWithInventoryData($date, $reservationData['apartment_id']);
            }

            $rate = $parentRate;
        }

        if (!$isGetInfo) {
            // log about new rate
            $logId = $logger->save(
                Logger::MODULE_BOOKING,
                $reservationId,
                Logger::ACTION_BOOKING_MODIFY,
                sprintf(TextConstants::MODIFICATION_RATE_CHANGE_IF_ORIGINAL_NOT_EXIST, $rate['rate_name'], $date)
            );

            $logsTeamDao = $this->getServiceLocator()->get('dao_action_logs_logs_team');
            $logsTeamDao->save(['action_log_id' => $logId, 'team_id' => TeamService::TEAM_CONTACT_CENTER]);
        }

        return $rate;
    }
}