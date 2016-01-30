<?php

namespace Backoffice\Controller;

use DDD\Service\Apartel\DistributionView as DistributionViewService;
use DDD\Service\ApartmentGroup as ApartmentGroupService;
use Library\Authentication\BackofficeAuthenticationService;
use Library\Controller\ControllerBase;
use Library\Constants\Roles;

use Zend\View\Model\JsonModel;
use Zend\View\Model\ViewModel;

/**
 * Class DistributionViewController
 * @package Backoffice\Controller
 */
class DistributionViewController extends ControllerBase
{
    public function indexAction()
    {
        /**
         * @var BackofficeAuthenticationService $authenticationService
         * @var DistributionViewService $distributionViewService
         * @var ApartmentGroupService $apartmentGroupService
         */
        $authenticationService = $this->getServiceLocator()->get('library_backoffice_auth');
        $distributionViewService = $this->getServiceLocator()->get('service_distribution_view');
        $apartmentGroupService = $this->getServiceLocator()->get('service_apartment_group');

        $hasDevTestRole = $authenticationService->hasRole(Roles::ROLE_DEVELOPMENT_TESTING);

        $options = $distributionViewService->getOptions($hasDevTestRole);

        $groups = [];
        $result = $apartmentGroupService->getApartmentGroupsListForSelect();
        foreach ($result as $row) {
            $groups[$row['country']][$row['id']] = [
                'id' => $row['id'],
                'name' => $row['name'] . ($row['usage_apartel'] ? ' (Apartel)' : ''),
                'country' => $row['country']
            ];
        }

        $router = $this->getEvent()->getRouter();
        $ajaxSourceUrl = $router->assemble(['controller' => 'distribution-view', 'action' => 'ajax-get-distribution-list'],
            ['name' => 'backoffice/default']);

        return new ViewModel([
            'options' => $options,
            'groups' => $groups,
            'ajaxSourceUrl' => $ajaxSourceUrl
        ]);
    }

    public function ajaxGetDistributionListAction() 
    {
        /**
         * @var DistributionViewService $distributionViewService
         */
        $distributionViewService = $this->getServiceLocator()->get('service_distribution_view');

    	// get query parameters
    	$apartelId = $this->params()->fromQuery('apartelId', 0);

    	// get reservations data
    	$data = $distributionViewService->getDataByApartelId($apartelId);

    	$responseArray = array(
    		"aaData" => $data
    	);

    	return new JsonModel(
    		$responseArray
    	);
    }
}
