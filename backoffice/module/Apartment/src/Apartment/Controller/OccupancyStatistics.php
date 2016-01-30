<?php

namespace Apartment\Controller;

use Apartment\Controller\Base as ApartmentBaseController;
use Library\Constants\DomainConstants;
use Library\Constants\Objects;
use Library\Constants\Roles;

use Zend\View\Model\ViewModel;
use Zend\View\Model\JsonModel;

use Apartment\Form\Statistics as StatisticsForm;

use DDD\Service\Location as LocationService;


/**
 * Class OccupancyStatistics
 * @package Apartment\Controller
 *
 * @author ginosi
 */
class OccupancyStatistics extends ApartmentBaseController
{

	public function indexAction()
    {

	}

    public function statisticsAction()
    {
        $months = Objects::getMonthLongNames();

        $y = $this->params()->fromRoute('year', null);
        $m = $this->params()->fromRoute('month', null);

        $StatisticsForm = new StatisticsForm($months);
        $formTemplate   = 'form-templates/search-statistics';
        $viewModelForm  = new ViewModel();

        $requestDate = null;
        if (!is_null($y) && !is_null($y)) {
            $requestDate = $y . '_' . $m;
        }

        $viewModelForm->setVariables(
            [
                'form'        => $StatisticsForm,
                'requestDate' => $requestDate
            ]
        );

        $viewModelForm->setTemplate($formTemplate);

        $router = $this->getEvent()->getRouter();

        $ajaxSourceUrl = $router->assemble(
            [
                'controller' => 'apartment',
                'action'     => 'get-occupancy-statistics-json'
            ],
            ['name' => 'occupancy_statistics']
        );

        $viewModel = new ViewModel(['ajaxSourceUrl' => $ajaxSourceUrl]);

        $viewModel->addChild($viewModelForm, 'formOutput');
        $viewModel->setTemplate('apartment/occupancy-statistics/index');

        return $viewModel;
    }

    public function getOccupancyStatisticsJsonAction()
    {
        $router         = $this->getEvent()->getRouter();
        $queryParams    = $this->params()->fromQuery();

        /**
         * @var \DDD\Service\Accommodations $accommodationsService
         */
        $accommodationsService = $this->getServiceLocator()->get('service_accommodations');

        if (!empty($queryParams['request_date'])) {
            $queryParams['starting_form'] = $queryParams['request_date'];
        }

        $isInventoryManager = false;
        $saleStatistics     = $accommodationsService->getOccupancyStatistics($queryParams);
        $authService        = $this->getServiceLocator()->get('library_backoffice_auth');

        if ($authService->hasRole(Roles::ROLE_APARTMENT_INVENTORY_MANAGER)) {
            $isInventoryManager = true;
        }

        $aaData = [];
        foreach ($saleStatistics['statistics'] as $row){
            $month1 = isset($row[$saleStatistics['monthList'][0]]) ? $row[$saleStatistics['monthList'][0]] : 0;
            $month2 = isset($row[$saleStatistics['monthList'][1]]) ? $row[$saleStatistics['monthList'][1]] : 0;
            $month3 = isset($row[$saleStatistics['monthList'][2]]) ? $row[$saleStatistics['monthList'][2]] : 0;
            $unsold = isset($row['unsold_days']) ? $row['unsold_days'] : 0;

            $unsold_class = ($unsold <= 3 ) ? 'success' : (($unsold <= 7 ) ? 'warning': 'danger');
            $month1_class = ($month1 <= 65) ? 'danger'  : (($month1 <= 79) ? 'warning': 'success');
            $month2_class = ($month2 <= 65) ? 'danger'  : (($month2 <= 79) ? 'warning': 'success');
            $month3_class = ($month3 <= 65) ? 'danger'  : (($month3 <= 79) ? 'warning': 'success');

            $apartmentURL = '';
            if ($isInventoryManager) {
                $apartmentURL = $router->assemble(['apartment_id' => $row['id']], ['name' => 'apartment/calendar']);
            } else {
                $apartmentURL = $router->assemble(['apartment_id' => $row['id']], ['name' => 'apartment/statistics']);
            }

            $aaData[] = [
                '<a href="'.$apartmentURL.'" target="_blank">'. $row['name'] . '</a>',
                $row['building'],
                $row['city_name'],
                $row['pax'],
                $row['bedrooms'],
                '<div class="progress">
                    <div class="progress-label">' . $unsold . '</div>
                    <div class="progress-bar progress-bar-' . $unsold_class .'" ' .
                    'role="progressbar" aria-valuenow="' . $unsold . '" aria-valuemin="0" aria-valuemax="10" style="width: ' . $unsold * 10 .'%;">
                    </div>
                </div>',
                '<div class="progress">
                    <div class="progress-label">' . $month1 . '%</div>
                    <div class="progress-bar progress-bar-' . $month1_class .'" ' .
                    'role="progressbar" aria-valuenow="' . $month1 . '" aria-valuemin="0" aria-valuemax="100" style="width: ' . $month1 .'%;">
                    </div>
                </div>',
                '<div class="progress">
                    <div class="progress-label">' . $month2 . '%</div>
                    <div class="progress-bar progress-bar-' . $month2_class .'" ' .
                    'role="progressbar" aria-valuenow="' . $month2 . '" aria-valuemin="0" aria-valuemax="100" style="width: ' . $month2 .'%;">
                    </div>
                </div>',
                '<div class="progress">
                    <div class="progress-label">' . $month3 . '%</div>
                    <div class="progress-bar progress-bar-' . $month3_class .'" ' .
                    'role="progressbar" aria-valuenow="' . $month3 . '" aria-valuemin="0" aria-valuemax="100" style="width: ' . $month3 .'%;">
                    </div>
                </div>'
            ];
        }

        return new JsonModel(["aaData" => $aaData]);
    }
}