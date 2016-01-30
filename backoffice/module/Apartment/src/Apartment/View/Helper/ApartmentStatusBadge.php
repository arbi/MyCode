<?php

namespace Apartment\View\Helper;

use Zend\View\Helper\AbstractHelper;
use Zend\ServiceManager\ServiceLocatorInterface;
use Library\Constants\Objects;

/**
 * Render apartment status badge
 *
 * @package Apartment
 */
class ApartmentStatusBadge extends AbstractHelper {
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
		$status = $apartmentGeneralService->getStatus($apartmentId);
		
		$apartmentStatuses = Objects::getProductStatuses();
		
		$label = '';
		switch ($status) {
			case Objects::PRODUCT_STATUS_LIVEANDSELLIG:
			case Objects::PRODUCT_STATUS_SELLING:
			case Objects::PRODUCT_STATUS_SELLINGNOTSEARCHABLE:
				$label = '-success';
				break;
			case Objects::PRODUCT_STATUS_DISABLED:
				$label = '-danger';
				break;
            case Objects::PRODUCT_STATUS_SANDBOX:
                $label = '-warning';
                break;
			default:
				$label = '-default';
				break;
		}
		$apartmentStatusText = (isset($apartmentStatuses[$status]) ? $apartmentStatuses[$status] : '');
		$badgeTemplate = '<span class="label label%1$s">%2$s</span>';
		$html = sprintf($badgeTemplate, $label, $apartmentStatusText);
		return $html;
	}
}