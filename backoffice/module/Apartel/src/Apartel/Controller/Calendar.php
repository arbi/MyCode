<?php

namespace Apartel\Controller;

use Apartel\Controller\Base as ApartelBaseController;
use Apartel\Form\Connection as ApartelForm;
use Library\ChannelManager\Testing\ConnectionTest;
use Library\Constants\Objects;
use Library\Constants\Roles;
use Library\Constants\TextConstants;
use Library\Utility\Helper;
use Zend\Http\Request;
use Zend\View\Model\JsonModel;
use Library\ChannelManager\ChannelManager as Chm;
use Zend\View\Model\ViewModel;

use DDD\Service\Apartel\OTADistribution;
use Library\OTACrawler\OTACrawler;
use Library\OTACrawler\Product\Apartelle;

/**
 * Class Calendar
 * @package Apartel\Controller
 */
class Calendar extends ApartelBaseController
{
    public function indexAction()
    {
        /**
         * @var \DDD\Dao\Apartel\Type $typeDao
         * @var \DDD\Service\Apartel\Calendar $calendarService
         */
        $typeDao = $this->getServiceLocator()->get('dao_apartel_type');

        $year = $this->params()->fromRoute('year', 0);
        $month = $this->params()->fromRoute('month', 0);
        $roomTypeId = $this->params()->fromRoute('type_id', 0);

        if (!$roomTypeId || ($roomTypeId && (!$year || !$month))) {
            if (!$roomTypeId) {
                $roomType = $typeDao->getFirstRoomType($this->apartelId);
                if (!$roomType) {
                    return [
                        'noRoomType' => true
                    ];
                }
                $roomTypeId = $roomType['id'];
            }

            return $this->redirect()->toRoute('apartel/calendar', [
                'type_id' => $roomTypeId,
                'year' => date('Y'),
                'month' => date('m')
            ], [], true);
        }

        // get main params from route, month and year
        if ($roomTypeId && $year && $month) {

            $roomTypes = $typeDao->getAllTypes($this->apartelId);
            $calendarService = $this->getServiceLocator()->get('service_apartel_calendar');
            $calendarData = $calendarService->getCalendarData($this->apartelId, $roomTypeId, $year, $month);

            $roleManager = 'no';
            $auth = $this->getServiceLocator()->get('library_backoffice_auth');
            if ($auth->hasRole(Roles::ROLE_APARTMENT_INVENTORY_MANAGER)) {
                $roleManager = 'yes';
            }

            return [
                'year' => $year,
                'month' => $month,
                'roomTypes' => $roomTypes,
                'roomTypeId' => $roomTypeId,
                'rates' => $calendarData['rates'],
                'dayOfWeek' => $calendarData['dayOfWeek'],
                'givenMonthDaysCount' => $calendarData['givenMonthDaysCount'],
                'isConnected' => $calendarData['isConnected'],
                'monthStart' => $calendarData['monthStart'],
                'monthEnd' => $calendarData['monthEnd'],
                'inventory' => $calendarData['inventory'],
                'roleManager' => $roleManager,
            ];
        }
	}

    public function ajaxUpdateRatePricesAction()
    {
        /**
         * @var Request $request
         * @var \DDD\Service\Apartel\Inventory $inventoryService
         */
        $request = $this->getRequest();

        $output = [
            'bo'  => ['status' => 'error'],
        ];

        try {
            $price = $request->getPost('parent_price', null);
            $date = $request->getPost('date', null);
            $lockPrice = $request->getPost('lock_price', null);
            $roomTypeId = $this->params()->fromRoute('type_id', 0);
            if ($request->isPost() && $request->isXmlHttpRequest() && $roomTypeId && $price) {
                $inventoryService = $this->getServiceLocator()->get('service_apartel_inventory');
                $dateFrom = $dateTo = date('Y-m-d', strtotime($date));
                $lockPrice = $lockPrice ? 1 : 0;
                $responseUpdate = $inventoryService->updatePriceByRange($roomTypeId, $price, $dateFrom, $dateTo, null, 0, $lockPrice, null);
                if ($responseUpdate['status'] == 'success') {
                    $output['bo']['status'] = 'success';
                    $output['bo']['msg'] = $responseUpdate['msg'];
                } else {
                    throw new \Exception($responseUpdate['msg']);
                }
            } else {
                $output['bo']['msg'] = 'Bad request.';
            }
        } catch (\Exception $ex) {
            $output['bo']['msg'] = $ex->getMessage();
        }

        return new JsonModel($output);
    }

    public function ajaxSynchronizeMonthAction()
    {
        /**
         * @var \DDD\Service\Queue\InventorySynchronizationQueue $syncService
         */
        $syncService = $this->getServiceLocator()->get('service_queue_inventory_synchronization_queue');
        $request = $this->getRequest();
        $result = [
            'status' => 'error',
            'msg' =>  TextConstants::ERROR,
        ];

        try {
            $roomTypeId = $this->params()->fromRoute('type_id', 0);
            if ($request->isPost() && $request->isXmlHttpRequest() && $roomTypeId) {
                $dateFrom = $request->getPost('date_from', null);
                $dateTo = $request->getPost('date_to', null);
                if (!$dateFrom || !$dateTo) {
                    return new JsonModel($result);
                }

                // send queue
                $syncService->push($roomTypeId, $dateFrom, $dateTo, [], $syncService::ENTITY_TYPE_APARTEL);
                $result = [
                    'status' => 'success',
                    'msg' =>  'Rate changes successfully pushed to queue.',
                ];
            }
        } catch (\Exception $e) {

        }

        return new JsonModel($result);
    }
}
