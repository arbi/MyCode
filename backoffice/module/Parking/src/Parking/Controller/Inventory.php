<?php

namespace Parking\Controller;

use Library\Controller\ControllerBase;
use Library\Constants\TextConstants;
use Library\Constants\Roles;

use Zend\View\Model\ViewModel;
use Zend\View\Model\JsonModel;

class Inventory extends ControllerBase
{
    public function indexAction()
    {
        /**
         * @var \DDD\Service\Parking\Inventory $inventoryService
         */
        $inventoryService = $this->getServiceLocator()->get('service_parking_inventory');
        $viewData = $inventoryService->getIndexData();

        return [
            'lots' => $viewData['lots']
        ];
    }

    public function ajaxViewAction()
    {
        $result  = ['status' => 'success'];
        $request = $this->getRequest();
        $auth    = $this->getServiceLocator()->get('library_backoffice_auth');

        try {
            if ($request->isXmlHttpRequest()) {

               	$lotId = (int)$request->getPost('lots');
               	$dateRange = $request->getPost('inventory_date_range');
                /**
                 * @var \DDD\Service\Parking\Inventory $inventoryService
                 */
                $inventoryService = $this->getServiceLocator()->get('service_parking_inventory');
                $viewData = $inventoryService->getViewData($lotId, $dateRange);
                $partial = $this->getServiceLocator()->get('viewhelpermanager')->get('partial');

                $link = '/booking/edit/';
                if ($auth->hasRole(Roles::ROLE_FRONTIER_MANAGEMENT)) {
                    $link = 'frontier?id=1_';
                }

                $response = $partial('partial/inventory.phtml', [
                    'spots'       => $viewData['spots'],
                    'from'        => $viewData['from'],
                    'to'          => $viewData['to'],
                    'tableWidth'  => $viewData['tableWidth'],
                    'dayCount'    => $viewData['dayCount'],
                    'reservedDay' => $viewData['reservedDay'],
                    'closeDay'    => $viewData['closeDay'],
                    'lotId'       => $lotId,
                    'isFrontier'  => $auth->hasRole(Roles::ROLE_FRONTIER_MANAGEMENT)
                ]);

                $result['result'] = $response;
            }
        } catch (\Exception $e) {
            $result['status'] = 'error';
            $result['msg'] = TextConstants::SERVER_ERROR . $e->getMessage();
        }

        return new JsonModel($result);
    }
}
