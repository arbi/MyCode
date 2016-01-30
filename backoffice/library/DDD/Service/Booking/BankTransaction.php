<?php

namespace DDD\Service\Booking;

use CreditCard\Service\Card as CardService;
use DDD\Dao\Finance\Transaction\Transactions;
use DDD\Domain\Booking\ChargeTransaction;
use DDD\Domain\Booking\ForCharge;
use DDD\Service\Partners;
use DDD\Service\Psp;
use DDD\Service\ServiceBase;
use Library\ActionLogger\Logger;
use Library\Constants\Objects;
use Library\Constants\TextConstants;
use Library\Finance\Base\Account;
use Library\Finance\Base\TransactionBase;
use Library\Finance\CreditCard\CreditCard;
use Library\Finance\Transaction\Transactor\Reservation;
use Library\Utility\Helper;
use DDD\Domain\Booking\TransactionSummary;

/**
 * Service class providing methods to work with bank transactions
 * @author Tigran Petrosyan
 */
final class BankTransaction extends ServiceBase
{
	const BANK_TRANSACTION_STATUS_APPROVED = 1;
	const BANK_TRANSACTION_STATUS_DECLINED = 2;
	const BANK_TRANSACTION_STATUS_PENDING = 3;
	const BANK_TRANSACTION_STATUS_VOIDED = 4;

	const BANK_TRANSACTION_TYPE_COLLECT = 1;
	const BANK_TRANSACTION_TYPE_REFUND = 2;
	const BANK_TRANSACTION_TYPE_CASH = 3;
	const BANK_TRANSACTION_TYPE_CASH_REFUND = 8;
	const BANK_TRANSACTION_TYPE_CHARGEBACK_FRAUD = 4;
	const BANK_TRANSACTION_TYPE_CHARGEBACK_DISPUTE = 5;
	const BANK_TRANSACTION_TYPE_CHARGEBACK_OTHER = 6;
	const BANK_TRANSACTION_TYPE_VALIDATION = 7;
	const BANK_TRANSACTION_TYPE_PAY = 9;
	const BANK_TRANSACTION_TYPE_BANK_DEPOSIT = 13;
	const BANK_TRANSACTION_TYPE_DEDUCTED_SALARY = 14;

	const TRANSACTION_MONEY_DIRECTION_GINOSI_COLLECT = 2;
	const TRANSACTION_MONEY_DIRECTION_PARTNER_COLLECT = 3;
    const FRONTIER_TRANSACTION_REVIEWED = 1;

    static $transactionStatus = [
        self::BANK_TRANSACTION_STATUS_APPROVED => 'Approve',
        self::BANK_TRANSACTION_STATUS_DECLINED => 'Decline',
        self::BANK_TRANSACTION_STATUS_PENDING => 'Pending',
        self::BANK_TRANSACTION_STATUS_VOIDED => 'Void',
    ];

    static $transactionMoneyDirection = [
        self::TRANSACTION_MONEY_DIRECTION_GINOSI_COLLECT => 'Ginosi',
        self::TRANSACTION_MONEY_DIRECTION_PARTNER_COLLECT => 'Partner',
    ];

    public static $converseBankData = [
        'testimony' => [1, 2, 13, 14],
        'percent' => 2.7,
        'days' => 2,
    ];

    /**
     * @param array $data
     * @param int $moneyDirection
     * @param bool $isFrontier
     *
     * @return bool|string
     */
    public function saveTransaction($data, $moneyDirection = self::TRANSACTION_MONEY_DIRECTION_GINOSI_COLLECT, $isFrontier = false)
    {
        /**
         * @var \DDD\Dao\Booking\ChargeTransaction $bankTransactionDao
         * @var \Library\Authentication\BackofficeAuthenticationService $authenticationService
         * @var \DDD\Service\Booking\BookingTicket $bookingTicketService
         * @var \DDD\Domain\Booking\ForCharge $rowBooking
         * @var ForCharge $bookingDao
         * @var \DDD\Service\Fraud $serviceFraud
         * @var \DDD\Dao\Psp\Psp $pspDao
         * @var Logger $logger
         */
        $transactionDate = date('Y-m-d H:i:s');
        $bankTransactionDao = $this->getServiceLocator()->get('dao_booking_change_transaction');
        $pspDao = $this->getServiceLocator()->get('dao_psp_psp');
        $transactionType = (int)$data['transaction_type'];
        $transactionStatus = (isset($data['transaction_status']) ? (int)$data['transaction_status'] : 0);
        $errorRespond = [
            'status' => 'error',
            'msg' => TextConstants::ERROR_CHARGED,
        ];
        $error = '';

        $transactionTypesWhichRequireCreditCard = [
            self::BANK_TRANSACTION_TYPE_COLLECT,
            self::BANK_TRANSACTION_TYPE_REFUND,
            self::BANK_TRANSACTION_TYPE_VALIDATION
        ];


        if (in_array($transactionType, $transactionTypesWhichRequireCreditCard) && (!isset($data['cardId']) || !(int)$data['cardId'])) {
            return [
                'status' => 'error',
                'msg' => TextConstants::ERROR_NO_CARD,
            ];
        }

        $creditCardId = intval($data['cardId']);

        /**
         * @var \DDD\Dao\Booking\Booking $bookingDao
         */
        $bookingDao = $this->getServiceLocator()->get('dao_booking_booking');
        $bookingDao->setEntity(new \DDD\Domain\Booking\ForCharge());

		$rowBooking = $bookingDao->fetchOne(['res_number' => $data['res_number']], ['id', 'partner_id', 'customer_id']);

		if (!$rowBooking) {
            return $errorRespond;
		}

		try {
            if (!$isFrontier) {
                $bankTransactionDao->beginTransaction();
            }

			$authenticationService = $this->getServiceLocator()->get('library_backoffice_auth');
			$bookingTicketService = $this->getServiceLocator()->get('service_booking_booking_ticket');
			$loggedInUserID = $authenticationService->getIdentity()->id;

            $transactionApartmentAmount = $data['transaction_acc_amount'];
			$accAmount = number_format(doubleval($transactionApartmentAmount), 2, '.', '');

			if ($transactionType == self::BANK_TRANSACTION_TYPE_DEDUCTED_SALARY) {
                $bankAmount = 0;
                $bankRate = 0;
                $moneyAccountCurrency = $data['acc_currency'];
                $cashUser = (int)$data['userCache_id'];
			} else {
                $moneyAccountCurrency = $data['transaction_money_account_currency'];
                $bankRate = $data['transaction_money_account_currency_rate'];
				$bankAmount = number_format($data['transaction_charge_amount'], 2, '.', '');
                $cashUser = 0;
			}

            // Calculate exact bank amount
            $bankAmount = $this->applyPercentDeductionByPSP($bankAmount, $data['transaction_psp']);

            if ($transactionType == self::BANK_TRANSACTION_TYPE_DEDUCTED_SALARY) {
                $moneyAccountId = 0;
            } elseif (in_array($transactionType, [self::BANK_TRANSACTION_TYPE_CASH, self::BANK_TRANSACTION_TYPE_CASH_REFUND])) {
                $moneyAccountId = (int)$data['personal_account_id'];
            } elseif (in_array($transactionType, [
                self::BANK_TRANSACTION_TYPE_CHARGEBACK_DISPUTE,
                self::BANK_TRANSACTION_TYPE_CHARGEBACK_FRAUD,
                self::BANK_TRANSACTION_TYPE_CHARGEBACK_OTHER,
            ])) {
                $moneyAccountId = (int)$data['transaction_chargeback_bank'];
            } elseif ($transactionType == self::BANK_TRANSACTION_TYPE_BANK_DEPOSIT) {
                $moneyAccountId = (int)$data['money_account_deposit_id'];
                $moneyDirection = (int)$data['money_direction_received'];
            } else {
                $moneyAccountId = (int)$data['transaction_money_account_id'];
            }

            if ($transactionType == self::BANK_TRANSACTION_TYPE_BANK_DEPOSIT) {
                $moneyDirection = self::TRANSACTION_MONEY_DIRECTION_GINOSI_COLLECT;
            }

            // All Refunds and Chargebacks
			if (in_array($transactionType, [
                self::BANK_TRANSACTION_TYPE_REFUND,
                self::BANK_TRANSACTION_TYPE_CHARGEBACK_FRAUD,
                self::BANK_TRANSACTION_TYPE_CHARGEBACK_DISPUTE,
                self::BANK_TRANSACTION_TYPE_CHARGEBACK_OTHER,
                self::BANK_TRANSACTION_TYPE_CASH_REFUND,
            ])) {
				if ($transactionType != self::BANK_TRANSACTION_TYPE_CASH_REFUND) {
					$bankAmount = -$bankAmount;
                }

				$accAmount = - $accAmount;
			}

			$params = [
                'reservation_id'         => $data['reservation_id'],
				'money_account_id' 	     => $moneyAccountId,
				'date' 			         => $transactionDate,
				'user_id' 		         => $loggedInUserID,
				'bank_amount' 	         => $bankAmount,
				'money_account_currency' => $moneyAccountCurrency,
				'acc_amount' 	         => $accAmount,
				'bank_rate' 	         => $bankRate,
				'comment' 		         => Helper::setLog('commentWithoutData', Helper::stripTages($data['transaction_charge_comment'])),
				'type' 			         => $transactionType,
				'cache_user' 	         => $cashUser,
				'apartment_id' 		     => (int)$data['accId'],
				'money_direction'        => $moneyDirection,
			];

            if (in_array($transactionType, [
                self::BANK_TRANSACTION_TYPE_CASH,
                self::BANK_TRANSACTION_TYPE_CASH_REFUND,
                self::BANK_TRANSACTION_TYPE_BANK_DEPOSIT,
                self::BANK_TRANSACTION_TYPE_DEDUCTED_SALARY,
            ])) {
                $params['cc_id'] = 0;
            } else {
                $params['cc_id'] = $creditCardId;
            }

                if ($transactionType == self::BANK_TRANSACTION_TYPE_COLLECT) {
                    $cardStatus = $transactionStatus + 1;

                    /**
                     * @var CardService $cardService
                     */
                    $cardService = $this->getServiceLocator()->get('service_card');

                    $cardPartnerBusinessModel = $cardService->getCardPartnerBusinessModel($creditCardId);

                    // check card partner business model
                    if ($cardPartnerBusinessModel && $cardPartnerBusinessModel == Partners::BUSINESS_MODEL_GINOSI_COLLECT_PARTNER) {
                        $params['money_direction'] = self::TRANSACTION_MONEY_DIRECTION_PARTNER_COLLECT;

                        if ($transactionStatus == self::BANK_TRANSACTION_STATUS_APPROVED) {
                            $cardStatus = CardService::CC_STATUS_DO_NOT_USE;
                        }
                    }

                    // update card status
                    $cardService->changeCardStatus($creditCardId, $cardStatus);
                }

                // Define Transaction Status
                if ($transactionType == self::BANK_TRANSACTION_TYPE_CASH || $transactionType == self::BANK_TRANSACTION_TYPE_CASH_REFUND) {
                    $params['status'] = self::BANK_TRANSACTION_STATUS_PENDING;
                } elseif ($transactionStatus) {
                    $params['status'] = $transactionStatus;
                } else {
                    $params['status'] = self::BANK_TRANSACTION_STATUS_PENDING;
                }

                if (in_array($transactionType, [
                    self::BANK_TRANSACTION_TYPE_COLLECT,
                    self::BANK_TRANSACTION_TYPE_REFUND,
                    self::BANK_TRANSACTION_TYPE_VALIDATION,
                    self::BANK_TRANSACTION_TYPE_BANK_DEPOSIT,
                ])) {
                    $params['psp_id'] = $data['transaction_psp'];

                    if ($transactionStatus == self::BANK_TRANSACTION_STATUS_DECLINED) {
                        if (isset($data['transaction_error_code']) && $data['transaction_error_code']) {
                            $params['error_code'] = $data['transaction_error_code'];
                        }
                    }

                    if ($transactionStatus == self::BANK_TRANSACTION_STATUS_APPROVED) {
                        if (isset($data['transaction_auth_code']) && $data['transaction_auth_code']) {
                            $params['auth_code'] = $data['transaction_auth_code'];
                        }

                        if (isset($data['transaction_rrn']) && $data['transaction_rrn']) {
                            $params['rrn'] = $data['transaction_rrn'];
                        }
                    }
                }

                // Frontier charge
                if ($isFrontier) {
                    $params['status'] = self::BANK_TRANSACTION_STATUS_PENDING;
                    $params['reviewed'] = self::FRONTIER_TRANSACTION_REVIEWED;
                }
//            }

			// save transaction
			$bankTransactionDao->save($params);

            $transactorId = $bankTransactionDao->lastInsertValue;

			// after transaction save, we must recalculate balance and update it in reservations table
			$balances = $bookingTicketService->getSumAndBalanc($rowBooking->getId());
			$updateReservationData = [
				'guest_balance' => number_format($balances['ginosiBalanceInApartmentCurrency'], 2, '.', ''),
				'partner_balance' => number_format($balances['partnerBalanceInApartmentCurrency'], 2, '.', '')
			];

			if (in_array($transactionType, [
                    self::BANK_TRANSACTION_TYPE_COLLECT,
                    self::BANK_TRANSACTION_TYPE_REFUND,
                    self::BANK_TRANSACTION_TYPE_VALIDATION
                ]) && $transactionStatus == self::BANK_TRANSACTION_STATUS_APPROVED) {
				$updateReservationData['funds_confirmed'] = BookingTicket::CC_STATUS_VALID;
            }

			$bookingDao->save($updateReservationData, ['res_number' => $data['res_number']]);

			// in case when transaction is chargeback and it's reason of fraud, we must update data in black list table
			if ($transactionType == self::BANK_TRANSACTION_TYPE_CHARGEBACK_FRAUD && $transactionStatus == self::BANK_TRANSACTION_STATUS_APPROVED) {
                $serviceFraud = $this->getServiceLocator()->get('service_fraud');
                $fraud = $serviceFraud->saveFraudManual($rowBooking->getId());

                if (isset($fraud['status']) && $fraud['status'] != 'success' && isset($fraud['msg'])) {
                    $error .= $fraud['msg'];
                }
			}

            // Make money transaction
            $transactionTypesToSkip = [
                self::BANK_TRANSACTION_TYPE_DEDUCTED_SALARY,
                self::BANK_TRANSACTION_TYPE_VALIDATION,
            ];

            $pspDao->setEntity(new \ArrayObject());
            $isBatch = $pspDao->fetchOne(['id' => $data['transaction_psp']], ['batch']);
            $isBatch = $isBatch ? $isBatch['batch'] : false;

            // For some cases money transaction is not acceptable
            if (!in_array($transactionType, $transactionTypesToSkip) && $params['status'] != self::BANK_TRANSACTION_STATUS_DECLINED && !$isBatch) {
                if ($transactionStatus != self::BANK_TRANSACTION_STATUS_DECLINED) {
                    $transactionDate = $this->changeDateForSomePSP($transactionDate, $data['transaction_psp']);
                }

                $reservationTransaction = new Reservation(TransactionBase::ACCOUNT_CUSTOMER, TransactionBase::ACCOUNT_MONEY_ACCOUNT);
                $reservationTransaction->setServiceLocator($this->getServiceLocator());
                $reservationTransaction->setAccountIdentity($rowBooking->getCustomerId(), $moneyAccountId);
                $reservationTransaction->setTransactorId($transactorId);
                $reservationTransaction->setTransactionDate($transactionDate);
                $reservationTransaction->setDescription("Reservation Transaction #{$data['res_number']}.");
                $reservationTransaction->setAmount($bankAmount);
                $reservationTransaction->setStatus($params['status']);
                $reservationTransaction->prepare();

                if (!$reservationTransaction->process()) {
                    throw new \Exception('Cannot process money transaction.');
                }
            }

            if (!$isFrontier) {
                $bankTransactionDao->commitTransaction();
            }

            if ($error) {
                $status = 'warning';
                $msg = TextConstants::SUCCESS_TRANSACTED . $error;
            } else {
                $status = 'success';
                $msg = TextConstants::SUCCESS_TRANSACTED;
            }

            return [
                'status' => $status,
                'msg' => $msg,
            ];
		} catch (\Exception $ex) {
            if (!$isFrontier) {
                $bankTransactionDao->rollbackTransaction();
            }

            return $errorRespond;
		}
	}

	/**
	 * Get transactions summary to calculate balances
     *
	 * @param int $reservationId
	 * @param number $moneyDirection
     * @param array $excludeTransactionTypes
     *
	 * @return TransactionSummary
	 */
	public function getTransactionsSummary($reservationId, $moneyDirection, $excludeTransactionTypes = [])
    {
		/**
         * @var \DDD\Dao\Booking\ChargeTransaction $bankTransactionDao
         */
		$bankTransactionDao = $this->getServiceLocator()->get('dao_booking_change_transaction');
		$transactionsSummary = $bankTransactionDao->calculateTransactionsSummary($reservationId, $moneyDirection, $excludeTransactionTypes);

		return $transactionsSummary;
	}

    /**
     * @param int $transactionId
     * @param int $transactionStatus
     * @param int $transactionType
     * @return array
     */
    public function changeTransactionState($transactionId, $transactionStatus, $transactionType)
    {
        /**
         * @var \DDD\Dao\Booking\ChargeTransaction $bankTransactionDao
         * @var \Library\Authentication\BackofficeAuthenticationService $authenticationService
         * @var \DDD\Service\Booking\BookingTicket $bookingTicketService
         * @var ChargeTransaction $transactionData
         * @var Transactions $moneyTransactionDao
         */
        $authService = $this->getServiceLocator()->get('library_backoffice_auth');
        $bookingTicketService = $this->getServiceLocator()->get('service_booking_booking_ticket');
        $bankTransactionDao = $this->getServiceLocator()->get('dao_booking_change_transaction');
        $moneyTransactionDao = $this->getServiceLocator()->get('dao_finance_transaction_transactions');
        $bookingDao = $this->getServiceLocator()->get('dao_booking_booking');

        $timestamp = date('Y-m-d H:i:s');

        try {
            $bankTransactionDao->beginTransaction();

            if (!($transactionId > 0 && $transactionStatus > 0)) {
                throw new \RuntimeException(TextConstants::ERROR_BAD_REQUEST);
            }

            $auth = $authService->getIdentity();
            $logger = $auth->firstname . ' ' . $auth->lastname;

            $transactionData = $bankTransactionDao->fetchOne(['id' => $transactionId], ['reservation_id', 'money_transaction_id', 'status', 'comment', 'type']);
            if (!$transactionData) {
                throw new \RuntimeException(TextConstants::ERROR_BAD_REQUEST);
            }

            $comment = sprintf(TextConstants::COMMENT_TRANSACTION_ONLY_STATUS, self::$transactionStatus[$transactionData->getStatus()], self::$transactionStatus[$transactionStatus], $timestamp, $logger);

            $changeData = ['status' => $transactionStatus, 'reviewed' => 0];
            $returnStatus = 'success';
            $returnMsg = TextConstants::SUCCESS_UPDATE;

            if ($transactionType > 0 && in_array($transactionType, [self::BANK_TRANSACTION_TYPE_CHARGEBACK_FRAUD, self::BANK_TRANSACTION_TYPE_CHARGEBACK_OTHER, self::BANK_TRANSACTION_TYPE_CHARGEBACK_DISPUTE,])) {
                $changeData['type'] = $transactionType;

                // in case when transaction is chargeback and it's reason of fraud, we must update data in black list table
                if ($transactionType == self::BANK_TRANSACTION_TYPE_CHARGEBACK_FRAUD && $transactionStatus == self::BANK_TRANSACTION_STATUS_APPROVED) {
                    $serviceFraud = $this->getServiceLocator()->get('service_fraud');
                    $fraud = $serviceFraud->saveFraudManual($transactionData->getReservationId());

                    if (isset($fraud['status']) && $fraud['status'] != 'success' && isset($fraud['msg'])) {
                        $returnStatus = 'warning';
                        $returnMsg .= "<br>{$fraud['msg']}";
                    }
                }

                $comment = sprintf(
                    TextConstants::COMMENT_TRANSACTION_STATUS_TYPE,
                    Objects::getChargeType()[$transactionData->getType()],
                    self::$transactionStatus[$transactionData->getStatus()],
                    Objects::getChargeType()[$transactionType],
                    self::$transactionStatus[$transactionStatus],
                    $timestamp,
                    $logger
                );
            }

            if ($transactionData->getComment()) {
                $comment = "{$transactionData->getComment()}<br>{$comment}";
            }

            // Money Transaction status change
            if ($transactionStatus == self::BANK_TRANSACTION_STATUS_VOIDED) {
                $siblingCount = $moneyTransactionDao->getSiblingTransactionsCount($transactionData->getMoneyTransactionId());

                // If One to One connected reservation transaction
                if ($siblingCount == 1) {
                    // Disconnect from money transaction
                    $changeData['money_transaction_id'] = null;
                    $returnMsg = 'Reservation Transaction voided and all related Money Transactions have also been removed.';

                    // Remove money transaction
                    $moneyTransactionDao->delete(['id' => $transactionData->getMoneyTransactionId()]);
                } else {
                    $returnMsg = 'Reservation Transaction has been voided, however the Money Transaction has NOT been removed.';
                }
            }

            $changeData['comment'] = $comment;
            $bankTransactionDao->save($changeData, ['id' => $transactionId]);

            // after transaction save, we must recalculate balance and update it in reservations table
            $balances = $bookingTicketService->getSumAndBalanc($transactionData->getReservationId());
            $updateReservationData = ['guest_balance' => number_format($balances['ginosiBalanceInApartmentCurrency'], 2, '.', ''), 'partner_balance' => number_format($balances['partnerBalanceInApartmentCurrency'], 2, '.', '')];

            $bookingDao->save($updateReservationData, ['id' => $transactionData->getReservationId()]);
            $bankTransactionDao->commitTransaction();

            $result = [
                'status' => $returnStatus,
                'msg' => $returnMsg,
            ];
        } catch (\RuntimeException $ex) {
            $bankTransactionDao->rollbackTransaction();
            $result = [
                'status' => 'error',
                'msg' => $ex->getMessage(),
            ];
        } catch (\Exception $ex) {
            $bankTransactionDao->rollbackTransaction();
            $result = [
                'status' => 'error',
                'msg' => TextConstants::SERVER_ERROR,
            ];
        }

        return $result;
    }

    /**
     * @param int $pspId
     * @param string $dateRange
     * @return array
     */
    public function getCollectionReadyVirtualReservations($pspId, $dateRange)
    {
        /**
         * @var \DDD\Dao\Booking\ChargeTransaction $bankTransactionDao
         */
        $bankTransactionDao = $this->getServiceLocator()->get('dao_booking_change_transaction');
        $transactionList = [];

        list($dateFrom, $dateTo) = explode(' - ', $dateRange);

        $transactions = $bankTransactionDao->getCollectionReadyVirtualReservations($pspId, $dateFrom, $dateTo);

        if ($transactions->count()) {
            foreach ($transactions as $transaction) {
                array_push($transactionList, $transaction);
            }
        }

        return $transactionList;
    }

    /**
     * Increase date by 2 working days
     *
     * NOTE! In case of logic change, this method can accelerate.
     * For now only Convers Bank is a non batch PSP, but situation can change.
     *
     * @param string $date
     * @param int $pspId
     * @return float
     */
    private function changeDateForSomePSP($date, $pspId)
    {
        if (in_array($pspId, self::$converseBankData['testimony'])) {
            $day = date('Y-m-d', strtotime($date));
            $time = date('H:i:s', strtotime($date));
            $weekDay = self::$converseBankData['days'];
            $date = (
                new \DateTime("{$day} +{$weekDay} Weekday")
            )->getTimestamp();

            $date = date('Y-m-d', $date) . ' ' . $time;
        }

        return $date;
    }

    /**
     * @param float $amount
     * @param int $pspId
     * @return float
     */
    private function applyPercentDeductionByPSP($amount, $pspId)
    {
        if (in_array($pspId, self::$converseBankData['testimony'])) {
            $amount -= $amount * self::$converseBankData['percent'] / 100;
        }

        return $amount;
    }
}
