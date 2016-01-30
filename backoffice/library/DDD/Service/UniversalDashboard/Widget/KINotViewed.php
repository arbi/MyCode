<?php

namespace DDD\Service\UniversalDashboard\Widget;

use DDD\Service\ServiceBase;

/**
 * Methods to work with "Key Instructions Not Viewed Reservations" widget
 * @author Tigran Petrosyan
 */
final class KINotViewed extends ServiceBase
{
	/**
	 * Get KI not viewed reservations
	 * @return Array
	 */
	public function getKINotViewedReservations()
    {
		/* @var $bookingDao \DDD\Dao\Booking\Booking */
		$bookingDao = $this->getServiceLocator()->get('dao_booking_booking');

		$kiNotViewedReservations = $bookingDao->getKINotViewedReservations();

		return $kiNotViewedReservations;
	}

    /**
	 * Get KI not viewed reservations
	 * @return int
	 */
	public function getKINotViewedReservationsCount()
    {
		/* @var $bookingDao \DDD\Dao\Booking\Booking */
		$bookingDao = $this->getServiceLocator()->get('dao_booking_booking');
		return $bookingDao->getKINotViewedReservationsCount();
	}
}
