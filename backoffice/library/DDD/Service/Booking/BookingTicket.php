<?php

namespace DDD\Service\Booking;

use CreditCard\Service\Card;
use DDD\Dao\Booking\ReservationNightly;
use DDD\Dao\Partners\Partners as PartnersDAO;
use DDD\Dao\Psp\Psp;
use DDD\Dao\User\UserManager;
use DDD\Domain\Review\ReviewBooking;
use DDD\Service\Apartment\Inventory;
use DDD\Service\Apartment\Rate;
use DDD\Service\ChannelManager;
use DDD\Service\Notifications;
use DDD\Service\Reservation\ChargeAuthorization as ChargeAuthorizationService;
use DDD\Service\Reservation\Main as ReservationMainService;
use DDD\Service\ServiceBase;
use DDD\Service\Booking\Charge as ChargesServices;
use DDD\Service\Task;
use DDD\Service\Taxes as TaxesServices;
use DDD\Service\Team\Team;
use DDD\Service\User as UserService;
use DDD\Service\User\Main as UserMain;
use DDD\Domain\Booking\BookingTicket as BookingTicketDomain;
use DDD\Service\Booking as BookingService;
use DDD\Service\Team\Team as TeamService;
use DDD\Service\Lock\General as LockService;

use Library\ActionLogger\Logger;
use Library\Constants\Constants;
use Library\Constants\DbTables;
use Library\Constants\Objects;
use Library\Finance\CreditCard\CreditCard;
use Library\Finance\Finance;
use Library\Utility\Helper;
use Library\Constants\TextConstants;
use Library\Constants\DomainConstants;
use Library\Utility\Currency;
use Library\Validator\ClassicValidator;
use Library\Constants\Roles;

use Zend\Db\Sql\Where;
use Zend\Validator\Db\RecordExists;
use Zend\Validator\EmailAddress;
use Zend\Session\Container;

/**
 * Service class providing methods needed to work with reservation ticket
 * @author Tigran Petrosyan
 */
class BookingTicket extends ServiceBase
{
	const OVERBOOKING_STATUS_NORMAL = 0;
	const OVERBOOKING_STATUS_OVERBOOKED = 1;
	const OVERBOOKING_STATUS_RESOLVED = 2;

    public static $overbookingOptions = [
        self::OVERBOOKING_STATUS_NORMAL => 'Normal',
        self::OVERBOOKING_STATUS_OVERBOOKED => 'Overbooked',
        self::OVERBOOKING_STATUS_RESOLVED => 'Resolved'
    ];


    const CC_STATUS_UNKNOWN = 0;
    const CC_STATUS_VALID   = 1;
    const CC_STATUS_INVALID = 2;

	const MODEL_GINOSI  = 2;
    const MODEL_PARTNER = 3;

	const NOT_SEND_KI_PAGE_STATUS = 0;
    const SEND_KI_PAGE_STATUS     = 1;
    const EXPIRED_KI_PAGE_STATUS  = 1;

    const PROVIDE_CC_PAGE_STATUS_NEW       = 0;
    const PROVIDE_CC_PAGE_STATUS_PROVIDE   = 1;
    const PROVIDE_CC_PAGE_STATUS_NOT_CHECK = 2;

    const CHECKOUT_STATE  = 2;
    const INSPECTED_STATE = 3;

    const BOOKED_STATE_NOT_CHANGED = 0;
    const BOOKED_STATE_CHANGED     = 1;

    protected $_partnerDao = null;

    const SECRET_DISCOUNT_AFFILIATE_ID        = 3;
    const SECRET_DISCOUNT_AFFILIATE_REFERENCE = 'erebuni';

    const BOOKING_ARRIVAL_STATUS_EXPECTED       = 0;
    const BOOKING_ARRIVAL_STATUS_CHECKED_IN 	= 1;
    const BOOKING_ARRIVAL_STATUS_CHECKED_OUT	= 2;
    const BOOKING_ARRIVAL_STATUS_INSPECTED  	= 3;
    const BOOKING_ARRIVAL_STATUS_NO_SHOW        = 4;

    public static $arrivalStatuses = [
        self::BOOKING_ARRIVAL_STATUS_EXPECTED    => 'Expected',
        self::BOOKING_ARRIVAL_STATUS_CHECKED_IN  => 'Checked-in',
        self::BOOKING_ARRIVAL_STATUS_CHECKED_OUT => 'Checked-out',
        self::BOOKING_ARRIVAL_STATUS_NO_SHOW     => 'No Show'
    ];

    /**
     * Generate Reservation Number.
     *
     * @return string
     */
    public function generateResNumber()
    {
        /**
         * @var \DDD\Dao\Booking\Booking $bookingDao
         */
        $bookingDao = $this->getServiceLocator()->get('dao_booking_booking');
        $bookingDao->setEntity(new \DDD\Domain\Booking\ResId());

        $bookingDomain = $bookingDao->getMaxId();

        // Get Max Id from Bookings
        $resNumber = $bookingDomain->getId();

        $alphanumeric = array_merge(
            range('A', 'Z'),
            range(1, 9)
        );

        $extraCharCount = 2;

        for ($i = 0; $i < $extraCharCount; $i++) {
            $resNumber .= $alphanumeric[rand(0, 32)];
        }

        return $resNumber;
    }

    /**
     * @param $string
     * @param bool $solt
     * @param string $algo
     * @return bool|string
     */
    public function generatePageHash($string, $solt = FALSE, $algo = 'sha256')
    {
        if (!in_array($algo, hash_algos())) {
            return FALSE;
        }

        if ($solt) {
            $string .= $solt;
        }

        return hash($algo, $string);
    }

    /**
	 * Get data for booking ticket by reservation number
	 *
	 * @param string $resNumber
	 * @throws \Exception
	 * @return BookingTicketDomain
	 */
	public function getBookingByResNumber($resNumber)
    {
		/* @var $dao \DDD\Dao\Booking\Booking */
		$dao = $this->getServiceLocator()->get('dao_booking_booking');
		$result = $dao->getBookingByResNumber($resNumber);

		return $result;
	}

	/**
	 * Get data for booking ticket by reservation id
	 *
	 * @param int $resId
	 * @return \DDD\Domain\Booking\BookingTicket
	 */
	public function getBookingThicketByReservationId($resId)
    {
		/* @var $dao \DDD\Dao\Booking\Booking */
		$dao = $this->getServiceLocator()->get('dao_booking_booking');
		$result = $dao->getReservationById($resId);

		return $result;
	}

	/**
	 * @param \DDD\Domain\Booking\BookingTicket $data
	 * @param array $formData
	 *
	 * @return array
	 */
	public function prepareData($data, $formData)
    {
		/**
		 * @var ReviewBooking $review
         * @var \DDD\Service\Reservation\PartnerSpecific $partnerSpecificService
		 * @var \DDD\Service\Booking\Charge $bookingChargeService
         * @var \DDD\Dao\Booking\Booking $bookingDao
		 */

        $partnerSpecificService = $this->getServiceLocator()->get('service_reservation_partner_specific');
		$daoReview              = new \DDD\Dao\Accommodation\Review($this->getServiceLocator(), 'DDD\Domain\Review\ReviewBooking');
		$bookingChargeService   = $this->getServiceLocator()->get('service_booking_charge');
		$cityService            = $this->getServiceLocator()->get('service_location');
        $bookingDao             = $this->getServiceLocator()->get('dao_booking_booking');

        $bookingDao->setEntity(new \DDD\Domain\Booking\PrepareData());

		$dataOther     = [];
        $reservationId = $data->getId();

        $startDate     = $data->getDate_from();
        $endDate       = $data->getDate_to();

		$review_res_number = $data->getResNumber();
		$review = $daoReview->fetchOne(['res_number' => $review_res_number]);

		if ($review) {
			$data->setHas_review(true);
		} else {
			$data->setHas_review(false);
		}

		$auth = $this->getServiceLocator()->get('library_backoffice_auth');
		$dataOther['credit_card_view'] = false;


		if ($auth->hasRole(Roles::ROLE_CREDIT_CARD)) {
			$dataOther['credit_card_view'] = true;
			$dataOther['secure_url'] = "https://" . DomainConstants::WS_SECURE_DOMAIN_NAME . "/booking/update-cc-details?code=" . $formData['data']->getProvideCcPageHash();
		}

		if ($auth->hasRole(Roles::ROLE_RESERVATIONS)) {
			$dataOther['reservation_role'] = true;
		} else {
			$bookingService = $this->getServiceLocator()->get('service_booking');

			$part = $bookingService->getPartnerById($data->getPartnerId());
			$dataOther['affiliate'] = $part->getPartnerName();
		}

        $keyPageStatus = '';
        if (!Helper::checkDatesByDaysCount(1, $data->getDate_to())) {
            $keyPageStatus = "Expired";
        } elseif ($data->getKiPageStatus() == 1) {
            $kiPageHash = $data->getKiPageHash();
            $keyPageStatus = "<a target='_blank'  href='https://" . DomainConstants::WS_SECURE_DOMAIN_NAME . '/key?view=0&code=' . $kiPageHash . "' >Key Entry Link</a>";
        } elseif (!$data->getKiPageStatus()) {
            $keyPageStatus = "Not set yet";
        }

		$is_penalty_applied = $this->isPenaltyApplied($data->getDate_from(), $data->getIsRefundable(), $data->getRefundableBeforeHours());
		$penalty_period = $this->calculateCancelationPeriod($data->getStatus(), $data->getCancelation_date(), $data->getDate_to(), $is_penalty_applied);

		$dataOther['keyPageStatus'] = $keyPageStatus;
		$dataOther['penalty_period'] = $penalty_period;

		$penalty_val = number_format(str_replace(",", ".", $data->getPenalty_val()), 2, '.', '');
		$penalty     = number_format(str_replace(",", ".", $data->getPenalty()), 2, '.', '');

		if (Rate::APARTMENT_RATE_REFUNDABLE == $data->getIsRefundable()) {
			if ($data->getRefundableBeforeHours() > 48) {
				$dataOther['cancellation'] = "This deal can be canceled for FREE up to " . ($data->getRefundableBeforeHours() / 24) . " days before arrival";
			} else {
				$dataOther['cancellation'] = "This deal can be canceled for FREE up to " . $data->getRefundableBeforeHours() . " hours before arrival";
			}
		} else {
		    $dataOther['cancellation'] = 'Non refundable';
        }

		$dataOther['panelty_type'] = Helper::getPenalty($penalty, $penalty_val, $data->getApartmentCurrencyCode());

		foreach ($formData['options']['statuses'] as $row) {
			if ($row->getId() == $data->getStatus()) {
				$dataOther['status_name'] = $row->getName();

				break;
			}
		}

		foreach ($formData['options']['partners'] as $row) {
			if ($row->getGid() == $data->getPartnerId()) {
				$dataOther['partner_name'] = $row->getPartnerName();
                $dataOther['partner_id']   = $data->getPartnerId();
				break;
			}
		}

		$customerReservations = $bookingDao->getCustomerReservationsStatuses($data->getGuestEmail());
		$bookedReservationsCount = 0;
		$cancelledReservationsCount = 0;
		$hasDebtBalance = false;

		foreach ($customerReservations as $customerReservation) {
            if (!$hasDebtBalance && $customerReservation->getBalance() < 0 && $data->getResNumber() != $customerReservation->getResNumber()) {
                $hasDebtBalance = true;
            }

			if ($customerReservation->getStatus() == BookingService::BOOKING_STATUS_BOOKED) {
				$bookedReservationsCount++;
			} else {
				$cancelledReservationsCount++;
			}
		}

        $dataOther['pendingCreditCardInQueue'] = false;
        $pendingCreditCardInQueue = $bookingDao->getPendingInQueueCardsByReservationId($reservationId);
        if ($pendingCreditCardInQueue->count()) {
            $dataOther['pendingCreditCardInQueue'] = true;
        }

        /**
         * @var \DDD\Service\Fraud $fraudService
         */
        $fraudService = $this->getServiceLocator()->get('service_fraud');
		$fraud = $fraudService->getFraudForReservation($reservationId);
		$dataOther['terminals']               = Objects::getTerminalList();
		$dataOther['addons']                  = $formData['options']['addons'];
		$dataOther['addons_array']            = $formData['options']['addons_array'];
		$dataOther['fraud']                   = $fraud;
		$dataOther['psp']                     = [];
		$dataOther['curencyRate']             = $formData['options']['curencyRate'];
		$dataOther['charges']                 = $formData['options']['charges'];
		$dataOther['charged']                 = $formData['options']['charged'];
		$dataOther['chargeType']              = $formData['options']['chargeType'];
		$dataOther['transactions']            = $formData['options']['transactions'];

		$dataOther['ginosiCollectChargesSummaryInApartmentCurrency'] 		= $formData['options']['balances_and_summaries']['ginosiCollectChargesSummaryInApartmentCurrency'];
		$dataOther['ginosiCollectChargesSummaryInCustomerCurrency'] 		= $formData['options']['balances_and_summaries']['ginosiCollectChargesSummaryInCustomerCurrency'];
		$dataOther['partnerCollectChargesSummaryInApartmentCurrency'] 		= $formData['options']['balances_and_summaries']['partnerCollectChargesSummaryInApartmentCurrency'];
		$dataOther['partnerCollectChargesSummaryInCustomerCurrency'] 		= $formData['options']['balances_and_summaries']['partnerCollectChargesSummaryInCustomerCurrency'];
		$dataOther['ginosiCollectTransactionsSummaryInApartmentCurrency'] 	= $formData['options']['balances_and_summaries']['ginosiCollectTransactionsSummaryInApartmentCurrency'];
		$dataOther['ginosiCollectTransactionsSummaryInCustomerCurrency'] 	= $formData['options']['balances_and_summaries']['ginosiCollectTransactionsSummaryInCustomerCurrency'];
		$dataOther['partnerCollectTransactionsSummaryInApartmentCurrency'] 	= $formData['options']['balances_and_summaries']['partnerCollectTransactionsSummaryInApartmentCurrency'];
		$dataOther['partnerCollectTransactionsSummaryInCustomerCurrency'] 	= $formData['options']['balances_and_summaries']['partnerCollectTransactionsSummaryInCustomerCurrency'];

		$dataOther['chargesSummaryInApartmentCurrency'] 		= $formData['options']['balances_and_summaries']['chargesSummaryInApartmentCurrency'];
		$dataOther['chargesSummaryInCustomerCurrency'] 			= $formData['options']['balances_and_summaries']['chargesSummaryInCustomerCurrency'];

		$dataOther['transactionsSummaryInApartmentCurrency'] 	= $formData['options']['balances_and_summaries']['transactionsSummaryInApartmentCurrency'];
		$dataOther['transactionsSummaryInCustomerCurrency'] 	= $formData['options']['balances_and_summaries']['transactionsSummaryInCustomerCurrency'];

		$dataOther['ginosiBalanceInApartmentCurrency'] 	= $formData['options']['balances_and_summaries']['ginosiBalanceInApartmentCurrency'];
		$dataOther['ginosiBalanceInCustomerCurrency'] 	= $formData['options']['balances_and_summaries']['ginosiBalanceInCustomerCurrency'];
		$dataOther['partnerBalanceInApartmentCurrency'] = $formData['options']['balances_and_summaries']['partnerBalanceInApartmentCurrency'];
		$dataOther['partnerBalanceInCustomerCurrency'] 	= $formData['options']['balances_and_summaries']['partnerBalanceInCustomerCurrency'];

		$dataOther['resCount']                = $bookedReservationsCount;
		$dataOther['cancelCount']             = $cancelledReservationsCount;
		$dataOther['hasDebtBalance']          = $hasDebtBalance;
		$dataOther['hasPayableRole']          = $formData['options']['hasPayableRole'];
		$dataOther['hasTransactionVerifierRole'] = $formData['options']['hasTransactionVerifierRole'];

		$dataOther['creditCardsForAuthorization'] = $formData['options']['credit_cards_for_authorization'];

        //Calculate Taxes
        $nightCount = Helper::getDaysFromTwoDate($data->getDate_to(), $data->getDate_from());
        /** @var \DDD\Service\Taxes $taxesService */
        $taxesService = $this->getServiceLocator()->get('service_taxes');
        $taxesParams = [
            'tot'                    => $data->getTot(),
            'tot_type'               => $data->getTotType(),
            'tot_included'           => $data->getTotIncluded(),
            'tot_additional'         => $data->getTotAdditional(),
            'tot_max_duration'       => $data->getTotMaxDuration(),
            'vat'                    => $data->getVat(),
            'vat_type'               => $data->getVatType(),
            'vat_included'           => $data->getVatIncluded(),
            'vat_additional'         => $data->getVatAdditional(),
            'vat_max_duration'       => $data->getVatMaxDuration(),
            'city_tax'               => $data->getCity_tax(),
            'city_tax_type'          => $data->getCityTaxType(),
            'city_tax_included'      => $data->getCityTaxIncluded(),
            'city_tax_additional'    => $data->getCityTaxAdditional(),
            'city_tax_max_duration'  => $data->getCityTaxMaxDuration(),
            'sales_tax'              => $data->getSales_tax(),
            'sales_tax_type'         => $data->getSalesTaxType(),
            'sales_tax_included'     => $data->getSalesTaxIncluded(),
            'sales_tax_additional'   => $data->getSalesTaxAdditional(),
            'sales_tax_max_duration' => $data->getSalesTaxMaxDuration(),
            'apartment_currency'     => $data->getApartmentCurrencyCode(),
            'customer_currency'      => $data->getGuestCurrencyCode(),
            'country_currency'       => $data->getCountryCurrecny(),
            'night_count'            => $nightCount,
            'rate_capacity'          => $data->getRateCapacity(),
            'occupancy'              => $data->getOccupancy()
        ];
        $taxesData = $taxesService->getTaxesForCharge($taxesParams);
        $dataOther += $taxesData;

        /**
         * @var \DDD\Service\Booking\ReservationIssues $reservationIssuesService
         */
        $reservationIssuesService = $this->getServiceLocator()->get('service_booking_reservation_issues');

        $issuesData = $reservationIssuesService->getReservationIssues($reservationId);

        $dataOther['hasIssues'] = false;
        $dataOther['hasIssuesText'] = '';

        if (count($issuesData) > 0) {
            $dataOther['hasIssuesText'] .= '<p>' . TextConstants::ISSUE_DETECTED_FOLLOWING . ':<br>';

            foreach ($issuesData as $issue) {
                $dataOther['hasIssuesText'] .= '<li>' . $issue->getTitle() . '</li>';
            }

            $dataOther['hasIssuesText'] .= '</p>';
            $dataOther['hasIssues'] = true;
        }


        /**
         * @var Card $cardService
         */
        $cardService = $this->getServiceLocator()->get('service_card');
        $customerCreditCards = $cardService->getCustomerCreditCardsForReservationTicket($data->getCustomerId());

        $creditCardsMerge = $customerCreditCards;
        $dataOther['hasValidCard'] = $creditCardsMerge['hasValidCard'];

        foreach ($formData['options']['psp'] as $psp) {
            array_push($dataOther['psp'], $psp);
        }

        /**
         * @var \DDD\Dao\MoneyAccount\MoneyAccount $moneyAccountDao
         */
        $moneyAccountDao = $this->getServiceLocator()->get('dao_money_account_money_account');
        $moneyAccountList = $moneyAccountDao->getActiveBankList();
        $dataOther['money_account_list'] = $moneyAccountList;
        $dataOther['person_account_list'] = $moneyAccountDao->getActivePersonList();

        /**
         * @var \DDD\Dao\Booking\ReservationNightly $reservationNightlyDao
         * @var \DDD\Dao\Apartment\Rate $apartmentRateDao
         */
        $reservationNightlyDao = $this->getServiceLocator()->get('dao_booking_reservation_nightly');
        $apartmentRateDao = $this->getServiceLocator()->get('dao_apartment_rate');

        // get nightly data
        $nightsData = $reservationNightlyDao->getNightsDateWithCharge($reservationId);
        $nightsParse = $dates = $ratesPriceByDateParse = [];
        foreach ($nightsData as $night) {
            $dates[] = $night['date'];

            // if partner specific get specific price
            $priceForNight = $partnerSpecificService->getPartnerDeductedPrice($data->getPartnerId(), $night['price'], $data->getApartmentIdAssigned());
            $nightsParse["_" . $night['id']] = [
                'reservation_nightly_id' => $night['id'],
                'apartment_id' => $night['apartment_id'],
                'rate_id' => $night['rate_id'],
                'rate_name' => $night['rate_name'],
                'price' => $priceForNight,
                'date' => $night['date'],
                //'has_charge' => ($night['charge_id'] ? 'yes' : 'no'),
            ];

            if ($night['charge_id']) {
                $ratesPriceByDateParse[$night['date']][] = [
                    'id' => $night['rate_id'],
                    'rate_name' => $night['rate_name'] . ' *',
                    'price' => $night['price'],
                    'date' => $night['date'],
                ];
            }
        }

        // check is apartel
        $apartel = $bookingDao->checkIsApartel($reservationId);

        // get rate data by date
        if ($apartel) {
            /** @var \DDD\Dao\Apartel\Rate $apartelRateDao */
            $apartelRateDao = $this->getServiceLocator()->get('dao_apartel_rate');
            $ratesPriceByDate = $apartelRateDao->getRatesPriceByDate($data->getRoom_id(), $dates);
        } else {
            $ratesPriceByDate = $apartmentRateDao->getRatesPriceByDate($data->getApartmentIdAssigned(), $dates);
        }

        foreach ($ratesPriceByDate as $rate) {
            $ratesPriceByDateParse[$rate['date']][] = [
                'id' => $rate['id'],
                'rate_name' => $rate['rate_name'],
                'price' => $rate['price'],
                'date' => $rate['date'],
            ];
        }

        // date list
        $current = strtotime($data->getDate_from());
        $last = strtotime($data->getDate_to());
        $dateList = []; $k =1;
        while ($current < $last) {
            $dateList[$k] = date('Y-m-d', $current);
            $current = strtotime('+1 day', $current);
            $k++;
        }
        $dataOther['dateList'] = json_encode($dateList);

        // get near reservation dates
        $usedFrom = $data->getDate_from();
        $usedTo = $data->getDate_to();

        /**
         * @var \DDD\Dao\Apartment\Inventory $inventoryDao
         */
        $beforeDate = date('Y-m-d', strtotime('-10 day', strtotime($usedFrom)));
        $afterDate = date('Y-m-d', strtotime('+10 day', strtotime($usedTo)));
        $inventoryDao = $this->getServiceLocator()->get('dao_apartment_inventory');
        $dateAvListBeforeAfterReservation = $inventoryDao->getDateAvListBeforeAfterReservation(
            $data->getApartmentIdAssigned(), $beforeDate, $afterDate);
        $dateListParseCheckIn = $dateListParseCheckOut = []; $classHighlight = '';
        foreach ($dateAvListBeforeAfterReservation as $row) {
            if ($row['availability'] == 1) {
                $classHighlight = 'date-open';
            } else {
                $classHighlight = 'date-closed';
            }

            if ($row['date'] >= $usedFrom && $row['date'] <= $usedTo) {
                $classHighlight = 'date-open date-used';
            }

            $dateListParseCheckIn[$row['date']] = [
                'date' => $row['date'],
                'class' => $classHighlight,
                'availability' => $row['availability'],
            ];
        }

        foreach ($dateListParseCheckIn as $row) {

            $dateForCheckout = date('Y-m-d', strtotime('-1 day', strtotime($row['date'])));

            if (!isset($dateListParseCheckIn[$dateForCheckout]['availability'])) {
                continue;
            }

            if ($dateListParseCheckIn[$dateForCheckout]['availability'] == 1) {
                $classHighlight = 'date-open';
            } else {
                $classHighlight = 'date-closed';
            }

            if ($dateForCheckout >= $usedFrom && $dateForCheckout < $usedTo) {
                $classHighlight = 'date-open';
            }

            if ($row['date'] >= $usedFrom && $row['date'] <= $usedTo) {
                $classHighlight .= ' date-used';
            }

            $dateListParseCheckOut[$dateForCheckout] = [
                'date' => $row['date'],
                'class' => $classHighlight,
            ];
        }

        $dataOther['dateForHighlightsCheckIn'] = json_encode($dateListParseCheckIn);
        $dataOther['dateForHighlightsCheckOut'] = json_encode($dateListParseCheckOut);

        // calculate total apartment and taxes
        $totalApartmentView = [];
        $totalChargeType = [
            BookingAddon::ADDON_TYPE_ACC,
            BookingAddon::ADDON_TYPE_TAX_TOT,
            BookingAddon::ADDON_TYPE_TAX_VAT,
            BookingAddon::ADDON_TYPE_CITY_TAX,
            BookingAddon::ADDON_TYPE_SALES_TAX,
            BookingAddon::ADDON_TYPE_DISCOUNT,
            BookingAddon::ADDON_TYPE_PARKING
        ];
        $value = '';
        $hasReversedCharges = false;

        /** @var \DDD\Domain\Booking\Charge $row */
        foreach ($dataOther['charged'] as $row) {
            if ($row->getStatus() == ChargesServices::CHARGE_STATUS_DELETED) {
                $hasReversedCharges = true;
            }

            if (   in_array($row->getAddons_type(), $totalChargeType)
                && $row->getAddons_type() != BookingAddon::ADDON_TYPE_PARKING
                && ($row->getStatus() == ChargesServices::CHARGE_STATUS_NORMAL)
                && (int)$row->getReservationNightlyId()
            ) {
                if (($row->getAddons_type() == BookingAddon::ADDON_TYPE_ACC || $row->getAddons_type() == BookingAddon::ADDON_TYPE_ACC) && $row->getRateName()  ) {
                    $value = $row->getRateName();
                } elseif($row->getAddons_value() > 0) {
                    $value = $row->getAddons_value();
                    if($row->getTaxType() == TaxesServices::TAXES_TYPE_PERCENT) {
                        $value .= ' %';
                    } elseif($row->getTaxType() == TaxesServices::TAXES_TYPE_PER_NIGHT) {
                        $value .= ' p/n';
                    } elseif($row->getTaxType() == TaxesServices::TAXES_TYPE_PER_PERSON) {
                        $value .= ' p/p';
                    }
                }

                if (!isset($totalApartmentView[$row->getAddons_type()])) {
                    $totalApartmentView[$row->getAddons_type()] = [
                        'name'       => $row->getAddon(),
                        'dateMin'    => $row->getReservationNightlyDate(),
                        'dateMax'    => $row->getReservationNightlyDate(),
                        'value'      => $value,
                        'price'      => $row->getAcc_amount(),
                        'collection' => ChargesServices::$moneyDirectionOptions[$row->getMoneyDirection()],
                        'commission' => $row->getCommission(),
                    ];
                } else {
                    $totalApartmentView[$row->getAddons_type()]['price'] += $row->getAcc_amount();

                    if ($totalApartmentView[$row->getAddons_type()]['dateMin'] > $row->getReservationNightlyDate()) {
                        $totalApartmentView[$row->getAddons_type()]['dateMin'] = $row->getReservationNightlyDate();
                    }

                    if ($totalApartmentView[$row->getAddons_type()]['dateMax'] < $row->getReservationNightlyDate()) {
                        $totalApartmentView[$row->getAddons_type()]['dateMax'] = $row->getReservationNightlyDate();
                    }
                }
            }
        }

        //creating an array where spot names are keys
        //and values are arrays with all dates
        //Example :
        //            [spot1] => Array
        //            (
        //                  [0] => 2015-07-22
        //                  [1] => 2015-07-23
        //                  [2] => 2015-07-25
        //                  [3] => 2015-07-26
        //                  [4] => 2015-07-27
        //                  [5] => 2015-07-28
        //                  [6] => 2015-07-29
        //                  [7] => 2015-07-30
        //                  [8] => 2015-07-31
        //                  [9] => 2015-08-01
        //            )
        //             [spot2] => Array
        //            (
        //                  [0] => 2015-07-22
        //                  [1] => 2015-07-23
        //                  [2] => 2015-07-24
        //                  [3] => 2015-08-01
        //            )
        $AllParkingSpotDatesArray = [];
        foreach ($dataOther['charged'] as $row) {
            if (
                $row->getAddons_type() == BookingAddon::ADDON_TYPE_PARKING
                && ($row->getStatus() == ChargesServices::CHARGE_STATUS_NORMAL)
                && (int)$row->getReservationNightlyId()
            ) {
                if (!isset($AllParkingSpotDatesArray[$row->getRateName()])) {
                    $AllParkingSpotDatesArray[$row->getRateName()] = [];
                }
                array_push($AllParkingSpotDatesArray[$row->getRateName()], $row->getReservationNightlyDate());
            }
        }
        //sorting spot level arrays by date
        foreach ($AllParkingSpotDatesArray as &$singleSpotDatesForSort) {
            sort($singleSpotDatesForSort);
        }
        //creating an array where keys are spot names
        //and the values are array containing arrays of date ranges
        //Example:
        //    [spot1] => Array
        //    (
        //        [0] => Array
        //        (
        //            [0] => 2015-07-22
        //            [1] => 2015-07-23
        //         )
        //        [1] => Array
        //         (
        //            [0] => 2015-07-25
        //            [1] => 2015-07-26
        //            [2] => 2015-07-27
        //            [3] => 2015-07-28
        //            [4] => 2015-07-29
        //            [5] => 2015-07-30
        //            [6] => 2015-07-31
        //            [7] => 2015-08-01
        //          )
        //    )
        //    [spot2] => Array
        //    (
        //        [0] => Array
        //        (
        //            [0] => 2015-07-22
        //            [1] => 2015-07-23
        //            [2] => 2015-07-24
        //        )
        //        [1] => Array
        //        (
        //            [0] => 2015-08-01
        //        )
        //    )
        $allParkingSpotDatesArrayByDateRanges = [];
        $secondsInOneDay = 86400;
        foreach ($AllParkingSpotDatesArray as $rowKey => $singleSpotDates) {
            $i = 0;
            $k = 0;
            $allParkingSpotDatesArrayByDateRanges[$rowKey] = [];
            foreach($singleSpotDates as $date) {
                if ($i == 0) {
                    $allParkingSpotDatesArrayByDateRanges[$rowKey][$k] = [$date];
                } else {
                    $differenceBetweenDatesInSeconds = strtotime($date) - strtotime($singleSpotDates[$i-1]);
                    if ($differenceBetweenDatesInSeconds != $secondsInOneDay) {
                        $k++;
                    }
                    if (!isset($allParkingSpotDatesArrayByDateRanges[$rowKey][$k])) {
                        $allParkingSpotDatesArrayByDateRanges[$rowKey][$k] = [];
                    }
                    array_push($allParkingSpotDatesArrayByDateRanges[$rowKey][$k],$date);
                }
                $i++;
            }
        }
        $combinedArray = [];
        foreach ($allParkingSpotDatesArrayByDateRanges as $spotName => $parkingSpotDateRangeItem) {
            foreach($parkingSpotDateRangeItem as $dateRanges) {
                array_push($combinedArray,
                [
                    'spot' => $spotName,
                    'dateStart' => min($dateRanges),
                    'dateEnd'   => max($dateRanges)
                ]
                );
            }
        }

        foreach ($dataOther['charged'] as $row) {
            if (
                $row->getAddons_type() == BookingAddon::ADDON_TYPE_PARKING
                && ($row->getStatus() == ChargesServices::CHARGE_STATUS_NORMAL)
                && (int)$row->getReservationNightlyId()
            ) {

               foreach ($combinedArray as &$item) {
                   if (
                       $row->getRateName() == $item['spot']
                       &&
                       $row->getReservationNightlyDate() >= $item['dateStart']
                       &&
                       $row->getReservationNightlyDate() <= $item['dateEnd']
                      ) {
                            if (!isset($item['price'])) {
                                $item['price'] = 0;
                            }
                       $item['price'] += $row->getAcc_amount();
                       $item['collection'] = ChargesServices::$moneyDirectionOptions[$row->getMoneyDirection()];
                       $item['commission'] = $row->getCommission();
                       $item['value'] = $item['spot'];
                       $item['name'] = 'Parking';
                       break;
                   }
               }


            }
        }
        foreach ($combinedArray as &$it) {
            $it['date'] = date(Constants::GLOBAL_DATE_WO_YEAR, strtotime($it['dateStart'])) . ' - ' . date(Constants::GLOBAL_DATE_WO_YEAR, strtotime($it['dateEnd'] . ' + 1 day'));

        }

        $dataOther['totalChargeView'] = $totalApartmentView;
        array_push($dataOther['totalChargeView'], $combinedArray);
        $dataOther['totalChargeType'] = $totalChargeType;
        $dataOther['hasReversedCharges'] = $hasReversedCharges;

		$dataOther['penaltyPrice'] = 0;

		if (array_key_exists(0, $dataOther['totalChargeView']) && !$data->getCheckCharged()) {
			$refundLimitDay            = $data->getRefundableBeforeHours() / 24;
			$currentCityDate           = $cityService->getCurrentDateCity($data->getApartmentCityId());
			$accomodationPrice         = $data->getBooker_price();
			$dataOther['penaltyPrice'] = number_format($accomodationPrice, 2, '.', '');

			if (((strtotime($data->getDate_from()) - strtotime(date('Y-m-d', strtotime($currentCityDate)))) / 86400) > $refundLimitDay) {
				$penaltyPercent = $data->getPenalty_val() * 0.01;
				$penaltyPrice = round($data->getBooker_price() * $penaltyPercent, 2);
				$dataOther['penaltyPrice'] = number_format($penaltyPrice, 2, '.', '');
			}
		}

		if (array_key_exists(BookingAddon::ADDON_TYPE_ACC, $dataOther['totalChargeView'])) {
			$accomodationPrice         = number_format($dataOther['totalChargeView'][BookingAddon::ADDON_TYPE_ACC]['price'], 2, '.', '');
			$dataOther['penaltyPrice'] = $accomodationPrice;

			if ($data->getPenalty()) {
				$penaltyPrice              = $accomodationPrice - $bookingChargeService->recalculatePenalty($reservationId, $accomodationPrice);
				$dataOther['penaltyPrice'] = number_format($penaltyPrice, 2, '.', '');
				$refundLimitDay            = $data->getRefundableBeforeHours() / 24;
				$currentCityDate           = $cityService->getCurrentDateCity($data->getApartmentCityId());

				if (((strtotime($data->getDate_from()) - strtotime(date('Y-m-d', strtotime($currentCityDate)))) / 86400) < $refundLimitDay) {
					$dataOther['penaltyPrice'] = $accomodationPrice;
				}
			}
		}

        $bankTransactionList = [];
        $listTransactionType = [
            BankTransaction::BANK_TRANSACTION_TYPE_VALIDATION => Objects::getChargeType()[BankTransaction::BANK_TRANSACTION_TYPE_VALIDATION],
            BankTransaction::BANK_TRANSACTION_TYPE_CASH => Objects::getChargeType()[BankTransaction::BANK_TRANSACTION_TYPE_CASH],
            BankTransaction::BANK_TRANSACTION_TYPE_BANK_DEPOSIT => Objects::getChargeType()[BankTransaction::BANK_TRANSACTION_TYPE_BANK_DEPOSIT],
            BankTransaction::BANK_TRANSACTION_TYPE_DEDUCTED_SALARY => Objects::getChargeType()[BankTransaction::BANK_TRANSACTION_TYPE_DEDUCTED_SALARY],
        ];

        // collect from card
        if ($dataOther['hasValidCard']) {
            $listTransactionType[BankTransaction::BANK_TRANSACTION_TYPE_COLLECT] = Objects::getChargeType()[BankTransaction::BANK_TRANSACTION_TYPE_COLLECT];
        }

        if($dataOther['transactions']) {
            foreach ($dataOther['transactions'] as $bank) {
                if(in_array($bank->getStatus(), [
                    BankTransaction::BANK_TRANSACTION_STATUS_APPROVED,
                    BankTransaction::BANK_TRANSACTION_STATUS_PENDING
                ])) {
                    $listTransactionType[BankTransaction::BANK_TRANSACTION_TYPE_CASH_REFUND] = Objects::getChargeType()[BankTransaction::BANK_TRANSACTION_TYPE_CASH_REFUND];

                    if($bank->getType() == BankTransaction::BANK_TRANSACTION_TYPE_COLLECT) {
                        $listTransactionType[BankTransaction::BANK_TRANSACTION_TYPE_REFUND] = Objects::getChargeType()[BankTransaction::BANK_TRANSACTION_TYPE_REFUND];
                        $listTransactionType[BankTransaction::BANK_TRANSACTION_TYPE_CHARGEBACK_FRAUD] = Objects::getChargeType()[BankTransaction::BANK_TRANSACTION_TYPE_CHARGEBACK_FRAUD];
                        $listTransactionType[BankTransaction::BANK_TRANSACTION_TYPE_CHARGEBACK_OTHER] = Objects::getChargeType()[BankTransaction::BANK_TRANSACTION_TYPE_CHARGEBACK_OTHER];
                        $listTransactionType[BankTransaction::BANK_TRANSACTION_TYPE_CHARGEBACK_DISPUTE] = Objects::getChargeType()[BankTransaction::BANK_TRANSACTION_TYPE_CHARGEBACK_DISPUTE];
                    }
                }

                if ($bank->getMoneyAccountId() > 0 && in_array($bank->getStatus(), [
                    BankTransaction::BANK_TRANSACTION_STATUS_APPROVED,
                    BankTransaction::BANK_TRANSACTION_STATUS_PENDING
                ])) {
                    $bankTransactionList[$bank->getMoneyAccountId()] = [
                        'money_account_name' => $bank->getBank(),
                        'money_account_currency' => $bank->getMoneyAccountCurrency(),
                    ];
                }
            }
        }
        asort($listTransactionType);
        $dataOther['listTransactionType'] = $listTransactionType;
        $dataOther['bank_transaction_list'] = $bankTransactionList;

        // partner commission for charge
        $dataOther['partner_commission_charge'] = $partnerSpecificService->getPartnerDeductedCommission($data->getPartnerId(), $data->getPartnerCommission());

        // get apartel reservations bulk if needed
        if ($data->getApartelId() && $data->getChannelResId()) {

            /** @var \DDD\Dao\ChannelManager\ReservationIdentificator $reservationIdentificatorDao */
            $reservationIdentificatorDao = $this->getServiceLocator()->get('dao_channel_manager_reservation_identificator');
            $apartelReservationsBulk = $reservationIdentificatorDao->getReservationsIdentificatorDataByChannelResId($data->getChannelResId(), $data->getId());
            if ($apartelReservationsBulk->count()) {
                $dataOther['apartel_reservations_bulk'] = $apartelReservationsBulk;
            }
        }

        return [
			'data' => $data,
			'otherData' => $dataOther,
            'creditCards' => $creditCardsMerge['card_list'],
            'nightsData' => $nightsParse,
            'ratesByDate' => $ratesPriceByDateParse
		];
	}



	public function getBookingOptions($data) {
        /**
         * @var \DDD\Service\Booking\BookingAddon $bookingAddonService
         * @var \DDD\Dao\Partners\Partners $partnerDao
         * @var \DDD\Dao\Booking\Charge $chargeDao
         * @var \DDD\Dao\Booking\ChargeTransaction $bankTransactionDao
         * @var \DDD\Dao\ApartmentGroup\ApartmentGroup $apartmentGroupDao
         * @var ChargeAuthorizationService $chargeAuthorizationService
         * @var Psp $pspDao
         */
        $bookingAddonService     = $this->getServiceLocator()->get('service_booking_booking_addon');
        $partnerDao              = $this->getServiceLocator()->get('dao_partners_partners');
        $apartmentGroupDao       = $this->getServiceLocator()->get('dao_apartment_group_apartment_group');
        $chargeDao               = $this->getServiceLocator()->get('dao_booking_charge');
        $bankTransactionDao      = $this->getServiceLocator()->get('dao_booking_change_transaction');
        $apartmentDao            = $this->getServiceLocator()->get('dao_accommodation_accommodations');
        $curencyRateDao          = new \DDD\Dao\Currency\Currency($this->getServiceLocator(), 'DDD\Domain\Booking\Currency');
        $countryDao              = new \DDD\Dao\Geolocation\Countries($this->getServiceLocator());
        $teamsDao                = new \DDD\Dao\Team\Team($this->getServiceLocator());
        $statusDao               = new \DDD\Dao\Booking\Statuses($this->getServiceLocator());
        $accDeailsDao            = new \DDD\Dao\Apartment\Details($this->getServiceLocator(), 'ArrayObject');
        $apartmentDescriptionDao = new \DDD\Dao\Apartment\Description($this->getServiceLocator(), 'ArrayObject');
        $chargeAuthorizationService = $this->getServiceLocator()->get('service_reservation_charge_authorization');

        $detailsRow = $accDeailsDao->fetchOne(
            ['apartment_id' => $data['apartmentIdAssigned']]
        );

        $accInfo = $apartmentDao->fetchOne(['id' => $data['apartmentIdAssigned']]);

        $apartmentDescription = $apartmentDescriptionDao->fetchOne(['apartment_id' => $data['apartmentIdAssigned']]);

        $result                 = [];

        $result['apartment']['check_in'] = $apartmentDescription['check_in'];

        $status                 = $data['status'];
        $countries              = $countryDao->getAllActiveCountries();
        $result['countries']    = $countries;

        /**
         * @todo do not fetchAll
         * Fetch using usage service
         */
        $result['teams']        = $teamsDao->fetchAll(['usage_notifiable' => 1]);

        $affiliateID            = $data['aff_id'];
        $partner                = $partnerDao->getPartners($affiliateID);
        $result['partners']     = iterator_to_array($partner);
        $result['partner_data'] = $partnerDao->getPartnerModel($affiliateID);;

        $statuses               = $statusDao->getAllList($status);
        $result['statuses']     = iterator_to_array($statuses);
        $pspDao                 = $this->getServiceLocator()->get('dao_psp_psp');
        $pspList                = $pspDao->getPspListForTransaction();
        $result['psp']          = iterator_to_array($pspList);
        $addonsList             = $bookingAddonService->getAddons();
        $addonsArray            = $bookingAddonService->getAddonsArray();

        $creditCardsForAuthorization = $chargeAuthorizationService->getCreditCardsForAuthorization($data['id']);
        $result['credit_cards_for_authorization'] = $creditCardsForAuthorization;

        if ($status == BookingService::BOOKING_STATUS_BOOKED) {
            unset($addonsArray[BookingAddon::ADDON_TYPE_PENALTY]);
        } else {
            unset($addonsArray[BookingAddon::ADDON_TYPE_ACC]);
            unset($addonsArray[BookingAddon::ADDON_TYPE_NIGHT]);
        }

        $result['addons']       = $addonsList;
        $result['addons_array'] = $addonsArray;
        $result['apartels']     = $apartmentGroupDao->getApartelsByApartmentId($data['apartmentIdAssigned']);

        $curencyRate            = $curencyRateDao->fetchAll();
        $curencyRateArray       = [];

		foreach ($curencyRate as $row) {
            if ($detailsRow) {
                if ((int)$detailsRow['cleaning_fee']) {
                    if ($row->getId() == $accInfo->getCurrencyId()) {
                        $cleaningFeeData = [
                            'currency_id' => $row->getId(),
                            'cname' => $row->getCode(),
                            'currency_rate' => $row->getValue(),
                        ];
                    }
                }

            }

			$curencyRateArray[] = [
                'code'  => $row->getCode(),
                'value' => $row->getValue(),
            ];
		}

        if ($detailsRow) {
            if ((int)$detailsRow['cleaning_fee']) {
                $result['addons_array'][BookingAddon::ADDON_TYPE_CLEANING_FEE]['value'] = $detailsRow['cleaning_fee'];
                $result['addons_array'][BookingAddon::ADDON_TYPE_CLEANING_FEE]['currency_id'] = $cleaningFeeData['currency_id'];
                $result['addons_array'][BookingAddon::ADDON_TYPE_CLEANING_FEE]['cname'] = $cleaningFeeData['cname'];
                $result['addons_array'][BookingAddon::ADDON_TYPE_CLEANING_FEE]['currency_rate'] = $cleaningFeeData['currency_rate'];
            }

        }

        $result['curencyRate'] = json_encode($curencyRateArray);
        $chargeType            = Objects::getChargeType();
        $result['chargeType']  = $chargeType;

        $charges = $chargeDao->getChargesByReservationId($data['id'], 1);
        $result['charges'] = iterator_to_array($charges);


        $charged           = $chargeDao->getChargesByReservationId($data['id'], 0);
        $result['charged'] = iterator_to_array($charged);

        $getSumAndBalance = $this->getSumAndBalanc($data['id']);
        $result['balances_and_summaries'] = $getSumAndBalance;

        $transactions           = $bankTransactionDao->getReservationTransactions($data['id']);
        $result['transactions'] = ($transactions->count()) ? (iterator_to_array($transactions)) : false;

		$auth = $this->getServiceLocator()->get('library_backoffice_auth');
		$hasFinanceRole = $auth->hasRole(Roles::ROLE_RESERVATION_FINANCE);
		$hasPayableRole = $auth->hasRole(Roles::ROLE_BILLPAY);
		$hasTransactionVerifierRole = $auth->hasRole(Roles::ROLE_BOOKING_TRANSACTION_VERIFIER);
		$result['hasFinanceRole'] = $hasFinanceRole;
		$result['hasFinanceRole'] = $hasFinanceRole;
		$result['hasPayableRole'] = $hasPayableRole;
		$result['hasTransactionVerifierRole'] = $hasTransactionVerifierRole;

		if ($auth->hasRole(Roles::ROLE_CREDIT_CARD)) {
			$result['hasCreditCardViewer'] = true;
		}

		return $result;
	}

    public function bookingSave($data)
    {
		/**
		 * @var \DDD\Domain\Booking\BookingTicket $rowBooking
		 * @var \Library\Authentication\BackofficeAuthenticationService $authenticationService
		 * @var \DDD\Service\Booking\BankTransaction $bankTransactionService
		 * @var ChannelManager $serviceChannelManager
		 * @var Inventory $serviceInventory
         * @var \DDD\Service\Fraud $serviceFraud
         * @var \DDD\Service\Partners $partnerService
         * @var \DDD\Service\Task $taskService
         * @var \DDD\Dao\Task\Task $taskDao
         * @var Logger $logger
         * @var \DDD\Dao\Booking\Booking $bookingDao
         * @var \DDD\Service\Booking\ReservationIssues $reservationIssuesService
		 */
        $authenticationService    = $this->getServiceLocator()->get('library_backoffice_auth');
        $cityService              = $this->getServiceLocator()->get('service_location');
        $serviceInventory         = $this->getServiceLocator()->get('service_apartment_inventory');
        $serviceFraud             = $this->getServiceLocator()->get('service_fraud');
        $taskService              = $this->getServiceLocator()->get('service_task');
        $taskDao                  = $this->getServiceLocator()->get('dao_task_task');
        $logger                   = $this->getServiceLocator()->get('ActionLogger');
        $bookingDao               = $this->getServiceLocator()->get('dao_booking_booking');
        $reservationIssuesService = $this->getServiceLocator()->get('service_booking_reservation_issues');

		$addToBlackList = isset($data['addToBlackList']) ? $data['addToBlackList'] : 0;
		unset($data['addToBlackList']);

        $bookingDao->setEntity(new \DDD\Domain\Booking\BookingTicket());

        $rowBooking = $bookingDao->getBookingTicketData((int)$data['booking_id']);

        $cub_status = ['status' => 'success'];
        $status     = 'success';
        $msg        = '';

        if (!$rowBooking) {
			return [
                'status'     => 'error',
                'msg'        => TextConstants::ERROR_ROW,
                'cub_status' => $cub_status,
			];
		}

		$hasFinanceRole = $authenticationService->hasRole(Roles::ROLE_RESERVATION_FINANCE);
		$hasCreditCardView = $authenticationService->hasRole(Roles::ROLE_CREDIT_CARD);

        $data['finance_paid_affiliate'] = isset($data['finance_paid_affiliate'])
            ? (int)$data['finance_paid_affiliate']
            : 0;

		$params = [
            'guest_first_name'   => Helper::stripTages($data['guest_name']),
            'guest_last_name'    => Helper::stripTages($data['guest_last_name']),
            'guest_email'        => Helper::stripTages($data['guest_email']),
            'secondary_email'    => Helper::stripTages($data['second_guest_email']),
            'guest_country_id'   => ($data['guest_country'] > 0) ? (int)$data['guest_country'] : null,
            'guest_city_name'    => Helper::stripTages($data['guest_city']),
            'guest_address'      => Helper::stripTages($data['guest_address']),
            'guest_zip_code'     => Helper::stripTages($data['guest_zipcode']),
            'guest_phone'        => Helper::stripTages($data['guest_phone']),
            'guest_travel_phone' => Helper::stripTages($data['guest_travel_phone']),
            'partner_ref'        => Helper::stripTages($data['booking_affiliate_reference']),
            'partner_settled'    => $data['finance_paid_affiliate'],
            'no_collection'      => (int)$data['finance_no_collection'],
            'apartel_id'         => (int)$data['apartel_id'],
            'occupancy'          => (int)$data['occupancy'],
            'ki_viewed'          => (int)$data['finance_key_instructions'],
            'model'              => (int)$data['model'],
		];

        if ($params['secondary_email'] == $params['guest_email']) {
            $params['secondary_email'] = null;
        }

        if ((int)$data['overbooking_status'] != $rowBooking->getOverbookingStatus()) {
            $changeOverbookingStatus = $this->changeOverbookingStatus((int)$data['booking_id'], (int)$data['overbooking_status']);

            if (!$changeOverbookingStatus) {

                if ((int)$data['overbooking_status'] != self::OVERBOOKING_STATUS_OVERBOOKED) {
                    $status = 'error';
                    $msg .= TextConstants::OVERBOOKING_STATUS_CHANGE_ERROR;
                } elseif ((int)$data['overbooking_status'] == self::OVERBOOKING_STATUS_OVERBOOKED) {
                    $status = 'error';
                    $msg .= TextConstants::OVERBOOKING_STATUS_CHANGE_NOT_OPEN_DAY;
                }

            }

            unset($data['overbooking_status']);
        }

        if (!empty($data['booking_arrival_time'])) {
            $params['guest_arrival_time'] = Helper::stripTages($data['booking_arrival_time']);
        }

        // if change partner
		if (isset($data['booking_partners']) && $data['booking_partners'] != $rowBooking->getPartnerId()) {
            $params['partner_id'] = $data['booking_partners'];
            $partnerService = $this->getServiceLocator()->get('service_partners');
            $partnerData = $partnerService->getPartnerDataForReservation($params['partner_id'], $rowBooking->getApartmentIdAssigned(), true);
            $params['partner_commission'] = $partnerData->getCommission();
            $params['partner_name'] = $partnerData->getPartnerName();
		}

		if (   isset($data['finance_booked_state'])
            && isset($data['finance_booked_state_changed'])
            && $data['finance_booked_state_changed'] == self::BOOKED_STATE_CHANGED
        ) {
			$params['arrival_status'] = (int)$data['finance_booked_state'];

			if ($data['finance_booked_state'] > 0) {
				$params['ki_viewed'] = 1;
			}

            $currentDateCity = $cityService->getCurrentDateCity($rowBooking->getApartmentCityId());

			switch ($data['finance_booked_state']) {
				case 1: // check in
					if (is_null($rowBooking->getActualArrivalDate())) {
						$params['arrival_date'] = $currentDateCity;
					}

					break;
				case 2: // check out
					if (is_null($rowBooking->getActualDepartureDate())) {
						$params['departure_date'] = $currentDateCity;
                        // change cleaning task start date
                        $taskService->changeTaskDate(
                            $rowBooking->getId(),
                            $rowBooking->getApartmentIdAssigned(),
                            $currentDateCity,
                            strtotime($rowBooking->getDate_to()),
                            Task::TYPE_CLEANING,
                            Task::TASK_IS_HOUSEKEEPING
                        );
					}

                    break;
                case 4: // no show
                    $taskService->createNoShowTask(
                        (int)$data['booking_id'],
                        $authenticationService->getIdentity(),
                        $rowBooking->getApartmentIdAssigned(),
                        $rowBooking->getDate_from()
                    );

					break;
			}
		}

		// Action Logging
        $this->setGinosiComment($rowBooking, $data, [
            'fin'    => $hasFinanceRole,
            'credit' => $hasCreditCardView,
		]);

		// Destination status can't be booked or unknown
		$weirdStatusList = [
			BookingService::BOOKING_STATUS_BOOKED,
			BookingService::BOOKING_STATUS_CANCELLED_PENDING,
		];

		// Booking status was changed and assumes Cancellation
		if (   isset($data['booking_statuses'])
            && in_array($rowBooking->getStatus(), $weirdStatusList)
            && !in_array($data['booking_statuses'], $weirdStatusList)
        ) {
            // Only if reservation is not canceled-moved to "bad" reservation
			if ($status != 'error') {
				if (in_array($data['booking_statuses'], [
                    BookingService::BOOKING_STATUS_CANCELLED_UNWANTED,
                    BookingService::BOOKING_STATUS_CANCELLED_FRAUDULANT,
                    BookingService::BOOKING_STATUS_CANCELLED_NOSHOW
                ])) {
					$data['send_mail'] = false;
				}

				$serviceInventory->processCancellation(
                    $rowBooking->getResNumber(),
                    true,
                    $data['send_mail'],
                    (int)$data['booking_statuses'],
                    $data['selected-email']
                );
			}

            $taskWhere = new Where();
            $taskWhere
                ->equalTo('task_type', Task::TYPE_CCCA)
                ->equalTo('res_id', $data['booking_id']);

            $taskDao->save(
                ['task_status' => Task::STATUS_CANCEL],
                $taskWhere
            );
		}

		if (   isset($data['booking_statuses'])
            && in_array($rowBooking->getStatus(), BookingService::$bookingCancelStatuses)
            && in_array($data['booking_statuses'], BookingService::$bookingCancelStatuses)
        ) {
			$params['status'] = $data['booking_statuses'];
		}

        $params['ccca_verified'] = (int)$data['ccca_verified'];

        if ($params['ccca_verified'] != $rowBooking->getCccaVerified()
            && $params['ccca_verified'] == BookingService::CCCA_VERIFIED
        ) {
            $reservationTasks = $taskDao->getReservationTasksByType($data['booking_id'], Task::TYPE_CCCA);

            if ($reservationTasks->count()) {
                $firstTaskId = $reservationTasks->current()->getId();

                $taskDao->update(
                    ['task_status' => Task::STATUS_DONE],
                    ['id' => $firstTaskId]
                );

                foreach ($reservationTasks as $task) {
                    if ($task->getId() != $firstTaskId) {
                        $taskDao->update(
                            ['task_status' => Task::STATUS_CANCEL],
                            ['id' => $task->getId()]
                        );
                    }
                }
            }
        }

		if ($hasFinanceRole) {
			$params['payment_settled'] = (int)$data['finance_reservation_settled'];

			if (   isset($data['finance_reservation_settled'])
                && $data['finance_reservation_settled'] == 1
                && $rowBooking->getPayment_settled() != 1
            ) {
				$params['settled_date'] = date("Y-m-d H:i:s");
                $reservationIssuesService->resolveReservationAllIssues($data['booking_id'], TRUE);
			} elseif (isset($data['finance_reservation_settled']) && $data['finance_reservation_settled'] == 0) {
				$params['settled_date'] = '0000-00-00 00:00:00';
            }
		} else {
			if ((int)$data['finance_no_collection'] == 1) {
                $params['payment_settled'] = 0;
                $params['settled_date']    = '0000-00-00 00:00:00';
			}
		}

		if ($hasCreditCardView && isset($data['finance_valid_card'])) {
			$params['funds_confirmed'] = (int)$data['finance_valid_card'];
		}

        // If occupancy was changed
        if ($data['occupancy'] != $rowBooking->getOccupancy()) {
            $logger->save(Logger::MODULE_BOOKING, $data['booking_id'], Logger::ACTION_OCUPANCY_CHANGE, [$rowBooking->getOccupancy(), $data['occupancy']]);
        }

        // save Data
		$bookingDao->save($params, ['res_number' => $data['booking_res_number']]);

		if (   isset($data['booking_statuses']) && $rowBooking->getStatus() != $data['booking_statuses'] ) {
            if ($data['booking_statuses'] == BookingService::BOOKING_STATUS_CANCELLED_FRAUDULANT) {
                $serviceFraud->saveFraudManual($data['booking_id']);
            } elseif ($data['booking_statuses'] == BookingService::BOOKING_STATUS_CANCELLED_UNWANTED && $addToBlackList) {
                $serviceFraud->saveFraudManual($data['booking_id'], false);
            }
		}

        $reservationIssuesService->checkReservationIssues($data['booking_id']);

		return [
            'status'     => $status,
            'msg'        => $msg,
            'cub_status' => $cub_status,
		];
	}

	/**
	 * Update last opened reservation agent's name in reservation ticket
	 * @param string $resNumber
	 */
	public function updateLastReservationAgent($resNumber)
    {
        $authService = $this->getServiceLocator()->get('library_backoffice_auth');
        $bookingDao  = $this->getServiceLocator()->get('dao_booking_booking');
        $userManager = $this->getServiceLocator()->get('dao_user_user_manager');

        $userId      = $authService->getIdentity()->id;
        $userData    = $userManager->getUserById($userId);

        if ($userData && ($userData->getDepartmentId() == TeamService::TEAM_CONTACT_CENTER || $userData->getDepartmentId() == TeamService::TEAM_FINANCE)) {
            $identity         = $authService->getIdentity();
            $reservationAgent = $identity->firstname . ' ' . $identity->lastname;
            $bookingDao->save(['last_agent' => $reservationAgent], ['res_number' => $resNumber]);
        }
	}

	/**
	 * @param int $reservationId
	 * @return array string
	 */
	public function getSumAndBalanc($reservationId)
    {
		/**
         * @var \DDD\Service\Booking\Charge $bookingChargeService
		 * @var \DDD\Service\Booking\BankTransaction $bankTransactionService
         */
		$bookingChargeService = $this->getServiceLocator()->get('service_booking_charge');
		$bankTransactionService = $this->getServiceLocator()->get('service_booking_bank_transaction');

		$ginosiCollectChargesSummary = $bookingChargeService->getChargesSummary($reservationId, Charge::CHARGE_MONEY_DIRECTION_GINOSI_COLLECT);
		$ginosiCollectChargesSummaryInApartmentCurrency = $ginosiCollectChargesSummary->getSummaryInApartmentCurrency();
		$ginosiCollectChargesSummaryInCustomerCurrency = $ginosiCollectChargesSummary->getSummaryInCustomerCurrency();

		$partnerCollectChargesSummary = $bookingChargeService->getChargesSummary($reservationId, Charge::CHARGE_MONEY_DIRECTION_PARTNER_COLLECT);
		$partnerCollectChargesSummaryInApartmentCurrency = $partnerCollectChargesSummary->getSummaryInApartmentCurrency();
		$partnerCollectChargesSummaryInCustomerCurrency = $partnerCollectChargesSummary->getSummaryInCustomerCurrency();

		$ginosiCollectTransactionsSummary = $bankTransactionService->getTransactionsSummary($reservationId, BankTransaction::TRANSACTION_MONEY_DIRECTION_GINOSI_COLLECT);
		$ginosiCollectTransactionsSummaryInApartmentCurrency = $ginosiCollectTransactionsSummary->getSummaryInApartmentCurrency();
		$ginosiCollectTransactionsSummaryInCustomerCurrency = $ginosiCollectTransactionsSummary->getSummaryInCustomerCurrency();

		$partnerCollectTransactionsSummary = $bankTransactionService->getTransactionsSummary($reservationId, BankTransaction::TRANSACTION_MONEY_DIRECTION_PARTNER_COLLECT);
		$partnerCollectTransactionsSummaryInApartmentCurrency = $partnerCollectTransactionsSummary->getSummaryInApartmentCurrency();
		$partnerCollectTransactionsSummaryInCustomerCurrency = $partnerCollectTransactionsSummary->getSummaryInCustomerCurrency();

		$result = [];
		$result['ginosiCollectChargesSummaryInApartmentCurrency'] = $ginosiCollectChargesSummaryInApartmentCurrency;
		$result['ginosiCollectChargesSummaryInCustomerCurrency'] = $ginosiCollectChargesSummaryInCustomerCurrency;
		$result['partnerCollectChargesSummaryInApartmentCurrency'] = $partnerCollectChargesSummaryInApartmentCurrency;
		$result['partnerCollectChargesSummaryInCustomerCurrency'] = $partnerCollectChargesSummaryInCustomerCurrency;
		$result['ginosiCollectTransactionsSummaryInApartmentCurrency'] = $ginosiCollectTransactionsSummaryInApartmentCurrency;
		$result['ginosiCollectTransactionsSummaryInCustomerCurrency'] = $ginosiCollectTransactionsSummaryInCustomerCurrency;
		$result['partnerCollectTransactionsSummaryInApartmentCurrency'] = $partnerCollectTransactionsSummaryInApartmentCurrency;
		$result['partnerCollectTransactionsSummaryInCustomerCurrency'] = $partnerCollectTransactionsSummaryInCustomerCurrency;

		$result['chargesSummaryInApartmentCurrency'] = $ginosiCollectChargesSummary->getTotalInApartmentCurrency() + $partnerCollectChargesSummary->getTotalInApartmentCurrency();
		$result['chargesSummaryInCustomerCurrency'] = $ginosiCollectChargesSummary->getTotalInCustomerCurrency() + $partnerCollectChargesSummary->getTotalInCustomerCurrency();

		$result['transactionsSummaryInApartmentCurrency'] = $ginosiCollectTransactionsSummaryInApartmentCurrency + $partnerCollectTransactionsSummaryInApartmentCurrency;
		$result['transactionsSummaryInCustomerCurrency'] = $ginosiCollectTransactionsSummaryInCustomerCurrency + $partnerCollectTransactionsSummaryInCustomerCurrency;

		$result['ginosiBalanceInApartmentCurrency'] = $ginosiCollectTransactionsSummaryInApartmentCurrency - $ginosiCollectChargesSummaryInApartmentCurrency;
		$result['ginosiBalanceInCustomerCurrency'] = $ginosiCollectTransactionsSummaryInCustomerCurrency - $ginosiCollectChargesSummaryInCustomerCurrency;

		$result['partnerBalanceInApartmentCurrency'] = $partnerCollectTransactionsSummaryInApartmentCurrency - $partnerCollectChargesSummaryInApartmentCurrency + $ginosiCollectChargesSummary->getCommissionSummaryInApartmentCurrency();
		$result['partnerBalanceInCustomerCurrency'] = $partnerCollectTransactionsSummaryInCustomerCurrency - $partnerCollectChargesSummaryInCustomerCurrency + $ginosiCollectChargesSummary->getCommissionSummaryInCustomerCurrency();

		return $result;
	}

    /**
     * @todo out of standard code, use constants, use camelCase syntax
     *
     * @param $check_in_date
     * @param $penalty_type
     * @param $ref_before
     * @return int
     */
    public function isPenaltyApplied($check_in_date, $penalty_type, $ref_before)
    {
		$bool = 0;

		if (2 == $penalty_type) {
			$bool = 1;
		} elseif (1 == $penalty_type) {
			$check_in_date = strtotime($check_in_date);
			$dif = $check_in_date - strtotime("now");
			$ref_before = $ref_before * 3600;

			if ($dif <  $ref_before || $dif < 0) {
				$bool = 1;
			}
		}

		return $bool;
	}

	public function calculateCancelationPeriod(
		$status,
		$cancelation_date,
		$co_date,
		$is_penalty_applied
	) {
		$co_date = strtotime($co_date);
		$dif = $co_date - strtotime("now");

		if ($status != 1) {
			$penalty_period = 'Canceled on ' . $cancelation_date;
		} elseif ($dif <= 0) {
			$penalty_period = 'Stayed';
		} elseif ($is_penalty_applied) {
			$penalty_period = 'Penalty Period';
		} else {
			$penalty_period = 'Flexible Period';
		}

		return $penalty_period;
	}

	private function removeSymbols($value)
    {
		return strtolower(preg_replace('/[^\p{L}\p{N}]/u', '', $value));
	}

    /**
     * @param \DDD\Domain\Booking\BookingTicket $booking
     * @param array $data
     * @param array $permission
     */
    private function setGinosiComment($booking, $data, $permission)
    {
        /**
         * @var Logger $logger
         */
        $logger = $this->getServiceLocator()->get('ActionLogger');

		if ($permission['credit']) {
			if ($booking->getFunds_confirmed() != $data['finance_valid_card']) {
                $logger->save(
                	Logger::MODULE_BOOKING,
                	$booking->getId(),
                	Logger::ACTION_BOOKING_CC_STATUS,
                	(int)$data['finance_valid_card'] + 1
                );
			}
		}

        if ($booking->isKiViewed() != (int)$data['finance_key_instructions']) {
            $logger->save(
            	Logger::MODULE_BOOKING,
            	$booking->getId(),
            	Logger::ACTION_KI_VIEWED,
            	(int)$data['finance_key_instructions']
            );
        }

        if ($booking->getApartelId() != (int)$data['apartel_id']) {
            $logger->save(
            	Logger::MODULE_BOOKING,
            	$booking->getId(),
            	Logger::ACTION_APARTEL_ID,
            	(int)$data['apartel_id']
            );
        }

        if ($booking->getPartnerSettled() != (int)$data['finance_paid_affiliate']) {
            $logger->save(
            	Logger::MODULE_BOOKING,
            	$booking->getId(),
            	Logger::ACTION_PARTNER_SETTLED,
            	(int)$data['finance_paid_affiliate']
            );
        }

        if ($booking->getNo_collection() != (int)$data['finance_no_collection']) {
            $logger->save(
            	Logger::MODULE_BOOKING,
            	$booking->getId(),
            	Logger::ACTION_NO_COLLECTION,
            	(int)$data['finance_no_collection']
            );
        }

        if ($booking->getCccaVerified() != (int)$data['ccca_verified']) {
            $logger->save(
                Logger::MODULE_BOOKING,
                $booking->getId(),
                Logger::ACTION_CCCA_VERIFIED,
                (int)$data['ccca_verified']
            );
        }

		if ($permission['fin']) {
            if ($booking->getPayment_settled() != (int)$data['finance_reservation_settled']) {
                $logger->save(
                	Logger::MODULE_BOOKING,
                	$booking->getId(),
                	Logger::ACTION_RESERVATION_SETTLED,
                	(int)$data['finance_reservation_settled']
                );
            }
		}

		if (isset($data['booking_statuses']) && $booking->getStatus() != $data['booking_statuses']) {
            $logger->save(
            	Logger::MODULE_BOOKING,
            	$booking->getId(),
            	Logger::ACTION_BOOKING_STATUSES,
            	Objects::getBookingStatusMapping()[$data['booking_statuses']]
            );
		}

		if (isset($data['finance_booked_state']) && $booking->getArrivalStatus() != $data['finance_booked_state']
            && ($booking->getArrivalStatus() != self::INSPECTED_STATE
                || ($booking->getArrivalStatus() == self::INSPECTED_STATE
                && $data['finance_booked_state'] != self::CHECKOUT_STATE))
            && $data['finance_booked_state_changed'] == self::BOOKED_STATE_CHANGED

        ) {
            $logger->save(Logger::MODULE_BOOKING, $booking->getId(), Logger::ACTION_CHECK_IN, $data['finance_booked_state']);
		}

        $msg = $data['booking_ginosi_comment'];
        if ($data['booking_ginosi_comment_team']) {
            $teamDao  = $this->getServiceLocator()->get('dao_team_team');
            $teamName = $teamDao->getTeamNameById($data['booking_ginosi_comment_team']);
            $msg .= '</br><b>' . $teamName . ' Notified</b>';
        }
		if ($data['booking_ginosi_comment'] != '') {
            $logId = $logger->save(
            	Logger::MODULE_BOOKING,
            	$booking->getId(),
                empty($data['booking_ginosi_comment_frontier']) ? Logger::ACTION_COMMENT : Logger::ACTION_HOUSEKEEPING_COMMENT,
                $msg
            );

            if ($data['booking_ginosi_comment_team']) {
                $logsTeamDao = $this->getServiceLocator()->get('dao_action_logs_logs_team');
                $logsTeamDao->save(['action_log_id' => $logId, 'team_id' => $data['booking_ginosi_comment_team']]);
            }
		}
	}

    public function sendMailFromBookingTicket($id, $num, $customerEmail)
    {
        /**
         * @var Logger $logger
         */
        $logger    = $this->getServiceLocator()->get('ActionLogger');
        $logAction = Logger::ACTION_BOOKING_EMAIL_EX;
        $cmd       = false;

        if ($num == 1) {
            $logAction = Logger::ACTION_BOOKING_EMAIL;
            $logValue = Logger::VALUE_EMAIL_GINOSI_RESERVATION;
            $cmd = 'ginosole reservation-email send-ginosi --id=' . escapeshellarg($id) . ' -v';
        } elseif ($num == 2) {
            $logAction = Logger::ACTION_BOOKING_EMAIL;
            $logValue = Logger::VALUE_EMAIL_GUEST_RESERVATION;
            $cmd = 'ginosole reservation-email send-guest --id=' . escapeshellarg($id) . ' --email= ' . $customerEmail . ' -v';
        } elseif ($num == 3) {
            $logAction = Logger::ACTION_BOOKING_EMAIL_EX;
            $logValue = 'Guest Key Instructions mail has been sent to ' . $customerEmail;
            $cmd = 'ginosole reservation-email send-ki --id=' . escapeshellarg($id) . ' --email= ' . $customerEmail . ' -bo -v';
        } elseif ($num == 4) {
            $logAction = Logger::ACTION_BOOKING_EMAIL_EX;
            $logValue = 'Guest Review Request mail has been sent to ' . $customerEmail;
            $cmd = 'ginosole reservation-email send-review --id=' . escapeshellarg($id) . ' --email= ' . $customerEmail. ' -v';
        }

        if ($cmd) {
            $output = shell_exec($cmd);

            if (strstr(strtolower($output), 'error')) {
                return false;
            }

            $logger->save(Logger::MODULE_BOOKING, $id, $logAction, $logValue);
        }

        return true;
    }

    /**
     * @param int|bool $reservationId
     * @return array
     */
    public function getReceiptData($reservationId = false)
    {
        /**
         * @var \DDD\Dao\Booking\Charge $chargeDao
         * @var \DDD\Dao\Booking\ChargeTransaction $transactionDao
         * @var \DDD\Dao\Booking\Booking $bookingDao
         */
        $chargeDao = $this->getServiceLocator()->get('dao_booking_charge');
        $transactionDao = $this->getServiceLocator()->get('dao_booking_change_transaction');
        $bookingDao = $this->getServiceLocator()->get('dao_booking_booking');

        if (!$reservationId) {
            return [
                'status' => 'error',
                'msg' => TextConstants::ERROR,
            ];
        }

        $reservationData = $bookingDao->getReceiptData($reservationId);

        if (!$reservationData) {
            return [
                'status' => 'error',
                'msg' => TextConstants::ERROR,
            ];
        }

        $charges = $chargeDao->getChargesByReservationId($reservationId, 1, false, true);
        $transactions = $transactionDao->getReservationTransactionsAllCurrency($reservationId);

        // Generate smarter charges list
        // We don't want customers to see two Tax charges of same type for same day!
        // Besides, we don't really need all of the info getChargesByReservationId() provides
        $smarterCharges = [];
        if ($charges && $charges->count()) {
            foreach ($charges as $charge) {
                if (!isset($smarterCharges[$charge->getAddons_type() . '-' . $charge->getReservationNightlyDate()])) {
                    $smarterCharges[$charge->getAddons_type() . '-' . $charge->getReservationNightlyDate()] = [
                        'id' => $charge->getId(),
                        'acc_amount' => $charge->getAcc_amount(),
                        'acc_currency' => $charge->getApartmentCurrency(),
                        'addon' => $charge->getAddon(),
                        'addons_value' => $charge->getAddons_value(),
                        'addons_type' => $charge->getAddons_type(),
                        'type' => $charge->getType(),
                        'location_join' => $charge->getLocation_join(),
                        'tax_type' => $charge->getTaxType(),
                        'reservation_nighly_date' => $charge->getReservationNightlyDate(),
                        'rate_name' => $charge->getRateName(),
                        'money_direction' => $charge->getMoneyDirection(),
                    ];
                } else {
                    $smarterCharges
                        [$charge->getAddons_type() . '-' . $charge->getReservationNightlyDate()]
                            ['acc_amount'] += $charge->getAcc_amount();
                    $smarterCharges
                        [$charge->getAddons_type() . '-' . $charge->getReservationNightlyDate()]
                            ['addons_value'] += $charge->getAddons_value();
                }
            }
        }

        return [
            'status'       => 'success',
            'reservation'  => $reservationData,
            'charges'      => $smarterCharges,
            'transactions' => $transactions,
            'today'        => date('d M Y'),
            'nightCount'   => Helper::getDaysFromTwoDate($reservationData['date_from'], $reservationData['date_to']),
        ];
    }

    /**
     * @param int|bool $reservationId
     * @return array
     */
    public function getSendReceipt($reservationId = false, $customEmail = null)
    {
        /**
         * @var Logger $logger
         */
        $logger = $this->getServiceLocator()->get('ActionLogger');
        $result = [
            'status' => 'error',
            'msg'    => TextConstants::ERROR_SEND_MAIL,
        ];

        try {
            if ($reservationId) {
                $bookingTicketDao = new \DDD\Dao\Booking\Booking($this->getServiceLocator(), '\ArrayObject');
                $ticketResult     = $bookingTicketDao->fetchOne(['id' => $reservationId], ['guest_first_name', 'guest_last_name', 'guest_email']);
                if ($ticketResult) {

                    $guestEmail = !empty($customEmail) ? $customEmail : $ticketResult['guest_email'];

                    $output = shell_exec('ginosole reservation-email send-receipt --id=' . $reservationId . ' --email=' . $guestEmail . ' -v');

                    if (!strstr(strtolower($output), 'error')) {
                        $guestName = $ticketResult['guest_first_name'] . ' ' . $ticketResult['guest_last_name'];

                        $logger->save(
                            Logger::MODULE_BOOKING,
                            $reservationId,
                            Logger::ACTION_RESERVATION_EMAIL_RECEIPT,
                            "Receipt has been sent to {$guestName} &lt;{$guestEmail}&gt;"
                        );

                        $result = [
                            'status' => 'success',
                            'msg'    => TextConstants::SUCCESS_SEND_MAIL,
                        ];
                    }
                }
            }
        } catch (\Exception $ex) {
            // do nothing
        }
        return $result;
    }

    /**
     * @param $data
     * @param bool $reservationId
     * @return array
     */
    public function validateAndCheckDiscountData($data, $reservationId = false)
    {
        $result = [
            'valid'   => false,
            'message' => ''
        ];

        try {

            if ($reservationId) {
                /**
                 * @var \DDD\Dao\Booking\Booking $reservationDao
                 */
                $reservationDao = $this->getServiceLocator()->get('dao_booking_booking');

                $reservationData = $reservationDao->getDataForDiscountValidationById($reservationId);
                $data = [
                    'email' => $reservationData->getGuestEmail(),
                    'aff_id' => $reservationData->getPartnerId()
                ];
            }

            $visitor = new Container('visitor');

            if (!isset($data['aff_id']) && !is_null($visitor->partnerId) && (int)$visitor->partnerId) {
                $data['aff_id'] = (int)$visitor->partnerId;
            }

            $emailValidator = new EmailAddress();
            $emailValidator->setOptions(['domain' => true]);

            // validate Email for ginosiks
            if (isset($data['aff_id']) && $data['aff_id'] == self::SECRET_DISCOUNT_AFFILIATE_ID) {
                if (!isset($data['email']) || empty($data['email'])) {
                    $result['message'] .= "Email field is not submitted.\n";
                } elseif (!$emailValidator->isValid($data['email'])) {
                    $result['message'] .= "Email not valid.\n";
                } else {

                    /**
                     * @var UserManager $userManager
                     */
                    $userManager = $this->getServiceLocator()->get('dao_user_user_manager');

                    $userRow = $userManager->getUserIdByEmailAddress($data['email']);

                    // validate User
                    if (!$userRow['id']) {
                        $result['message'] .= "Email does not match the Ginosi User.\n";
                    } elseif ($userRow['system'] != 0 || $userRow['disabled'] != 0) {
                        $result['message'] .= "User disabled or system.\n";
                    } else {
                        $result['email'] = strtolower($data['email']);
                    }
                }
            }

            // validate Affiliate Id
            if (!isset($data['aff_id']) || !is_numeric($data['aff_id'])) {
                $result['message'] .= "Affiliate Id field is not submitted.\n";
            } else {
                $result['aff_id'] = (int)$data['aff_id'];
            }

            // get Affiliate Discount Value
            if (isset($result['aff_id'])) {
                /**
                 * @var PartnersDAO $partnerDao
                 */
                $partnerDao = $this->getServiceLocator()->get('dao_partners_partners');
                $partnerData = $partnerDao->getPartnerNameAndDiscountById($result['aff_id']);

                // check Affiliate Discount Value
                if (empty($partnerData['discount']) && $partnerData['discount'] <= 0) {
                    $result['message'] .= "Affiliate does not have discounts.\n";
                } else {
                    $result['discount_value'] = $partnerData['discount'];
                    $result['partner_name']   = $partnerData['partner_name'];
                }
            }

            // final judgment
            if (empty($result['message'])) {
                $result['valid'] = true;
                unset($result['message']);
            }

        } catch (\Exception $e) {
            $this->gr2logException($e, 'Cannot validate and check reservation discount data', $data);
        }

        return $result;
    }

    /**
     * @param $reservationId
     * @param $dateFrom
     * @param $dateTo
     * @param bool $isGetInfo
     * @return array
     */
    public function changeReservationDate($reservationId, $dateFrom, $dateTo, $isGetInfo = false)
    {
        /**
         * @var ReservationNightly $reservationNightlyDao
         * @var \DDD\Service\Reservation\Main $reservationService
         * @var \DDD\Service\Reservation\RateSelector $rateSelector
         * @var \DDD\Dao\Apartment\Inventory $inventoryDao
         * @var \DDD\Dao\Booking\Booking $bookingDao
         */

        $inventoryDao          = $this->getServiceLocator()->get('dao_apartment_inventory');
        $reservationNightlyDao = $this->getServiceLocator()->get('dao_booking_reservation_nightly');
        $reservationService    = $this->getServiceLocator()->get('service_reservation_main');
        $rateSelector          = $this->getServiceLocator()->get('service_reservation_rate_selector');
        $bookingDao            = $this->getServiceLocator()->get('dao_booking_booking');

        $reservationData       = $bookingDao->getReservationDataForChangeDate($reservationId);
        $today                 = Helper::getCurrenctDateByTimezone($reservationData['timezone']);

        $bookingDao->setEntity(new \ArrayObject());

        // Data check
        if (!$reservationData
            || !$dateFrom || !ClassicValidator::validateDate($dateFrom, 'Y-m-d')
            || !$dateTo || !ClassicValidator::validateDate($dateTo, 'Y-m-d')
            || strtotime($dateFrom) >= strtotime($dateTo)
        ) {
            return ['status' => 'error', 'msg' => TextConstants::MODIFICATION_BAD_DATA_FOR_CHANGE];
        }

        $error = '';
        if ($reservationData['date_from'] != $dateFrom && strtotime($today) > strtotime($dateFrom)) {
            $error .= TextConstants::MODIFICATION_DATE_NOT_PAST;
        }

        if ($reservationData['status'] != BookingService::BOOKING_STATUS_BOOKED) {
            $error .= TextConstants::MODIFICATION_STATUS_BOOKED;
        }

        if ($reservationData['date_from'] == $dateFrom && $reservationData['date_to'] == $dateTo) {
            $error .= TextConstants::MODIFICATION_NO_INDICATED;
        }

        if (strtotime($today) > strtotime($dateTo)) {
            $error .= TextConstants::MODIFICATION_DATE_SHOULD_FUTURE;
        }

        if ($error) {
            return ['status' => 'error', 'msg' => $error];
        }

        // check is apartel
        $apartel = $reservationData['apartel_id'] && $reservationData['channel_res_id'] > 0 ? $reservationData['apartel_id'] : false;

        // New Date generation
        $resNightlyData = $reservationNightlyDao->fetchAll(['reservation_id' => $reservationId, 'status' => ReservationMainService::STATUS_BOOKED], [], 'date ASC');
        $existingNightlyData = $startRateApartment = $lastRateApartment = $existingDate = $newNightlyData = [];
        $existingNightCount = $resNightlyData->count();

        if (!$existingNightCount) {
            return ['status' => 'error', 'msg' => TextConstants::MODIFICATION_BAD_DATA_FOR_CHANGE];
        }

        foreach ($resNightlyData as $key => $night) {
            $existingNightlyData[$night['date']] = $night;
        }

        // existing date array
        $existingDate = array_keys($existingNightlyData);
        $startRateApartment = $existingNightlyData[current($existingDate)];
        $lastRateApartment = $existingNightlyData[end($existingDate)];

        $dateFromTime = strtotime($dateFrom);
        $dateToTime = strtotime($dateTo);

        while ($dateFromTime < $dateToTime) {
            $date = date('Y-m-d', $dateFromTime);

            if (in_array($date, $existingDate)) { // existing date
                $newFromData = $existingNightlyData[$date];
            } else {
                if (strtotime($date) < strtotime($reservationData['date_from'])) { // get rate data from start date
                    $newFromData = $startRateApartment;
                    $rateIdForChangeDate = $startRateApartment['rate_id'];
                } else { // get rate data from last date
                    $rateIdForChangeDate = $lastRateApartment['rate_id'];
                    $newFromData = $lastRateApartment;
                }

                if ($apartel) {
                    /**
                     * @var \DDD\Dao\Apartel\Rate $apartelRateDao
                     * @var \DDD\Dao\Apartel\Inventory $apartelInventoryDao
                     */
                    $apartelRateDao = $this->getServiceLocator()->get('dao_apartel_rate');
                    $apartelInventoryDao = $this->getServiceLocator()->get('dao_apartel_inventory');

                    // rate exist and active if not use fuzzy logic
                    if (!$apartelRateDao->checkRateExistAndActive($rateIdForChangeDate)) {
                        $newFromData = $rateSelector->getSelectorRate($reservationId, $date, $isGetInfo, $apartel);
                        $rateIdForChangeDate = $newFromData['rate_id'];
                    }

                    // check apartment availability
                    if (!$inventoryDao->checkApartmentAvailabilityApartmentDateList($newFromData['apartment_id'], [$date])) {
                        return ['status' => 'error', 'msg' => TextConstants::ERROR_NO_AVAILABILITY];
                    }

                    // check apartel availability and get price
                    $priceApartel = $apartelInventoryDao->getPriceByRateIdDate($rateIdForChangeDate, $date);

                    $newPrice = $priceApartel['price'];
                } else {
                    /** @var \DDD\Dao\Apartment\Rate $rateDao */
                    $rateDao = $this->getServiceLocator()->get('dao_apartment_rate');

                    // rate exist and active if not use fuzzy logic
                    if (!$rateDao->checkRateExistAndActive($rateIdForChangeDate)) {
                        $newFromData = $rateSelector->getSelectorRate($reservationId, $date, $isGetInfo);
                        $rateIdForChangeDate = $newFromData['rate_id'];
                    }

                    $changeDatePrice = $inventoryDao->fetchOne([
                        'rate_id' => $rateIdForChangeDate,
                        'date' => $date,
                        'availability' => 1], ['price']);

                    if (!$changeDatePrice) {
                        return ['status' => 'error', 'msg' => TextConstants::ERROR_NO_AVAILABILITY];
                    }

                    $newPrice = $changeDatePrice->getPrice();
                }

                $newFromData['price'] = $newPrice;
            }

            $newNightlyData[$date] = [
                'apartment_id' => $newFromData['apartment_id'],
                'room_id'      => $newFromData['room_id'],
                'rate_id'      => $newFromData['rate_id'],
                'rate_name'    => $newFromData['rate_name'],
                'price'        => $newFromData['price'],
                'date'         => $date,
                'capacity'     => $newFromData['capacity']
            ];
            $dateFromTime = strtotime('+1 day', $dateFromTime);
        }

        if (empty($newNightlyData)) {
            return ['status' => 'error', 'msg' => 'Bad Data for change Date'];
        }

        $changeDateProcess = $reservationService->changeDateForModification($newNightlyData, $reservationId, $isGetInfo, $apartel);

        // for view
        if ($isGetInfo && $changeDateProcess['status'] == 'success') {
            $addonDao = $this->getServiceLocator()->get('dao_booking_addons');
            $forViewInfo = []; $type = 'Other';
            foreach ($changeDateProcess['data'] as $date => $chargeType) {
                foreach ($chargeType['data'] as $row) {

                    if (isset($row['view_charge_name'])) {
                        $type = $row['view_charge_name'];
                    } elseif (isset($row['addons_type'])) {
                        $addonName = $addonDao->fetchOne(['id' => $row['addons_type']], ['name']);
                        if ($addonName) {
                            $type = $addonName->getName();
                        }
                    }

                    $forViewInfo[] = [
                        'date'      => $date,
                        'rate_name' => isset($row['rate_name']) ? $row['rate_name'] : '',
                        'price'     => ($chargeType['type'] == 'insert' ? $row['acc_amount'] : -1 * $row['acc_amount']),
                        'type'      => $type,
                    ];
                }
            }

            return ['status' => 'success', 'data' => $forViewInfo];
        }

        return $changeDateProcess;
    }

    /**
     * @param $reservationId
     * @param $status
     * @param $updateAvailability
     * @param $forceResolve
     * @return bool
     *
     * @author Tigran Petrosyan
     */
    public function changeOverbookingStatus($reservationId, $status, $updateAvailability = true, $forceResolve = false)
    {
        /**
         * @var Logger $logger
         * @var ReservationMainService $reservationMainService
         */
        $logger = $this->getServiceLocator()->get('ActionLogger');

        if ($status == self::OVERBOOKING_STATUS_RESOLVED || $status == self::OVERBOOKING_STATUS_OVERBOOKED) {
            /**
             * @var \DDD\Dao\Booking\Booking $reservationDao
             */
            $reservationDao = $this->getServiceLocator()->get('dao_booking_booking');
            $reservationMainService = $this->getServiceLocator()->get('service_reservation_main');

            // check availability if status change to resolved
            if (!$forceResolve && $status == self::OVERBOOKING_STATUS_RESOLVED) {
                $reservationData = $reservationDao->getReservationDataForResolved($reservationId);
                if (!$reservationData) {
                    return false;
                }

                /** @var \DDD\Dao\Apartment\inventory $apartmentInventoryDao */
                $apartmentInventoryDao = $this->getServiceLocator()->get('dao_apartment_inventory');
                $checkAvailability = $apartmentInventoryDao->checkApartmentAvailability($reservationData['apartment_id'], $reservationData['date_from'], $reservationData['date_to']);
                if(!$checkAvailability) {
                    return false;
                }
            }

            // general case save at first
            if ($status == self::OVERBOOKING_STATUS_RESOLVED) { // !$checker
                $reservationDao->save(
                    ['overbooking_status' => $status],
                    ['id' => $reservationId]
                );
            }

            $logger->save(
                Logger::MODULE_BOOKING,
                $reservationId,
                Logger::ACTION_OVERBOOKING_STATUS_CHANGE,
                $status
            );

            $result = true;
            if ($updateAvailability) {
                if ($status == self::OVERBOOKING_STATUS_RESOLVED) {
                    $result = $reservationMainService->resolveOverbooking($reservationId, $updateAvailability);
                } else if ($status == self::OVERBOOKING_STATUS_OVERBOOKED) {
                    $result = $reservationMainService->markOverbooked($reservationId, $updateAvailability);
                }
            }

            // overbooking case, while modifying dates, save last
            if ($status == self::OVERBOOKING_STATUS_OVERBOOKED) { // $checker
                $reservationDao->save(
                    ['overbooking_status' => $status],
                    ['id' => $reservationId]
                );
            }

            return $result;
        } else {
            return false;
        }
    }

    public function changeArrivalStatus($resId, $status)
    {
        /**
         * @var Logger $logger
         * @var \DDD\Service\Task $taskService
         * @var \DDD\Dao\Task\Type $taskTypeDao
         * @var \DDD\Domain\Booking\Booking $bookingRow
         * @var \DDD\Service\Location $cityService
         */
        $logger      = $this->getServiceLocator()->get('ActionLogger');
        $cityService = $this->getServiceLocator()->get('service_location');
        $bookingDao  = $this->getServiceLocator()->get('dao_booking_booking');
        $taskService = $this->getServiceLocator()->get('service_task');
        $auth        = $this->getServiceLocator()->get('library_backoffice_auth');

        $user = $auth->getIdentity();

        $bookingRow  = $bookingDao->fetchOne(['id' => $resId]);
        $currentDate = $cityService->getCurrentDateCity($bookingRow->getApartmentCityId());
        $oldStatus   = $bookingRow->getArrivalStatus();

        if ($status != $oldStatus) {
            if ($status != self::BOOKING_ARRIVAL_STATUS_INSPECTED)
                $logger->save(Logger::MODULE_BOOKING, $resId, Logger::ACTION_CHECK_IN, $status);

            $params = ['arrival_status' => $status];

            switch ($status) {
                case self::BOOKING_ARRIVAL_STATUS_CHECKED_IN:
                    if ($bookingRow->getActualArrivalDate() == null) {
                        $params['arrival_date'] = $currentDate;
                    }
                    break;
                case self::BOOKING_ARRIVAL_STATUS_CHECKED_OUT:
                    if ($bookingRow->getActualDepartureDate() == null) {
                        $params['departure_date'] = $currentDate;
                    }
                    break;

                case self::BOOKING_ARRIVAL_STATUS_NO_SHOW:
                    $taskService->createNoShowTask(
						$resId, $user, $bookingRow->getApartmentIdAssigned(),
						$bookingRow->getDateFrom()
					);
                    break;
            }

            $bookingDao->save($params, ['id' => $resId]);
        }

        return true;
    }

    /**
     *
     * @param int $reservationId
     * @return bool
     */
    public function markAsUnsettledReservationById($reservationId)
    {
        try {
            /**
             * @var \DDD\Dao\Booking\Booking $bookingDao
             */
            $bookingDao = $this->getServiceLocator()->get('dao_booking_booking');
            $bookingDao->setEntity(new \DDD\Domain\Booking\BookingTicket());

            $bookingData = $bookingDao->getBookingTicketData($reservationId);

            if (($bookingData->getPartnerSettled() == 1) && ($bookingData->getPayment_settled() == 1)) {
                $unsettledData = [
                    'partner_settled' => 0,
                    'payment_settled' => 0,
                ];
                $logMessagePrefix = 'Partner and Customer';
            } elseif ($bookingData->getPayment_settled() == 1) {
                $unsettledData = [
                    'payment_settled' => 0
                ];
                $logMessagePrefix = 'Customer';
            } elseif ($bookingData->getPartnerSettled() == 1) {
                $unsettledData = [
                    'partner_settled' => 0
                ];
                $logMessagePrefix = 'Partner';
            } else {
                return FALSE;
            }

            $bookingDao->save(
                $unsettledData,
                ['id' => $reservationId]
            );

            /* @var $auth \Library\Authentication\BackofficeAuthenticationService */
            $auth = $this->getServiceLocator()->get('library_backoffice_auth');
            $boUserName = $auth->getIdentity()->firstname . ' ' . $auth->getIdentity()->lastname;

            $logger = $this->getServiceLocator()->get('ActionLogger');

            $logger->save(
                Logger::MODULE_BOOKING,
                $reservationId,
                Logger::ACTION_RESERVATION_UNSETTLED,
                $logMessagePrefix . ' settled unchecked because of a change made by ' . $boUserName
            );

            return TRUE;
        } catch (\Exception $e) {
            $this->gr2logException($e, 'Cannot mark as unsettled reservation', [
                'reservation_id' => $reservationId
            ]);

            return FALSE;
        }
    }

    public function getPartnerDao()
    {
		if ($this->_partnerDao === null) {
			$this->_partnerDao = new \DDD\Dao\Partners\Partners($this->getServiceLocator(), 'DDD\Domain\Partners\PartnerBooking');
		}

		return $this->_partnerDao;
	}

    public static function getBadEmail()
    {
        return [
            'noemail@ginosi.com',
            'nomail@ginosi.com',
            'nomail@gmail.com',
            'resteam@ginosi.com',
            'reservations@ginosi.com'
        ];
    }

    /**
     * @param $resId
     * @param $pin
     * @param $usages
     * @param bool|false $getNull
     * @return array
     */
    public function getLockByReservation($resId, $pin, $usages, $getNull = false)
    {
        /**
         * @var \DDD\Dao\Booking\Booking $bookingDao
         */
        $bookingDao = $this->getServiceLocator()->get('dao_booking_booking');
        $bookingDao->setEntity(new \DDD\Domain\Booking\BookingTableRow());

        $response = [];

        foreach ($usages as $usage) {
            /*
            switch ($usage) {
                case LockService::USAGE_APARTMENT_TYPE:
                    $selectedDb = 'apartments';
                    break;
                case LockService::USAGE_BUILDING_TYPE:
                    $selectedDb = 'building';
                    break;
                case LockService::USAGE_PARKING_TYPE:
                    $selectedDb = 'parking';
                    break;
            }*/

            $lockData = $bookingDao->getLockByReservation($resId, $usage);

            if ($lockData) {

                $lockData = iterator_to_array($lockData);
                $lockType = $lockData[0]['type_id'];
                $timezone = $lockData[0]['timezone'];
                $lockCode = null;

                switch ($lockType) {
                    case LockService::SETTING_TYPE_LOCK_BOX:
                        $time     = new \DateTime(null, new \DateTimeZone($timezone));
                        $currentMonth = $time->format('F');

                        foreach ($lockData as $row) {
                            if ($currentMonth == $row['name']) {
                                $lockCode['type'] = LockService::SETTING_TYPE_LOCK_BOX;
                                $lockCode['code'] = $row['value'];
                            }
                        }
                        break;
                    case LockService::SETTING_TYPE_PIN:
                        foreach ($lockData as $value) {
                            if ($value['setting_item_id'] == LockService::TYPE_PIN_MASTER_PREFIX) {
                                $prefix = $value['value'];
                            }

                            if ($value['setting_item_id'] == LockService::TYPE_PIN_MASTER_SUFFIX) {
                                $suffix = $value['value'];
                            }
                        }

                        $lockCode['type'] = LockService::SETTING_TYPE_PIN;
                        $lockCode['code'] = $prefix . $pin . $suffix;
                        break;
                    case LockService::SETTING_TYPE_ASC:
                        foreach ($lockData as $value) {
                            if (!$getNull || !empty($value['value'])) {
                                $lockCode['type'] = LockService::SETTING_TYPE_ASC;
                                $lockCode['code'] = $value['value'];
                            }
                        }
                        break;
                    case LockService::SETTING_TYPE_MECHANICAL:
                        if (!$getNull) {
                            $lockCode['type'] = LockService::SETTING_TYPE_MECHANICAL;
                            $lockCode['code'] = 'Mechanical Key';
                        }
                        break;
                    case LockService::SETTING_TYPE_NONE:
                        if (!$getNull) {
                            $lockCode['type'] = LockService::SETTING_TYPE_NONE;
                            $lockCode['code'] = 'Free Entrance';
                        }
                        break;
                    default:
                        $lockCode['type'] = LockService::SETTING_TYPE_NONE;
                        $lockCode['code'] = 'Free Entrance';
                }

                $response[$usage] = $lockCode;
            }
        }
        return $response;
    }

    /**
     * @param $reservationNumber string
     * @return int
     */
    public function getReservationIdByReservationNumber($reservationNumber)
    {
        /**
         * @var \DDD\Dao\Booking\Booking $reservationsDao
         */
        $reservationsDao = $this->getServiceLocator()->get('dao_booking_booking');

        /**
         * @var \DDD\Domain\Booking\Booking $reservation
         */
        $reservation = $reservationsDao->fetchOne(
            ['res_number' => $reservationNumber],
            ['id']
        );

        return $reservation->getId();
    }

    /**
     * @param array $data
     * @throws \RuntimeException
     */
    public function updateReservationDetails($data)
    {
        /**
         * @var \DDD\Dao\Booking\Booking $reservationsDao
         * @var Notifications $notificationService
         * @var Team $teamService
         */
        $reservationsDao = $this->getServiceLocator()->get('dao_booking_booking');
        $notificationService = $this->getServiceLocator()->get('service_notifications');
        $teamService = $this->getServiceLocator()->get('service_team_team');
        $reservationsDao->setEntity(new \ArrayObject());
        $bookingData = [];
        $isBooking = function($input) {
            return preg_match('/@guest.booking.com$/i', $input);
        };

        $reservation = $reservationsDao->fetchOne(['ki_page_hash' => $data['code']], [
            'guest_email', 'secondary_email', 'guest_first_name', 'guest_last_name'
        ]);

        if (!$reservation) {
            throw new \RuntimeException('Reservation not found.');
        }

        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            throw new \RuntimeException('Email is invalid.');
        }

        if (empty($reservation['guest_email'])) {
            $bookingData = ['guest_email' => $data['email']];
        } else {
            if (empty($reservation['secondary_email'])) {
                $bookingData = [
                    'guest_email' => $data['email'],
                    'secondary_email' => $reservation['guest_email'],
                ];
            } else {
                if (preg_match('/@guest.booking.com$/i', $reservation['secondary_email'])) {
                    $bookingData['secondary_email'] = $data['email'];
                } else {
                    if (preg_match('/@guest.booking.com$/i', $reservation['guest_email'])) {
                        $bookingData['guest_email'] = $data['email'];
                    } else {
                        $bookingData = [
                            'guest_email' => $data['email'],
                            'secondary_email' => $reservation['guest_email'],
                        ];
                    }
                }
            }
        }

        if (!empty($data['phone'])) {
            $bookingData['guest_travel_phone'] = strip_tags($data['phone']);
        }

        $reservationsDao->save($bookingData, ['ki_page_hash' => $data['code']]);

        if (!empty($data['subscribe'])) {
            $mailChimpService = $this->getServiceLocator()->get('MailChimp');
            $config = $this->getServiceLocator()->get('config');
            $subscribersListId = $config['mail_chimp']['subscribers']['list_id'];

            $member = $mailChimpService->getListMember($subscribersListId, $data['email']);

            $guest = (object)[
                'firstName' => $reservation['guest_first_name'],
                'lastName' => $reservation['guest_last_name'],
                'email' => $data['email']
            ];

            if (!$member) {
                $response = $mailChimpService->addMemberToList($subscribersListId, $guest);

                if (!$response || !$response->id) {
                    throw new \RuntimeException('Unable to add guest into MailChimp list.');
                }
            } else {
                if ($member->status !== 'subscribed'
                    || $member->merge_fields->FNAME !== $guest->firstName
                    || $member->merge_fields->LNAME !== $guest->lastName
                ) {
                    $response = $mailChimpService->updateMemberDataInList($subscribersListId, $guest);

                    if (!$response || !$response->id) {
                        throw new \RuntimeException('Unable to save guest details in MailChimp.');
                    }
                }
            }
        }
    }
}
