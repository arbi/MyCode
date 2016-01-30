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
 * Class Inventory
 * @package Apartel\Controller
 */
class Inventory extends ApartelBaseController
{
    public function indexAction()
    {
        /**
         * @var \DDD\Dao\Apartel\Type $typeDao
         * @var \DDD\Service\Apartel\Calendar $calendarService
         */
        $typeDao = $this->getServiceLocator()->get('dao_apartel_type');
        $roomTypeId = $this->params()->fromRoute('type_id', 0);

        if (!$roomTypeId) {
                $roomType = $typeDao->getFirstRoomType($this->apartelId);
                if (!$roomType) {
                    return [
                        'noRoomType' => true
                    ];
                }
                $roomTypeId = $roomType['id'];

            return $this->redirect()->toRoute('apartel/inventory', [
                'type_id' => $roomTypeId,
            ], [], true);
        }

        $roomTypes = $typeDao->getAllTypes($this->apartelId);

        return [
            'roomTypes' => $roomTypes,
            'roomTypeId' => $roomTypeId,
        ];
	}

    public function ajaxUpdatePricesAction()
    {
        /**
         * @var Request $request
         * @var \DDD\Service\Apartel\Inventory $inventoryService
         */
        $request = $this->getRequest();
        $output = ['status' => 'error', 'msg' => TextConstants::ERROR];

        try {
            $price = $request->getPost('parent_price', null);
            $dateRange = $request->getPost('date_range', null);
            $lockPrice = $request->getPost('lock_price', 0);
            $priceType = $request->getPost('price_type', null);
            $weekDays  = $this->params()->fromPost('week_days', null);
            $forceUpdatePrice = $this->params()->fromPost('force_update_price', null);

            $roomTypeId = $this->params()->fromRoute('type_id', 0);
            if ($request->isPost() && $request->isXmlHttpRequest() && $roomTypeId && $price && $dateRange && !is_null($priceType)) {

                $inventoryService = $this->getServiceLocator()->get('service_apartel_inventory');
                $responseUpdate = $inventoryService->updateInventoryRangeByPrice($roomTypeId, $dateRange, $weekDays, $price, $priceType, $lockPrice, 0, $forceUpdatePrice);
                $output['status'] = $responseUpdate['status'];
                $output['msg'] = $responseUpdate['msg'];

            } else {
                $output['msg'] = 'Bad request.';
            }
        } catch (\Exception $ex) {
            $output['msg'] = $ex->getMessage();
        }

        return new JsonModel($output);
    }

}
