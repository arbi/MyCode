<?php

namespace Parking\View\Helper;

use Zend\View\Helper\AbstractHelper;
use Zend\ServiceManager\ServiceLocatorInterface;

class ParkingPageTitle extends AbstractHelper {
	/**
	 * @var ServiceLocatorInterface
	 */
	private $serviceLocator;

	/**
	 * Get service locator
	 *
	 * @return ServiceLocatorInterface
	 */
	public function getServiceLocator()
    {
		return $this->serviceLocator;
	}

	/**
	 * Set service locator
	 *
	 * @param ServiceLocatorInterface $serviceLocator
	 */
	public function setServiceLocator(ServiceLocatorInterface $serviceLocator)
    {
		$this->serviceLocator = $serviceLocator;
	}

	/**
	 * @param int $parkingLotId
	 * @return string
	 */
	public function __invoke($parkingLotId)
    {
        /**
         * @var \DDD\Dao\Parking\General $parkingGeneralDao
         */
        $parkingGeneralDao = $this->getServiceLocator()->get('dao_parking_general');
		$parkingLotDomain  = $parkingGeneralDao->getParkingById($parkingLotId);
		$viewParams = [
            'name' => ($parkingLotDomain) ? $parkingLotDomain->getName() : '',
            'parkingLotId' => $parkingLotId,
		];

		return $this->getView()->render('partial/parking-page-title', $viewParams);
	}
}
