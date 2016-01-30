<?php

namespace Apartment\Controller;

use Apartment\Controller\Base as ApartmentBaseController;

use DDD\Service\Apartment\Inventory;
use DDD\Service\Booking;
use DDD\Service\Accommodations as ApartmentService;
use DDD\Service\Notifications as NotificationService;
use DDD\Dao\Apartment\Inventory as InventoryDao;

use Library\ChannelManager\ChannelManager;
use Library\ChannelManager\CivilResponder;
use Library\Constants\Constants;
use Library\Constants\Objects;
use Library\Constants\Roles;
use Library\Constants\TextConstants;
use Library\ActionLogger\Logger as ActionLogger;

use Zend\Http\Request;
use Zend\Http\Response;
use Zend\View\Helper\Json;
use Zend\View\Model\JsonModel;
use Zend\View\Model\ViewModel;

class InventoryCalendar extends ApartmentBaseController
{
	public function indexAction()
    {
        if($this->apartmentStatus == Objects::PRODUCT_STATUS_DISABLED) {
            $this->redirect()->toRoute('apartment/general', ['apartment_id' => $this->apartmentId]);
        }

		/*
		 * @todo move out this
		 */
		$weekDays = array (
            'Sunday'    => 0,
            'Monday'    => 1,
            'Tuesday'   => 2,
            'Wednesday' => 3,
            'Thursday'  => 4,
            'Friday'    => 5,
            'Saturday'  => 6
		);

		// get main params from route, month and year
		$year = $this->params()->fromRoute('year', 0);
		$month = $this->params()->fromRoute('month', 0);

		if ($year && $month) {
			// do checks for given month and year
            $roleManager = 'no';
            $auth = $this->getServiceLocator()->get('library_backoffice_auth');

            if ($auth->hasRole(Roles::ROLE_APARTMENT_INVENTORY_MANAGER)) {
                $roleManager = 'yes';
            }

			$givenMonthName = date("F", mktime(0, 0, 0, $month, 10)); // get given month name

			$firstDayOfGivenMonthTimestamp = strtotime('first day of ' . $year . '-' . $month); // get first day of given month in miliseconds
			$firstDayOfGivenMonthDate = getdate($firstDayOfGivenMonthTimestamp); // get date array from timestamp

			$dayOfWeek = $weekDays[$firstDayOfGivenMonthDate['weekday']]; // get day of week for given month first day to correctly render calendar
			$givenMonthDaysCount = cal_days_in_month(CAL_GREGORIAN, $month, $year);

			/** @var $rateService \DDD\Service\Apartment\Rate */
			$rateService = $this->getServiceLocator()->get('service_apartment_rate');
			$rates = $rateService->getApartmentRates($this->apartmentId);
			// building inventory array
			$inventory = array ();

			foreach ($rates as $rate) {

				$rateID = $rate->getID();
				$rateAvailability = $rateService->getRateAvailabilityForMonth($rateID, $year, $month);

				foreach ($rateAvailability as $singleDayAvailability) {
					$inventory[$rateID][$singleDayAvailability->getDate()] = [
                        "availability" => $singleDayAvailability->getAvailability(),
                        "price" => $singleDayAvailability->getPrice(),
                        "isLockPrice" => $singleDayAvailability->getIsLockPrice(),
					];
				}
			}

            $detailsDao       = $this->getServiceLocator()->get('dao_apartment_details');
            $apartmentDetails = $detailsDao->fetchOne(['apartment_id' => $this->apartmentId], ['sync_cubilis']);
            $isConnected      = $apartmentDetails->getSync_cubilis();

            $urlToggleAvailability = $this->url()->fromRoute('apartment/calendar/toggle-availability', ['apartment_id' => $this->apartmentId, 'year' => date('Y'), 'month' => date('m')]);
            $urlUpdatePrices       = $this->url()->fromRoute('apartment/calendar/update-prices', ['apartment_id' => $this->apartmentId, 'year' => date('Y'), 'month' => date('m')]);

            $date = new \DateTime();
            $date->setDate($year, $month, 1);
            $monthStart = $date->format('Y-m-d');

            if ($monthStart < date('Y-m-d', strtotime('-1 days'))) {
                $monthStart = date('Y-m-d', strtotime('-1 days'));
            }

            $date->setDate($year, $month, $givenMonthDaysCount);
            $monthEnd = $date->format('Y-m-d');

			return new ViewModel([
				'apartmentId'           => $this->apartmentId,
				'apartmentStatus'       => $this->apartmentStatus,
				'year'                  => $year,
				'month'                 => $month,
				'givenMonthName'        => $givenMonthName,
				'givenMonthDaysCount'   => $givenMonthDaysCount,
				'dayOfWeek'             => $dayOfWeek,
				'rates'                 => $rates,
				'inventory'             => $inventory,
				'urlToggleAvailability' => $urlToggleAvailability,
				'urlUpdatePrices'       => $urlUpdatePrices,
				'roleManager'           => $roleManager,
				'monthStart'            => $monthStart,
				'monthEnd'              => $monthEnd,
                'isConnected'           => $isConnected
			]);
		} else {
			return $this->redirect()->toRoute('apartment/calendar', [
				"year" => date('Y'),
				"month" => date('m')
			], [], true);
		}
	}

	public function ajaxToggleAvailabilityAction()
    {
		/**
		 * @var Request $request
		 * @var Response $response
		 * @var \DDD\Service\Apartment\Inventory $inventoryService
		 */
		$request = $this->getRequest();

		$output = [
			'bo' => ['status' => 'error'],
		];

		try {
			$date = $request->getPost('date', null);
			$action = $request->getPost('action', null);
			$reasonMessage = $request->getPost('message', null);

			if ($request->isPost() && $request->isXmlHttpRequest()) {
                $inventoryService = $this->getServiceLocator()->get('service_apartment_inventory');
				if (strtotime($date) !== false && in_array($action, ['open', 'close'])) {
                    $apartmentId = $this->apartmentId;
                    $inventoryDao = new InventoryDao($this->getServiceLocator());
					$availability = ($action == 'open' ? 1 : 0);

                    $preInvData = $inventoryDao->getApartmentAvailabilityByDate(
                        $apartmentId, date('Y-m-d', strtotime($date))
                    );

                    $preAvailability = null;
                    if ($preInvData) {
                        $preAvailability = $preInvData->getAvailability();
                    }

                    if (!$availability && !$reasonMessage) {
                        throw new \Exception(TextConstants::AVAILABILITY_CLOSE_MSG);
                    }

                    $responseUpdate = $inventoryService->updateAvailabilityFromCalendar($apartmentId, $date, $availability);
                    $auth = $this->getServiceLocator()->get('library_backoffice_auth');
                    if ($responseUpdate['status'] == 'success') {
                        if (!$availability) {
                            $reasonMessage = (!empty($reasonMessage)) ? '<br><i>Reason:</i> "' . $reasonMessage .'"' : '';

                            /* @var $actionLogger \Library\ActionLogger\Logger */
                            $actionLogger = $this->getServiceLocator()->get('ActionLogger');
                            $actionLogger->save(
                                ActionLogger::MODULE_APARTMENT_CALENDAR,
                                $apartmentId,
                                ActionLogger::ACTION_APARTMENT_CALENDAR_AVAILABILITY,
                                'Availability <b>closed</b> for ' . $date . $reasonMessage
                            );
                        }

                        $output['bo']['status'] = 'success';
                        $output['bo']['msg'] = $responseUpdate['msg'];
                    } else {
                        throw new \Exception($responseUpdate['msg']); //'Cannot update price.'
                    }

                    $dateExploded = explode(' ', $date);

                    /* @var $apartmentInventoryService \DDD\Service\Apartment\Inventory */
                    $apartmentInventoryService = $this ->getServiceLocator() ->get('service_apartment_inventory');
                    $accommodationsDao         = $this->getServiceLocator()->get('dao_accommodation_accommodations');

                    $bookingOnDate = $apartmentInventoryService->checkApartmentAvailabilityByDate(
                        $apartmentId,
                        $dateExploded[0],
                        Booking::BOOKING_STATUS_BOOKED
                    );


                    $userId  = $auth->getIdentity()->id;

                    $accInfo = $accommodationsDao->fetchOne(["id" => $apartmentId]);

                    $sellingStatus = [
                        ApartmentService::APARTMENT_STATUS_LIVE_AND_SELLING,
                        ApartmentService::APARTMENT_STATUS_SELLING_NOT_SEARCHABLE
                    ];

                    if (   in_array($accInfo->getStatus(), $sellingStatus)
                        && $preAvailability != $availability
                        && $availability == 0
                        && !$bookingOnDate
                    ) {

                        $notifService      = $this->getServiceLocator()->get('service_notifications');
                        $userManagerDao    = $this->getServiceLocator()->get('dao_user_user_manager');
                        $userGroupDao      = $this->getServiceLocator()->get('dao_user_user_groups');

                        $accName = $accInfo->getName();
                        $userInfo = $userManagerDao->getUserById($userId);

                        $roledUsers = $userGroupDao->getUsersByGroupId(Roles::ROLE_APARTMENT_AVAILABILITY_MONITOR);

                        $recipient = [];
                        foreach ($roledUsers as $roledUser) {
                            $recipient[] = $roledUser->getUserId();
                        }

                        $now = date('Y-m-d');

                        $sender       = NotificationService::$availabilityMonitoring;
                        $calendarDate = date('Y/m', strtotime($date));
                        $closeDate    = date('Y-m-d', strtotime($date));

                        if ($availability) {
                            $notifMsg = TextConstants::OPEN_APARTMENT_CALENDAR;
                        } else {
                            $notifMsg = TextConstants::CLOSE_APARTMENT_CALENDAR . ' ' . $reasonMessage;
                        }

                        // notification
                        if (!$auth->hasRole(Roles::ROLE_NO_TRACK_AVAILABILITY_CHANGES)) {

                            $message = sprintf(
                                $notifMsg,
                                $userId,
                                $userInfo->getFirstname() . ' ' . $userInfo->getLastname(),
                                $apartmentId,
                                $calendarDate,
                                $accName,
                                $closeDate
                            );

                            $url = '/apartment/' . $apartmentId . '/calendar/' . $calendarDate;
                            $notificationData = [
                                'recipient' => $recipient,
                                'sender'    => $sender,
                                'sender_id' => $userId,
                                'message'   => $message,
                                'url'   => $url,
                                'show_date' => $now
                            ];

                            $notifService->createNotification($notificationData);
                        }
                    }
				} else {
					$output['bo']['msg'] = 'Bad parameters.';
				}
			} else {
				$output['bo']['msg'] = 'Bad request.';
			}
		} catch (\Exception $ex) {
			$output['bo']['msg'] = $ex->getMessage();
		}

		return new JsonModel($output);
	}

	public function ajaxUpdateRatePricesAction()
    {
		/**
		 * @var Request $request
		 * @var Response $response
		 * @var Inventory $inventoryService
		 */
		$request = $this->getRequest();

		$output = [
			'bo'  => ['status' => 'error'],
		];

		try {
			$price = $request->getPost('parent_price', null);
			$date = $request->getPost('date', null);
			$lockPrice = $request->getPost('lock_price', null);

			if ($request->isPost() && $request->isXmlHttpRequest()) {
                $inventoryService = $this->getServiceLocator()->get('service_apartment_inventory');
                $dateFrom = $dateTo = date('Y-m-d', strtotime($date));
                $lockPrice = $lockPrice ? 1 : 0;
                $responseUpdate = $inventoryService->updatePriceByRange($this->apartmentId, $price, $dateFrom, $dateTo, null, 0, $lockPrice, null);
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
            if ($request->isPost() && $request->isXmlHttpRequest()) {
                $dateFrom = $request->getPost('date_from', null);
                $dateTo = $request->getPost('date_to', null);
                if (!$dateFrom || !$dateTo) {
                    return new JsonModel($result);
                }

                // send apartment
                $syncService->push($this->apartmentId, $dateFrom, $dateTo);
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
