<?php

namespace Backoffice\Controller;

use Library\Authentication\BackofficeAuthenticationService;
use Library\Controller\ControllerBase;
use DDD\Service\GroupInventory as GroupInventoryService;
use Library\Utility\Debug;
use Zend\Http\Request;
use Library\Utility\Helper;
use Library\Constants\TextConstants;
use Zend\View\Model\ViewModel;
use Zend\View\Model\JsonModel;
use Library\Constants\Roles;

class GroupInventoryController extends ControllerBase
{
    public function indexAction()
    {
        /**
         * @var \DDD\Service\ApartmentGroup $service
         */
        $service    = $this->getServiceLocator()->get('service_apartment_group');

        $groups = [];
        $result = $service->getApartmentGroupsListForSelect();
        foreach ($result as $row) {
            $groups[$row['country']][$row['id']] = [
                'id' => $row['id'],
                'name' => $row['name'] . ($row['usage_apartel'] ? ' (Apartel)' : ''),
                'country' => $row['country']
            ];
        }

        return new ViewModel([
            'groups' => $groups
        ]);
    }

    public function ajaxViewAction()
    {
        $result = ['status' => 'success'];
        $request = $this->getRequest();

        try {
            if ($request->isXmlHttpRequest()) {
               	$service = $this->getGroupInventoryService();

               	$apartmentGroupId = (int)$request->getPost('apartment_group_id');
               	$dateRange = $request->getPost('inventory_date_range');
               	$roomType = $request->getPost('room_type', 0);

               	$dateRangeArray = explode(' - ', $dateRange);

               	$from = (isset($dateRangeArray[0]) && $dateRangeArray[0] != '') ? $dateRangeArray[0] : date('Y-m-d');
               	$to = (isset($dateRangeArray[1]) && $dateRangeArray[1] != '') ? $dateRangeArray[1] : date('Y-m-d', strtotime('today + 7 days'));
               	$roomCount = $request->getPost('room_count');
               	$sort = $request->getPost('sort');

               	if ($roomCount == '') {
               		$roomCount = -1;
               	}

               	$response = $service->composeGroupAvailabilityForDateRange($apartmentGroupId, $from, $to, $roomCount, $sort, $roomType);

               	$result['list'] = $response['list'];
               	$result['days'] = $response['days'];
                $result['overbookings'] = [];

                if (!is_array($response['overbookings'])) {
                    $result['overbookings'] = iterator_to_array($response['overbookings']);
                }
            }
        } catch (\Exception $e) {
            $result['status'] = 'error';
            $result['msg'] = TextConstants::SERVER_ERROR;
        }

        return new JsonModel($result);
    }

    public function ajaxSaveMovesAction()
    {
        $return = [
            'status' => 'error',
            'msg' => 'Moving apartments is not possible.',
        ];

        try {
            $request = $this->getRequest();

            /**
             * @var \DDD\Service\Booking $bookingService
             */
            $bookingService = $this->getServiceLocator()->get('service_booking');

            if ($request->isXmlHttpRequest()) {
                $moves = $request->getPost('moves');
                $movesMapping = [];

                foreach ($moves as $move) {
                    $movesMapping[$move['resNumber']] = $move['moveTo'];
                }

                $return = $bookingService->simultaneouslyMoveReservations($movesMapping);

                if ($return['status'] == 'success') {
                    Helper::setFlashMessage(['success' => $return['msg']]);
                }
            }
        } catch (\Exception $e) {
            $return['msg'] = $e->getMessage();
        }

        return new JsonModel($return);
    }

    public function ajaxGetRoomTypeAction()
    {
        $result = ['status' => 'success'];
        $request = $this->getRequest();

        try {
            if ($request->isXmlHttpRequest()) {

                $groupId = (int)$request->getPost('group_id');
                /**
                 * @var \DDD\Dao\Apartel\Type $roomTypeDao
                 */
                $roomTypeDao = $this->getServiceLocator()->get('dao_apartel_type');
                $roomTypes = $roomTypeDao->getAllRoomTypesByGroupId($groupId);
                $result['room_types'] = iterator_to_array($roomTypes);
            }
        } catch (\Exception $e) {
            $result['status'] = 'error';
            $result['msg'] = TextConstants::SERVER_ERROR;
        }

        return new JsonModel($result);
    }

    /**
     * @return GroupInventoryService
     */
    private function getGroupInventoryService()
    {
        return $this->getServiceLocator()->get('service_group_inventory');
    }
}
