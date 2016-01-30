<?php

namespace Backoffice\Controller;

use DDD\Service\ApartmentGroup\Usages\Concierge as UsageConciergeService;
use Library\Controller\ControllerBase;
use Zend\View\Model\ViewModel;

/**
 * Class ConciergeDashboardController
 * @package Backoffice\Controller
 *
 * @author Tigran Petrosyan
 */
class ConciergeDashboardController extends ControllerBase
{
    /**
     * Index page action for Concierge Dashboard
     * Available dashboards will be shown here, if there is one dashboard available user automatically will be redirected to that dashboard's page
     *
     * @return array|ViewModel
     */
    public function indexAction()
    {
        /**
         * @var UsageConciergeService $usageConciergeService
         */
        $usageConciergeService = $this->getServiceLocator()->get('service_apartment_group_usages_concierge');

        $conciergeApartmentGroups = $usageConciergeService->getUserAvailableConciergeApartmentGroups();

        if ($conciergeApartmentGroups && count($conciergeApartmentGroups) == 1) {
            $this->redirect()->toRoute(
                'backoffice/default',
                [
                    'controller' => 'concierge',
                    'action'     =>'item',
                    'id'         => $conciergeApartmentGroups->current()->getId()
                ]
            );
        }

        return new ViewModel([
            'conciergeApartmentGroups' => $conciergeApartmentGroups
        ]);
    }
}
