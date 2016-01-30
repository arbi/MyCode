<?php
namespace Apartment\View\Helper;

use Zend\View\Helper\AbstractHelper;
use Zend\ServiceManager\ServiceLocatorInterface;
use DDD\Dao\Booking\Booking;

class ApartmentCurrentReservationLink extends AbstractHelper {
    
    /*
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


	public function __invoke($apartmentId) {
		$bookingDao = new Booking($this->getServiceLocator(), 'ArrayObject');
		$response = $bookingDao->getCurrentReservationByAcc($apartmentId, date('Y-m-d'));
		if($response && $response['res_number'])
            return '<a href="/booking/edit/'.$response['res_number'].'" class="action-item label label-info pull-right" target="blank">Current reservation&nbsp;<span class="glyphicon glyphicon-share"></span></a>';

	}
}