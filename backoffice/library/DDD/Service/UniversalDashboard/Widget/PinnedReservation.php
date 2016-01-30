<?php

namespace DDD\Service\UniversalDashboard\Widget;

use DDD\Service\ServiceBase;

/**
 * Methods to work with "Pinned reservation" widget
 */
final class PinnedReservation extends ServiceBase
{
	/**
	 * Get user's pinned reservation
	 * @return \DDD\Domain\UniversalDashboard\Widget\PinnedReservation[]
	 */
	public function getAllPinnedReservation($userId)
	{
		/* @var $dao \DDD\Dao\Booking\PinnedReservation */
		$pinnedReservationDao = $this
			->getServiceLocator()
			->get('dao_booking_pinned_reservation');

		$result = $pinnedReservationDao->fetchAll(
			['user_id' => $userId]
		);

		return $result;
	}

	/**
	 * Get user's pinned reservations count
	 * @return int
	 */
	public function getAllPinnedReservationsCount($userId)
	{
		/* @var $dao \DDD\Dao\Booking\PinnedReservation */
		$pinnedReservationDao = $this->getServiceLocator()->get('dao_booking_pinned_reservation');
		$count = $pinnedReservationDao->getAllPinnedReservationsCount($userId);

		return $count;
	}

	public function getPinnedReservation($userId, $resNum)
	{
		/* @var $dao \DDD\Dao\Booking\PinnedReservation */
		$pinnedReservationDao = $this
			->getServiceLocator()
			->get('dao_booking_pinned_reservation');

		$result = $pinnedReservationDao->fetchOne(
			[
				'user_id'    => $userId,
				'res_number' => $resNum
			]
		);

		return $result;
	}
}