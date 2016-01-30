<?php

namespace DDD\Service\Apartment;

use DDD\Dao\Apartment\Rate as ApartmentRate;
use DDD\Dao\Booking\Charge;
use DDD\Dao\User\UserManager;
use DDD\Domain\Apartment\ProductRate\CubilisRoomRate;
use DDD\Domain\Apartment\ProductRate\WithStatus;
use DDD\Service\Availability;
use DDD\Service\ServiceBase;
use DDD\Service\Task;
use DDD\Service\User;
use DDD\Service\Partners;
use DDD\Service\Booking\Charge as ChargeService;
use DDD\Service\Booking as BookingService;
use DDD\Service\Booking\BookingAddon;
use DDD\Service\Apartment\Rate;
use DDD\Service\Task as TaskService;
use DDD\Dao\Apartment\Inventory as InventoryDao;

use DDD\Service\Booking\BookingTicket as ReservationTicketService;


use Library\ActionLogger\Logger;
use Library\Constants\Objects;
use Library\Constants\TextConstants;
use Library\Utility\Debug;
use Library\Utility\Helper;

use Zend\Db\Sql\Where;
use Zend\Stdlib\ArrayObject;



class Inventory extends ServiceBase
{
    /**
     * @var array
     */
    static public $weekEndDays = ['Fri', 'Sat'];

    /**
     * @var array
     */
    static public $changePriceActionList = [0, 1, 2, 3, 4];


    const PRICE_CHANGE_LIMIT = 70;

    /**
     * @param $res_number
     * @param bool $push
     * @param bool $sendEmail
     * @param int $cancellationType
     * @param bool $emailAddress
     * @return bool
     */
    public function processCancellation(
        $res_number,
        $push = false,
        $sendEmail = true,
        $cancellationType = BookingService::BOOKING_STATUS_CANCELLED_BY_CUSTOMER,
        $emailAddress = false
    ) {
	    /**
	     * @var \DDD\Service\Booking\BookingTicket $bookingTicketService
	     * @var \Library\Authentication\BackofficeAuthenticationService $authenticationService
	     * @var \DDD\Domain\Booking\ForCancelCharge $charge
	     * @var Logger $logger
         * @var \DDD\Dao\Booking\ChargeDeleted $chargeDeletedDao
         * @var \DDD\Service\Availability $availabilityService
         * @var \DDD\Dao\Booking\Booking $bookingDao
	     */
        $chargeDeletedDao = $this->getServiceLocator()->get('dao_booking_charge_deleted');
        $logger = $this->getServiceLocator()->get('ActionLogger');
        $availabilityService = $this->getServiceLocator()->get('service_availability');
        $bookingDao = $this->getServiceLocator()->get('dao_booking_booking');

        $bookingDao->setEntity(new \DDD\Domain\Booking\ForCancel());

        $rowBooking = $bookingDao->getBookingForCancel($res_number);

        if (!$rowBooking) {
            return false;
        }

	    // If ticket status equals unknown it means ticket was canceled, availability was opened and now we should
	    // apply our processes to calculate commissions, balance, etc... Now we need to apply real cancelation logic
	    if ($rowBooking->getStatus() != BookingService::BOOKING_STATUS_CANCELLED_PENDING
            && $rowBooking->isOverbooking() != ReservationTicketService::OVERBOOKING_STATUS_OVERBOOKED) {
            // do not open availability if apartment status is not "Live and Selling" or "Selling not Searchable"
            $availabilityService->updateAvailabilityAllPartForApartment($rowBooking->getId(), $this->getNewAvailability(), true);

            // change apartel availability and sync cubilis if this apartment is apartel
            $availabilityService->updateAvailabilityAllPartForApartel($rowBooking->getId(), true);
        }
		// Global parameters
		$ticketModel                    = $rowBooking->getModel();
	    $affiliateID                    = $rowBooking->getAffiliateID();
	    $expediaPartnerID               = Partners::PARTNER_EXPEDIA;
	    $affiliateCommission            = $rowBooking->getPartnerCommission();
	    $loggedInUserID                 = $this->getUserId();
	    $noActionCancellationStatuses   = [
			BookingService::BOOKING_STATUS_CANCELLED_MOVED,
		];

		if (in_array($cancellationType, $noActionCancellationStatuses)) {
			// Not commission partner if not expedia
		} elseif ($cancellationType != BookingService::BOOKING_STATUS_CANCELLED_PENDING) {
			// Common parameters for charge
			$params = [
				'reservation_id'	=> $rowBooking->getId(),
				'date' 				=> date('Y-m-d H:i:s'),
				'user_id' 			=> $loggedInUserID,
				'customer_currency' => $rowBooking->getGuestCurrencyCode(),
				'acc_currency' 		=> $rowBooking->getApartmentCurrencyCode(),
				'status' 			=> ChargeService::CHARGE_STATUS_NORMAL,
			];

            $reverseParams = [
                'reservation_id'	=> $rowBooking->getId(),
                'date' 				=> date('Y-m-d H:i:s'),
                'user_id' 			=> $loggedInUserID,
            ];


			$chargeDao = $this->getChargingDao();
			$charges = $chargeDao->getFirstAccTaxCharge($rowBooking->getId());
			$charges = iterator_to_array($charges);

			//open parking availability for that period
			$parkingInventoryDao = $this->getServiceLocator()->get('dao_parking_spot_inventory');
            foreach($charges as $charge) {
				if (
					$charge->getAddons_type() == BookingAddon::ADDON_TYPE_PARKING
					&&
					(int) $charge->getEntityId() > 0
				) {
					$parkingInventoryDao->save(
						['availability' => 1],
						[
							'spot_id' => $charge->getEntityId(), 'date' => $charge->getReservationNightlyDate()
						]);
				}
			}
			$accChargeInApartmentCurrency = 0;
			$accChargeInCustomerCurrency = 0;

			$parkingCommissionChargeAmountInApartmentCurrency = 0;
			$parkingCommissionChargeAmountInCustomerCurrency = 0;

			$totCommissionChargeAmountInApartmentCurrency = 0;
			$totCommissionChargeAmountInCustomerCurrency = 0;

			$taxes = [];

			foreach ($charges as $charge) {
				switch ($charge->getAddons_type()) {
					case BookingAddon::ADDON_TYPE_ACC:
						$accChargeInApartmentCurrency += $charge->getAcc_amount();
						$accChargeInCustomerCurrency += $charge->getCustomer_amount();

						break;
					case BookingAddon::ADDON_TYPE_TAX_TOT:
						$totCommissionChargeAmountInApartmentCurrency += $charge->getAcc_amount();
						$totCommissionChargeAmountInCustomerCurrency += $charge->getCustomer_amount();

						break;
                    case BookingAddon::ADDON_TYPE_PARKING:
						if ($charge->getCommission() > 0) {
							$parkingCommissionChargeAmountInApartmentCurrency += $charge->getAcc_amount();
							$parkingCommissionChargeAmountInCustomerCurrency += $charge->getCustomer_amount();
						}

						break;
					case BookingAddon::ADDON_TYPE_CLEANING_FEE:

						break;
                    case BookingAddon::ADDON_TYPE_DISCOUNT:

                        break;
                    case BookingAddon::ADDON_TYPE_COMPENSATION:

                        break;
                    case BookingAddon::ADDON_TYPE_EXTRA_PERSON:

                        break;
					default:
						// Compose array of tax charges
						$chargeAddonsValue = $rowBooking->{"get" . ucfirst($charge->getLocation_tax())}();
						$taxes[] = [
							'cxl_apply' => $charge->getCxl_apply(),
							'value' 	=> $chargeAddonsValue,
						];

						break;
				}

				if (  in_array(
                          $cancellationType,
                          [
                              BookingService::BOOKING_STATUS_CANCELLED_NOSHOW,
                              BookingService::BOOKING_STATUS_CANCELLED_BY_CUSTOMER
                          ]
                      )
                    && in_array(
                        $charge->getAddons_type(),
                        [
                            BookingAddon::ADDON_TYPE_PARKING,
                            BookingAddon::ADDON_TYPE_CLEANING_FEE,
                        ]
                    )
                ) {
					continue;
				} else {
					// Reverse charges
					if ($charge->getAddons_type() == BookingAddon::ADDON_TYPE_PARKING ||
                        $charge->getAddons_type() == BookingAddon::ADDON_TYPE_CLEANING_FEE ||
                        $rowBooking->getIsRefundable() == Rate::APARTMENT_RATE_REFUNDABLE ||
						$cancellationType == BookingService::BOOKING_STATUS_CANCELLED_INVALID ||
						$cancellationType == BookingService::BOOKING_STATUS_CANCELLED_EXCEPTION
                        ) { // Parking or refundable type
						$removedId = $charge->getId();

						$chargeDao->save(['status' => ChargeService::CHARGE_STATUS_DELETED], ['id' => $removedId]);
                        if (!$chargeDeletedDao->fetchOne(['reservation_charge_id' => $removedId])) {
                            $reverseParams['reservation_charge_id'] = $removedId;
                            $chargeDeletedDao->save($reverseParams);
                        }

					}
				}
			}

			if ($rowBooking->getIsRefundable() == Rate::APARTMENT_RATE_REFUNDABLE) {
				// Penalty
				if ($rowBooking->getPenalty_hours() <= $rowBooking->getRefundableBeforeHours()) {
					// Default params + penalty type
					$penaltyChargeParams = $params;
					$penaltyChargeParams['type'] = 'p';

					// Calculate penalty amount
					$ticketPenaltyAmount = $rowBooking->getPenaltyFixedAmount();
					$penaltyChargeAmountInApartmentCurrency = $ticketPenaltyAmount;

                    //time of cancel
//					foreach ($taxes as $tax) {
//						if ($tax['cxl_apply'] == 1) {
//							$penaltyChargeAmountInApartmentCurrency += $accChargeInApartmentCurrency * $tax['value'] / 100;
//						} elseif ($tax['cxl_apply'] == 2) {
//							$penaltyChargeAmountInApartmentCurrency += $ticketPenaltyAmount * $tax['value'] / 100;
//						}
//					}

					$ticketPenaltyAmountInCustomerCurrency = $ticketPenaltyAmount * $rowBooking->getCurrency_rate();
					$penaltyChargeAmountInCustomerCurrency = $penaltyChargeAmountInApartmentCurrency * $rowBooking->getCurrency_rate();

					// Without taxes
					$ticketPenaltyAmountInApartmentCurrency = number_format($ticketPenaltyAmount, 2, '.', '');
					$ticketPenaltyAmountInCustomerCurrency = number_format($ticketPenaltyAmountInCustomerCurrency, 2, '.', '');

					// Including taxes
					$penaltyChargeAmountInApartmentCurrency = number_format($penaltyChargeAmountInApartmentCurrency, 2, '.', '');
					$penaltyChargeAmountInCustomerCurrency = number_format($penaltyChargeAmountInCustomerCurrency, 2, '.', '');


					// initializing....
					$penaltyChargeCommission = 0;
					$penaltyChargeModel = ChargeService::CHARGE_MONEY_DIRECTION_GINOSI_COLLECT;

					switch ($cancellationType) {
						case BookingService::BOOKING_STATUS_CANCELLED_BY_CUSTOMER:
						case BookingService::BOOKING_STATUS_CANCELLED_NOSHOW:
						case BookingService::BOOKING_STATUS_CANCELLED_TEST_BOOKING;
							if ($ticketModel == Partners::BUSINESS_MODEL_GINOSI_COLLECT) {
								$penaltyChargeModel = ChargeService::CHARGE_MONEY_DIRECTION_GINOSI_COLLECT;

								if ($affiliateID == $expediaPartnerID) {
									$penaltyChargeCommission = $affiliateCommission;
								} else {
									$penaltyChargeCommission = 0;
								}

								$penaltyChargeParams['acc_amount'] = $penaltyChargeAmountInApartmentCurrency;
								$penaltyChargeParams['customer_amount'] = $penaltyChargeAmountInCustomerCurrency;
							} elseif (in_array($ticketModel, Partners::partnerBusinessModel())) {
								$penaltyChargeModel = ChargeService::CHARGE_MONEY_DIRECTION_PARTNER_COLLECT;
								$penaltyChargeCommission = $affiliateCommission;

								$penaltyChargeParams['acc_amount'] = $penaltyChargeAmountInApartmentCurrency;
								$penaltyChargeParams['customer_amount'] = $penaltyChargeAmountInCustomerCurrency;

                                // reverse only ginosi collect charges
                                $this->reverseChages($charges, $reverseParams, ChargeService::CHARGE_MONEY_DIRECTION_GINOSI_COLLECT);
							}

                            if ($cancellationType == BookingService::BOOKING_STATUS_CANCELLED_TEST_BOOKING) {
                                // reverse all charges
                                $this->reverseChages($charges, $reverseParams);
                            }

							break;
						case BookingService::BOOKING_STATUS_CANCELLED_BY_GINOSI:
							if ($ticketModel == Partners::BUSINESS_MODEL_GINOSI_COLLECT) {
                                $partnerAllCharges = [];

                                foreach ($charges as $charge) {
                                    // Calculate partner commission
                                    if ($charge->getCommission() > 0 || ($affiliateID == $expediaPartnerID && $charge->getAddons_type() == BookingAddon::ADDON_TYPE_TAX_TOT)) {
                                        //$charge->getAddons_type() == BookingAddon::ADDON_TYPE_PARKING &&

                                        $chargeAmountInApartmentCurrency = $charge->getAcc_amount();
                                        $chargeAmountInCustomerCurrency = $charge->getCustomer_amount();
                                        $partnerCharge = $params;
                                        $partnerCharge['type'] = 'g';
                                        $partnerCharge['money_direction'] = ChargeService::CHARGE_MONEY_DIRECTION_GINOSI_COLLECT;
                                        $partnerCharge['commission'] = 100;
                                        $partnerCharge['acc_amount'] = $chargeAmountInApartmentCurrency * $affiliateCommission / 100;
                                        $partnerCharge['customer_amount'] = $chargeAmountInCustomerCurrency * $affiliateCommission / 100;
                                        $partnerAllCharges[] = $partnerCharge;
                                    }

                                    // Reverse charges
                                    $removedId = $charge->getId();
                                    $chargeDao->save(['status' => ChargeService::CHARGE_STATUS_DELETED], ['id' => $removedId]);
                                    if (!$chargeDeletedDao->fetchOne(['reservation_charge_id' => $removedId])) {
                                        $reverseParams['reservation_charge_id'] = $removedId;
                                        $chargeDeletedDao->save($reverseParams);
                                    }
                                }

                                foreach ($partnerAllCharges as $pCharge) {
                                    $chargeDao->save($pCharge);
                                }

							} elseif (in_array($ticketModel, Partners::partnerBusinessModel())) {
								// reverse all charges
                                $this->reverseChages($charges, $reverseParams);
							}

							break;
						case BookingService::BOOKING_STATUS_CANCELLED_INVALID:
						case BookingService::BOOKING_STATUS_CANCELLED_EXCEPTION:
                            break;
	                    case BookingService::BOOKING_STATUS_CANCELLED_UNWANTED:
	                    case BookingService::BOOKING_STATUS_CANCELLED_FRAUDULANT:
                            if ($ticketModel == Partners::BUSINESS_MODEL_GINOSI_COLLECT) {
                                $partnerAllCharges = [];
                                foreach ($charges as $charge) {
                                    // Calculate partner commission
                                    if ($charge->getCommission() > 0 && $charge->getMoneyDirection() == ChargeService::CHARGE_MONEY_DIRECTION_GINOSI_COLLECT) {
                                        $chargeDao->update(['commission' => 0], ['id' => $charge->getId()]);
                                    }

                                }
                            } else {
                                // reverse only ginosi collect charges
                                $this->reverseChages($charges, $reverseParams, ChargeService::CHARGE_MONEY_DIRECTION_GINOSI_COLLECT);
                            }

                            break;
					}

					$penaltyChargeParams['commission'] = $penaltyChargeCommission;
					$penaltyChargeParams['money_direction'] = $penaltyChargeModel;

                    if (isset($penaltyChargeParams['acc_amount']) && $penaltyChargeParams['acc_amount'] > 0) {
                        $chargeDao->save($penaltyChargeParams);
                    }
                } else {
                    // Flexible period
                    $partnerAllCharges = [];

                    foreach ($charges as $charge) {
                        if ($cancellationType == BookingService::BOOKING_STATUS_CANCELLED_BY_GINOSI) {
                            // Calculate partner commission
                            if ($charge->getCommission() > 0 || ($affiliateID == $expediaPartnerID && $charge->getAddons_type() == BookingAddon::ADDON_TYPE_TAX_TOT)) {
                                $chargeAmountInApartmentCurrency = $charge->getAcc_amount();
                                $chargeAmountInCustomerCurrency = $charge->getCustomer_amount();
                                $partnerCharge = $params;
                                $partnerCharge['type'] = 'g';
                                $partnerCharge['money_direction'] = ChargeService::CHARGE_MONEY_DIRECTION_GINOSI_COLLECT;
                                $partnerCharge['commission'] = 100;
                                $partnerCharge['acc_amount'] = $chargeAmountInApartmentCurrency * $affiliateCommission / 100;
                                $partnerCharge['customer_amount'] = $chargeAmountInCustomerCurrency * $affiliateCommission / 100;
                                $partnerAllCharges[] = $partnerCharge;
                            }
                        }

                        // Reverse charges
                        $removedId = $charge->getId();
                        $chargeDao->save(['status' => ChargeService::CHARGE_STATUS_DELETED], ['id' => $removedId]);
                        if (!$chargeDeletedDao->fetchOne(['reservation_charge_id' => $removedId])) {
                            $reverseParams['reservation_charge_id'] = $removedId;
                            $chargeDeletedDao->save($reverseParams);
                        }
                    }

                    foreach ($partnerAllCharges as $pCharge) {
                        $chargeDao->save($pCharge);
                    }
                }
			} elseif ($rowBooking->getIsRefundable() == Rate::APARTMENT_RATE_NON_REFUNDABLE) {
                switch ($cancellationType) {
                    case BookingService::BOOKING_STATUS_CANCELLED_BY_CUSTOMER:
                    case BookingService::BOOKING_STATUS_CANCELLED_NOSHOW:
                    case BookingService::BOOKING_STATUS_CANCELLED_TEST_BOOKING:
                    	if ($ticketModel == Partners::BUSINESS_MODEL_GINOSI_COLLECT) {
		                    foreach ($charges as $charge) {
		                    	if ($affiliateID == $expediaPartnerID) {
		                    		// pay commission for apartment
		                    		if ($charge->getAddons_type() != BookingAddon::ADDON_TYPE_ACC) {
                                        $chargeDao->update(['commission' => 0], ['id' => $charge->getId()]);
		                    		}
	                    		} else {
		                    		$chargeDao->update(['commission' => 0], ['id' => $charge->getId()]);
		                    	}
		                    }
                    	} else {
                            // reverse only ginosi collect charges
                            $this->reverseChages($charges, $reverseParams, ChargeService::CHARGE_MONEY_DIRECTION_GINOSI_COLLECT);
                    	}

                        if ($cancellationType == BookingService::BOOKING_STATUS_CANCELLED_TEST_BOOKING) {
                            // reverse all charges
                            $this->reverseChages($charges, $reverseParams);
                        }

                        break;
                    case BookingService::BOOKING_STATUS_CANCELLED_BY_GINOSI:
                    	if ($ticketModel == Partners::BUSINESS_MODEL_GINOSI_COLLECT) {
                            $partnerAllCharges = [];

	                    	foreach ($charges as $charge) {
	                    		// partner commission calculate
	                    		if ($charge->getCommission() > 0 || ($affiliateID == $expediaPartnerID && $charge->getAddons_type() == BookingAddon::ADDON_TYPE_TAX_TOT)) {
                                    //$charge->getAddons_type() == BookingAddon::ADDON_TYPE_PARKING &&

	                    			$chargeAmountInApartmentCurrency = $charge->getAcc_amount();
	                    			$chargeAmountInCustomerCurrency = $charge->getCustomer_amount();
	                    			$partnerCharge = $params;
                                    $partnerCharge['type'] = 'g';
	                    			$partnerCharge['money_direction'] = ChargeService::CHARGE_MONEY_DIRECTION_GINOSI_COLLECT;
	                    			$partnerCharge['commission'] = 100;
	                    			$partnerCharge['acc_amount'] = $chargeAmountInApartmentCurrency * $affiliateCommission / 100;
	                    			$partnerCharge['customer_amount'] = $chargeAmountInCustomerCurrency * $affiliateCommission / 100;
                                    $partnerAllCharges[] = $partnerCharge;
                                }

                                // Reverse charges
                                $removedId = $charge->getId();
                                $chargeDao->save(['status' => ChargeService::CHARGE_STATUS_DELETED], ['id' => $removedId]);
                                if (!$chargeDeletedDao->fetchOne(['reservation_charge_id' => $removedId])) {
                                    $reverseParams['reservation_charge_id'] = $removedId;
                                    $chargeDeletedDao->save($reverseParams);
                                }
	                    	}

                            foreach ($partnerAllCharges as $pCharge) {
                                $chargeDao->save($pCharge);
                            }

                    	} else {
                            // reverse all charges
                    		$this->reverseChages($charges, $reverseParams);
                    	}

                    	break;
                    case BookingService::BOOKING_STATUS_CANCELLED_INVALID:
                    case BookingService::BOOKING_STATUS_CANCELLED_EXCEPTION:
                        break;
                    case BookingService::BOOKING_STATUS_CANCELLED_UNWANTED:
                    case BookingService::BOOKING_STATUS_CANCELLED_FRAUDULANT:
                        if ($ticketModel == Partners::BUSINESS_MODEL_GINOSI_COLLECT) {
                            $partnerAllCharges = [];
                            foreach ($charges as $charge) {
                                // Calculate partner commission
                                if ($charge->getCommission() > 0 && $charge->getMoneyDirection() == ChargeService::CHARGE_MONEY_DIRECTION_GINOSI_COLLECT) {
                                    $chargeDao->update(['commission' => 0], ['id' => $charge->getId()]);
                                }

                            }
                        } else {
                            // reverse only ginosi collect charges
                            $this->reverseChages($charges, $reverseParams, ChargeService::CHARGE_MONEY_DIRECTION_GINOSI_COLLECT);
                        }

                        break;
				}
			}
		} else {
			// do nothing
		}

	    if ($cancellationType == BookingService::BOOKING_STATUS_CANCELLED_PENDING) {
		    $logger->save(Logger::MODULE_BOOKING, $rowBooking->getId(), Logger::ACTION_BOOKING_STATUSES, Logger::VALUE_CANCELED_UNKNOWN);
		    $bookingTicketData = [
			    'cancelation_date' => date('Y-m-d H:i:s'),
			    'status' => $cancellationType,
		    ];
	    } else {
		    // Update balances
		    $bookingTicketService = $this->getServiceLocator()->get('service_booking_booking_ticket');
		    $balances = $bookingTicketService->getSumAndBalanc($rowBooking->getId());
		    $penaltyBit = 0;

		    if ($rowBooking->getIsRefundable() == 2 || ($rowBooking->getIsRefundable() != 2 && $rowBooking->getPenalty_hours() <= $rowBooking->getRefundableBeforeHours())) {
			    $penaltyBit = 1;
		    }

		    $bookingTicketData = [
			    'guest_balance' => $balances['ginosiBalanceInApartmentCurrency'],
			    'partner_balance' => $balances['partnerBalanceInApartmentCurrency'],
			    'status' => $cancellationType,
			    'penalty_bit' => $penaltyBit,
		    ];

            if ($rowBooking->getStatus() != BookingService::BOOKING_STATUS_CANCELLED_PENDING) {
                $bookingTicketData['cancelation_date'] = date('Y-m-d H:i:s');
            }

		    // If CC Status is invalid and both of balances equals 0 then ticket must be marked as settled
//		    if ($cancellationType == BookingService::BOOKING_STATUS_CANCELLED_INVALID) {
//			    if ($balances['ginosiBalanceInApartmentCurrency'] == 0 && $balances['partnerBalanceInApartmentCurrency'] == 0) {
//				    $bookingTicketData['payment_settled'] = 1;
//				    $bookingTicketData['settled_date'] = date('Y-m-d H:i:s');
//
//                    $logger->save(Logger::MODULE_BOOKING, $rowBooking->getId(), Logger::ACTION_RESERVATION_SETTLED);
//			    }
//		    }
	    }

        $bookingDao->save($bookingTicketData, [
	        'res_number' => $res_number,
        ]);
        $taskService = $this->getServiceLocator()->get('service_task');
		//delete auto-created cleaning tasks
		$taskService->deleteTask(
			[
				'res_id'         => $rowBooking->getId(),
				'is_hk'          => TaskService::TASK_IS_HOUSEKEEPING,
				'task_status'    => TaskService::STATUS_NEW
			]
		);

		if ($rowBooking->getArrivalStatus() == ReservationTicketService::BOOKING_ARRIVAL_STATUS_CHECKED_IN) {
			//checked in, but canceled
			//create extra task for cleaning, and key change for tomorrow
			$taskService->createExtraCleaningCancelReservationAfterCheckin($rowBooking->getId(), $rowBooking->getApartmentIdAssigned(), Task::CASE_CANCEL);
		} else {
			//if the previous task is done , or the reservation has started
			    //create extra task for key change on next reservation's checkin date
			$taskService->checkAndCreateExtraTaskForStartedReservationsCancelation($rowBooking->getId(), $rowBooking->getApartmentIdAssigned(), Task::CASE_CANCEL);
		}


        // Delete nightly reservation
        /**
         * @var ReservationNightly $reservationNightlyDao
         */
        $reservationNightlyDao = $this->getServiceLocator()->get('dao_booking_reservation_nightly');
        $reservationNightlyDao->delete(['reservation_id' => $rowBooking->getId()]);

        // Send cancelations email
	    !$sendEmail ?: $this->sendCancelationEmail($rowBooking->getId(), $emailAddress);

	    return true;
	}

    private function reverseChages($charges, $reverseParams, $moneyDirectionType = FALSE)
    {
        try {
            /* @var $chargeDeletedDao \DDD\Dao\Booking\ChargeDeleted */
            $chargeDeletedDao = $this->getServiceLocator()->get('dao_booking_charge_deleted');

            /* @var $chargeDao \DDD\Dao\Booking\Booking */
            $chargeDao = $this->getChargingDao();

            foreach ($charges as $charge) {
                if (!$moneyDirectionType
                    || $charge->getMoneyDirection() == ChargeService::CHARGE_MONEY_DIRECTION_GINOSI_COLLECT
                ) {
                    $chargeDao->save(
                        ['status' => ChargeService::CHARGE_STATUS_DELETED],
                        ['id' => $charge->getId()]
                    );

                    if (!$chargeDeletedDao->fetchOne(['reservation_charge_id' => $charge->getId()])) {
                        $reverseParams['reservation_charge_id'] = $charge->getId();
                        $chargeDeletedDao->save($reverseParams);
                    }
                }
            }

            return TRUE;
        } catch (\Exception $e) {
            $this->gr2logException($e, 'Cannot reverse a charge', $charges);

            return FALSE;
        }
    }

    private function sendCancelationEmail($ticketId, $emailAddress) {
        $shell = 'ginosole reservation-email send-modification-cancel --id=' . $ticketId . ' --ginosi --booker';

        if ($emailAddress) {
            $shell .= ' --email=' . $emailAddress;
        }

		$output = shell_exec($shell);

        if (strstr(strtolower($output), 'error')) {
            return false;
        }

        return true;
	}

	public function getUserName($userId) {
		/** @var UserManager $userService */
		$usermanagerDao = $this->getServiceLocator()->get('dao_user_user_manager');

		$userId = $userId ?: 199; // Automatic Transaction (System User)
		$userDomain = $usermanagerDao->fetchOne(['id' => $userId]);

		return $userDomain->getFirstName() . ' ' . $userDomain->getLastName();
	}

	private function getUserId() {
		$authenticationService = $this->getServiceLocator()->get('library_backoffice_auth');
		$userId = 0;

		if (isset($authenticationService->getIdentity()->id)) {
			$userId = $authenticationService->getIdentity()->id;
		}

		return $userId;
	}

	private function getNewAvailability() {
		return 1;
	}

    /**
     * @param $apartmentId
     * @param $dateRange
     * @param $weekDays
     * @param $availability
     * @return \DDD\Domain\Apartment\Inventory\RateAvailability[] | bool
     */
	public function updateInventoryRangeByAvailability($apartmentId, $dateRange, $weekDays, $availability, $all = false)
    {
        /**
         * @var \DDD\Dao\Apartment\Inventory $inventoryDao$inventoryDao
         * @var \DDD\Service\Availability $availabilityService
         */
        $inventoryDao = $this->getServiceLocator()->get('dao_apartment_inventory');
        $availabilityService = $this->getServiceLocator()->get('service_availability');

		// Define Variables
		$dateRange = Helper::refactorDateRange($dateRange);
		$weekDays = Helper::reformatWeekdays($weekDays);

        $dateFrom = $dateRange['date_from'];
        $dateTo = $dateRange['date_to'];

        // update availability
        $inventoryDao->updateAvailabilityByApartmentId($apartmentId, $availability, $dateFrom, $dateTo, $weekDays, $all);

        // update apartel
        $availabilityService->updateAvailabilityWithQueueForApartelByApartmentDateRange($apartmentId, $dateFrom, $updateDateTo = date('Y-m-d', strtotime('+1 day', strtotime($dateTo))), true);

        // send queue
        $this->setToQueue($apartmentId, $dateFrom, $dateTo, $weekDays, $availability);

        return ['status' => 'success', 'msg' => TextConstants::SUCCESS_UPDATE];
	}

    /**
     * @param $apartmentId
     * @param $date
     * @param $availability
     * @return $this|bool|Inventory
     */
    public function updateAvailabilityFromCalendar($apartmentId, $date, $availability)
    {
        /**
         * @var \DDD\Dao\Apartment\Inventory $inventoryDao
         * @var \DDD\Service\Availability $availabilityService
         */
        $inventoryDao = $this->getServiceLocator()->get('dao_apartment_inventory');
        $availabilityService = $this->getServiceLocator()->get('service_availability');
        $dateFrom = $dateTo = date('Y-m-d', strtotime($date));

        // update availability
        $inventoryDao->updateAvailabilityByApartmentIdAndDate($apartmentId, $date, $availability);

        // update apartel
        $availabilityService->updateAvailabilityWithQueueForApartelByApartmentDateRange($apartmentId, $dateFrom, date('Y-m-d',  strtotime('+1 day', strtotime($dateTo))), true);


        // send queue
        $this->setToQueue($apartmentId, $dateFrom, $dateTo, null, $availability);

        return ['status' => 'success', 'msg' => TextConstants::SUCCESS_UPDATE];
    }

    /**
     * @param $apartmentId
     * @param $dateRange
     * @param $weekDays
     * @param $price
     * @param $priceType
     * @param int $setLockPrice
     * @param int $forceLockPrice
     * @param int $forceUpdatePrice
     * @return $this|array|bool|Inventory
     */
	public function updateInventoryRangeByPrice($apartmentId, $dateRange, $weekDays, $price, $priceType, $setLockPrice = 0, $forceLockPrice = 0, $forceUpdatePrice = 0)
    {
        /**
         * @var \DDD\Dao\Apartment\Inventory $inventoryDao
         */
        $inventoryDao = $this->getServiceLocator()->get('dao_apartment_inventory');

		// Define Variables
		$dateRange = Helper::refactorDateRange($dateRange);
		$weekDays = Helper::reformatWeekdays($weekDays);
        // check price changes
        if (!$forceUpdatePrice) {
            $priceAVGOld = $inventoryDao->getPriceAVGRange($apartmentId, $dateRange['date_from'], $dateRange['date_to'], $weekDays);
            $priceAVGNew = $inventoryDao->getPriceAVGRangeByPriceType($apartmentId, $dateRange['date_from'], $dateRange['date_to'], $weekDays, $price, $priceType);

            if ($priceAVGNew  < $priceAVGOld - $priceAVGOld * self::PRICE_CHANGE_LIMIT/100) {
                return ['status' => 'limit_exceed', 'msg' => TextConstants::PRICE_EXCEED_LIMIT];
            }
        }

        return $this->updatePriceByRange($apartmentId, $price, $dateRange['date_from'], $dateRange['date_to'], $weekDays, $priceType, $setLockPrice, $forceLockPrice);
	}

    /**
     * @param $apartmentId
     * @param $price
     * @param $dateFrom
     * @param $dateTo
     * @param $weekDays
     * @param $priceType
     * @param $setLockPrice
     * @param $forceLockPrice
     * @return $this|bool|Inventory
     */
    public function updatePriceByRange($apartmentId, $price, $dateFrom, $dateTo, $weekDays, $priceType, $setLockPrice, $forceLockPrice)
    {
        // update price
        $this->updatePriceByApartmentId($apartmentId, $price, $dateFrom, $dateTo, $weekDays, $priceType, $setLockPrice, $forceLockPrice);

        // set queue
        return $this->setToQueue($apartmentId, $dateFrom, $dateTo, $weekDays, null);
    }

    /**
     * @param $apartmentId
     * @param $dateFrom
     * @param $dateTo
     * @param $weekDays
     * @param $isAvailability
     * @return $this|bool
     */
    public function setToQueue($apartmentId, $dateFrom, $dateTo, $weekDays, $isAvailability)
    {
        /**
         * @var \DDD\Service\Queue\InventorySynchronizationQueue $queueService
         * @var \DDD\Dao\Apartment\Inventory $inventoryDao
         */
        $queueService = $this->getServiceLocator()->get('service_queue_inventory_synchronization_queue');
        $inventoryDao = $this->getServiceLocator()->get('dao_apartment_inventory');

        $auth = $this->getServiceLocator()->get('library_backoffice_auth');
        $userId = $auth->getIdentity()->id;

        // send data to Graylog
        $logArray = [
            'product_type'               => 'Apartment',
            'module'                     => 'Apartment',
            'controller'                 => 'Inventory',
            'apartment_id'               => $apartmentId,
            'date_from'                  => $dateFrom,
            'date_to'                    => $dateTo,
            'action_mode'                => 'days range',
            'user_id'                    => $userId
        ];

        if (!is_null($isAvailability)) {
            $logArray['action_type'] = 'availability';
            $logArray['availability_update_action'] = ($isAvailability) ? 'open' : 'close';
            $this->gr2info('Availability Update', $logArray);
        } else {
            $logArray['action_type'] = 'price';
            $parentInventoryData = $inventoryDao->getRateInventoryData($apartmentId, $dateFrom, $dateTo, $weekDays, null);
            foreach ($parentInventoryData as $parentRate) {
                $logArray['price_value'] = $parentRate['price'];
                $logArray['date_from'] = $parentRate['date'];
                $logArray['date_to'] = $parentRate['date'];
                $this->gr2info('Availability Update', $logArray);
            }
        }

        // set to queue
        $weekDayArray = [];
        if ($weekDays) {
            $weekDayArray = explode(',', $weekDays);
        }
        $queueService->push($apartmentId, $dateFrom, $dateTo, $weekDayArray);

        return ['status' => 'success', 'msg' => TextConstants::SUCCESS_UPDATE];
    }

    /**
     * @param $apartmentId
     * @param $amount
     * @param $dateFrom
     * @param $dateTo
     * @param $weekDays
     * @param $priceType
     * @param int $setLockPrice
     * @param $forceLockPrice
     * @throws \Exception
     */
    public function  updatePriceByApartmentId($apartmentId, $amount, $dateFrom, $dateTo, $weekDays, $priceType, $setLockPrice = 0, $forceLockPrice)
    {
        /**
         * @var \DDD\Dao\Apartment\Rate $rateDao
         * @var \DDD\Dao\Apartment\Inventory $inventoryDao
         */
        $rateDao = $this->getServiceLocator()->get('dao_apartment_rate');
        $inventoryDao = $this->getServiceLocator()->get('dao_apartment_inventory');

        // get parent rate
        $parentRateId = $rateDao->getApartmentParentRate($apartmentId);

        // update parent rate price by range
        $inventoryDao->updateParentRatePriceByRang($amount, $priceType, $parentRateId['id'], $dateFrom, $dateTo, $weekDays, $forceLockPrice);

        // get rates without parent rate
        $rates = $rateDao->getApartmentRatesWithoutParent($apartmentId);
        $rates = iterator_to_array($rates);

        // get parent inventory date by range
        $parentInventoryData = $inventoryDao->getRateInventoryData($apartmentId, $dateFrom, $dateTo, $weekDays, $forceLockPrice);

        // for check rate date already updated or not
        $checkRateIdDateCombination = [];

        foreach ($parentInventoryData as $parentRate) {
            // get date name
            $percentField = Helper::getDateWeekType($parentRate['date']);
            $parentRatePrice = $parentRate['price'];

            // update all rate without parent
            foreach ($rates as $rate) {
                if (isset($checkRateIdDateCombination[$parentRate['date']]) && in_array($rate['id'], $checkRateIdDateCombination[$parentRate['date']])) {
                    continue;
                }

                $checkRateIdDateCombination[$parentRate['date']][] = $rate['id'];
                $childPrice = round($parentRatePrice + $parentRatePrice * $rate[$percentField] / 100, 2);
                $inventoryDao->update([
                    'price' => $childPrice
                ], [
                    'rate_id' => $rate['id'],
                    'date' => $parentRate['date'],
                ]);
            }
        }

        // set lock price bit
        $inventoryDao->updateLockPriceBit($apartmentId, $dateFrom, $dateTo, $weekDays, $setLockPrice, $forceLockPrice);
    }

	public function applyPriceStrategy($value, $price = 0, $action = 0)
    {
		if (!in_array($action, self::$changePriceActionList)) {
			throw new \Exception('Undefined action.');
		}

		$newPrice = 0;

		switch ($action) {
			case 0:
				// Amount
				$newPrice = $value;
				break;
			case 1:
				// Percent Less
				$newPrice = $price + $price / 100 * $value;
				break;
			case 2:
				// Percent More
				$newPrice = $price - $price / 100 * $value;
				break;
			case 3:
				// Amount Less
				$newPrice = $price + $value;
				break;
			case 4:
				// Amount More
				$newPrice = $price - $value;
				break;
		}

		return $newPrice;
	}

	/**
	 * @param bool $yearly
	 * @param int $apartmentId
	 * @throws \Exception
	 * @return bool
	 */
	public function updateAvailability($yearly = false, $apartmentId = null)
    {
        // @todo refactor
        /** @var \DDD\Dao\Apartment\Rate  $productRateDao */
        $productRateDao = $this->getServiceLocator()->get('dao_apartment_rate');

		try {
			$productRateDomainList = $productRateDao->getAllActiveRatesByApartmentId($apartmentId);

			if ($productRateDomainList->count()) {
				$firstDay = date('Y-m-d', strtotime('first day of this month'));
				$nextYear = date('Y-m-d', strtotime($firstDay . " +12 months"));

				if ($yearly) {
					$dateFrom = $firstDay;
					$dateTo = date('Y-m-d', strtotime($firstDay . " +13 months"));
				} else {
					$dateFrom = date("Y-m-d", strtotime("-1 month", strtotime(date($nextYear))));
					$dateTo = date("Y-m-d", strtotime("+1 month", strtotime(date($nextYear))));
				}

				foreach ($productRateDomainList as $productRateDomain) {
					$this->insertBundle($productRateDomain, $dateFrom, $dateTo, $apartmentId);

					// Delete past availabilities monthly, only when script called by cron
					if (!$yearly) {
						$this->deletePastAvailabilities($productRateDomain->getId());
					}
				}
			} else {
				throw new \Exception('No rates found to update.');
			}
		} catch (\Exception $ex) {
			if (!$yearly) {
                $this->gr2crit('Monthly availability update for Apartment is failed');
			}

			return false;
		}

		return true;
	}

	public function repairAvailability($dateFrom, $dateTo, $rateId)
    {
		if (is_null($dateFrom) || is_null($dateTo)) {
			throw new \Exception('Parameters --date-from and --date-to is required.');
		}

        /** @var \DDD\Dao\Apartment\Rate  $productRateDao */
        $productRateDao = $this->getServiceLocator()->get('dao_apartment_rate');
		$productRateDomainList = $productRateDao->getAllActiveRatesByRateId($rateId);

		if ($productRateDomainList->count()) {
			foreach ($productRateDomainList as $productRateDomain) {
				$this->insertBundle($productRateDomain, $dateFrom, date('Y-m-d', strtotime('+1 day', strtotime($dateTo))), false);
			}

			return true;
		} else {
			throw new \Exception('Nothing compares.');
		}
	}

	/**
	 * @param int $rateId
	 * @return void
	 */
	private function deletePastAvailabilities($rateId)
    {
		$dateTo = date('Y-m-d', strtotime('first day of this month -1 month'));

		$inventoryDao = $this->getApartmentInventoryDao();
		$inventoryDao->deleteAvailabilities($rateId, $dateTo);
	}

	/**
	 * @param WithStatus $productRateDomain
	 * @param string $dateFrom
	 * @param string $dateTo
	 * @param bool $isFromRateManagement
	 * @throws \Exception
	 * @return void
	 */
	private function insertBundle($productRateDomain, $dateFrom, $dateTo, $isFromRateManagement)
    {
		$inventoryDao = $this->getApartmentInventoryDao();
		$dateSeeker = new \DateTime($dateFrom);
		$masterRates = [];
		$master = false;
        $isChanged = $isLockPrice = 0;

		if ($productRateDomain->getType() == Rate::TYPE1) {
			$master = true;
		} else {
            /** @var \DDD\Dao\Apartment\Rate  $productRateDao */
            $productRateDao = $this->getServiceLocator()->get('dao_apartment_rate');
            $productRateDomainTmp = $productRateDao->getMasterRateByApartmentId($productRateDomain->getApartmentId());
			if ($productRateDomainTmp) {
				$masterRates = $this->getRateAvailabilityByDate($productRateDomainTmp['id'], $dateFrom, $dateTo);
			}
		}

        $apartmentGeneralService = $this->getServiceLocator()->get('service_apartment_general');
		while ($dateSeeker->format('Y-m-d') < $dateTo) {
			$goodLookingDay = $dateSeeker->format('Y-m-d');
			$weekDayName = $dateSeeker->format('D');
			$dateSeeker->modify('+1 day');

			// If Availability with rate id and date is found, then skip that day
			if ($inventoryDao->getByRateIdAndDate($productRateDomain->getId(), $goodLookingDay)) {
				continue;
			}

            $openNextMonthAvailability = $apartmentGeneralService->getOpenNextMonthAvailability($productRateDomain->getApartmentId());
            if ($openNextMonthAvailability) {
                // Choose Best Availability
                $availability = $productRateDomain->getDefaultAvailability();
            } else {
                $availability = 0;
            }


			if (!$master) {
				if (isset($masterRates[$goodLookingDay]['availability'])) {
					$availability = $masterRates[$goodLookingDay]['availability'];
                    $isChanged = $masterRates[$goodLookingDay]['isChanged'];
                    $isLockPrice = $masterRates[$goodLookingDay]['isLockPrice'];
				}
			}

			if (!$inventoryDao->save([
				'apartment_id' => $productRateDomain->getApartmentId(),
				'room_id' => $productRateDomain->getRoomId(),
				'rate_id' => $productRateDomain->getId(),
				'date' => $goodLookingDay,
				'availability' => $availability,
				'price' => $this->getAppropriatePrice($productRateDomain, $weekDayName),
				'is_changed' => $isChanged,
				'is_lock_price' => $isLockPrice,
			])) {
				throw new \Exception('Something went wrong with availability creation.');
			}

            if (!$isFromRateManagement && !is_null($productRateDomain->getCubilisId())) {
                /**  @var \DDD\Service\Queue\InventorySynchronizationQueue $syncService */
                $syncService = $this->getServiceLocator()->get('service_queue_inventory_synchronization_queue');
                $syncService->push($productRateDomain->getApartmentId(), $goodLookingDay, $goodLookingDay);
            }
		}
	}

	private function getRateAvailabilityByDate($masterRateId, $dateFrom, $dateTo)
    {
        $inventoryDao = new \DDD\Dao\Apartment\Inventory($this->getServiceLocator());
        $inventoryDomainList = $inventoryDao->getAvailabilityByRateIdAndDateRange($masterRateId, $dateFrom, $dateTo);
        $output = [];

        if ($inventoryDomainList->count()) {
            foreach ($inventoryDomainList as $inventoryDomain) {
                $output[$inventoryDomain->getDate()] = [
                    'availability' => $inventoryDomain->getAvailability(),
                    'isChanged' => $inventoryDomain->getIsChanged(),
                    'isLockPrice' => $inventoryDomain->getIsLockPrice(),
                ];
            }
        }
        return $output;
    }

	/**
	 * @param WithStatus $productRateDomain
	 * @param string $weekDayName
	 * @return float
	 */
	private function getAppropriatePrice($productRateDomain, $weekDayName)
    {
		if (in_array($weekDayName, self::$weekEndDays)) {
			return $productRateDomain->getWeekendPrice();
		}

		return $productRateDomain->getWeekPrice();
	}

    /**
     * @param $apartmentId
     * @param $date
     * @param bool $bookingStatus
     * @return \ArrayObject|bool
     */
    public function checkApartmentAvailabilityByDate($apartmentId, $date, $bookingStatus = false)
    {
        /* @var $ReservationNightlyDao \DDD\Dao\Booking\ReservationNightly */
        $ReservationNightlyDao = $this->getServiceLocator()->get('dao_booking_reservation_nightly');

        $reservationOnDate = $ReservationNightlyDao->getNightDataByDateAndApartmentId($apartmentId, $date);

        if ($reservationOnDate && $bookingStatus) {
            /* @var $bookingTicketService \DDD\Service\Booking\BookingTicket */
            $bookingTicketService = $this->getServiceLocator()->get('service_booking_booking_ticket');

            $bookingTicketData = $bookingTicketService->getBookingThicketByReservationId($reservationOnDate['reservation_id']);

            if ($bookingStatus != $bookingTicketData->getStatus()) {
                return FALSE;
            }
        }

        return $reservationOnDate;
    }

	/**
     * @access public
     * @param string $domain
     * @return \DDD\Dao\Apartment\Inventory
     */
    private function getApartmentInventoryDao($domain = '\DDD\Domain\Apartment\Inventory\RateAvailabilityCancel') {
		return new InventoryDao($this->getServiceLocator(), $domain);
	}

    private function getChargingDao($domain = 'DDD\Domain\Booking\ForCancelCharge') {
        return new Charge($this->getServiceLocator(), $domain);
    }

    private function getRateDao($domain = 'DDD\Domain\Apartment\Rate\ForPenalty') {
        return new ApartmentRate($this->getServiceLocator(), $domain);
    }

    private function validateDate($date)
    {
        if (!(list($year, $month, $day) = explode('-', $date))) {
            return false;
        } else {
            if (isset($year) && is_numeric($year) && is_numeric($month) && is_numeric($day)) {
                return checkdate($month, $day, $year);
            }
        }

        return false;
    }
}
