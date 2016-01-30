<?php

namespace Apartment\View\Helper;

use DDD\Service\Apartment\General;
use Library\Constants\Objects;
use Zend\View\Helper\AbstractHelper;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * Render apartment website link
 *
 * @package Apartment
 */
class ApartmentWebsiteLink extends AbstractHelper {
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
        /**
         * @var General $apartmentGeneralService
         */
        $apartmentGeneralService = $this->getServiceLocator()->get('service_apartment_general');
		$websiteURL = $apartmentGeneralService->getWebsiteLink($apartmentId);

		if ($websiteURL) {
            return ' <a href="' . $websiteURL . '" class="action-item label label-info pull-right margin-left-5" target="_blank">See on website&nbsp;<span class="glyphicon glyphicon-share"></span></a> ';
        }

        return '';
	}
}
