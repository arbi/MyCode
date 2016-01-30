<?php

namespace Apartment\Controller;

use Apartment\Controller\Base as ApartmentBaseController;
use Zend\View\Model\ViewModel;

/**
 * Class SalesStatistics
 * @package Apartment\Controller
 */
class SalesStatistics extends ApartmentBaseController
{
    public function indexAction()
    {
        /**
         * @var \DDD\Service\Booking $reservationsService
         */
        $reservationsService = $this->getServiceLocator()->get('service_booking');
        $saleStatistics = $reservationsService->getSalesStatistics();

        $viewModel = new ViewModel([
            'saleStatistics' => $saleStatistics
        ]);

        return $viewModel;
    }
}