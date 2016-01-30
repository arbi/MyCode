<?php

namespace DDD\Service\UniversalDashboard\Widget;

use DDD\Service\ServiceBase;

/**
 * Methods to work with "No Collection Reservations" widget
 * @author Tigran Petrosyan
 */
final class NoCollection extends ServiceBase
{
	/**
	 * Get No Collection reservations
	 * @return Array
	 */
	public function getNoCollectionReservations()
    {
		/* @var $bookingDao \DDD\Dao\Booking\Booking */
		$bookingDao = $this->getServiceLocator()->get('dao_booking_booking');

		$noCollectionReservations = $bookingDao->getNoCollectionReservations();

		return $noCollectionReservations;
	}
	/**
	 * Get No Collection reservations count
	 * @return int
	 */
	public function getNoCollectionReservationsCount()
    {
		/* @var $bookingDao \DDD\Dao\Booking\Booking */
		$bookingDao = $this->getServiceLocator()->get('dao_booking_booking');

		$count = $bookingDao->getNoCollectionReservationsCount();

		return $count;
	}
}

?>