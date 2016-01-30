<?php

namespace Apartment\View\Helper;

use Zend\View\Helper\AbstractHelper;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * Render apartment review score badge
 *
 * @package Apartment
 */
class ApartmentReviewScoreBadge extends AbstractHelper {
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
		$apartmentGeneralDao = $this->getServiceLocator()->get('dao_apartment_general');
		$reviewScore    = $apartmentGeneralDao->getReviewScore($apartmentId)['score'];

		$label = '';
		if ($reviewScore > 4) {
			$label = 'label-success';
		} else if ($reviewScore > 3) {
			$label = 'label-warning';
		} else {
			$label = 'label-danger';
		}
		
		$badgeTemplate = '<span class="label %1$s">Review Score - %2$s</span>';
		$html = sprintf($badgeTemplate, $label, $reviewScore);
		return $html;
	}
}