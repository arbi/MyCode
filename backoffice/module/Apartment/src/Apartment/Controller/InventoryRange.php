<?php

namespace Apartment\Controller;

use Apartment\Controller\Base as ApartmentBaseController;

use DDD\Service\Apartment\Inventory as InventoryService;
use DDD\Service\Apartment\Rate;
use DDD\Service\Accommodations as ApartmentService;

use Library\ChannelManager\ChannelManager;
use Library\ChannelManager\CivilResponder;
use Library\Constants\Objects;
use Library\Constants\Roles;
use Library\Utility\Helper;
use Library\Constants\TextConstants;
use DDD\Service\Notifications as NotificationService;
use Library\ActionLogger\Logger as ActionLogger;

use Zend\Http\Request;
use Zend\View\Model\JsonModel;
use Zend\View\Model\ViewModel;

class InventoryRange extends ApartmentBaseController {

	public function indexAction()
    {
        if($this->apartmentStatus == Objects::PRODUCT_STATUS_DISABLED) {
            $this->redirect()->toRoute('apartment/general', ['apartment_id' => $this->apartmentId]);
        }

		// get selected rate ID
		$selectedRateID = $this->params ()->fromRoute ( 'rate_id', 0 );
		$urlUpdate = $this->url()->fromRoute('apartment/inventory-range/update', [
			'apartment_id' => $this->apartmentId,
			'rate_id' => $selectedRateID,
		]);

		return new ViewModel( array (
			'apartmentId' => $this->apartmentId,
            'apartmentStatus' => $this->apartmentStatus,
			'selectedRateID' => $selectedRateID,
			'urlUpdate' => $urlUpdate,
		) );
	}

	public function updateAction() {
		$output = [
			'bo' => ['status' => 'error'],
		];

		try {
			/**
			 * @var Request $request
			 * @var \DDD\Service\Apartment\Inventory $inventoryService
			 * @var Rate $apartmentRateService
			 * @var \DDD\Service\Apartment\General $apartmentService
			 */
			$request = $this->getRequest();

			/**
			 * Definitions
			 */
			if ($request->isPost() && $request->isXmlHttpRequest()) {
				$inventoryService     = $this->getServiceLocator()->get('service_apartment_inventory');

				// Required as Route params
                $apartmentId = $this->params()->fromRoute('apartment_id');

				// Required
				$type      = $this->params()->fromPost('type', null); // availability|price|price-extra
				$dateRange = $this->params()->fromPost('date_range', null);
				$weekDays  = $this->params()->fromPost('days', null);

					// Optional (depends on type of choice)
				$availability = $this->params()->fromPost('avail', null);
				$amount       = $this->params()->fromPost('amount', null);
				$priceType    = $this->params()->fromPost('price_type', null);
				$lockPrice    = $this->params()->fromPost('lock_price', null);
				$comment      = $this->params()->fromPost('comment', null);
				$forceUpdatePrice = $this->params()->fromPost('force_update_price', null);

				// Check Imposable Situation
				if (is_null($apartmentId)) {
					throw new \Exception('Some parameters from route are missing.');
				}

				// Check Required Parameters
				if (is_null($type) || is_null($dateRange) || is_null($weekDays) || empty($dateRange) || empty($weekDays)) {
					throw new \Exception('Some required parameters are missing.');
				}

				if (!empty($dateRange)) {
					$dates = explode(' - ', $dateRange);
					$start = strtotime($dates[0]);
					$end   = strtotime($dates[1]);
					if (   count($dates) != 2
						|| ($start < strtotime("-2 day"))
						|| ($end > strtotime("+1 year"))
					) {
						throw new \Exception('Incorrect date range.');
					}
				}

				// Check Action Type
				if (!in_array($type, ['availability', 'price', 'price-extra'])) {
					throw new \Exception('Type is wrong.');
				}

                if ($type == 'availability' && !is_null($availability)) {

                    if (!$availability && !$comment) {
                        throw new \Exception(TextConstants::AVAILABILITY_CLOSE_MSG);
                    }

					$responseUpdate = $inventoryService->updateInventoryRangeByAvailability(
						$apartmentId,
						$dateRange,
						$weekDays,
						$availability
					);

                    if ($responseUpdate['status'] == 'success') {
						$output['bo']['status'] = 'success';
						$output['bo']['msg']    = $responseUpdate['msg'];

						$accommodationsDao = $this->getServiceLocator()->get('dao_accommodation_accommodations');
						$accInfo           = $accommodationsDao->fetchOne(["id" => $apartmentId]);

						$sellingStatus = [
	                        ApartmentService::APARTMENT_STATUS_LIVE_AND_SELLING,
	                        ApartmentService::APARTMENT_STATUS_SELLING_NOT_SEARCHABLE
	                    ];

						if (   in_array($accInfo->getStatus(), $sellingStatus)
							&& !is_null($availability)
							&& $availability == 0
						) {
							$auth              = $this->getServiceLocator()->get('library_backoffice_auth');
							$userId            = $auth->getIdentity()->id;
							$accName           = $accInfo->getName();
							$notifService      = $this->getServiceLocator()->get('service_notifications');
							$userManagerDao    = $this->getServiceLocator()->get('dao_user_user_manager');
							$userGroupDao      = $this->getServiceLocator()->get('dao_user_user_groups');
							$userInfo          = $userManagerDao->getUserById($userId);
							$roledUsers        = $userGroupDao->getUsersByGroupId(Roles::ROLE_APARTMENT_AVAILABILITY_MONITOR);

		            		$recipient = [];
		            		foreach ($roledUsers as $roledUser) {
		            			$recipient[] = $roledUser->getUserId();
		            		}

                            $dateRangeArray = Helper::refactorDateRange($dateRange);
							$now = date('Y-m-d');

							$sender       = NotificationService::$availabilityMonitoring;
							$calendarDate = date('Y/m', strtotime($dateRangeArray['date_from']));

							$days = [
								'Monday', 'Tuesday', 'Wednesday',
								'Thursday', 'Friday', 'Saturday', 'Sunday'
							];

							$exceptDays = [];
							foreach ($weekDays as $key => $value) {
								if ($value == 0) {
									$exceptDays[] = $days[$key];
								}
							}

							if ($exceptDays) {
                                $exceptDays = implode(', ', $exceptDays);
								$exceptDays = 'except: <b>' . $exceptDays . '</b>';
							} else {
								$exceptDays = '';
							}

							if ($availability) {
								$notifMsg = TextConstants::OPEN_APARTMENT_INVENTORY;
							} else {
								$notifMsg = TextConstants::CLOSE_APARTMENT_INVENTORY;
							}

							if ($comment) {
								$reasonMessage = (!empty($comment)) ? '<br><i>Reason:</i> "' . $comment .'"' : '';

		                        /* @var $actionLogger \Library\ActionLogger\Logger */
		                        $actionLogger = $this->getServiceLocator()->get('ActionLogger');
		                        $actionLogger->save(
		                            ActionLogger::MODULE_APARTMENT_INVENTORY,
		                            $apartmentId,
		                            ActionLogger::ACTION_APARTMENT_INVENTORY_AVAILABILITY,
		                            'Availability <b>closed</b> for ' . $dateRange . ' <b>' . $exceptDays . '</b> ' . $reasonMessage
		                        );
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
                                    $dateRangeArray['date_from'],
                                    $dateRangeArray['date_to'],
                                    $exceptDays
                                );

                                $url = '/apartment/' . $apartmentId . '/calendar/' . $calendarDate;
                                $notificationData = [
                                    'recipient' => $recipient,
                                    'sender' => $sender,
                                    'sender_id' => $userId,
                                    'message' => $message,
                                    'url' => $url,
                                    'show_date' => $now
                                ];

                                $notifService->createNotification($notificationData);
                            }
			        	}
                    } else {
						throw new \Exception($responseUpdate['msg']);
					}
				} elseif ($type == 'price' && !is_null($amount) && !is_null($priceType)) { // && !in_array($priceType, InventoryService::$changePriceActionList)
                    $responseUpdate = $inventoryService->updateInventoryRangeByPrice(
                    	$apartmentId,
                    	$dateRange,
                    	$weekDays,
                    	$amount,
                    	$priceType,
                    	$lockPrice,
                        0,
                        $forceUpdatePrice
                    );

                    $output['bo']['status'] = $responseUpdate['status'];
                    $output['bo']['msg']    = $responseUpdate['msg'];
				} else {
					throw new \Exception('Some optional parameters are missing.');
				}
			} else {
				throw new \Exception('Bad request.');
			}
		} catch (\RuntimeException $ex) {
			$output['bo']['status'] = 'success';
			$output['bo']['msg']    = 'availability successfully updated';
		} catch (\Exception $ex) {
			$output['bo']['msg'] = $ex->getMessage();
		}

		return new JsonModel($output);
	}
}
