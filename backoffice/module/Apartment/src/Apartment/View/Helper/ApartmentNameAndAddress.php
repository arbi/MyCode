<?php

namespace Apartment\View\Helper;

use Zend\View\Helper\AbstractHelper;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * Render apartment name and address
 *
 * @package Apartment
 */
class ApartmentNameAndAddress extends AbstractHelper {
	/**
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
	 * @param int $apartmentId
	 * @return string
	 * @var $apartmentGeneralService \DDD\Service\Apartment\General
	 */
	public function __invoke($apartmentId) {
		$apartmentGeneralService = $this->getServiceLocator()->get('service_apartment_general');
		$fullAddressDomain = $apartmentGeneralService->getFullAddress($apartmentId);
		$viewParams = [
            'name' => ($fullAddressDomain) ? $fullAddressDomain->getName() : '',
            'address' => ($fullAddressDomain) ? $fullAddressDomain->getFullAddressWithoutName() : '',
            'apartmentId' => $apartmentId,
		];

		return $this->getView()->render('partial/name-address', $viewParams);
	}
}
