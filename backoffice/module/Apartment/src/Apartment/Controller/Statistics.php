<?php

namespace Apartment\Controller;

use Apartment\Controller\Base as ApartmentBaseController;
use Library\Utility\Debug;
use DDD\Service\Booking as ForBookingStatus;

class Statistics extends ApartmentBaseController
{
	public function indexAction()
    {
        /**
         * @var \DDD\Service\Apartment\Statistics $apartmentStatisticsService
         */
        $apartmentStatisticsService = $this
			->getServiceLocator()
			->get('service_apartment_statistics');

        $apartmentId = $this->apartmentId;
		$basicData 	 = $apartmentStatisticsService->getBasicData($apartmentId);
        $budged 	 = $apartmentStatisticsService->getBudgedData($apartmentId);

		return [
            'sale' 			  => $basicData['sale'],
            'sale_previous'   => $basicData['sale_previous'],
            'months' 	      => $basicData['months'],
            'currency' 		  => $basicData['currencyCode'],
            'months_previous' => $basicData['months_previous'],
            'indicator' 	  => $budged,
            'apartmentId'     => $this->apartmentId,
            'apartmentStatus' => $this->apartmentStatus
		];
	}
}
