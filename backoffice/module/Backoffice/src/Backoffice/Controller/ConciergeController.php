<?php

namespace Backoffice\Controller;

use DDD\Service\Booking\BookingTicket;
use Library\Finance\Finance;
use Library\Utility\Helper;
use Library\Constants\Roles;
use Library\Constants\TextConstants;
use Library\ActionLogger\Logger;
use Library\Controller\ControllerBase;

use Zend\View\Model\JsonModel;
use Zend\View\Model\ViewModel;

class ConciergeController extends ControllerBase
{
	protected $_concierge;

	public function indexAction()
    {
		return new ViewModel();
	}

	public function ajaxgetuserAction()
	{
		$result = array('rc'=>'00', 'result'=>array());
		$request = $this->getRequest();
		try{
			if($request->isXmlHttpRequest()) {
				$txt        = strip_tags(trim($request->getPost('txt')));
				$user_id    = (int)$request->getPost('user_id');
				$service    = $this->getServiceLocator()->get('dao_user_user_manager');
				$users   = $service->searchUserByAutocomplate($txt);
				$res = array();
				foreach ($users as $key=>$row){
					if($user_id == $row->getId() || $row->getApartmentGroupId() === null){
						$res[$key]['id']   = $row->getId();
						$res[$key]['name'] = $row->getFirstName() . ' ' . $row->getLastName();
					}
				}
				$result['result'] = $res;
			}
		} catch (\Exception $e) {
			echo $e;
			$result['rc'] = '01';
		}
		return new JsonModel($result);
	}

    /**
     * @return \DDD\Service\ApartmentGroup
     */
    public function getConcierge()
    {
		if ($this->_concierge === null) {
			$this->_concierge = $this->getServiceLocator()->get('service_apartment_group');
		}

		return $this->_concierge;
	}

	public function itemAction()
    {
        /**
         * @var $auth \Library\Authentication\BackofficeAuthenticationService
         */

        $id = (int)$this->params()->fromRoute('id', 0);
        $service = $this->getConcierge();
        if (!$id ||  !($rowObj = $service->getConciergeByGroupId($id))) {
            Helper::setFlashMessage(['error' => TextConstants::ERROR_NO_ITEM]);
            return $this->redirect()->toRoute('backoffice/default', ['controller' => 'concierge', 'action' => 'view']);
        }

        $auth           = $this->getServiceLocator()->get('library_backoffice_auth');
        $authId         = (int)$auth->getIdentity()->id;
        $external       = (int)$auth->getIdentity()->external;
        $usermanagerDao = $this->getServiceLocator()->get('dao_user_user_manager');
        $userInfo       = $usermanagerDao->fetchOne(['id' => $authId]);

        $currentDate = time();
        if (!is_null($userInfo->getTimezone())) {
            $currentDate = Helper::getCurrenctDateByTimezone($userInfo->getTimezone());
        }

        if ($auth->hasRole(Roles::ROLE_GLOBAL_APARTMENT_GROUP_MANAGER)) {
            $userId = false;
        } elseif ($auth->hasRole(Roles::ROLE_CONCIERGE_DASHBOARD) || $auth->hasRole(Roles::ROLE_APARTMENT_GROUP_MANAGEMENT)) {
            $userId = $authId;
        } else {
            return ['errorPage' => 'error'];
        }

        $isBookingManager = false;
        if ($auth->hasRole(Roles::ROLE_BOOKING_MANAGEMENT)) {
            $isBookingManager = true;
        }

        $hasFronterCharg = false;
        if ($auth->hasRole(Roles::ROLE_FRONTIER_CHARGE)) {
            $hasFronterCharg = true;
        }

        $hasFrontierCard = false;
        if ($auth->hasRole(Roles::ROLE_FRONTIER_MANAGEMENT)) {
            $hasFrontierCard = true;
        }

        $hasCurrentStayView = false;
        if ($auth->hasRole(Roles::ROLE_CONCIERGE_CURRENT_STAYS)) {
            $hasCurrentStayView = true;
        }

        $checkID = $service->checkGroupForUser($id, $userId);

        if (!$checkID) {
            return ['errorPage' => 'error'];
        }

        $timezone = 'UTC';
        $group_name = '';
        $accommodationList = $service->getApartmentGroupItems($id);

        if (is_object($rowObj)) {
            $timezone = $rowObj->getTimezone();
            $group_name = $rowObj->getName();
        }
        $conciergeView = $service->getConciergeView($accommodationList, $timezone);
        // get bad email list
        $getBadEmail = BookingTicket::getBadEmail();

        return  [
            'currentStays'          => $conciergeView['currentStays'],
            'arrivalsYesterday'     => $conciergeView['arrivalsYesterday'],
            'arrivalsToday'         => $conciergeView['arrivalsToday'],
            'arrivalsTomorrow'      => $conciergeView['arrivalsTomorrow'],
            'checkoutsToday'        => $conciergeView['checkoutsToday'],
            'checkoutsTomorrow'     => $conciergeView['checkoutsTomorrow'],
            'checkoutsYesterday'    => $conciergeView['checkoutsYesterday'],
            'dateInTimezone'        => $conciergeView['dateInTimezone'],
            'groupId'               => $id,
            'groupName'             => $group_name,
            'isBookingManager'      => $isBookingManager,
            'hasFronterCharg'       => $hasFronterCharg,
            'hasCurrentStayView'    => $hasCurrentStayView,
            'currentDate'           => $currentDate,
            'hasFrontierCard'       => $hasFrontierCard,
            'getBadEmail'           => json_encode($getBadEmail),
            'userIsExternal'        => $external
        ];
	}

	public function ajaxgetmenegerAction()
	{
		$result = array('rc'=>'00', 'result'=>array());
		$request = $this->getRequest();
		try{
			if($request->isXmlHttpRequest()) {
				$txt      = strip_tags(trim($request->getPost('txt')));
				$service  = $this->getServiceLocator()->get('dao_user_user_manager');
				$manager  = $service->searchManagerByAutocomplate($txt);
				$res = array();
				foreach ($manager as $key=>$row){
					$res[$key]['id']   = $row->getId();
					$res[$key]['name'] = $row->getFirstName() . ' ' . $row->getLastName();
				}
				$result['result'] = $res;
			}
		} catch (\Exception $e) {
			$result['rc'] = '01';
		}
		return new JsonModel($result);
	}

    /**
     *
     * @param int $actionId
     * @return string
     */
    private function identifyApartmentGroupAction($actionId)
    {
        $apartmentGroupActions = [
            Logger::ACTION_APARTMENT_GROUPS_NAME => 'Group Name',
            Logger::ACTION_APARTMENT_GROUPS_APARTMENT_LIST => 'Apartments List',
            Logger::ACTION_APARTMENT_GROUPS_USAGE => 'Group\'s Usages',
        ];

        if (isset($apartmentGroupActions[$actionId])) {
            return $apartmentGroupActions[$actionId];
        }

        return 'not defined';
    }
}
