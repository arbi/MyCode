<?php

namespace Apartment\Controller;

use Apartment\Controller\Base as ApartmentBaseController;
use Symfony\Component\Console\Helper\Helper;
use Zend\View\Model\JsonModel;

class WelcomeNote extends ApartmentBaseController {

    /**
     * Apartment Welcome Note Page
     *
     * @return \Zend\View\Model\ViewModel
     */
	public function indexAction()
    {
		/**
         * @var \DDD\Service\Apartment\General $apartmentGeneralService
         * @var \DDD\Service\Task $taskService
         * @var \DDD\Dao\Textline\Apartment $texLineApartmentDao
		 */
        $apartmentGeneralService = $this->getServiceLocator()->get('service_apartment_general');
        $taskService             = $this->getServiceLocator()->get('service_task');
        $textLineApartmentDao    = $this->getServiceLocator()->get('dao_textline_apartment');

        $apartmentTasks   = $taskService->getFrontierTasksOnApartment($this->apartmentId);

        // get apartment usage information
        $apartmentUsage = $textLineApartmentDao->getApartmentUsageByApartmentId($this->apartmentId);

        // get building usage information
        $apartmentBuildingUsage = $textLineApartmentDao->getApartmentBuildingUsageByApartmentId($this->apartmentId);

        // get building facilities information
        $apartmentBuildingFacility = $textLineApartmentDao->getApartmentBuildingFacilityByApartmentId($this->apartmentId);

        // get building policy information
        $apartmentBuildingPolicy = $textLineApartmentDao->getApartmentBuildingPolicyByApartmentId($this->apartmentId);

        // get apartment information
        $generalInfo = $apartmentGeneralService->getApartmentGeneral($this->apartmentId);

		$viewModel = new \Zend\View\Model\ViewModel();

        $viewModel->setTerminal(true);
        $viewModel->setVariables([
            'apartment'                 => $generalInfo,
            'apartmentTasks'            => $apartmentTasks,
            'apartmentUsage'            => $apartmentUsage,
            'apartmentBuildingUsage'    => $apartmentBuildingUsage,
            'apartmentBuildingFacility' => $apartmentBuildingFacility,
            'apartmentBuildingPolicy'   => $apartmentBuildingPolicy
		]);

		return $viewModel;
	}
}
