<?php

namespace DDD\Service\UniversalDashboard\Widget;

use DDD\Service\ServiceBase;
use Library\ActionLogger\Logger;
use Zend\Validator\Db\RecordExists;
use Library\Constants\DbTables;
use Library\Utility\Helper;
use Zend\Db\Sql\Expression;

/**
 * Methods to work with "Mark As Settled" widget
 * @author Tigran Petrosyan
 */
final class ToBeSettled extends ServiceBase
{
	/**
	 * Get issue detected reservations
	 * @return Array
	 */
	public function getToBeSettledReservations()
    {
		/** @var \DDD\Dao\Booking\Booking $bookingDao */
		$bookingDao = $this->getServiceLocator()->get('dao_booking_booking');

		$toBeSettledReservations = $bookingDao->getToBeSettledReservations();

		return $toBeSettledReservations;
	}

	/**
	 * Get issue detected reservations count
	 * @return int
	 */
	public function getToBeSettledReservationsCount()
    {
		/** @var \DDD\Dao\Booking\Booking $bookingDao */
		$bookingDao = $this->getServiceLocator()->get('dao_booking_booking');
		$count = $bookingDao->getToBeSettledReservationsCount();

		return $count;
	}

	/**
	 * Mark as settled
	 * @param string $resNumber
	 * @return boolean
	 */
	public function markAsSettled($resNumber)
    {
        /**
         * @var \DDD\Dao\Booking\Booking $bookingDao
         * @var Logger $logger
         */
		$dbAdapter = $this->getServiceLocator()->get('Zend\Db\Adapter\Adapter');
        $logger = $this->getServiceLocator()->get('ActionLogger');
		$reservationExistValidator = new RecordExists(['adapter' => $dbAdapter, 'table' => DbTables::TBL_BOOKINGS, 'field' => 'res_number']);

		if ($reservationExistValidator->isValid($resNumber)) {
			$bookingDao = $this->getServiceLocator()->get('dao_booking_booking');

			$bookingDao->update([
				'payment_settled' => 1,
				'settled_date' => date('Y-m-d H:i:s'),
            ], [
				'res_number' => $resNumber,
			]);

            $bookingDomain = $bookingDao->fetchOne(['res_number' => $resNumber], ['id']);
            $logger->save(Logger::MODULE_BOOKING, $bookingDomain->getId(), Logger::ACTION_RESERVATION_SETTLED);

            return true;
		}

		return false;
	}
}
