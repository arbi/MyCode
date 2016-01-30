<?php

namespace DDD\Service\Reservation;

use DDD\Service\Partners as PartnerService;
use DDD\Service\ServiceBase;
use DDD\Service\Taxes;
use Library\Constants\TextConstants;

/**
 * Class Identificator
 * @package DDD\Service\Reservation
 */
class Identificator extends ServiceBase
{
    /**
     * @param $roomStayList
     * @param $channelResId
     * @return array|bool
     */
    public function identificatorModification($roomStayList, $channelResId)
    {
        /**
         * @var \DDD\Dao\ChannelManager\ReservationIdentificator $reservationIdentificatorDao
         */
        if (!$channelResId || empty($roomStayList)) {
            return false;
        }

        $correctRoomStayList = [
            'cancel' => [],
            'modify' => [],
            'new' => [],
        ];

        // get current reservation with channel res id
        $reservationIdentificatorDao = $this->getServiceLocator()->get('dao_channel_manager_reservation_identificator');
        $oldReservationsObjects = $reservationIdentificatorDao->getReservationsByChannelResId($channelResId);
        /** @var \DDD\Domain\Booking\ChannelReservation[] $oldReservations */
        $oldReservations = [];
        foreach ($oldReservationsObjects as $object) {
            $oldReservations[] = $object;
        }

        $buildingId = 0;
        // check with guest name and date
        foreach ($oldReservations as $key => $old) {
            if (!$buildingId) {
                $buildingId = $old->getBuildingId();
            }
            $keyRoomStay = $this->compareWith($roomStayList, $old->getRoomId(), $old->getRateId(), $old->getGuestName(), $old->getIDateFrom(), $old->getIDateTo());
            if ($keyRoomStay !== false) {
                $correctRoomStayList['modify'][] = ['roomStay' => $roomStayList[$keyRoomStay], 'resData' => $old];
                unset($roomStayList[$keyRoomStay]);
                unset($oldReservations[$key]);
            }
        }

        // check with guest name
        foreach ($oldReservations as $key => $old) {
            $keyRoomStay = $this->compareWith($roomStayList, $old->getRoomId(), $old->getRateId(), $old->getGuestName());
            if ($keyRoomStay !== false) {
                $correctRoomStayList['modify'][] = ['roomStay' => $roomStayList[$keyRoomStay], 'resData' => $old];
                unset($roomStayList[$keyRoomStay]);
                unset($oldReservations[$key]);
            }
        }

        // check with date
        foreach ($oldReservations as $key => $old) {
            $keyRoomStay = $this->compareWith($roomStayList, $old->getRoomId(), $old->getRateId(), false, $old->getIDateFrom(), $old->getIDateTo());
            if ($keyRoomStay !== false) {
                $correctRoomStayList['modify'][] = ['roomStay' => $roomStayList[$keyRoomStay], 'resData' => $old];
                unset($roomStayList[$keyRoomStay]);
                unset($oldReservations[$key]);
            }
        }

        // check without guest name and date
        foreach ($oldReservations as $key => $old) {
            $keyRoomStay = $this->compareWith($roomStayList, $old->getRoomId(), $old->getRateId());
            if ($keyRoomStay !== false) {
                $correctRoomStayList['modify'][] = ['roomStay' => $roomStayList[$keyRoomStay], 'resData' => $old];
                unset($roomStayList[$keyRoomStay]);
                unset($oldReservations[$key]);
            }
        }

        // if $oldReservations not empty than is cancelation reservation
        foreach ($oldReservations as $key => $old) {
            $correctRoomStayList['cancel'][] = $old;
            unset($oldReservations[$key]);
        }

        // if $roomStayList not empty than is new reservation
        foreach ($roomStayList as $key => $roomStay) {
            $correctRoomStayList['new'][] = ['roomStay' => $roomStay, 'buildingId' => $buildingId];
            unset($roomStayList[$key]);
        }

        return $correctRoomStayList;
    }

    /**
     * @param $roomStayList
     * @param $roomId
     * @param $rateId
     * @param bool $guestName
     * @param bool $dateFrom
     * @param bool $dateTo
     * @return bool|int|string
     */
    private function compareWith($roomStayList, $roomId, $rateId, $guestName = false, $dateFrom = false, $dateTo = false)
    {
        $guestName = strtolower(trim($guestName));
        foreach ($roomStayList as $key => $roomStay) {
            if (
                $roomStay['roomTypeId'] == $roomId && $roomStay['rateId'] == $rateId &&
                (!$guestName || ($guestName && strtolower(trim($roomStay['guestName'])) == $guestName)) &&
                ((!$dateFrom && !$dateTo) || ($dateFrom && $dateTo && $dateFrom == $roomStay['dateFrom'] &&  $dateTo == $roomStay['dateTo']))

            ) {
                return $key;
            }
        }

        return false;
    }
}