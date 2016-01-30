<?php

namespace DDD\Service\UniversalDashboard\Widget;

use DDD\Service\ServiceBase;

/**
 * Methods to work with "Validate CC" widget
 * @author Tigran Petrosyan
 */
final class ValidateCC extends ServiceBase
{
	/**
	 * Get "validate cc" reservations
	 * @return Array
	 */
	public function getValidateCCReservations() {
		/* @var $bookingDao \DDD\Dao\Booking\Booking */
		$bookingDao = $this->getServiceLocator()->get('dao_booking_booking');

		$validateCCReservations = $bookingDao->getValidateCCReservations();

		return $validateCCReservations;
	}

    /**
	 * Get "validate cc" reservations
	 * @return int
	 */
	public function getValidateCCReservationsCount() {
		/* @var $bookingDao \DDD\Dao\Booking\Booking */
		$bookingDao = $this->getServiceLocator()->get('dao_booking_booking');

		$count = $bookingDao->getValidateCCReservationsCount();

		return $count;
	}
}
