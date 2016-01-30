<?php

namespace Apartment\View\Helper;

use Library\Constants\TextConstants;
use Zend\View\Helper\AbstractHelper;
use Zend\ServiceManager\ServiceLocatorInterface;
use Library\Constants\Objects;

/**
 * Render apartment cubilis badge
 *
 * @package Apartment
 */
class ApartmentCubilisBadge extends AbstractHelper {
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
		$apartmentGeneralService = $this->getServiceLocator()->get('service_apartment_general');
		$isConnected = $apartmentGeneralService->isCubilisConnected($apartmentId);
		
		$label = 'label-default';
		$text = TextConstants::CUBILIS_NOT_CONNECTED;
		if ($isConnected) {
			$label = 'label-success';
			$text = TextConstants::CUBILIS_CONNECTED;
		}
		
		$badgeTemplate = '<span class="label %1$s">%2$s</span>';
		$html = sprintf($badgeTemplate, $label, $text);
		return $html;
	}
}