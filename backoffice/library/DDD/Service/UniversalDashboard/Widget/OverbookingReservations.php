<?php

namespace DDD\Service\UniversalDashboard\Widget;

use DDD\Dao\Booking\Booking;
use DDD\Service\Booking\BookingTicket;
use DDD\Service\ServiceBase;

/**
 * Methods to work with "Overbooking reservations" widget
 *
 * @author Tigran Petrosyan
 */
final class OverbookingReservations extends ServiceBase
{
    /**
     * @return \DDD\Domain\UniversalDashboard\Widget\OverbookingReservation[]
     */
    public function getOverbookingReservations()
	{
        /**
         * @var Booking $reservationsDao
         */
        $reservationsDao = $this->getServiceLocator()->get('dao_booking_booking');

		$result = $reservationsDao->getOverbookingReservations();

		return $result;
	}

    /**
     * @return int
     */
    public function getOverbookingReservationsCount()
	{
        /**
         * @var Booking $reservationsDao
         */
        $reservationsDao = $this->getServiceLocator()->get('dao_booking_booking');

        $count = $reservationsDao->getOverbookingReservationsCount();

		return $count;
	}
}