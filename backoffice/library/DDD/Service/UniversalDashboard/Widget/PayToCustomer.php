<?php

namespace DDD\Service\UniversalDashboard\Widget;

use DDD\Service\ServiceBase;

final class PayToCustomer extends ServiceBase
{
	/**
	 * Get issue detected reservations
	 * @return Array
	 */
	public function getPayToCustomerReservations()
    {
		/* @var $bookingDao \DDD\Dao\Booking\Booking */
		$bookingDao = $this->getServiceLocator()->get('dao_booking_booking');

		$payToCustomerReservations = $bookingDao->getPayToCustomerReservations();

		return $payToCustomerReservations;
	}

    /**
	 * Get issue detected reservations
	 * @return Array
	 */
	public function getPayToCustomerReservationsCount()
    {
		/* @var $bookingDao \DDD\Dao\Booking\Booking */
		$bookingDao = $this->getServiceLocator()->get('dao_booking_booking');
		$count = $bookingDao->getPayToCustomerReservationsCount();

		return $count;
	}
}
