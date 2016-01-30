<?php

namespace DDD\Service\UniversalDashboard\Widget;

use DDD\Dao\Booking\Booking;
use DDD\Service\Booking as BookingService;
use DDD\Service\Apartment\Inventory;
use DDD\Service\Booking\BookingTicket;
use DDD\Service\ServiceBase;

/**
 * Methods to work with "Pending Cancelations" widget
 */
final class PendingCancelation extends ServiceBase
{
	/**
	 * Get pending cancelations
	 * @return Array
	 */
	public function getPendingCancelationReservations()
    {
		/* @var $bookingDao \DDD\Dao\Booking\Booking */
		$bookingDao = $this->getServiceLocator()->get('dao_booking_booking');

		$cancelPendingReservations = $bookingDao->getPendingCancelationReservations();

		return $cancelPendingReservations;
	}

	/**
	 * Get pending cancelations
	 * @return int
	 */
	public function getPendingCancelationReservationsCount()
    {
		/* @var $bookingDao \DDD\Dao\Booking\Booking */
		$bookingDao = $this->getServiceLocator()->get('dao_booking_booking');
		$count = $bookingDao->getPendingCancelationReservationsCount();

		return $count;
	}

	/**
	 * Process Cancel
	 * @param string $resNumber
	 * @param int $bookingStatus
	 * @return boolean
	 */
	public function applyCancelation($resNumber, $bookingStatus) {
		/** @var Inventory $inventoryService */
		$inventoryService = $this->getServiceLocator()->get('service_apartment_inventory');
		$bookingDao = new Booking($this->getServiceLocator(), '\DDD\Domain\Booking\ResId');
		$bookingDomain = $bookingDao->getBookingTicketByReservationNumber($resNumber);

		$bookingStatus = (
			$bookingDomain->getFundsConfirmed() == BookingTicket::CC_STATUS_INVALID
				? BookingService::BOOKING_STATUS_CANCELLED_INVALID
				: $bookingStatus
		);

		return $inventoryService->processCancellation($resNumber, false, false, $bookingStatus);
	}
}
