<?php

namespace DDD\Service\UniversalDashboard\Widget;

use DDD\Service\ServiceBase;
use Library\ActionLogger\Logger;
use Zend\Validator\Db\RecordExists;
use Library\Constants\DbTables;
use DDD\Service\Booking\BankTransaction;
use Library\Utility\Currency;
use Library\Utility\Helper;
use Zend\Db\Sql\Expression;
/**
 * Methods to work with "Collect From Partner" widget
 * @author Tigran Petrosyan
 */
final class CollectFromPartner extends ServiceBase {

	/**
	 * Get "Collect From Partner" reservations
	 * @return Array
	 */
	public function getCollectFromPartnerReservations() {
		/* @var $bookingDao \DDD\Dao\Booking\Booking */
		$bookingDao = $this->getServiceLocator()->get('dao_booking_booking');

		$collectFromPartnerReservations = $bookingDao->getCollectFromPartnerReservations();

		return $collectFromPartnerReservations;
	}

    /**
	 * Get "Collect From Partner" reservations count
	 * @return int
	 */
	public function getCollectFromPartnerReservationsCount() {
		/* @var $bookingDao \DDD\Dao\Booking\Booking */
		$bookingDao = $this->getServiceLocator()->get('dao_booking_booking');
		$count = $bookingDao->getCollectFromPartnerReservationsCount();

		return $count;
	}
}
