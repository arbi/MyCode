<?php

namespace DDD\Service\UniversalDashboard\Widget;

use DDD\Dao\Booking\Booking as ReservationDAO;
use DDD\Service\ServiceBase;

/**
 * Class NotChargedApartelReservations
 * Service class providing Methods to work with "Charge Apartel Reservations" widget
 * @package DDD\Service\UniversalDashboard\Widget
 *
 * @author Tigran Petrosyan
 */
final class NotChargedApartelReservations extends ServiceBase
{
    /**
     * Get not charged apartel reservations which are in penalty period
     * @return \DDD\Domain\UniversalDashboard\Widget\NotChargedApartelReservations[]
     */
    public function getNotChargedApartelReservations()
    {
        /**
         * @var ReservationDAO $reservationDao
         */
        $reservationDao = $this->getServiceLocator()->get('dao_booking_booking');

		$notChargedApartelReservations = $reservationDao->getNotChargedApartelReservations();

		return $notChargedApartelReservations;
	}

    /**
	 * Get not charged apartel reservations count
	 * @return int
	 */
	public function getNotChargedApartelReservationsCount()
    {
        /**
         * @var ReservationDAO $reservationDao
         */
        $reservationDao = $this->getServiceLocator()->get('dao_booking_booking');

		$count = $reservationDao->getNotChargedApartelReservationsCount();

		return $count;
	}
}
