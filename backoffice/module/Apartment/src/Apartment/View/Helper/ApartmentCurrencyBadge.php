<?php

namespace Apartment\View\Helper;

use DDD\Service\Apartment\General;
use Zend\View\Helper\AbstractHelper;
use Zend\ServiceManager\ServiceLocatorInterface;
use Library\Constants\Objects;

/**
 * Render apartment currency badge
 *
 * @package Apartment
 */
class ApartmentCurrencyBadge extends AbstractHelper {
	/**
	 *
	 * @access private
	 * @var ServiceLocatorInterface
	 */
	private $serviceLocator;

	/**
	 * Get service locator
	 *
	 * @return ServiceLocatorInterface
	 */
	public function getServiceLocator() {
		return $this->serviceLocator;
	}

	/**
	 * Set service locator
	 *
	 * @param ServiceLocatorInterface $serviceLocator
	 */
	public function setServiceLocator(ServiceLocatorInterface $serviceLocator) {
		$this->serviceLocator = $serviceLocator;
	}

	/**
	 *
	 * @param int $apartmentId
	 * @return string
	 * @var $apartmentGeneralService \DDD\Service\Apartment\General
	 */
	public function __invoke($apartmentId) {
		/** @var General $apartmentGeneralService */
		$apartmentGeneralService = $this->getServiceLocator()->get('service_apartment_general');
		$currency = $apartmentGeneralService->getCurrency($apartmentId);

		return '<span class="label label-warning">' . $currency . '</span>';
	}
}
