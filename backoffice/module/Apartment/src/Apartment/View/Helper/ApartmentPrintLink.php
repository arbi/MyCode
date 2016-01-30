<?php

namespace Apartment\View\Helper;

use DDD\Service\Apartment\General;
use Library\Constants\DomainConstants;
use Library\Constants\Objects;
use Zend\View\Helper\AbstractHelper;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * Render apartment print link
 *
 * @package Apartment
 */
class ApartmentPrintLink extends AbstractHelper {
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
	 */
	public function __invoke($apartmentId)
	{
		$url = '//' . DomainConstants::BO_DOMAIN_NAME . '/apartment/' . $apartmentId . '/welcome-note';

		return '<a href="' . $url . '" class="action-item label label-info pull-right margin-left-5" target="_blank">Welcome Note&nbsp;<span class="glyphicon glyphicon-print"></span></a> ';
    }
}
