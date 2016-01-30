<?php

namespace DDD\Service\Reservation;

use CreditCard\Service\Card;
use DDD\Dao\Apartment\Inventory;
use DDD\Dao\Booking\Booking;
use DDD\Dao\Booking\ReservationNightly;
use DDD\Service\Task;
use DDD\Service\Apartment\Rate;
use DDD\Service\Availability;
use DDD\Domain\Apartment\ProductRate\Penalty as PenaltyDomain;
use DDD\Service\Booking\BookingTicket;
use DDD\Service\ChannelManager;
use DDD\Service\Customer;
use DDD\Service\PenaltyCalculation;
use DDD\Service\ServiceBase;
use DDD\Service\User as UserService;

use Library\ActionLogger\Logger;
use Library\Constants\Objects;
use Library\Constants\TextConstants;
use Library\Finance\Finance;
use Library\Utility\Helper;
use Library\Utility\Key;

/**
 * Class Main
 * @package DDD\Service\Reservation
 */

class Main extends ServiceBase
{
    const STATUS_BOOKED = 1;
    const STATUS_CANCELED = 2;

    /**
     * @param array $data
     * @param $otherInfo
     * @param bool $updateCubilis
     * @return bool|int
     */
    public function registerReservation($data = [], $otherInfo, $updateCubilis = false)
    {
        // bad situation if empty data
        if (empty($data)) {
            $this->gr2err("Empty data In reservation");
            return false;
        }

        /**
         * @var Booking $bookingDao
         * @var Logger $logger
         * @var \DDD\Dao\ChannelManager\ReservationIdentificator $daoReservationIdentificator
         * @var ReservationNightly $reservationNightlyDao
         * @var Availability $availabilityService
         * @var WorstCXLPolicySelector $policyService
         * @var \DDD\Service\Booking $bookingService
         * @var \DDD\Service\Customer $customerService
         * @var \DDD\Service\task $taskService
         */
        $bookingDao             = $this->getServiceLocator()->get('dao_booking_booking');
        $reservationNightlyDao  = $this->getServiceLocator()->get('dao_booking_reservation_nightly');
        $logger                 = $this->getServiceLocator()->get('ActionLogger');
        $availabilityService    = $this->getServiceLocator()->get('service_availability');
        $policyService          = $this->getServiceLocator()->get('service_reservation_worst_cxl_policy_selector');
        $bookingService         = $this->getServiceLocator()->get('service_booking');
        $customerService        = $this->getServiceLocator()->get('service_customer');
        $taskService            = $this->getServiceLocator()->get('service_task');

        for (
            $pin = Key::generateRandomKeyCode();

            false !== $bookingService->checkDuplicateDoorCode(
                $pin,
                $data['acc_city_id'],
                $data['date_from']
            );
        ) {
            $pin = Key::generateRandomKeyCode();
        }

        $data['pin'] = $pin;

        $ccIssue = $emailOptions = $rateIds = [];

        // #cc_source
        // Create Customer and CC data
        $customerData = $data['customer_data'];
        unset($data['customer_data']);

        $emailAddress = $customerData['email'];
        $customer = $customerService->createCustomer($emailAddress);
        $customerId = $customer->getId();
        $data['customer_id'] = $customerId;
        $customerData['customer_id'] = $customerId;

        // remarks
        $remarks = null;
        if (isset($data['remarks']) && !empty(trim($data['remarks']))) {
            $remarks = trim($data['remarks']);
        }
        unset($data['remarks']);

        $customerIdentityForceData = [];
        if (isset($data['ip_address'])) {
            $customerIdentityForceData['ip_address'] = $data['ip_address'];
            unset($data['ip_address']);
        }

        // Save Reservation Date
        $reservationId = $bookingDao->save($data);

        if (!$reservationId) {
            $this->gr2err("Bad Process In Save Reservation", [
                'res_number' => $data['res_number'],
            ]);
            return false;
        }

        //if it is last minute reservation create extra key fob task for
        $taskService->createExtraTaskForLastMinuteReservation($data['apartment_id_assigned'], $reservationId);

        // create log for remarks
        if (!is_null($remarks)) {
            $logger->save(
                Logger::MODULE_BOOKING,
                $reservationId,
                Logger::ACTION_COMMENT,
                Helper::stripTages($remarks),
                UserService::USER_GUEST
            );
        }

        $apartmentId = $data['apartment_id_assigned'];

        // Check status
        $nightlyStatus = self::STATUS_BOOKED;

        // Save nightly data
        $ratesData = $otherInfo['ratesData'];
        foreach ($ratesData as $night) {
            if (!in_array($night['rate_id'], $rateIds)) {
                array_push($rateIds, $night['rate_id']);
            }

            $reservationNightlyDao->save([
                'reservation_id' => $reservationId,
                'apartment_id'   => $night['apartment_id'],
                'room_id'        => $night['room_id'],
                'rate_id'        => $night['rate_id'],
                'rate_name'      => $night['rate_name'],
                'price'          => $night['price'],
                'date'           => $night['date'],
                'capacity'       => $night['capacity'],
                'status'         => $nightlyStatus,
            ]);
        }

        // get reservation price
        $reservationPrice = $reservationNightlyDao->getReservationPrice($reservationId);

        // get rate names and capacity
        $getRateNameCapacity = $reservationNightlyDao->getRatesNameCapacity($reservationId);

        // get apartel id
        $apartel = isset($otherInfo['apartel']['apartel_id']) && $otherInfo['apartel']['apartel_id'] ? $otherInfo['apartel']['apartel_id'] : false;

        // set identificator
        if ($apartel && isset($otherInfo['identificator'])) {
            $daoReservationIdentificator = $this->getServiceLocator()->get('dao_channel_manager_reservation_identificator');
            $daoReservationIdentificator->save([
                'channel_res_id' => $otherInfo['identificator']['channel_res_id'],
                'reservation_id' => $reservationId,
                'room_id' => $otherInfo['identificator']['roomId'],
                'rate_id' => $otherInfo['identificator']['rateId'],
                'date_from' => $otherInfo['identificator']['dateFrom'],
                'date_to' => $otherInfo['identificator']['dateTo'],
                'guest_name' => trim($otherInfo['identificator']['guestName']),
            ]);
        }

        // get best penalty policy
        $policyData = $policyService->select($rateIds, $reservationPrice, $reservationId, true, $apartel);

        // Set price, penalty policy, capacity, rate_name
        $reservationSetParams = [
            'price'                   => $reservationPrice,
            'is_refundable'           => $policyData['is_refundable'],
            'penalty'                 => $policyData['penalty'],
            'penalty_fixed_amount'    => $policyData['penalty_fixed_amount'],
            'refundable_before_hours' => $policyData['refundable_before_hours'],
            'penalty_val'             => $policyData['penalty_val'],
            'man_count'               => $getRateNameCapacity['capacity'],
            'rate_name'               => $getRateNameCapacity['rates_name'],
        ];

        $bookingDao->save($reservationSetParams, ['id' => $reservationId]);

        // Update availability by date range
        $availability = $otherInfo['availability'];

        // check overbooking
        $isOverbooking = false;
        if (isset($data['overbooking_status']) && $data['overbooking_status']) {
            $isOverbooking = true;
        }

        // if not overbooking update availability
        if (!$isOverbooking) {
            // change apartment availability and sync cubilis
            $availabilityService->updateAvailabilityAllPartForApartment($reservationId, $availability, $updateCubilis);

            // change apartel availability and sync cubilis if this apartment is apartel
            $availabilityService->updateAvailabilityAllPartForApartel($reservationId, $updateCubilis);
        } else {
            // update channel
            $availabilityService->updateChannelByReservationId($reservationId);
        }

        // Overbooking Reservation
        if ($isOverbooking) {
            $emailOptions['overbooking'] = true;
            $logger->save(
                Logger::MODULE_BOOKING,
                $reservationId,
                Logger::ACTION_OVERBOOKING_STATUS_CHANGE,
                $data['overbooking_status']
            );
        }

        $logger->save(
            Logger::MODULE_BOOKING,
            $reservationId,
            Logger::ACTION_BOOKING_PARTNER_COMMISSION,
            'Partner Commission ' . $data['partner_commission'] . '%'
        );

        // Send CC Details if one has provided
        if (isset($otherInfo['cc_provided']) && $otherInfo['cc_provided']) {
            $ccIssue['cc_provided'] = true;
            $emailOptions['cc_provided'] = true;

            /**
             * @var Card $cardService
             */
            $cardService = $this->getServiceLocator()->get('service_card');
            $cardService->processCreditCardData($customerData);
        } else {
            $ccIssue['cc_provided'] = false;
        }

        if (isset($otherInfo['no_send_guest_mail'])) {
            $emailOptions['no_send_guest_mail'] = true;
        }

        /**
         * @var \DDD\Service\Booking\ReservationIssues $reservationIssuesService
         */
        $reservationIssuesService = $this->getServiceLocator()->get('service_booking_reservation_issues');

        // Check Issues
        $reservationIssuesService->checkReservationIssues($reservationId, $ccIssue);

        // Create Customer Identity
        $customerService->saveCustomerIdentityForReservation($reservationId, $customerIdentityForceData);

        // if missing rate create task
        if (isset($otherInfo['rateMissingTask'])) {
            $taskService->createAutoTaskForMissingRate($reservationId);
        }

        // Send Email
        $this->sendReservationEmail($reservationId, 'reservation', $emailOptions, $apartel);

        // log end
        $this->gr2info("Reservation successfully created", [
            'cron'               => 'ChannelManager',
            'apartment_id'       => $apartmentId,
            'reservation_id'     => $reservationId,
            'reservation_number' => $data['res_number']
        ]);
        return $reservationId;
    }

    /**
     * @param array $data
     * @param \DDD\Domain\Booking\ChannelReservation $bookingDomain
     * @param $otherInfo
     * @param bool $isOverbooking
     * @return bool
     */
    public function modifyReservation($data = [], $bookingDomain, $otherInfo, $isOverbooking = false)
    {
        /**
         * @var \DDD\Dao\ChannelManager\ReservationIdentificator $daoReservationIdentificator
         * @var Booking $bookingDao
         * @var Logger $logger
         * @var Availability $availabilityService
         * @var \DDD\Service\task $taskService
         */

        if (empty($data) || !$bookingDomain) {
            $this->gr2err("Empty data In Modification reservation");
            return false;
        }

        $bookingDao = $this->getServiceLocator()->get('dao_booking_booking');
        $logger = $this->getServiceLocator()->get('ActionLogger');
        $taskService = $this->getServiceLocator()->get('service_task');

        $reservationId = $bookingDomain->getId();

        // Modification: Customer/CC Creation

        $ccIssue = $emailOptions = [];

        // Send CC Details if one has provided
        if ($otherInfo['cc_provided'] == true) {
            $ccIssue['cc_provided'] = true;

            $customerData = $data['customer_data'];
            $customerId = $this->changeCCForModification($bookingDomain, $customerData);
            $data['customer_id'] = $customerId;
        } else {
            $ccIssue['cc_provided'] = false;
        }
        unset($data['customer_data']);

        if (isset($otherInfo['send_payment_modify'])) {
            $emailOptions['send_payment_modify'] = true;
        }

        /**
         * @var \DDD\Service\Booking\ReservationIssues $reservationIssuesService
         */
        $reservationIssuesService = $this->getServiceLocator()->get('service_booking_reservation_issues');

        $reservationIssuesService->checkReservationIssues($reservationId, $ccIssue);

        $apartmentId = $otherInfo['apartment_id'];

        // get apartel id
        $apartel = isset($otherInfo['apartel']['apartel_id']) && $otherInfo['apartel']['apartel_id'] ? $otherInfo['apartel']['apartel_id'] : false;

        // set identificator
        if ($apartel && isset($otherInfo['identificator'])) {
            $daoReservationIdentificator = $this->getServiceLocator()->get('dao_channel_manager_reservation_identificator');
            $daoReservationIdentificator->save([
                'date_from' => $otherInfo['identificator']['dateFrom'],
                'date_to' => $otherInfo['identificator']['dateTo'],
                'room_id' => $otherInfo['identificator']['roomId'],
                'rate_id' => $otherInfo['identificator']['rateId'],
                'guest_name' => trim($otherInfo['identificator']['guestName']),
            ], ['reservation_id' => $reservationId]);
        }

        if ($data['date_from'] != $bookingDomain->getDateFrom() || $data['date_to'] != $bookingDomain->getDateTo()) {
            // Rates data parsing
            $ratesData = $otherInfo['ratesData'];
            $ratesForChangeDate = $this->changeDateForModification($ratesData, $reservationId, false, $apartel, $isOverbooking);
            if ($ratesForChangeDate['status'] == 'error') {
                return false;
            }
        }

        $remarks = null;
        if (isset($data['remarks']) && !empty(trim($data['remarks']))) {
            $remarks = trim($data['remarks']);
        }

        unset($data['remarks']);

        if (isset($data['ip_address'])) {
            unset($data['ip_address']);
        }

        // Update reservation
        $bookingDao->save($data, ['id' => $reservationId]);

        // anyway update channel
        $availabilityService = $this->getServiceLocator()->get('service_availability');
        $availabilityService->updateChannelByReservationId($reservationId);

        // Reservation modified
        $reservationModifiedMessage = 'Modification received from Channel Manager and processed successfully';
        if ($data['date_from'] != $bookingDomain->getDateFrom() || $data['date_to'] != $bookingDomain->getDateTo()) {
            $reservationModifiedMessage .= ' : <br>';
            $thereIsAlreadyDateChange = false;

            if ($data['date_from'] < $bookingDomain->getDateFrom()) {
                $thereIsAlreadyDateChange = true;
                $oldDateFromMinusOneDay = date('Y-m-d', strtotime('-1 day', strtotime($bookingDomain->getDateFrom())));

                //change range is one day
                if ($oldDateFromMinusOneDay == $data['date_from']) {
                    $reservationModifiedMessage .= 'Dates modified: Added new date ' . $oldDateFromMinusOneDay;
                } else {
                    $reservationModifiedMessage .= 'Modified dates: Added new dates from ' . $data['date_from'] .
                        ' to ' . $oldDateFromMinusOneDay;
                }
            }

            if ($data['date_from'] > $bookingDomain->getDateFrom()) {
                if ($thereIsAlreadyDateChange){
                    $reservationModifiedMessage .= ' , ';
                }

                $thereIsAlreadyDateChange = true;
                $newDateMinusOneDay = date('Y-m-d', strtotime('-1 day', strtotime($data['date_from'])));

                if ($newDateMinusOneDay == $bookingDomain->getDateFrom()) {
                    $reservationModifiedMessage .= 'Modified dates: Updated with penalty and opened availability for ' . $newDateMinusOneDay;
                } else {
                    $reservationModifiedMessage .= 'Modified dates: Updated with penalty and opened availability from ' .
                        $bookingDomain->getDateFrom() . ' to ' . $newDateMinusOneDay;
                }

            }
            if ($data['date_to'] > $bookingDomain->getDateTo()) {
                $realNewDateTo = date('Y-m-d', strtotime('-1 day', strtotime($data['date_to'])));
                $realOldDateTo = date('Y-m-d', strtotime('-1 day', strtotime($bookingDomain->getDateTo())));

                if ($thereIsAlreadyDateChange){
                    $reservationModifiedMessage .= ' , ';
                }

                $thereIsAlreadyDateChange = true;
                $oldDateFromPlusOneDay = date('Y-m-d', strtotime('+1 day', strtotime($realOldDateTo)));

                if ($oldDateFromPlusOneDay == $realNewDateTo) {
                    $reservationModifiedMessage .= 'Modified dates: Added new date ' . $realNewDateTo;
                } else {
                    $reservationModifiedMessage .= 'Modified dates: Added new dates from ' . $oldDateFromPlusOneDay .
                        ' to ' . $realNewDateTo;
                }
            }
            if ($data['date_to'] < $bookingDomain->getDateTo()) {
                $realNewDateTo = date('Y-m-d', strtotime('-1 day', strtotime($data['date_to'])));
                $realOldDateTo = date('Y-m-d', strtotime('-1 day', strtotime($bookingDomain->getDateTo())));

                if ($thereIsAlreadyDateChange){
                    $reservationModifiedMessage .= ' , ';
                }

                $newDatePlusOneDay = date('Y-m-d', strtotime('+1 day', strtotime($realNewDateTo)));

                if ($newDatePlusOneDay == $realOldDateTo) {
                    $reservationModifiedMessage .= 'Modified dates: Updated with penalty and opened availability for ' . $newDatePlusOneDay;
                } else {
                    $reservationModifiedMessage .= 'Modified dates: Updated with penalty and opened availability from ' .
                        $newDatePlusOneDay . ' to ' . $realOldDateTo;
                }
            }
        }

        $logger->save(
            Logger::MODULE_BOOKING,
            $reservationId,
            Logger::ACTION_BOOKING_MODIFY,
            $reservationModifiedMessage
        );

        // create log for remarks
        if (!is_null($remarks)) {
            $actionLogDao = $this->getServiceLocator()->get('dao_action_logs_action_logs');

            $preActionLog = $actionLogDao->fetchOne([
                'module_id'   => Logger::MODULE_BOOKING,
                'identity_id' => $reservationId,
                'user_id'     => UserService::USER_GUEST,
                'action_id'   => Logger::ACTION_COMMENT,
            ]);

            if ($preActionLog) {
                $actionLogDao->save([
                    'value'     => Helper::stripTages($remarks),
                    'timestamp' => date('Y-m-d H:i:s')
                ], ['id' => $preActionLog['id']]);
            } else {
                $logger->save(
                    Logger::MODULE_BOOKING,
                    $reservationId,
                    Logger::ACTION_COMMENT,
                    Helper::stripTages($remarks),
                    UserService::USER_GUEST
                );
            }
        }

        // if missing rate create task
        if (isset($otherInfo['rateMissingTask'])) {
            $taskService->createAutoTaskForMissingRate($reservationId);
        }

        $this->sendReservationEmail($reservationId, 'modification', $emailOptions, $apartel);

        $this->gr2info("Booking ticket successfully modify", [
            'cron'           => 'ChannelManager',
            'apartment_id'   => $apartmentId,
            'reservation_id' => $reservationId
        ]);

        return true;
    }

    /**
     * @param int $bookingTicketId
     * @param string $type
     * Choices: reservation|modification|cancelation
     * @param array $options
     * @param bool|false|int $isApartel
     *
     * @return bool
     */
    private function sendReservationEmail($bookingTicketId, $type, array $options = [], $isApartel = false)
    {
        if (in_array($type, ['reservation', 'modification', 'cancelation'])) {
            $commands = [];

            switch ($type) {
                case 'reservation':
                    $ccp = (isset($options['cc_provided']) ? ("--ccp=yes") : '');

                    if (isset($options['overbooking'])) {
                        $commands[] = "ginosole reservation-email send-overbooking --id={$bookingTicketId} > /dev/null &";
                    } elseif (!isset($options['no_send_guest_mail']) && $isApartel === false) {
                        $commands[] = "ginosole reservation-email send-guest --id={$bookingTicketId} > /dev/null &";
                    }

                    $commands[] = "ginosole reservation-email send-ginosi --id={$bookingTicketId} {$ccp} > /dev/null &";

                    break;
                case 'modification':
                    $commands[] = (
                    isset($options['send_payment_modify'])
                        ? "ginosole reservation-email send-payment-details-updated-ginosi --id={$bookingTicketId} --ccp=yes > /dev/null &"
                        : "ginosole reservation-email send-modification-ginosi --id={$bookingTicketId} > /dev/null &"
                    );

                    break;
                default:
                    $modificationMailToBooker = ($isApartel) ? '' : '--booker';

                    $commands[] = "ginosole reservation-email send-modification-cancel --id={$bookingTicketId} --ginosi $modificationMailToBooker > /dev/null &";
            }

            if (count($commands)) {
                foreach ($commands as $command) {
                    $output = shell_exec($command);

                    if (!strstr(strtolower($output), 'error')) {
                        $this->gr2info(ucfirst($type) . ' email sent', [
                            'cron' => 'ChannelManager',
                        ]);
                    }
                }
            }

            return true;
        }

        return false;
    }

    /**
     * @param $cubilisRateIdDates
     * @param $roomTypeId
     * @param $dates
     * @param $bookingDomain
     * @param $fromCubilisRatesData
     * @param $productId
     * @param $channelResId
     * @param $partnerId
     * @param $isApartel
     * @param $building
     * @param $apartmentListUsed
     * @param $guestCount
     * @return array
     */
    public function getNightlyData(
        $cubilisRateIdDates,
        $roomTypeId,
        $dates,
        $bookingDomain,
        $fromCubilisRatesData,
        $productId,
        $channelResId,
        $partnerId,
        $isApartel,
        $building,
        $apartmentListUsed,
        $guestCount
    )
    {
        /**
         * @var \DDD\Dao\Apartment\General $apartmentDao
         * @var \DDD\Dao\Apartel\Inventory $apartelInventoryDao
         * @var \DDD\Dao\Apartel\General $apartelDao
         * @var \DDD\Service\Reservation\RateSelector $rateSelector
         * @var \DDD\Service\Reservation\RateSelector $rateSelector
         * @var \DDD\Service\Reservation\PartnerSpecific $partnerSpecificService
         * @var \DDD\Dao\Partners\Partners $partnerDao
         * @var \DDD\Service\Apartel\Type $apartelTypeService
         * @var \DDD\Service\Partners $partnerService
         */
        $rateSelector           = $this->getServiceLocator()->get('service_reservation_rate_selector');
        $partnerSpecificService = $this->getServiceLocator()->get('service_reservation_partner_specific');
        $apartmentInventoryDao  = new Inventory($this->getServiceLocator(), '\ArrayObject');
        $apartelTypeService     = $this->getServiceLocator()->get('service_apartel_type');
        $partnerDao             = $this->getServiceLocator()->get('dao_partners_partners');
        $partnerService         = $this->getServiceLocator()->get('service_partners');

        $result = [
            'ratesData' => [],
            'overbooking' => false,
            'apartel' => false
        ];

        // night count
        $nightCount = Helper::getDaysFromTwoDate($dates['date_from'], $dates['date_to']);

        // Apartel reservation
        if ($isApartel) {
            $apartelInventoryDao = $this->getServiceLocator()->get('dao_apartel_inventory');
            $apartelDao = $this->getServiceLocator()->get('dao_apartel_general');
            $apartelId = $productId;
            $rates = $apartelInventoryDao->getRateByCubilisRateIdDates($cubilisRateIdDates, $roomTypeId);

            // if empty get parent and create task
            if (!count($rates) || count($rates) != $nightCount) {
                $rates = $apartelInventoryDao->getRateByParentRateIdDates($dates, $roomTypeId);
                $result['rateMissingTask'] = true;
            }

            $rates = iterator_to_array($rates);
            $firstApartment = $apartelDao->getApartelCurrency($apartelId);
            $ginosiCurrency = $firstApartment['code'];


            // modification
            if ($bookingDomain) {
                $apartmentId = $bookingDomain->getApartmentIdAssigned();
            } else {
                // new reservation

                // get best apartment for apartel reservation
                $getApartmentForType = $apartelTypeService->getBestApartmentForType($roomTypeId, $dates, $guestCount, $apartmentListUsed, $building);

                if ($getApartmentForType['status'] == 'not-available') {
                    $result['overbooking'] = true;
                }

                $apartmentId = $getApartmentForType['apartment_id'];
            }
        } else {
            $rates = $apartmentInventoryDao->getRateByCubilisRateIdDates($cubilisRateIdDates, $roomTypeId);

            // if empty get parent and create task
            if (!count($rates) || count($rates) != $nightCount) {
                $rates = $apartmentInventoryDao->getRateByParentRateIdDates($dates, $roomTypeId);
                $result['rateMissingTask'] = true;
            }

            $apartmentId = $productId;
            $apartelId = 0;
            $apartmentDao = $this->getServiceLocator()->get('dao_apartment_general');
            $apartmentData = $apartmentDao->getCurrency($apartmentId);
            $ginosiCurrency = $apartmentData['code'];
        }

        $ratesData = [];
        $currencyNotMatch = $ratePriceNotMatch = $rateNameNot = false;

        //  Modification: check has availability this apartment after change date
        if ($bookingDomain && ($dates['date_from'] < $bookingDomain->getDateFrom() || $dates['date_to'] > $bookingDomain->getDateTo())) {
            $oldNightlyData = Helper::getDateListInRange($bookingDomain->getDateFrom(), date('Y-m-d', strtotime('-1 day', strtotime($bookingDomain->getDateTo()))));
            $newDateRange =  Helper::getDateListInRange($dates['date_from'], date('Y-m-d', strtotime('-1 day', strtotime($dates['date_to']))));
            $diffDates = array_diff($newDateRange, $oldNightlyData);
            $apartmentAvailability = $apartmentInventoryDao->checkApartmentAvailabilityApartmentDateList($apartmentId, $diffDates);

            if (!$apartmentAvailability) {
                $result['overbooking'] = true;
            }
        }

        // init strategy to get Partner Id if one was not registered in our system
        $partner = [
            'id'         => $partnerService::PARTNER_UNKNOWN,
            'commission' => $partnerService::PARTNER_UNKNOWN_COMMISSION
        ];

        if ($partnerId) {
            $changedPartnerId = $partnerSpecificService->changePartnerForSomeCases($partnerId, $apartmentId);
            $partnerId        = ($changedPartnerId) ? $changedPartnerId : $partnerId;
            $isOurPartnerId   = ($changedPartnerId) ? true : false;
            $partnerData      = $partnerService->getPartnerDataForReservation($partnerId, $apartmentId, $isOurPartnerId);

            $partner = [
                'id'         => $partnerData->getGid(),
                'commission' => $partnerData->getCommission()
            ];
        }

        // check apply fuzzy logic for this partner
        $applyFuzzyLogic = $partnerDao->checkFuzzyLogic($partner['id']);

        foreach ($rates as $rate) {
            $ourPrice = $rate['price'];

            // if modification check rate exist and active if not use fuzzy logic
            if ($bookingDomain && ($bookingDomain->getDateFrom() > $rate['date'] || $bookingDomain->getDateTo() <= $rate['date'])
                && (!$rate['rate_id'] || !$rate['active'])) {
                $rate = $rateSelector->getSelectorRate($bookingDomain->getId(), $rate['date'], false, $isApartel);
            }

            // check availability in Reservation
            if (!$bookingDomain && !$result['overbooking'] && $rate['availability'] == 0) {
                $result['overbooking'] = true;
            }

            // check rate price mismatch
            if ($applyFuzzyLogic && isset($fromCubilisRatesData[$rate['date']]['price']) && $ourPrice != $fromCubilisRatesData[$rate['date']]['price']) {
                // fuzzy logic for base price
                $priceFuzzyLogic = $partnerSpecificService->getBasePriceByFuzzyLogic($fromCubilisRatesData[$rate['date']]['price'], $partner, $apartmentId, $rate['capacity'], $nightCount);
                $price = $priceFuzzyLogic ? $priceFuzzyLogic : $ourPrice;
            } else {
                $price = $ourPrice;
            }

            $cubilisRateId = isset($fromCubilisRatesData[$rate['date']]['channel_rate_id']) ? $fromCubilisRatesData[$rate['date']]['channel_rate_id'] : 0;

            // check Rate Name
            if (isset($fromCubilisRatesData[$rate['date']]['rate_name']) && $fromCubilisRatesData[$rate['date']]['rate_name']) {
                $rateName = $fromCubilisRatesData[$rate['date']]['rate_name'];
            } else {
                $rateName = $rate['rate_name'];

                // send critical email if not send rate name
                if(!$rateNameNot) {
                    $this->gr2err('Cubilis Not Sending Rate Name', [
                        'cron'              => 'ChannelManager',
                        'apartment_id'      => $apartmentId,
                        'apartel_id'        => $apartelId,
                        'channel_rate_id'   => $cubilisRateId,
                        'channel_res_id'    => $channelResId,
                        'date'              => $rate['date']
                    ]);
                    $rateNameNot = true;
                }
            }

            $ratesData[$rate['date']] = [
                'apartment_id' => $apartmentId,
                'room_id' => $rate['room_type_id'],
                'rate_name' => $rateName,
                'price' => $price,
                'date' => $rate['date'],
                'capacity' => $rate['capacity'],
                'rate_id' => $rate['rate_id'],
                'availability' => $rate['availability'],
            ];

            // send critical email if has currency mismatch
            if (!$currencyNotMatch && (!isset($fromCubilisRatesData[$rate['date']]['currency']) ||
                    strtolower($ginosiCurrency) != strtolower($fromCubilisRatesData[$rate['date']]['currency']))) {
                $this->gr2err('Cubilis apartment currency mismatch with our apartment currency', [
                    'cron' => 'ChannelManager',
                    'apartment_id' => $apartmentId,
                    'apartel_id' => $apartelId,
                    'channel_res_id' => $channelResId,
                ]);
                $currencyNotMatch = true;
            }

        }

        // extra check if rate not matched
        if (count($rates) != $nightCount) {
            $result['overbooking'] = true;
        }

        // apartel data
        if ($isApartel) {
            $result['apartel']['apartment_id'] = $apartmentId;
            $result['apartel']['apartel_id'] = $apartelId;
        }

        $result['ratesData'] = $ratesData;

        return $result;
    }

    /**
     * @param int $rateId
     * @param string $dateFrom
     * @param string $dateTo
     * @return array
     */
    public function getRateDataByRateIdDates($rateId, $dateFrom, $dateTo)
    {
        $inventoryDao = new Inventory($this->getServiceLocator(), '\ArrayObject');
        $rates = $inventoryDao->getRateDataByRateIdDates($rateId, $dateFrom, $dateTo);
        $ratesData = [];

        foreach ($rates as $rate) {
            $ratesData[$rate['date']] = [
                'apartment_id' => $rate['apartment_id'],
                'room_id' => $rate['room_id'],
                'rate_name' => $rate['rate_name'],
                'price' => $rate['price'],
                'date' => $rate['date'],
                'capacity' => $rate['capacity'],
                'rate_id' => $rate['rate_id'],
            ];
        }

        return $ratesData;
    }

    /**
     * @param $ratesData
     * @param $reservationId
     * @param bool $isGetInfo
     * @param bool $isOverbooking
     * @param bool $apartel
     * @return array
     */
    public function changeDateForModification($ratesData, $reservationId, $isGetInfo = false, $apartel = false, $isOverbooking = false)
    {
        /**
         * @var Booking $bookingDao
         * @var BookingTicket $reservationTicketService
         * @var ReservationNightly $reservationNightlyDao
         * @var WorstCXLPolicySelector $policyService
         * @var PenaltyCalculation $penaltyService
         * @var Logger $logger
         * @var \DDD\Service\Booking\Charge $chargeService
         * @var \DDD\Service\Availability $availabilityService
         */
        if (empty($ratesData) || !$reservationId) {
            $this->gr2err("Bad situation while modifying date part: Bad Data for modification date", [
                'cron' => 'ChannelManager',
                'reservation_id' => $reservationId
            ]);
            return ['status' => 'error'];
        }

        $bookingDao               = $this->getServiceLocator()->get('dao_booking_booking');
        $reservationNightlyDao    = $this->getServiceLocator()->get('dao_booking_reservation_nightly');
        $policyService            = $this->getServiceLocator()->get('service_reservation_worst_cxl_policy_selector');
        $logger                   = $this->getServiceLocator()->get('ActionLogger');
        $chargeService            = $this->getServiceLocator()->get('service_booking_charge');
        $reservationTicketService = $this->getServiceLocator()->get('service_booking_booking_ticket');
        $availabilityService      = $this->getServiceLocator()->get('service_availability');
        $update                   = $insert = $delete = $rateIds = [];
        // Reservation Data
        $reservationData          = $bookingDao->getReservationPolicyData($reservationId);
        $resNightlyData           = $reservationNightlyDao->fetchAll(['reservation_id' => $reservationId, 'status' => self::STATUS_BOOKED]);
        $isFlexible               = $reservationData['penalty_hours'] > $reservationData['refundable_before_hours'] ? true : false;
        $today                    = Helper::getCurrenctDateByTimezone($reservationData['timezone']);
        $reservationSetParams     = [];
        $ratesDateKey             = array_keys($ratesData);

        sort($ratesDateKey);

        $newReservationNightCount = count($ratesDateKey);
        $priceAllRefundable       = 0;

        foreach ($resNightlyData as $row) {
            if (in_array($row['date'], $ratesDateKey) || strtotime($today) > strtotime($row['date'])) {
                unset($ratesData[$row['date']]);
            } else {
                if ($reservationData['is_refundable'] == Rate::APARTMENT_RATE_REFUNDABLE && $isFlexible) {
                    // Refundable flexible period, Nothing change, apply what has
                    $delete[$row['date']] = [
                        'id'            => $row['id'],
                        'price'         => $row['price'],
                        'apartment_id'  => $row['apartment_id'],
                        'room_id'       => $row['room_id'],
                        'rate_id'       => $row['rate_id'],
                        'date'          => $row['date'],
                        'capacity'      => $row['capacity'],
                        'rate_name'     => $row['rate_name'],
                        'is_refundable' => Rate::APARTMENT_RATE_REFUNDABLE,
                        'revers'        => true,
                        'update_price'  => false,
                    ];
                } elseif ($reservationData['is_refundable'] == Rate::APARTMENT_RATE_REFUNDABLE) {
                    // Refundable penalty period
                    $priceAllRefundable += $row['price'];
                    $update[$row['date']] = [
                        'id'            => $row['id'],
                        'price'         => $row['price'],
                        'apartment_id'  => $row['apartment_id'],
                        'room_id'       => $row['room_id'],
                        'rate_id'       => $row['rate_id'],
                        'date'          => $row['date'],
                        'capacity'      => $row['capacity'],
                        'rate_name'     => $row['rate_name'],
                        'is_refundable' => Rate::APARTMENT_RATE_REFUNDABLE,
                        'revers'        => false,
                        'update_price'  => false
                    ];
                } elseif ($reservationData['is_refundable'] == Rate::APARTMENT_RATE_NON_REFUNDABLE) {
                    // Non Refundable
                    $update[$row['date']] = [
                        'id'            => $row['id'],
                        'price'         => $row['price'],
                        'apartment_id'  => $row['apartment_id'],
                        'room_id'       => $row['room_id'],
                        'rate_id'       => $row['rate_id'],
                        'date'          => $row['date'],
                        'capacity'      => $row['capacity'],
                        'rate_name'     => $row['rate_name'],
                        'is_refundable' => Rate::APARTMENT_RATE_NON_REFUNDABLE,
                        'revers'        => false,
                        'update_price'  => false
                    ];
                }
                //open parking spot availability
                $chargeService->openParkingSpotAvailability($row['id']);

            }
        }

        // calculate refundable and penalty period price
        $updateDayCount = count($update);
        foreach ($update as $key => $row) {
            if ($row['is_refundable'] == Rate::APARTMENT_RATE_REFUNDABLE) {
                if ($reservationData['penalty'] == Rate::PENALTY_TYPE_PERCENT) {
                    $update[$key]['revers']       = true;
                    $update[$key]['update_price'] = true;
                    $update[$key]['price']        = $row['price'] * $reservationData['penalty_val'] / 100;
                } elseif ($reservationData['penalty'] == Rate::PENALTY_TYPE_FIXED_AMOUNT || $reservationData['penalty'] == Rate::PENALTY_TYPE_NIGHTS) {
                    if ($priceAllRefundable >= $reservationData['penalty_fixed_amount']) {
                        $penaltyPricePerNight         = $reservationData['penalty_fixed_amount'] / $updateDayCount;
                        $update[$key]['price']        = $penaltyPricePerNight;
                        $update[$key]['revers']       = true;
                        $update[$key]['update_price'] = true;
                    } else {
                        $update[$key]['revers']       = false;
                        $update[$key]['update_price'] = false;
                    }
                }
            }
        }

        $insert = $ratesData;
        $addedNightCount = count($insert);

        // Save and sync Nightly Data
        $nightlyData = [
            'insert' => ['data' => $insert, 'availability' => 0],
            'update' => ['data' => $update, 'availability' => 1],
            'delete' => ['data' => $delete, 'availability' => 1],
        ];

        if ($isGetInfo) {
            return $chargeService->chargeForModifyDate($nightlyData, $reservationId, true);
        }

        // if became overbooking change overbooking status
        if ($isOverbooking && $reservationData['overbooking_status'] != BookingTicket::OVERBOOKING_STATUS_OVERBOOKED) {
            $updateAvailabilityBit = (strtotime($today) < strtotime($reservationData['date_from']));
            $changeStatus = $reservationTicketService->changeOverbookingStatus($reservationId, BookingTicket::OVERBOOKING_STATUS_OVERBOOKED, $updateAvailabilityBit);

            if (!$changeStatus) {
                return ['status' => 'error'];
            }
        }

        $modifyDateLog = $availabilitySyncDays = [];
        $addedPrice = 0;

        foreach ($nightlyData as $keyType => $data) {
            foreach ($data['data'] as $keyNight => $night) {

                if ($keyType == 'insert') {
                    // Save nightly data
                    $nightlyInsertId = $reservationNightlyDao->save([
                        'reservation_id' => $reservationId,
                        'apartment_id' => $night['apartment_id'],
                        'room_id' => $night['room_id'],
                        'rate_id' => $night['rate_id'],
                        'rate_name' => $night['rate_name'],
                        'price' => $night['price'],
                        'date' => $night['date'],
                        'capacity' => $night['capacity'],
                        'status' => self::STATUS_BOOKED
                    ]);

                    $nightlyData['insert']['data'][$night['date']]['id'] = $nightlyInsertId;
                    $addedPrice += $night['price'];
                    $modifyDateLog[] = 'Date modified: Added date ' . $night['date'];
                }

                if ($keyType == 'update') {
                    // Update nightly data
                    $updateChange = ['status' => self::STATUS_CANCELED];
                    if ($night['is_refundable'] == Rate::APARTMENT_RATE_REFUNDABLE && $night['update_price']) {
                        $updateChange['price'] = $night['price'];
                    }

                    $reservationNightlyDao->save($updateChange,
                        ['reservation_id' => $reservationId, 'date' => $keyNight]);

                    $modifyDateLog[] = 'Date modified: Updated with penalty and opened availability ' . $night['date'];
                }

                if ($keyType == 'delete') {
                    // Delete nightly data
                    $reservationNightlyDao->delete(['reservation_id' => $reservationId, 'date' => $keyNight]);
                    $modifyDateLog[] = 'Date modified: Removed date ' . $night['date'];
                }

                $availabilitySyncDays[] = [
                    'apartment_id' => $night['apartment_id'],
                    'date' => $night['date'],
                    'availability' => $data['availability'],
                ];
            }
        }

        // update availability if not overbooking
         if (!$isOverbooking && $reservationData['overbooking_status'] != BookingTicket::OVERBOOKING_STATUS_OVERBOOKED && !empty($availabilitySyncDays)) {
            // change apartment availability and sync cubilis
            $availabilityService->updateAvailabilityApartmentByNight($availabilitySyncDays, $reservationId);

            // change apartel availability and sync cubilis if this apartment is apartel
            $availabilityService->updateAvailabilityApartelByNight($availabilitySyncDays, $reservationId);
        }

        // Action Log
        foreach ($modifyDateLog as $log) {
            $logger->save(
                Logger::MODULE_BOOKING,
                $reservationId,
                Logger::ACTION_BOOKING_MODIFY,
                $log
            );
        }

        // Get Good policy
        foreach ($insert as $row) {
            if (!in_array($row['rate_id'], $rateIds)) {
                $rateIds[] =  $row['rate_id'];
            }
        }

        // Get best penalty policy
        $reservationPrice = $reservationNightlyDao->getReservationPrice($reservationId);
        $policyData = $policyService->select($rateIds, $reservationPrice, $reservationId, false, $apartel);

        // calculate penalty amount
        if ($reservationData['is_refundable'] == Rate::APARTMENT_RATE_REFUNDABLE && $isFlexible) {
            $penaltyAmount = $policyData['penalty_fixed_amount'];
        } else {
            $addedDataPenalty = 0;

            if ($addedPrice && $addedNightCount) {
                if ($policyData['is_refundable'] ==  Rate::APARTMENT_RATE_NON_REFUNDABLE) {
                    $addedDataPenalty = $addedPrice;
                } else {
                    $addedDataPenalty = $policyData['penalty_fixed_amount']/$newReservationNightCount*$addedNightCount;
                }
            }

            $penaltyAmount = $reservationData['penalty_fixed_amount'] + $addedDataPenalty;
        }

        // get rate names and capacity
        $getRateNameCapacity = $reservationNightlyDao->getRatesNameCapacity($reservationId);

        // Set price, penalty policy, capacity, rate_name, change date
        $dateTo = date('Y-m-d', strtotime('+1 day', strtotime(end($ratesDateKey))));
        $reservationSetParams = $reservationSetParams + [
            'price' => $reservationPrice,
            'is_refundable' => $policyData['is_refundable'],
            'penalty' => $policyData['penalty'],
            'penalty_fixed_amount' => $penaltyAmount,
            'refundable_before_hours' => $policyData['refundable_before_hours'],
            'penalty_val' => $policyData['penalty_val'],
            'man_count' => $getRateNameCapacity['capacity'],
            'rate_name' => $getRateNameCapacity['rates_name'],
            'date_from' => $ratesDateKey[0],
            'date_to' => $dateTo,
        ];

        $bookingDao->save($reservationSetParams, ['id' => $reservationId]);

        // update availability if before change date reservation is overbooking, but after change not overbooking
        if (!$isOverbooking && $reservationData['overbooking_status'] == BookingTicket::OVERBOOKING_STATUS_OVERBOOKED) {
            $changeStatus = $reservationTicketService->changeOverbookingStatus($reservationId, BookingTicket::OVERBOOKING_STATUS_RESOLVED);

            if (!$changeStatus) {
                return ['status' => 'error'];
            }
        }

        // set charge if not has charge
        if ($reservationData['check_charged']) {
            $chargeService->chargeForModifyDate($nightlyData, $reservationId, false, true);
        }
        /**
         * @var \DDD\Service\Task $taskService
         */
        $taskService = $this->getServiceLocator()->get('service_task');
        $taskService->deleteTask(['extra_inspection' => Task::TASK_EXTRA_INSPECTION, 'res_id' => $reservationId]);
        return ['status' => 'success'];
    }

    public function getCollectFromPartnerReservationsAsJson()
    {
        /**
         * @var \DDD\Dao\Booking\Booking $bookingDao
         */
        $bookingDao = $this->getServiceLocator()->get('dao_booking_booking');
        $bookingDao->setEntity(new \DDD\Domain\Booking\BookingTableRow());

        $reservations = $bookingDao->getCollectFromPartnerReservations();
        $reservationList = [];

        if ($reservations->count()) {
            foreach ($reservations as $reservation) {
                array_push($reservationList, [
                    'id' => $reservation->getReservationId(),
                    'res_number' => $reservation->getReservationNumber(),
                    'status' => \DDD\Service\Booking::$bookingStatuses[$reservation->getStatus()],
                    'checkin' => date('j M Y', strtotime($reservation->getBookingDate())),
                    'checkout' => date('j M Y', strtotime($reservation->getDepartureDate())),
                    'apartment' => $reservation->getApartmentName(),
                    'partner_id' => $reservation->getPartnerId(),
                    'partner' => $reservation->getPartnerName(),
                    'balance' => $reservation->getPartnerBalance(),
                    'currency_symbol' => $reservation->getSymbol(),
                    'currency_id' => $reservation->getCurrencyId(),
                ]);
            }
        }

        return $reservationList;
    }

    public function getPayToPartnerReservationsAsJson()
    {
        /**
         * @var \DDD\Dao\Booking\Booking $bookingDao
         */
        $bookingDao = $this->getServiceLocator()->get('dao_booking_booking');
        $bookingDao->setEntity(new \DDD\Domain\Booking\BookingTableRow());

        $reservations = $bookingDao->getPayToPartnerReservations();
        $reservationList = [];

        if ($reservations->count()) {
            foreach ($reservations as $reservation) {
                array_push($reservationList, [
                    'id' => $reservation->getReservationId(),
                    'res_number' => $reservation->getReservationNumber(),
                    'status' => \DDD\Service\Booking::$bookingStatuses[$reservation->getStatus()],
                    'checkin' => date('j M Y', strtotime($reservation->getBookingDate())),
                    'checkout' => date('j M Y', strtotime($reservation->getDepartureDate())),
                    'apartment' => $reservation->getApartmentName(),
                    'partner_id' => $reservation->getPartnerId(),
                    'partner' => $reservation->getPartnerName(),
                    'is_virtual' => $reservation->isVirtual(),
                    'guest_balance' => $reservation->getGuestBalance(),
                    'partner_balance' => $reservation->getPartnerBalance(),
                    'currency_symbol' => $reservation->getSymbol(),
                    'currency_id' => $reservation->getCurrencyId(),
                ]);
            }
        }

        return $reservationList;
    }

    /**
     * @param \DDD\Domain\Booking\ChannelReservation $bookingDomain
     * @param $customerData
     * @return int
     */
    private function changeCCForModification($bookingDomain, $customerData)
    {
        $customerData['old_email_customer'] = $bookingDomain->getGuestEmail();
        $customerData['customer_id'] = $bookingDomain->getCustomerId();

        $customerData['source'] = Card::CC_SOURCE_CHANNEL_MODIFICATION_SYSTEM;

        // update customer email address
        if ($customerData['old_email_customer'] != $customerData['email']) {
            /**
             * @var Customer $customerService
             */
            $customerService = $this->getServiceLocator()->get('service_customer');

            $customerService->updateCustomerEmail($customerData['customer_id'], $customerData['email']);
        }

        /**
         * @var Card $cardService
         */
        $cardService = $this->getServiceLocator()->get('service_card');
        $cardService->processCreditCardData($customerData);

        return $customerData['customer_id'];
    }

    /**
     * @param $reservationId
     * @return bool
     */
    public function resolveOverbooking($reservationId)
    {
        /**
         * @var Availability $availabilityService
         */
        $availabilityService = $this->getServiceLocator()->get('service_availability');

        // change apartment availability and sync cubilis
        $updateApartmentAvailability = $availabilityService->updateAvailabilityAllPartForApartment($reservationId, 0, true);

        // change apartel availability and sync cubilis if this apartment is apartel
        $updateApartelAvailability = $availabilityService->updateAvailabilityAllPartForApartel($reservationId, true);

        if ((isset($updateApartmentAvailability['status']) && $updateApartmentAvailability['status'] == 'error') ||
            (isset($updateApartelAvailability['status']) && $updateApartelAvailability['status'] == 'error')) {

            $msgForChangeDate = (isset($updateApartmentAvailability['msg']) ? $updateApartmentAvailability['msg'] : '');
            $msgForChangeDate .= (isset($updateApartelAvailability['msg']) ? ' ' . $updateApartelAvailability['msg'] : '');
            $this->gr2err("Bad situation while resolving overbooking: {$msgForChangeDate}", [
                'reservation_id' => $reservationId
            ]);
            return false;
        }

        return true;
    }

    /**
     * @param $reservationId
     * @return bool
     */
    public function markOverbooked($reservationId)
    {
        /**
         * @var Availability $availabilityService
         */
        $availabilityService = $this->getServiceLocator()->get('service_availability');

        // change apartment availability and sync cubilis
        $updateApartmentAvailability = $availabilityService->updateAvailabilityAllPartForApartment($reservationId, 1, true);

        // change apartel availability and sync cubilis if this apartment is apartel
        $updateApartelAvailability = $availabilityService->updateAvailabilityAllPartForApartel($reservationId, true);

        if ((isset($updateApartmentAvailability['status']) && $updateApartmentAvailability['status'] == 'error') ||
            (isset($updateApartelAvailability['status']) && $updateApartelAvailability['status'] == 'error')) {

            $msgForChangeDate = (isset($updateApartmentAvailability['msg']) ? $updateApartmentAvailability['msg'] : '');
            $msgForChangeDate .= (isset($updateApartelAvailability['msg']) ? ' ' . $updateApartelAvailability['msg'] : '');
            $this->gr2err("Bad situation while marking overbooking: {$msgForChangeDate}", [
                'reservation_id' => $reservationId
            ]);
            return false;
        }

        return true;
    }
}
