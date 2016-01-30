<?php

namespace DDD\Service\UniversalDashboard\Widget;

use DDD\Service\ServiceBase;

/**
 * Methods to work with "Collect From Customer" widget
 * @author Tigran Petrosyan
 */
final class CollectFromCustomer extends ServiceBase
{
	/**
	 * Get issue detected reservations
	 * @return Array
	 */
	public function getCollectFromCustomerReservations()
    {
		/* @var $bookingDao \DDD\Dao\Booking\Booking */
		$bookingDao = $this->getServiceLocator()->get('dao_booking_booking');

		$collectFromCustomerReservations = $bookingDao->getCollectFromCustomerReservations();

		return $collectFromCustomerReservations;
	}

    /**
	 * Get issue detected reservations
	 * @return Array
	 */
	public function getCollectFromCustomerReservationsCount()
    {
		/* @var $bookingDao \DDD\Dao\Booking\Booking */
		$bookingDao = $this->getServiceLocator()->get('dao_booking_booking');
		$data = $bookingDao->getCollectFromCustomerReservations();
		return $data->count();
	}
}
