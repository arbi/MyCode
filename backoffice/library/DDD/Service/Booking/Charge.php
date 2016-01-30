<?php

namespace DDD\Service\Booking;

use DDD\Dao\Currency\Currency as CurrencyDAO;
use DDD\Domain\Booking\SaleStatisticsItem;
use DDD\Service\Apartment\Rate;
use DDD\Service\Psp;
use DDD\Service\ServiceBase;
use DDD\Service\Partners as PartnerService;
use DDD\Service\Location as LocationService;
use DDD\Service\Booking\BankTransaction as BankTransaction;
use DDD\Service\Taxes;
use DDD\Service\User as UserService;
use DDD\Service\Task as TaskService;
use DDD\Service\Team\Team as TeamService;
use Library\ActionLogger\Logger;
use Library\Constants\DbTables;
use Library\Constants\TextConstants;
use Library\Utility\Helper;
use Library\Constants\Constants;
use DDD\Domain\Booking\ChargeSummary;
use DDD\Service\Apartment\Main as ApartmentMainService;
use DDD\Service\Reservation\Main as ReservationMainService;

/**
 *
 * @author Tigran Petrosyan
 */
final class Charge extends ServiceBase
{
    const CHARGE_STATUS_NORMAL = 0;
    const CHARGE_STATUS_DELETED = 1;

    const CHARGE_MONEY_DIRECTION_GINOSI_COLLECT = 2;
    const CHARGE_MONEY_DIRECTION_PARTNER_COLLECT = 3;

    const DISCOUNT_GUEST = 20;
    const DISCOUNT_GINOSI_EMPLOYEE = 25;

    public static $moneyDirectionOptions = array(
        self::CHARGE_MONEY_DIRECTION_GINOSI_COLLECT => 'Ginosi',
        self::CHARGE_MONEY_DIRECTION_PARTNER_COLLECT => 'Partner',
    );

    /**
     * @param $reservations SaleStatisticsItem[]
     * @return string
     */
    public function getTotalPriceInEuroForSaleStatistics($reservations)
    {
        /**
         * @var CurrencyDAO $currencyDao
         */
        $currencyDao = $this->getServiceLocator()->get('dao_currency_currency');
        $currencyExchangeUtility = new \Library\Utility\Currency($currencyDao);

        $totalPriceInEuro = 0;

        foreach ($reservations as $reservation) {
            $reservationApartmentCurrencyIsoCode = $reservation->getApartmentCurrencyIsoCode();
            $reservationPriceInApartmentCurrency = $reservation->getReservationPriceInApartmentCurrency();

            if ($reservationApartmentCurrencyIsoCode == 'EUR') {
                $totalPriceInEuro += $reservationPriceInApartmentCurrency;
            } else {
                $totalPriceInEuro += $currencyExchangeUtility->convert($reservationPriceInApartmentCurrency, $reservationApartmentCurrencyIsoCode, 'EUR');
            }
        }

        return number_format($totalPriceInEuro, 2, '.', '');
    }


    public function saveCharge($data, $userId = false)
    {
        /**
         * @var \DDD\Dao\Booking\Booking $bookingDao
         * @var \Library\Authentication\BackofficeAuthenticationService $authenticationService
         * @var \DDD\Dao\Booking\Charge $chargingDao
         * @var \DDD\Dao\Booking\ChargeDeleted $chargeDeleteDao
         * @var \DDD\Dao\Booking\ReservationNightly $reservationNightlyDao
         * @var Logger $logger
         */
        $authenticationService = $this->getServiceLocator()->get('library_backoffice_auth');
        $chargingDao           = $this->getServiceLocator()->get('dao_booking_charge');
        $bookingDao            = $this->getServiceLocator()->get('dao_booking_booking');
        $chargeDeleteDao       = $this->getServiceLocator()->get('dao_booking_charge_deleted');
        $reservationNightlyDao = $this->getServiceLocator()->get('dao_booking_reservation_nightly');
        $logger                = $this->getServiceLocator()->get('ActionLogger');
        $isBookingExist        = $bookingDao->checkRowExist(DbTables::TBL_BOOKINGS, 'res_number', $data['res_number']);
        $parkingInventoryDao   = $this->getServiceLocator()->get('dao_parking_spot_inventory');
        $taskService           = $this->getServiceLocator()->get('service_task');

        if (!$isBookingExist) {
            return false;
        }

        $rowBooking = $bookingDao->getDataForCharge($data['res_number']);

        if ($userId) {
            $loggedInUserID = $userId;
        } else {
            $loggedInUserID = $authenticationService->getIdentity()->id;
        }

        $reservationId = $rowBooking['id'];
        $params = [
            'reservation_id' 	=> $reservationId,
            'date' 				=> date('Y-m-d H:i:s'),
            'user_id' 			=> $loggedInUserID,
            'comment' 			=> Helper::setLog('commentWithoutData', Helper::stripTages($data['charge_comment'])),
            'customer_currency' => Helper::stripTages($data['customerCurrency']),
            'acc_currency' 		=> Helper::stripTages($data['accommodationCurrency']),
            'apartment_id' 		=> (int)$data['accId'],
            'type' 				=> 'n',
        ];

        try {
            $chargingDao->beginTransaction();
            $check = false;
            $provideParking = [];
            $reverseProvideParking = [];

            // reverse charge
            if (isset($data['removed'])) {
                foreach ($data['removed'] as $row) {
                    if ((int)$row > 0) {
                        $removedId = $row;
                        //see if it's parking, open availability for the spot
                        $removedChargeInfo = $chargingDao->getChargeById($removedId);
                        if (
                            $removedChargeInfo->getAddons_type() == BookingAddon::ADDON_TYPE_PARKING
                            //check if it's night based parking charge 'cause before we were not charging it on nightly bases
                            && $removedChargeInfo->getReservationNightlyId() > 0
                            && $removedChargeInfo->getEntityId() > 0
                        ) {
                            $parkingInventoryDao->save(
                                ['availability' => 1],
                                [
                                    'spot_id' => $removedChargeInfo->getEntityId(), 'date' => $removedChargeInfo->getReservationNightlyDate()
                                ]);
                        }

                        if ($removedChargeInfo->getAddons_type() == BookingAddon::ADDON_TYPE_EXTRA_PERSON) {
                            $newOccupancy = $rowBooking['occupancy'] - (int)$removedChargeInfo->getAddons_value();
                            $bookingDao->save(
                                ['occupancy' => $newOccupancy],
                                ['id' => $rowBooking['id']]
                            );

                            $taskService->changeSubtaskOccupancy($rowBooking['id'], $newOccupancy);
                        }

                        $chargingDao->save(['status' => self::CHARGE_STATUS_DELETED], ['id' => $removedId]);
                        $chargeDeleteDao->save([
                            'reservation_id' => $reservationId,
                            'reservation_charge_id' => $removedId,
                            'date' => date('Y-m-d H:i:s'),
                            'user_id' => $loggedInUserID,
                            'comment' => Helper::setLog('commentWithoutData', Helper::stripTages($data['charge_comment'])),
                        ]);
                        $check = true;
                        $chargeRowRemoved = $chargingDao->checkChargeTypeIsParking($removedId);

                        if ($chargeRowRemoved) {
                            if (!isset($reverseProvideParking[$removedChargeInfo->getRateName()])) {
                                $reverseProvideParking[$removedChargeInfo->getRateName()] = [];
                            }
                            array_push($reverseProvideParking[$removedChargeInfo->getRateName()],$removedChargeInfo->getReservationNightlyDate());
                        }
                    }
                }
            }

            // charge
            if (isset($data['accommodation_amount'])) {

                foreach ($data['accommodation_amount'] as $key => $value) {

                    if (isset($data['entityId'][$key]) && (int)$data['entityId'][$key] > 0) {
                        $endDate = end($data['nightDate']);
                        if (strtotime('now') <= strtotime($endDate)) {
                            if ((int)$data['addonstype'][$key] == Constants::ADDONS_PARKING) {
                                $isAvailable = $parkingInventoryDao->getSpotInventoryAvailability(
                                    $data['entityId'][$key],
                                    $data['nightDate'][$key]
                                );

                                if (!$isAvailable) {
                                    return false;
                                }
                            }
                        }
                    }
                }

                foreach ($data['accommodation_amount'] as $key => $row) {

                    $price = number_format((float)$row, 2, '.', '');
                    $addonType = (int)$data['addonstype'][$key];

                    // nightly data
                    if (isset($data['reservation_nightly_ids'][$key]) && (int)$data['reservation_nightly_ids'][$key] > 0) {
                        $params['reservation_nightly_id'] = (int)$data['reservation_nightly_ids'][$key];
                        $params['rate_name'] = $data['rateNames'][$key];
                        $params['reservation_nightly_date'] = $data['nightDate'][$key];
                    } else {
                        $params['reservation_nightly_id'] = 0;
                        $params['rate_name'] = NUll;
                        $params['reservation_nightly_date'] = NULL;
                    }

                    //entityId is being used for parking now
                    if (isset($data['entityId'][$key]) && (int)$data['entityId'][$key] > 0) {
                        $params['entity_id'] = (int)$data['entityId'][$key];
                        if ((int)$data['addonstype'][$key] == Constants::ADDONS_PARKING) {
                            //close spot availability
                            $parkingInventoryDao->save(
                                ['availability' => 0],
                                [
                                    'spot_id' => $params['entity_id'], 'date' => $params['reservation_nightly_date']
                                ]);
                        }
                    } else {
                        $params['entity_id'] = 0;
                    }

                    // collection
                    if (isset($data['new_addon_money_direction'][$key])) {
                        $params['money_direction'] = (int)$data['new_addon_money_direction'][$key];
                    } else {
                        $params['money_direction'] = 0;
                    }

                    // commission
                    if (isset($data['new_addon_commission'][$key])) {
                        $params['commission'] = $data['new_addon_commission'][$key];
                    } else {
                        $params['commission'] = 0;
                    }

                    // Discount
                    if ($addonType == BookingAddon::ADDON_TYPE_DISCOUNT) {
                        $price *= -1;
                    }

                    // Compensation
                    if ($addonType == BookingAddon::ADDON_TYPE_COMPENSATION) {
                        $price *= -1;
                    }

                    $params['tax_type'] = (isset($data['taxtype'][$key]) ? (float)$data['taxtype'][$key] : 0);
                    $params['acc_amount'] = $price;
                    $params['addons_value'] = (isset($data['addons_value'][$key]) ? (float)$data['addons_value'][$key] : 0);
                    $params['addons_type'] = $addonType;
                    $params['status'] = 0;

                    $chargingDao->save($params);

                    if ((int)$data['addonstype'][$key] == Constants::ADDONS_PARKING) {

                        if (!isset($provideParking[$params['rate_name']])) {
                            $provideParking[$params['rate_name']] = [];
                        }
                        array_push($provideParking[$params['rate_name']],$params['reservation_nightly_date']);

                    }

                    // set reservation nightly price
                    if (isset($data['reservation_nightly_ids'][$key]) && (int)$data['reservation_nightly_ids'][$key] > 0
                        && $addonType == BookingAddon::ADDON_TYPE_ACC) {
                        // change reservation nightly price
                        $priceNight = $chargingDao->getChargePriceByNightlyId((int)$data['reservation_nightly_ids'][$key]);
                        if ($priceNight && isset($data['rateIds'][$key]) && (int)$data['rateIds'][$key] > 0) {
                            $reservationNightlyDao->save([
                                'price' => $priceNight,
                                'rate_id' => (int)$data['rateIds'][$key],
                                'rate_name' => $data['rateNames'][$key],
                            ], ['id' => (int)$data['reservation_nightly_ids'][$key]]);
                        }
                    }

                    // Extra person charges
                    if ($addonType == BookingAddon::ADDON_TYPE_EXTRA_PERSON && (int)$data['addons_value'][$key]) {
                        $newOccupancy = $rowBooking['occupancy'] + (int)$data['addons_value'][$key];
                        $bookingDao->save(['occupancy' => $newOccupancy], ['id' => $rowBooking['id']]);
                        $taskService->changeSubtaskOccupancy($rowBooking['id'], $newOccupancy);
                        $logger->save(
                            Logger::MODULE_BOOKING,
                            $rowBooking['id'],
                            Logger::ACTION_OCUPANCY_CHANGE,
                            [$rowBooking['occupancy'], $newOccupancy]
                        );
                    }

                    $check = true;
                }
            }

            $parkingChargesByDateRanges = $this->calculateDateRangesForSpot($provideParking);
            $reversedParkingChargesByDateRanges = $this->calculateDateRangesForSpot($reverseProvideParking);
            foreach ($parkingChargesByDateRanges as $parkingSpotRequested) {
                $logger->save(Logger::MODULE_BOOKING, $rowBooking['id'], Logger::ACTION_PROVIDE_PARKING, 'Provide Parking on ' . $parkingSpotRequested['date'] . ' for ' . $parkingSpotRequested['spot']);
            }

            foreach ($reversedParkingChargesByDateRanges as $parkingSpotReversed) {
                $logger->save(Logger::MODULE_BOOKING, $rowBooking['id'], Logger::ACTION_PROVIDE_PARKING, 'Do Not Provide Parking on ' . $parkingSpotReversed['date'] . ' for ' . $parkingSpotReversed['spot']);
            }


            if ($check) {
                $this->updateBalance($rowBooking['id'], true);
            }

            $chargingDao->commitTransaction();

            return $data['res_number'] . '#fd';
        } catch (\Exception $e) {
            $this->gr2logException($e, 'Transaction is not being created after charge', $data);

            $chargingDao->rollbackTransaction();
        }

        return false;
    }

    public function calculateDateRangesForSpot($spotArray)
    {
        foreach ($spotArray as &$singleSpotDatesForSort) {
            sort($singleSpotDatesForSort);
        }

        $allParkingSpotDatesArrayByDateRanges = [];
        $secondsInOneDay = 86400;

        foreach ($spotArray as $rowKey => $singleSpotDates) {
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

        foreach ($combinedArray as &$it) {
            $it['date'] = date('j M', strtotime($it['dateStart'])) . ' - ' . date('j M', strtotime($it['dateEnd'] . ' + 1 day'));

        }

        return $combinedArray;
    }

    public function cronFirstCharge()
    {
        /**
         * @var \DDD\Dao\Booking\Booking $bookingDao
         */
        $bookingDao = $this->getServiceLocator()->get('dao_booking_booking');
        $reservationsToCharge = $bookingDao->getForCharge();
        $this->chargeProcess($reservationsToCharge);
    }

    /**
     * @param $reservationsToCharge
     * @param array $params
     * @param bool $isGetInfo
     * @param bool $additionalCharges
     * @return array
     */
    public function chargeProcess($reservationsToCharge, $params = [], $isGetInfo = false, $additionalCharges = false)
    {
        /** @var \DDD\Dao\Booking\ReservationNightly $reservationNightlyDao */
        $reservationNightlyDao = $this->getServiceLocator()->get('dao_booking_reservation_nightly');
        /** @var \DDD\Service\Booking\BookingAddon $addonService */
        $addonService = $this->getServiceLocator()->get('service_booking_booking_addon');
        /** @var \DDD\Dao\Booking\Charge $chargeDao */
        $chargeDao = $this->getServiceLocator()->get('dao_booking_charge');
        /** @var \DDD\Service\Reservation\PartnerSpecific $partnerSpecificService */
        $partnerSpecificService = $this->getServiceLocator()->get('service_reservation_partner_specific');
        /** @var \DDD\Dao\Partners\Partners $partnerDao */
        $partnerDao = $this->getServiceLocator()->get('dao_partners_partners');
        /** @var \DDD\Dao\Accommodation\Accommodations $apartmentDao */
        $apartmentDao = $this->getServiceLocator()->get('dao_accommodation_accommodations');
        /** @var \DDD\Dao\Apartment\Spots $apartmentSpotsDao */
        $apartmentSpotsDao = $this->getServiceLocator()->get('dao_apartment_spots');
        $accDetailsDao     = new \DDD\Dao\Apartment\Details($this->getServiceLocator(), 'ArrayObject');
        $forViewInfo       = [];

        /**
         * @var int $key
         * @var \DDD\Domain\Booking\FirstCharge $reservation
         */
        foreach ($reservationsToCharge as $key => $reservation) {
            $parkingNights = $this->getParkingNightsFromRemarks($reservation->getRemarks());

            try {
                $reservationId = $reservation->getId();
                $charge = [
                    'reservation_id' => $reservationId,
                    'date'           => date('Y-m-d H:i:s'),
                    'user_id'        => isset($params['user_id']) ? $params['user_id'] : Constants::DEFAULT_USER,
                    'acc_currency'   => $reservation->getApartmentCurrencyCode(),
                    'apartment_id'   => $reservation->getApartmentIdAssigned(),
                    'type'           => 'n',
                ];
                $partnerId = $reservation->getPartnerId();
                // set business model
                if ($reservation->getModel() == PartnerService::BUSINESS_MODEL_GINOSI_COLLECT) {
                    $charge['money_direction'] = Charge::CHARGE_MONEY_DIRECTION_GINOSI_COLLECT;
                } elseif (in_array($reservation->getModel(), PartnerService::partnerBusinessModel())) {
                    $charge['money_direction'] = Charge::CHARGE_MONEY_DIRECTION_PARTNER_COLLECT;
                }

                $total = $apartmentChargePrice =
                    $apartmentAdditionalChargePrice = $chargeAddonsValue
                        = $chargeAddonsAdditionalValue = $nightPrice = $taxTypeForCharge = 0;

                if ($isGetInfo) {
                    $nightData = [[
                        'price'     => $params['price'],
                        'id'        => 0,
                        'date'      => '',
                        'rate_name' => '',
                    ]];
                } elseif (isset($params['reservationNighId'])) { // charge if date modified
                    $nightData = $reservationNightlyDao->fetchAll(['id' => $params['reservationNighId']], ['id', 'price', 'date', 'rate_name']);
                } else {
                    $nightData = $reservationNightlyDao->fetchAll(
                        [
                            'reservation_id' => $reservationId,
                            'status'         => ReservationMainService::STATUS_BOOKED
                        ],
                        ['id', 'price', 'date', 'rate_name']
                    );
                }

                // check partner specific commission
                $partnerCommission = $partnerSpecificService->getPartnerDeductedCommission($partnerId, $reservation->getPartnerCommission());

                $hasDiscount = false;
                // get partner discount
                $partnerInfo = $partnerDao->getPartnerById($partnerId);
                if (   $partnerInfo
                    && ceil($partnerInfo->getDiscount())
                    && !($reservation->getChannelName())
                ) {
                    $hasDiscount = true;
                }

                // addon list (taxes, cleaning fee etc.)
                $addons = $addonService->getAddonsArray();
                $checkUpdateBooking = false;
                $nightlyArr = [];
                // In case of reservation modification we pass the current tax duration in params
                // In other cases we use the iterator for check.
                $taxCurrentDurations = [
                    'tot'       => isset($params['current_tot_duration']) ? $params['current_tot_duration'] : 0,
                    'vat'       => isset($params['current_vat_duration']) ? $params['current_vat_duration'] : 0,
                    'city_tax'  => isset($params['current_city_tax_duration']) ? $params['current_city_tax_duration'] : 0,
                    'sales_tax' => isset($params['current_sales_tax_duration']) ? $params['current_sales_tax_duration'] : 0,
                ];

                foreach ($nightData as $night) {
                    array_push($nightlyArr, $night);
                    $nightPrice = $night['price'];

                    if ($nightPrice > 0) {
                        // if partner specific get specific price
                        $nightPrice = $partnerSpecificService->getPartnerDeductedPrice(
                            $partnerId,
                            $nightPrice,
                            $reservation->getApartmentIdAssigned()
                        );

                        $rawNightPrice = $nightPrice;
                        if ($hasDiscount) {
                            $rawNightPrice = number_format($nightPrice * (100 - $partnerInfo->getDiscount()) * 0.01, 2, '.', '');
                        }

                        foreach ($addons as $addon) {
                            $taxTypeForCharge = 0;

                            if ($addon['id'] == BookingAddon::ADDON_TYPE_ACC) {
                                $apartmentChargePrice = $nightPrice;
                                $chargeAddonsValue = $chargeAddonsAdditionalValue = 0;
                            } elseif ($addon['location_join'] != '') {
                                $taxIncluded    = ($reservation->{'get' . ucfirst(str_replace('_t', 'T', $addon['location_join'])) . 'Included'}() == LocationService::TAX_INCLUDED);
                                $taxMaxDuration = $reservation->{'get' . ucfirst(str_replace('_t', 'T', $addon['location_join'])) . 'MaxDuration'}();

                                if (!$taxIncluded && (!$taxMaxDuration || $taxCurrentDurations[$addon['location_join']] < $taxMaxDuration)) {
                                    ++$taxCurrentDurations[$addon['location_join']];
                                    $chargeAddonsValue           = $reservation->{'get' . ucfirst($addon['location_join'])}();
                                    $chargeAddonsAdditionalValue = $reservation->{'get' . ucfirst(str_replace('_t', 'T', $addon['location_join'])) . 'Additional'}();
                                    $taxType                     = $reservation->{'get' . ucfirst($addon['location_join']) . '_type'}();

                                    // In case we don't give commission to partner from charge additional value
                                    if ($chargeAddonsAdditionalValue && !$partnerInfo->hasAdditionalTaxCommission()) {
                                        $chargeAddonsValue += $chargeAddonsAdditionalValue;
                                        $chargeAddonsAdditionalValue = 0;
                                    }

                                    if ($taxType == Taxes::TAXES_TYPE_PERCENT) {
                                        $apartmentChargePrice = $chargeAddonsValue * $rawNightPrice / 100;
                                        $apartmentAdditionalChargePrice = $chargeAddonsAdditionalValue * $rawNightPrice / 100;
                                        $taxTypeForCharge     = Taxes::TAXES_TYPE_PERCENT;
                                    } elseif ($taxType == Taxes::TAXES_TYPE_PER_NIGHT) {
                                        $apartmentChargePrice = $chargeAddonsValue;
                                        $apartmentAdditionalChargePrice = $chargeAddonsAdditionalValue;
                                        $taxTypeForCharge     = Taxes::TAXES_TYPE_PER_NIGHT;
                                    } elseif ($taxType == Taxes::TAXES_TYPE_PER_PERSON) {
                                        $apartmentChargePrice = $chargeAddonsValue * $reservation->getOccupancy();
                                        $apartmentAdditionalChargePrice = $chargeAddonsAdditionalValue * $reservation->getOccupancy();
                                        $taxTypeForCharge     = Taxes::TAXES_TYPE_PER_PERSON;
                                    } else {
                                        $chargeAddonsValue = 0;
                                    }
                                    $apartmentChargePrice = number_format($apartmentChargePrice, 2, '.', '');
                                    $apartmentAdditionalChargePrice = number_format($apartmentAdditionalChargePrice, 2, '.', '');
                                } else {
                                    continue;
                                }
                            } else {
                                continue;
                            }

                            if (($addon['location_join'] && $chargeAddonsValue > 0) || $addon['id'] == BookingAddon::ADDON_TYPE_ACC ) {
                                $checkUpdateBooking = true;
                                $total += $apartmentChargePrice;

                                $charge['addons_type']            = $addon['id'];
                                $charge['acc_amount']             = $apartmentChargePrice;
                                $charge['addons_value']           = $chargeAddonsValue;
                                $charge['status']                 = 0;
                                $charge['tax_type']               = ($addon['id'] == BookingAddon::ADDON_TYPE_ACC ? 0 : $taxTypeForCharge);
                                $charge['reservation_nightly_id'] = $night['id'];
                                $charge['commission']             = ($addon['default_commission']) ? $partnerCommission : 0;

                                if ($addon['id'] == BookingAddon::ADDON_TYPE_ACC) {
                                    $charge['rate_name'] = $night['rate_name'];
                                }

                                $charge['reservation_nightly_date'] = $night['date'];

                                if ($chargeAddonsAdditionalValue && $addon['location_join']) {
                                    $additionalTaxCharge = $charge;
                                    $additionalTaxCharge['acc_amount'] = $apartmentAdditionalChargePrice;
                                    $additionalTaxCharge['addons_value'] = $chargeAddonsAdditionalValue;
                                    $additionalTaxCharge['commission'] = $partnerCommission;
                                }

                                if ($isGetInfo) {
                                    if ($addon['id'] == BookingAddon::ADDON_TYPE_ACC) {
                                        $charge['rate_name'] = $params['rate_name'];
                                    }
                                    array_push($forViewInfo, $charge);

                                    if ($chargeAddonsAdditionalValue) {
                                        array_push($forViewInfo, $additionalTaxCharge);
                                    }
                                } else {
                                    $chargeDao->save($charge);

                                    if ($chargeAddonsAdditionalValue) {
                                        $chargeDao->save($additionalTaxCharge);
                                    }
                                }

                                $charge['rate_name'] = null;
                                $charge['tax_type']  = 0;

                                // Ginosik and partners Discount
                                if ($addon['id'] == BookingAddon::ADDON_TYPE_ACC) {
                                    $bookingThicketService = $this->getServiceLocator()->get('service_booking_booking_ticket');
                                    $validDiscount = $bookingThicketService->validateAndCheckDiscountData([
                                        'email'  => $reservation->getGuestEmail(),
                                        'aff_id' => $partnerId
                                    ]);

                                    if ($validDiscount['valid'] && ceil($validDiscount['discount_value'])) {
                                        $charge['addons_type']  = BookingAddon::ADDON_TYPE_DISCOUNT;
                                        $charge['acc_amount']   = -($apartmentChargePrice * $validDiscount['discount_value'] / 100);
                                        $charge['addons_value'] = $validDiscount['discount_value'];
                                        $charge['status']       = 0;
                                        $charge['tax_type']     = Taxes::TAXES_TYPE_PERCENT;
                                        $charge['commission']   = ($addons[BookingAddon::ADDON_TYPE_DISCOUNT]['default_commission']) ? $partnerCommission : 0;
                                        $charge['rate_name']    = $validDiscount['discount_value'] . '%';
                                        if (!$isGetInfo) {
                                            $chargeDao->save($charge);
                                        } else {
                                            $charge['acc_amount'] = number_format(round($charge['acc_amount'], 2), 2, '.', '');
                                            $forViewInfo[] = $charge;
                                        }
                                        $charge['rate_name'] = null;
                                    }
                                }
                            }
                        }
                    }
                }

                $valueForCleaningFeeSpecification = 0;
                if (!$isGetInfo && in_array(BookingAddon::ADDON_TYPE_CLEANING_FEE, array_keys($addons)) && !isset($params['date_modify'])) {
                    $detailsRow = $accDetailsDao->fetchOne(
                        ['apartment_id' => $reservation->getApartmentIdAssigned()]
                    );

                    if ($detailsRow && (int)$detailsRow['cleaning_fee']) {

                        $chargeAddonsValue = $detailsRow['cleaning_fee'];

                        $charge['addons_type']              = BookingAddon::ADDON_TYPE_CLEANING_FEE;
                        $charge['acc_amount']               = $chargeAddonsValue;
                        $charge['addons_value']             = $chargeAddonsValue;
                        $charge['status']                   = 0;
                        $charge['tax_type']                 = 0;
                        $charge['commission']               = ($addons[BookingAddon::ADDON_TYPE_CLEANING_FEE]['default_commission']) ? $partnerCommission : 0;
                        $charge['reservation_nightly_id']   = 0;
                        $charge['reservation_nightly_date'] = null;

                        // cleaning fee specification
                        if ($partnerSpecificService->checkCleaningFeeSpecificationForCharge($partnerId)) {
                            $charge['commission'] = ($addons[BookingAddon::ADDON_TYPE_CLEANING_FEE]['default_commission']) ? $reservation->getPartnerCommission() : 0;
                            $charge['money_direction'] = Charge::CHARGE_MONEY_DIRECTION_GINOSI_COLLECT;
                            $valueForCleaningFeeSpecification = $chargeAddonsValue;
                        }

                        $chargeDao->save($charge);
                        $total += $chargeAddonsValue;
                    }
                }

                if ($parkingNights && !$additionalCharges) {
                    $resStartDate = $reservation->getDate_from();
                    $resEndDate   = $reservation->getDateTo();
                    $date1        = date_create($resStartDate);
                    $date2        = date_create($resEndDate);
                    $dateDiff     = date_diff($date2, $date1)->d + 1;

                    //parking nights are more than reservation nights

                    /**
                     * @var \DDD\Service\Parking\Spot\Inventory $parkingInventoryService
                     * @var \DDD\Dao\Parking\Spot\Inventory $parkingInventoryDao
                     */
                    $parkingInventoryDao     = $this->getServiceLocator()->get('dao_parking_spot_inventory');
                    $parkingStart            = $reservation->getDate_from();

                    $parkingEnd              = date('Y-m-j', strtotime($reservation->getDate_from() . '+' . $parkingNights . ' days'));
                    // $availableSpot           = $parkingInventoryService->getAvailableSpotForApartment($reservation->getApartmentIdAssigned(), $parkingStart, $parkingEnd);

                    $newApartmentPreferedSpotId = [];
                    $availableSpot = $selectedSpot = [];

                    $apartmentPreferSpots = $apartmentSpotsDao->getApartmentSpots($reservation->getApartmentIdAssigned());
                    if ($apartmentPreferSpots->count()) {
                        foreach ($apartmentPreferSpots as $apartmentPreferSpot) {
                            array_push($newApartmentPreferedSpotId, $apartmentPreferSpot['spot_id']);
                        }
                        $reservationApartmentId = $reservation->getApartmentIdAssigned();
                        $apartmentService = $this->getServiceLocator()->get('service_apartment_general');
                        $apartmentTimezone = $apartmentService->getApartmentTimezoneById($reservationApartmentId)['timezone'];
                        $datetime = new \DateTime('now');
                        $datetime->setTimezone(new \DateTimeZone($apartmentTimezone));
                        $dateToday             = $datetime->format('Y-m-d');
                        $availableSpot = $apartmentDao->getAvailableSpotsInLotForApartmentForDateRangeByPriority(
                            $reservationApartmentId,
                            $parkingStart,
                            $parkingEnd,
                            [],
                            false,
                            [],
                            $dateToday,
                            true,
                            $newApartmentPreferedSpotId
                        );

                        if (count($availableSpot)) {
                            foreach ($availableSpot as $row) {
                                $selectedSpot = $row;
                            }
                        }
                    }

                    if ( ($dateDiff < $parkingNights) || !count($availableSpot)) {
                        $taskService = $this->getServiceLocator()->get('service_task');
                        $taskService->parkingIssueTask(
                            $reservationId,
                            $reservation->getApartmentIdAssigned(),
                            $resStartDate
                        );
                    }

                    if (($dateDiff >= $parkingNights) && count($availableSpot) && count($selectedSpot)) {
                        $parkingCharge = [
                            'reservation_id'  => $reservationId,
                            'date'            => date('Y-m-d H:i:s'),
                            'user_id'         => isset($params['user_id']) ? $params['user_id'] : Constants::DEFAULT_USER,
                            'acc_currency'    => $reservation->getApartmentCurrencyCode(),
                            'apartment_id'    => $reservation->getApartmentIdAssigned(),
                            'type'            => 'n',
                            'addons_type'     => BookingAddon::ADDON_TYPE_PARKING,
                            'acc_amount'      => $selectedSpot['price'],
                            'rate_name'       => $selectedSpot['unit'] . '(' . $selectedSpot['name'] . ')',
                            'status'          => 0,
                            'addons_value'    => 0,
                            'entity_id'       => $selectedSpot['parking_spot_id'],
                            'tax_type'        => 0,
                            'commission'      => ($addons[BookingAddon::ADDON_TYPE_PARKING]['default_commission']) ? $partnerCommission : 0,
                            'money_direction' => Charge::CHARGE_MONEY_DIRECTION_GINOSI_COLLECT,
                        ];

                        for ($iterator = 0; $iterator < $parkingNights; $iterator++) {
                            if (!empty($nightlyArr[$iterator])) {
                                $parkingCharge['reservation_nightly_id'] = $nightlyArr[$iterator]['id'];
                                $parkingCharge['reservation_nightly_date'] = $nightlyArr[$iterator]['date'];
                                $chargeDao->save($parkingCharge);
                                $total += $selectedSpot['price'];

                                $parkingInventoryDao->save(
                                    ['availability' => 0],
                                    ['spot_id' => $parkingCharge['entity_id'], 'date' => $parkingCharge['reservation_nightly_date']]
                                );
                            }
                        }
                    }
                }

                // update guest balance or partner balance according to ticket model
                if (!$isGetInfo && $checkUpdateBooking) {
                    $this->updateBalance($reservationId, true);
                }

            } catch (\Exception $e) {
                $this->gr2logException($e, 'Charge Process fail', [
                    'reservation_id'     => $reservation->getId(),
                    'reservation_number' => $reservation->getReservationNumber()
                ]);
            }
        }
        return $forViewInfo;
    }

    /**
     * @param $nightlyData
     * @param $reservationId
     * @param bool $isGetInfo
     * @return array
     */
    public function chargeForModifyDate($nightlyData, $reservationId, $isGetInfo = false)
    {
        /**
         * @var \DDD\Dao\Booking\Booking $bookingDao
         * @var \DDD\Dao\Booking\Charge $chargingDao
         * @var \DDD\Dao\Booking\ChargeDeleted $chargeDeletedDao
         * @var Logger $logger
         */
        $chargingDao               = $this->getServiceLocator()->get('dao_booking_charge');
        $chargeDeletedDao          = $this->getServiceLocator()->get('dao_booking_charge_deleted');
        $bookingDao                = $this->getServiceLocator()->get('dao_booking_booking');
        $logger                    = $this->getServiceLocator()->get('ActionLogger');

        $serviceUserAuthentication = $this->getServiceLocator()->get('library_backoffice_auth');
        $userId                    = ($serviceUserAuthentication->hasIdentity() ? $serviceUserAuthentication->getIdentity()->id : Constants::DEFAULT_USER);
        $reservation               = $bookingDao->getForCharge($reservationId);

        if (!$reservationId || !$reservation) {
            return ['status' => 'error', 'msg' => 'Bad Data for continuous process'];
        }

        $currentTotDuration = $reservation->getTotMaxDuration() ? min($reservation->getTotMaxDuration(), $reservation->getNights()) : $reservation->getNights();
        $currentVatDuration = $reservation->getVatMaxDuration() ? min($reservation->getVatMaxDuration(), $reservation->getNights()) : $reservation->getNights();
        $currentCityTaxDuration = $reservation->getCityTaxMaxDuration() ? min($reservation->getCityTaxMaxDuration(), $reservation->getNights()) : $reservation->getNights();
        $currentSalesTaxDuration = $reservation->getSalesTaxMaxDuration() ? min($reservation->getSalesTaxMaxDuration(), $reservation->getNights()) : $reservation->getNights();

        $charge = [
            'reservation_id'    => $reservationId,
            'date' 				=> date('Y-m-d H:i:s'),
            'user_id' 			=> $userId,
            'acc_currency' 		=> $reservation->getApartmentCurrencyCode(),
            'apartment_id' 		=> $reservation->getApartmentIdAssigned(),
        ];
        $params['user_id'] = $userId;
        $params['date_modify'] = true;
        $forViewInfo = [];
        foreach ($nightlyData as $keyType => $data) {
            foreach ($data['data'] as $keyNight => $night) {
                $price = $night['price'];
                $params['price'] = $price;

                // charge
                if ($keyType == 'insert') {
                    $params['reservationNighId'] = ($isGetInfo ? 0 : $night['id']);
                    $params['rate_name'] = (isset($night['rate_name']) ? $night['rate_name'] : '');
                    $params['current_tot_duration'] = $currentTotDuration;
                    $params['current_vat_duration'] = $currentVatDuration;
                    $params['current_city_tax_duration'] = $currentCityTaxDuration;
                    $params['current_sales_tax_duration'] = $currentSalesTaxDuration;
                    $forViewInfo[$night['date']] = [
                        'type' => 'insert',
                        'data' => $this->chargeProcess([$reservation], $params, $isGetInfo, true)
                    ];

                    // Tot was applied
                    if (!$reservation->getTotMaxDuration() || $currentTotDuration < $reservation->getTotMaxDuration()) {
                        $currentTotDuration++;
                    }

                    // Tot was applied
                    if (!$reservation->getVatMaxDuration() || $currentVatDuration < $reservation->getVatMaxDuration()) {
                        $currentVatDuration++;
                    }

                    // Tot was applied
                    if (!$reservation->getCityTaxMaxDuration() || $currentCityTaxDuration < $reservation->getCityTaxMaxDuration()) {
                        $currentCityTaxDuration++;
                    }

                    // Tot was applied
                    if (!$reservation->getSalesTaxMaxDuration() || $currentSalesTaxDuration < $reservation->getSalesTaxMaxDuration()) {
                        $currentSalesTaxDuration++;
                    }
                }

                // reverse
                if (isset($night['revers']) && $night['revers']
                    && (($keyType == 'update' && $night['is_refundable'] == Rate::APARTMENT_RATE_REFUNDABLE) || $keyType == 'delete')) {
                    $chargesByNightly = $chargingDao->getChargeDataByNightlyIdAddons($night['id'], BookingAddon::ADDON_TYPE_ACC);
                    $priceReversed = 0;
                    foreach ($chargesByNightly as $deleted) {
                        if ($isGetInfo) {
                            $priceReversed += $deleted->getAcc_amount();
                        } else {
                            $chargeDeletedDao->save([
                                'reservation_id' => $reservationId,
                                'reservation_charge_id' => $deleted->getId(),
                                'user_id' => $userId,
                                'date' => date('Y-m-d H:i:s'),
                                'comment' => TextConstants::COMMENT_REVERSE_CHARGE_ON_MODIFY,
                            ]);
                        }
                    }

                    if (!$isGetInfo) {
                        $chargingDao->save(['status' => self::CHARGE_STATUS_DELETED], ['reservation_nightly_id' => $night['id']]);
                    } else {
                        if ($keyType == 'update') {
                            $forViewInfo[$night['date']] = ['type' => 'update', 'data' => [
                                [
                                    'acc_amount' => $priceReversed - $price,
                                    'view_charge_name' => 'Penalty',
                                    'rate_name' => (isset($night['rate_name']) ? $night['rate_name'] : '')
                                ]
                            ]];
                        } elseif ($keyType == 'delete') {
                            $forViewInfo[$night['date']] = ['type' => 'delete', 'data' => [
                                [
                                    'acc_amount' => $price,
                                    'view_charge_name' => 'Reverse',
                                    'rate_name' => (isset($night['rate_name']) ? $night['rate_name'] : '')
                                ]
                            ]];
                        }
                    }
                }

                // penalty
                if (!$isGetInfo && $keyType == 'update' && $night['is_refundable'] == Rate::APARTMENT_RATE_REFUNDABLE) {
                    $charge['type'] = 'p';
                    $charge['acc_amount'] = $price;
                    $charge['commission'] = 0;
                    $charge['money_direction'] = self::CHARGE_MONEY_DIRECTION_GINOSI_COLLECT;
                    $charge['reservation_nightly_id'] = $night['id'];
                    $charge['comment'] =  TextConstants::COMMENT_PENALTY_CHARGE_ON_MODIFY;
                    $chargingDao->save($charge);
                }
            }
        }
        return ['status' => 'success', 'data' => $forViewInfo];
    }

    /**
     * @todo THIS ONE MUST BE DELETED! DO NOT USE!
     * @param string $reservationId
     * @return array
     */
    public function getChargedTotal($reservationId) {

        /**
         * @var \DDD\Dao\Booking\Charge $chargeDao
         */
        $chargeDao = $this->getServiceLocator()->get('dao_booking_charge');

        $chargedSum = $chargeDao->chargedSum($reservationId);
        $chargedAccSum = ($chargedSum) ? number_format($chargedSum->getSum_acc(), 2, '.', '') : 0;
        $chargedCustomerSum = ($chargedSum) ? number_format($chargedSum->getSum_customer(), 2, '.', '') : 0;

        return [
            'acc' => $chargedAccSum,
            'customer' => $chargedCustomerSum,
        ];
    }

    /**
     * @param int $reservationId
     * @param number $moneyDirection
     * @return ChargeSummary
     */
    public function getChargesSummary($reservationId, $moneyDirection)
    {
        /**
         * @var \DDD\Dao\Booking\Charge $chargeDao
         */
        $chargeDao = $this->getServiceLocator()->get('dao_booking_charge');
        $chargesSummary = $chargeDao->calculateChargesSummary($reservationId, $moneyDirection);

        return $chargesSummary;
    }

    /**
     * @param array $data
     * @return bool
     */
    public function saveFrontierCharge($data)
    {
        /**
         * @var \DDD\Dao\Booking\Booking $bookingDao
         * @var \Library\Authentication\BackofficeAuthenticationService $authenticationService
         * @var \DDD\Dao\Booking\Charge $chargingDao
         * @var BankTransaction $serviceTransaction
         * @var Logger $logger
         * @var \DDD\Service\Booking\BookingAddon $addonService
         * @var \DDD\Dao\ApartmentGroup\ApartmentGroup $apartmentManagementDao
         */
        $addonService       = $this->getServiceLocator()->get('service_booking_booking_addon');
        $serviceTransaction = $this->getServiceLocator()->get('service_booking_bank_transaction');
        $bookingDao         = $this->getServiceLocator()->get('dao_booking_booking');
        $logger             = $this->getServiceLocator()->get('ActionLogger');
        $apartmentManagementDao = $this->getServiceLocator()->get('dao_apartment_group_apartment_group');

        if (!isset($data['bookingId']) ||
            !($rowBooking = $bookingDao->getBookingForFrontierCharge($data['bookingId'])) || !$data['cc_id']) {
            return false;
        }
        $authenticationService = $this->getServiceLocator()->get('library_backoffice_auth');
        $loggedInUserID        = $authenticationService->getIdentity()->id;
        $chargingDao           = $this->getServiceLocator()->get('dao_booking_charge');
        $parkingInventoryDao   = $this->getServiceLocator()->get('dao_parking_spot_inventory');
        $addons                = $addonService->getAddonsArray();

        $groupId = isset($data['groupId']) ? (int)$data['groupId'] : 0;
        $pspId = $apartmentManagementDao->getPsp($groupId);

        /**
         * @var Psp $pspService
         */
        $pspService = $this->getServiceLocator()->get('service_psp');
        $pspInfo    = $pspService->getPspInfo($pspId);

        try {
            $chargingDao->beginTransaction();

            if (isset($data['accommodation_amount'])) {

                foreach ($data['accommodation_amount'] as $key => $value) {

                    if (isset($data['entityId'][$key]) && (int)$data['entityId'][$key] > 0) {

                        if ((int)$data['addonstype'][$key] == Constants::ADDONS_PARKING) {
                            $isAvailable = $parkingInventoryDao->getSpotInventoryAvailability(
                                $data['entityId'][$key],
                                $data['nightDate'][$key]
                            );

                            if (!$isAvailable) {
                                return false;
                            }
                        }
                    }
                }

                $params = [
                    'reservation_id' => $rowBooking['id'],
                    'date'           => date('Y-m-d H:i:s'),
                    'user_id'        => $loggedInUserID,
                    'comment'        => Helper::setLog('commentWithoutData', $data['charge_comment']),
                    'acc_currency'   => $rowBooking['apartment_currency_code'],
                    'type'           => 'n',
                    'apartment_id'   => $rowBooking['apartment_id_assigned'],
                ];

                // set business model
                if ($rowBooking['model'] == PartnerService::BUSINESS_MODEL_GINOSI_COLLECT) {
                    $params['money_direction'] = Charge::CHARGE_MONEY_DIRECTION_GINOSI_COLLECT;
                } elseif (in_array($rowBooking['model'], PartnerService::partnerBusinessModel())) {
                    $params['money_direction'] = Charge::CHARGE_MONEY_DIRECTION_PARTNER_COLLECT;
                }
                $provideParking = [];
                foreach ($data['accommodation_amount'] as $key => $row) {


                    //nightly data
                    if (isset($data['reservation_nightly_ids'][$key]) && (int)$data['reservation_nightly_ids'][$key] > 0) {
                        $params['reservation_nightly_id']   = (int)$data['reservation_nightly_ids'][$key];
                        $params['rate_name']                = $data['rateNames'][$key];
                        $params['reservation_nightly_date'] = $data['nightDate'][$key];
                    } else {
                        $params['reservation_nightly_id']   = 0;
                        $params['rate_name']                = NUll;
                        $params['reservation_nightly_date'] = NULL;
                    }

                    //entityId is being used for parking now
                    if (isset($data['entityId'][$key]) && (int)$data['entityId'][$key] > 0) {
                        $params['entity_id'] = (int)$data['entityId'][$key];
                        if ((int)$data['addonstype'][$key] == Constants::ADDONS_PARKING) {
                            //close spot availability
                            $parkingInventoryDao->save(
                                ['availability' => 0],
                                ['spot_id' => $params['entity_id'], 'date' => $params['reservation_nightly_date']]
                            );
                        }
                    } else {
                        $params['entity_id'] = 0;
                    }

                    $params['acc_amount']   = number_format((float)$row, 2, '.', '');
                    $params['addons_type']  = (int)$data['addonstype'][$key];
                    $params['addons_value'] = (int)$data['addons_value'][$key];
                    $params['tax_type']     = (isset($data['taxtype'][$key]) ? (float)$data['taxtype'][$key] : 0);

                    if (isset($addons[$params['addons_type']]['default_commission']) && $addons[$params['addons_type']]['default_commission']) {
                        $params['commission'] = $rowBooking['partner_commission'];
                    } else {
                        $params['commission'] = 0;
                    }

                    $params['status'] = 0;
                    $chargingDao->save($params);

                    if ($params['addons_type'] == Constants::ADDONS_PARKING) {

                        if (!isset($provideParking[$params['rate_name']])) {
                            $provideParking[$params['rate_name']] = [];
                        }
                        array_push($provideParking[$params['rate_name']],$params['reservation_nightly_date']);

                    }
                }

                $parkingChargesByDateRanges = $this->calculateDateRangesForSpot($provideParking);
                foreach ($parkingChargesByDateRanges as $parkingSpotRequested) {
                    $logger->save(Logger::MODULE_BOOKING, $rowBooking['id'], Logger::ACTION_PROVIDE_PARKING, 'Provide Parking on ' . $parkingSpotRequested['date'] . ' for ' . $parkingSpotRequested['spot']);
                }

                $bookingTicketService   = $this->getServiceLocator()->get('service_booking_booking_ticket');
                $balanceAndSummaryArray = $bookingTicketService->getSumAndBalanc($rowBooking['id']);
                $bookingArray = [
                    'guest_balance'   => $balanceAndSummaryArray['ginosiBalanceInApartmentCurrency'],
                    'partner_balance' => $balanceAndSummaryArray['partnerBalanceInApartmentCurrency'],
                    'check_charged'   => 1,
                ];

                // recalculatePenalty
                $newPenalty = $this->recalculatePenalty($rowBooking['id']);

                if ($newPenalty > 0) {
                    $bookingArray['penalty_fixed_amount'] = $newPenalty;
                }

                $bookingDao->save($bookingArray, ['res_number' => $rowBooking['res_number']]);
            }

            $transactionAmountInApartmentCurrency = doubleval($data['transaction_amount_apartment_currency']);

            if (is_float($transactionAmountInApartmentCurrency) && $transactionAmountInApartmentCurrency > 0) {
                $transactionAmountInCustomerCurrency = $transactionAmountInApartmentCurrency * $rowBooking['currency_rate'];

                // Transaction
                $transactionData = [
                    'res_number'                              => $rowBooking['res_number'],
                    'transaction_type'                        => BankTransaction::BANK_TRANSACTION_TYPE_COLLECT,
                    'transaction_psp'                         => $pspId,
                    'reservation_id'                          => $rowBooking['id'],
                    'acc_currency_rate'                       => $rowBooking['acc_currency_rate'],
                    'transaction_acc_amount'                  => $transactionAmountInApartmentCurrency,
                    'transaction_customer_amount'             => $transactionAmountInCustomerCurrency,
                    'transaction_money_account_currency'      => $pspInfo['currency'],
                    'transaction_money_account_currency_rate' => $rowBooking['currency_rate'],
                    'transaction_charge_amount'               => $transactionAmountInApartmentCurrency,
                    'transaction_money_account_id'            => $pspInfo['money_account_id'],
                    'transaction_charge_comment'              => $data['charge_comment'],
                    'accId'                                   => $rowBooking['apartment_id_assigned'],
                    'transaction_status'                      => BankTransaction::BANK_TRANSACTION_STATUS_APPROVED,
                    'transaction_rrn'                         => '',
                    'transaction_auth_code'                   => '',
                    'cardId'                                  => $data['cc_id'],
                ];

                $responseTransaction = $serviceTransaction->saveTransaction($transactionData, BankTransaction::TRANSACTION_MONEY_DIRECTION_GINOSI_COLLECT, true);

                if ($responseTransaction['status'] == 'error') {
                    $chargingDao->rollbackTransaction();

                    return false;
                }
            } else {
                $chargingDao->rollbackTransaction();

                return false;
            }

            $chargingDao->commitTransaction();

            return $rowBooking['res_number'];
        } catch (\Exception $e) {
            $chargingDao->rollbackTransaction();
        }

        return false;
    }

    /**
     * @param $reservationId
     * @return mixed
     */
    public function recalculatePenalty($reservationId, $customPrice = false)
    {
        /**
         * @var \DDD\Dao\Booking\Charge $chargeDao
         * @var \DDD\Dao\Booking\Booking $bookingDao
         * @var \DDD\Service\PenaltyCalculation $penaltyService
         * @var \DDD\Service\Reservation\WorstCXLPolicySelector $policyService
         */
        $policyService   = $this->getServiceLocator()->get('service_reservation_worst_cxl_policy_selector');
        $chargeDao       = $this->getServiceLocator()->get('dao_booking_charge');
        $bookingDao      = $this->getServiceLocator()->get('dao_booking_booking');
        $reservationData = $bookingDao->getReservationPolicyData($reservationId);
        $chargedSum      = $chargeDao->chargedSumForPenalty($reservationId);

        if ($chargedSum <= $reservationData['penalty_fixed_amount'] || $reservationData['is_refundable'] == Rate::APARTMENT_RATE_NON_REFUNDABLE) {
            return 0;
        }

        $price = $reservationData['price'];

        if ($reservationData['penalty'] == Rate::PENALTY_TYPE_PERCENT && $customPrice) {
            $price = $customPrice;
        }

        $newPenaltyVal = $policyService->penaltyCalculateData(
            ['penalty_val' => $reservationData['penalty_val'], 'penalty_type' => $reservationData['penalty']],
            Helper::getDaysFromTwoDate($reservationData['date_from'], $reservationData['date_to']),
            $price, 1
        );
        return $newPenaltyVal['penalty_amount'];
    }

    public function openParkingSpotAvailability($nightlyId) {
        $chargeDao = $this->getServiceLocator()->get('dao_booking_charge');
        $result = $chargeDao->fetchOne(['reservation_nightly_id' => $nightlyId, 'status'=>self::CHARGE_STATUS_NORMAL,'addons_type' => Constants::ADDONS_PARKING]);

        if ($result) {
            $parkingSpotId = $result->getEntityId();
            $date = $result->getReservationNightlyDate();
            $parkingInventoryDao = $this->getServiceLocator()->get('dao_parking_spot_inventory');
            $parkingInventoryDao->save(
                ['availability' => 1],
                [
                    'spot_id' => $parkingSpotId, 'date' => $date
                ]);

        }

    }

    /**
     * @param string $reservationNumber
     * @return \Zend\Db\ResultSet\ResultSet
     */
    public function getParkingInfoByReservationId($reservationNumber)
    {
        $chargeDao = $this->getServiceLocator()->get('dao_booking_charge');
        return $chargeDao->getParkingInfoByReservationId($reservationNumber);
    }

    /**
     * @param string $remarks
     * @return bool|int
     */
    private function getParkingNightsFromRemarks($remarks)
    {
        $matches = [];

        if (preg_match('/Parking space \(per night: (?P<nights>\d+)n\)/', $remarks, $matches)) {
            if (!empty($matches['nights'])) {
                return $matches['nights'];
            }
        } else {
            return false;
        }
    }

    /**
     * @param $reservationId
     * @param bool|false $setCheckCharge
     */
    public function updateBalance ($reservationId, $setCheckCharge = false)
    {
        /**
         * @var \DDD\Service\Booking\BookingTicket $bookingTicketService
         * @var \DDD\Dao\Booking\Booking $bookingDao
         */

        $bookingDao = $this->getServiceLocator()->get('dao_booking_booking');
        $bookingTicketService = $this->getServiceLocator()->get('service_booking_booking_ticket');

        $balanceAndSummaryArray = $bookingTicketService->getSumAndBalanc($reservationId);
        $bookingArray = [
            'guest_balance' => $balanceAndSummaryArray['ginosiBalanceInApartmentCurrency'],
            'partner_balance' => $balanceAndSummaryArray['partnerBalanceInApartmentCurrency']
        ];

        if ($setCheckCharge) {
            $bookingArray['check_charged'] = 1;
        }

        //recalculatePenalty
        $newPenalty = $this->recalculatePenalty($reservationId);
        if ($newPenalty > 0) {
            $bookingArray['penalty_fixed_amount'] = $newPenalty;
        }

        //save booking data
        $bookingDao->save($bookingArray, ['id' => $reservationId]);
    }

    /**
     * @param $reservationId
     * @return array|\ArrayObject
     */
    public function getCharges($reservationId)
    {
        /**
         * @var \DDD\Dao\Booking\Booking $bookingDao
         */
        $bookingDao = $this->getServiceLocator()->get('dao_booking_booking');
        $checkCharged = $bookingDao->getDataById($reservationId, ['check_charged']);
        if ($checkCharged && $checkCharged['check_charged']) {
            return $this->getAlreadyChargedItems($reservationId);
        }

        return $this->getToBeChargedByReservationId($reservationId);
    }

    /**
     * @param \DDD\Domain\Booking\ChargeProcess $reservation
     * @param bool|false $isBookerPrice
     * @return array
     *
     */
    public function getToBeChargedItems($reservation, $isBookerPrice = false)
    {
        /**
         * @var $bookingTicketService \DDD\Service\Booking\BookingTicket
         * @var ApartmentMainService $apartmentMainService
         * @var \DDD\Service\Textline $textlineService
         */
        $bookingTicketService = $this->getServiceLocator()->get('service_booking_booking_ticket');
        $textlineService = $this->getServiceLocator()->get('service_textline');

        $discountParams = [];
        if ($reservation->getPartnerId()) {
            $discountParams = [
                'aff_id' => $reservation->getPartnerId(),
                'email' => $reservation->getGuestEmail(),
            ];
        }

        if ($isBookerPrice) {
            $totalPrice = $reservation->getBookerPrice();
            $price      = $reservation->getBookerPrice();
        } else {
            $totalPrice = $reservation->getPrice();
            $price = $reservation->getPrice();
        }

        $nightCount = Helper::getDaysFromTwoDate($reservation->getDateTo(), $reservation->getDateFrom());
        $currencySymbol = $reservation->getCurrencySymbol();

        // Night Charges
        $chargesList = [
            [
                'label'        => Helper::evaluateTextline($textlineService->getUniversalTextline(1580), ['{{NIGHT_COUNT}}' => $nightCount]),
                'price_view'   => $currencySymbol . number_format($totalPrice, 2, '.', ' '),
                'price'        => $totalPrice,
                'percent'      => 0,
                'type'         => 'night',
                'class'        => '',
                'description'  => '',
                'tax_included' => '',
            ]
        ];
        // Discount Charges
        $discountValidator = $bookingTicketService->validateAndCheckDiscountData($discountParams, false);
        $discounted = false;
        if (isset($discountValidator['discount_value']) && $discountValidator['valid'] &&
            isset($discountValidator['discount_value']) && ceil($discountValidator['discount_value'])) {
            $discountValue = $totalPrice * $discountValidator['discount_value'] / 100;
            $price   = $totalPrice - $discountValue;
            $discounted    = true;
            $totalPrice = $totalPrice - $discountValue;
            array_push($chargesList, [
                'label' => (isset($discountValidator['partner_name']) ?  $discountValidator['partner_name'] : '') . ' ' . $textlineService->getUniversalTextline(1503),
                'price_view' => '- ' . $currencySymbol . number_format($discountValue, 2, '.', ' '),
                'price' => $discountValue,
                'percent' => 0,
                'type'  => 'discount',
                'class' => 'text-danger',
                'description' => '',
                'tax_included' => '',
            ]);
        }

        // Taxes Charges
        /** @var \DDD\Service\Currency\Currency $currencyService */
        $currencyService = $this->getServiceLocator()->get('service_currency_currency');
        $currencyRate = 1;
        if ($reservation->getGuestCurrency() != $reservation->getApartmentCurrency()) {
            $currencyRate = $currencyService->getCurrencyConversionRate($reservation->getGuestCurrency(), $reservation->getApartmentCurrency());
        }

        if ($reservation->getCityTotType() > 0 && $reservation->getCityTot() > 0) {
            $taxDiscounted = false;
            $totDuration = ($reservation->getTotMaxDuration() ? min($reservation->getTotMaxDuration(), $nightCount) : $nightCount);
            $totValue = $reservation->getCityTot() + $reservation->getTotAdditional();
            if ($reservation->getCityTotType() == Taxes::TAXES_TYPE_PERCENT) {
                $cityTot  = $price / 100 * $totValue * $totDuration / $nightCount;
                $taxValue = $totValue . ' %';
                if ($discounted) {
                    $taxDiscounted = true;
                }
                $percent = $totValue;
            } else {
                $cityTot  = $totDuration * $totValue * $currencyRate;
                $taxValue = $currencySymbol . ' ' . number_format($totValue * $currencyRate, 2, '.', ' ') . ' ' . $textlineService->getUniversalTextline(1473);
                $percent = 0;
            }
            array_push($chargesList, [
                'label' => $textlineService->getUniversalTextline(1259) .
                    ' (' . $taxValue . ($reservation->getTotIncluded() == 1 ? ', ' . $textlineService->getUniversalTextline(1472) : '') . ') ' .
                    ($taxDiscounted ? $textlineService->getUniversalTextline(1633) : ''),
                'price_view' => $currencySymbol . number_format($cityTot, 2, '.', ' '),
                'price' => $cityTot,
                'percent' => $percent,
                'duration' => $totDuration,
                'type' => 'tax',
                'class' => 'text-primary',
                'description' => $textlineService->getUniversalTextline(1429),
                'tax_included' => $reservation->getTotIncluded() == 1 ? : '',
                'date' => $reservation->getDateFrom(),
                'type_id' => BookingAddon::ADDON_TYPE_TAX_TOT
            ]);

            if ($reservation->getTotIncluded() != 1) {
                $totalPrice += $cityTot;
            }
        }

        if ($reservation->getCityVatType() > 0 && $reservation->getCityVat() > 0) {
            $taxDiscounted = false;
            $vatDuration = ($reservation->getVatMaxDuration() ? min($reservation->getVatMaxDuration(), $nightCount) : $nightCount);
            $vatValue = $reservation->getCityVat() + $reservation->getVatAdditional();
            if ($reservation->getCityVatType() == Taxes::TAXES_TYPE_PERCENT) {
                $cityVat  = $price / 100 * $vatValue * $vatDuration / $nightCount;
                $taxValue = $vatValue . ' %';
                if ($discounted) {
                    $taxDiscounted = true;
                }
                $percent = $vatValue;
            } else {
                $cityVat  = $vatDuration * $vatValue * $currencyRate;
                $taxValue = $currencySymbol . number_format($vatValue * $currencyRate, 2, '.', ' ') . ' ' . $textlineService->getUniversalTextline(1473);
                $percent = 0;
            }

            array_push($chargesList, [
                'label' => $textlineService->getUniversalTextline(1260) .
                    ' (' . $taxValue . ($reservation->getVatIncluded() == 1 ? ', ' . $textlineService->getUniversalTextline(1472) : '') . ') ' .
                    ($taxDiscounted ? $textlineService->getUniversalTextline(1633) : ''),
                'price_view' => $currencySymbol . number_format($cityVat, 2, '.', ' '),
                'price' => $cityVat,
                'percent' => $percent,
                'duration' => $vatDuration,
                'type' => 'tax',
                'class' => 'text-primary',
                'description'    => $textlineService->getUniversalTextline(1430),
                'tax_included' => $reservation->getVatIncluded() == 1 ? : '',
                'date' => $reservation->getDateFrom(),
                'type_id' => BookingAddon::ADDON_TYPE_TAX_VAT
            ]);

            if ($reservation->getVatIncluded() != 1) {
                $totalPrice += $cityVat;
            }
        }

        if ($reservation->getCitySalesTaxType() > 0 && $reservation->getCitySalesTax() > 0) {
            $taxDiscounted = false;
            $salesTaxDuration = ($reservation->getSalesTaxMaxDuration() ? min($reservation->getSalesTaxMaxDuration(), $nightCount) : $nightCount);
            $salesTaxValue = $reservation->getCitySalesTax() + $reservation->getSalesTaxAdditional();
            if ($reservation->getCitySalesTaxType() == Taxes::TAXES_TYPE_PERCENT) {
                $citySalesTax = $price / 100 * $salesTaxValue * $salesTaxDuration / $nightCount;
                $taxValue     = $salesTaxValue . ' %';
                if ($discounted) {
                    $taxDiscounted = true;
                }
                $percent = $salesTaxValue;
            } else {
                $citySalesTax = $salesTaxDuration * $salesTaxValue * $currencyRate;
                $taxValue     = $currencySymbol . ' ' . number_format($salesTaxValue * $currencyRate, 2, '.', ' ') . ' ' . $textlineService->getUniversalTextline(1473);
                $percent = 0;
            }

            array_push($chargesList, [
                'label' => $textlineService->getUniversalTextline(1261) .
                    ' (' . $taxValue . ($reservation->getSalesTaxIncluded() == 1 ? ', ' . $textlineService->getUniversalTextline(1472) : '') . ') ' .
                    ($taxDiscounted ? $textlineService->getUniversalTextline(1633) : ''),
                'price_view' => $currencySymbol . number_format($citySalesTax, 2, '.', ' '),
                'price' => $citySalesTax,
                'percent' => $percent,
                'duration' => $salesTaxDuration,
                'type' => 'tax',
                'class' => 'text-primary',
                'description' => $textlineService->getUniversalTextline(1431),
                'tax_included' => $reservation->getSalesTaxIncluded() == 1 ? : '',
                'date' => $reservation->getDateFrom(),
                'type_id' => BookingAddon::ADDON_TYPE_SALES_TAX
            ]);

            if ($reservation->getSalesTaxIncluded() != 1) {
                $totalPrice += $citySalesTax;
            }
        }

        if ($reservation->getCityTaxType() > 0 && $reservation->getCityTax() > 0) {
            $cityTaxDuration = ($reservation->getCityTaxMaxDuration() ? min($reservation->getCityTaxMaxDuration(), $nightCount) : $nightCount);
            $cityTaxValue = $reservation->getCityTax() + $reservation->getCityTaxAdditional();
            $taxDiscounted = false;
            if ($reservation->getCityTaxType() == Taxes::TAXES_TYPE_PERCENT) {
                $cityTax  = $price / 100 * $cityTaxValue * $cityTaxDuration / $nightCount;
                $taxValue = $cityTaxValue . ' %';
                if ($discounted) {
                    $taxDiscounted = true;
                }
                $percent = $cityTaxValue;
            } elseif ($reservation->getCityTaxType() == Taxes::TAXES_TYPE_PER_PERSON) {
                $cityTax  = $cityTaxDuration * $cityTaxValue * $currencyRate * $reservation->getOccupancy();
                $taxValue = $currencySymbol . ' ' . number_format($cityTaxValue * $currencyRate, 2, '.', ' ') . ' ' . $textlineService->getUniversalTextline(1493);
                $percent = 0;
            // Per person per night
            } else {
                $cityTax  = $cityTaxDuration * $cityTaxValue * $currencyRate;
                $taxValue = $currencySymbol . ' ' . number_format($cityTaxValue * $currencyRate, 2, '.', ' ') . ' ' . $textlineService->getUniversalTextline(1473);
                $percent = 0;
            }

            array_push($chargesList, [
                'label' => $textlineService->getUniversalTextline(1262) .
                    ' (' . $taxValue . ($reservation->getCityTaxIncluded() == 1 ? ', ' . $textlineService->getUniversalTextline(1472) : '') . ') ' .
                    ($taxDiscounted ? $textlineService->getUniversalTextline(1633) : ''),
                'price_view' => $currencySymbol . number_format($cityTax, 2, '.', ' '),
                'price' => $cityTax,
                'percent' => $percent,
                'duration' => $cityTaxDuration,
                'type' => 'tax',
                'class' => 'text-primary',
                'description' => $textlineService->getUniversalTextline(1432),
                'tax_included' => $reservation->getCityTaxIncluded() == 1 ? : '',
                'date' => $reservation->getDateFrom(),
                'type_id' => BookingAddon::ADDON_TYPE_CITY_TAX
            ]);

            if ($reservation->getCityTaxIncluded() != 1) {
                $totalPrice += $cityTax;
            }
        }

        // parking taxes
        $parkingNights = $this->getParkingNightsFromRemarks($reservation->getRemarks());
        if ($parkingNights) {
            // get apartment dao`s
            $apartmentDao           = $this->getServiceLocator()->get('dao_accommodation_accommodations');
            $apartmentSpotsDao      = $this->getServiceLocator()->get('dao_apartment_spots');
            $reservationNightlyDao  = $this->getServiceLocator()->get('dao_booking_reservation_nightly');

            $resStartDate = $reservation->getDateFrom();
            $resEndDate   = $reservation->getDateTo();
            $date1        = date_create($resStartDate);
            $date2        = date_create($resEndDate);
            $dateDiff     = date_diff($date2, $date1)->d + 1;

            $nightData = $reservationNightlyDao->fetchAll(['reservation_id' => $reservation->getId()], ['id', 'price', 'date', 'rate_name']);
            $nightlyArr = [];
            foreach ($nightData as $night) {
                array_push($nightlyArr, $night);
            }

            /**
             * @var \DDD\Dao\Parking\Spot\Inventory $parkingInventoryDao
             */
            $parkingStart = $reservation->getDateFrom();
            $parkingEnd   = date('Y-m-j', strtotime($reservation->getDateFrom() . '+' . $parkingNights . ' days'));

            $newApartmentPreferedSpotId = $availableSpot = $selectedSpot = [];

            $apartmentPreferSpots = $apartmentSpotsDao->getApartmentSpots($reservation->getApartmentIdAssigned());
            if ($apartmentPreferSpots->count()) {
                foreach ($apartmentPreferSpots as $apartmentPreferSpot) {
                    array_push($newApartmentPreferedSpotId, $apartmentPreferSpot['spot_id']);
                }
                $reservationApartmentId = $reservation->getApartmentIdAssigned();
                $apartmentService = $this->getServiceLocator()->get('service_apartment_general');
                $apartmentTimezone = $apartmentService->getApartmentTimezoneById($reservationApartmentId)['timezone'];
                $datetime = new \DateTime('now');
                $datetime->setTimezone(new \DateTimeZone($apartmentTimezone));
                $dateToday             = $datetime->format('Y-m-d');
                $availableSpot = $apartmentDao->getAvailableSpotsInLotForApartmentForDateRangeByPriority(
                    $reservationApartmentId,
                    $parkingStart,
                    $parkingEnd,
                    [],
                    false,
                    [],
                    $dateToday,
                    true,
                    $newApartmentPreferedSpotId
                );

                if (count($availableSpot)) {
                    foreach ($availableSpot as $row) {
                        $selectedSpot = $row;
                    }
                }
            }

            $parkingTotal = 0;

            if (($dateDiff >= $parkingNights) && count($availableSpot) && count($selectedSpot)) {
                for ($iterator = 0; $iterator < $parkingNights; $iterator++) {
                    $parkingTotal += $selectedSpot['price'];
                }
            }

            if ($parkingTotal > 0) {
                $parkingTotal = $currencyRate * $parkingTotal;
                $totalPrice += $parkingTotal;

                array_push($chargesList, [
                    'label'        => 'Parking',
                    'price_view'   => $currencySymbol . number_format($parkingTotal, 2, '.', ' '),
                    'price'        => $parkingTotal,
                    'percent'      => 0,
                    'type'         => 'tax',
                    'class'        => 'text-primary',
                    'description'  => $textlineService->getUniversalTextline(1430),
                    'tax_included' => $reservation->getVatIncluded() == 1 ? : '',
                ]);
            }
        }

        // Cleaning Fee
        $apartmentMainService = $this->getServiceLocator()->get('service_apartment_main');
        $cleaningFee = $apartmentMainService->getApartmentCleaningFeeInGuestCurrency($reservation->getApartmentId(), $reservation->getApartmentCurrency(), $reservation->getGuestCurrency(), $reservation->getCheckCurrency());
        if ($cleaningFee) {
            $cleaningFee = $cleaningFee * $currencyRate;
            $totalPrice += $cleaningFee;
            array_push($chargesList, [
                'label' => $textlineService->getUniversalTextline(1497),
                'price_view' => $currencySymbol . number_format($cleaningFee, 2, '.', ' '),
                'price' => $cleaningFee,
                'percent' => 0,
                'type'  => 'fee',
                'class' => 'text-primary',
                'description' => $textlineService->getUniversalTextline(1498),
                'tax_included' => '',
            ]);
        }

        // Total
        array_push($chargesList, [
            'label' => $textlineService->getUniversalTextline(1305),
            'price_view' => $currencySymbol . number_format($totalPrice, 2, '.', ' '),
            'price' => $totalPrice,
            'percent' => 0,
            'type' => 'total',
            'class' => '',
            'description' => '',
            'tax_included' => '',
        ]);

        return $chargesList;
    }

    /**
     * @param $reservationId
     * @param bool|false $guestCurrency
     * @return array
     */
    public function getToBeChargedByReservationId($reservationId, $guestCurrency = false)
    {
        /**
         * @var \DDD\Dao\Booking\Booking $bookingDao
         * @var \DDD\Service\Booking\Charge $chargeService
         */
        $bookingDao = $this->getServiceLocator()->get('dao_booking_booking');
        $chargeService = $this->getServiceLocator()->get('service_booking_charge');

        $chargeData = $bookingDao->getDataForToBeCharged($reservationId, $guestCurrency); // 2nd parameter for guest currency type

        return $chargeService->getToBeChargedItems($chargeData, $guestCurrency);
    }

    /**
     * @param $reservationId
     * @return array
     */
    public function getAlreadyChargedItems($reservationId)
    {
        /**
         * @var \DDD\Dao\Booking\Charge $chargeDao
         * @var \DDD\Service\Textline $textlineService
         */
        $chargeDao = $this->getServiceLocator()->get('dao_booking_charge');
        $textlineService = $this->getServiceLocator()->get('service_textline');

        $charges = $chargeDao->getChargesForView($reservationId);
        $chargesList = [];
        $totalPrice = 0;
        $currencySymbol = '';
        foreach ($charges as $charge) {
            $totalPrice += $charge->getAmount();
            switch ($charge->getAddonsType()) {
                case BookingAddon::ADDON_TYPE_ACC:
                    array_push($chargesList, [
                        'label' => Helper::evaluateTextline($textlineService->getUniversalTextline(1585),
                            ['{{NIGHT_COUNT}}' => date(Constants::GLOBAL_DATE_FORMAT, strtotime($charge->getNightlyDate()))]),
                        'price_view' => $charge->getCurrencySymbol() . ' ' . number_format($charge->getAmount(), 2, '.', ' '),
                        'price' => $charge->getAmount(),
                        'percent' => 0,
                        'type' => 'night',
                        'class' => '',
                        'description' => '',
                        'tax_included' => ''
                    ]);
                    $currencySymbol = $charge->getCurrencySymbol();
                    break;
                case BookingAddon::ADDON_TYPE_TAX_TOT:
                case BookingAddon::ADDON_TYPE_TAX_VAT:
                case BookingAddon::ADDON_TYPE_CITY_TAX:
                case BookingAddon::ADDON_TYPE_SALES_TAX:

                    $labelTextLineId = 0;
                    $descriptionTextLineId = 0;
                    if ($charge->getAddonsType() == BookingAddon::ADDON_TYPE_TAX_TOT) {
                        $descriptionTextLineId = 1429;
                        $labelTextLineId = 1259;
                    } elseif ($charge->getAddonsType() == BookingAddon::ADDON_TYPE_TAX_VAT) {
                        $descriptionTextLineId = 1430;
                        $labelTextLineId = 1260;
                    } elseif ($charge->getAddonsType() == BookingAddon::ADDON_TYPE_SALES_TAX) {
                        $descriptionTextLineId = 1431;
                        $labelTextLineId = 1261;
                    } elseif ($charge->getAddonsType() == BookingAddon::ADDON_TYPE_CITY_TAX) {
                        $descriptionTextLineId = 1432;
                        $labelTextLineId = 1262;
                    }

                    if ($charge->getTaxType() == Taxes::TAXES_TYPE_PERCENT) {
                        $taxValue = $charge->getAddonsValue() . ' %';
                    } else {
                        $taxNameTextline = 1473;
                        if ($charge->getTaxType() == BookingAddon::ADDON_TYPE_CITY_TAX && $charge->getTaxType() == Taxes::TAXES_TYPE_PER_PERSON) {
                            $taxNameTextline = 1493;
                        }

                        $taxValue = $charge->getCurrencySymbol() . ' ' . number_format($charge->getAddonsValue(), 2, '.', ' ') . ' ' . $textlineService->getUniversalTextline($taxNameTextline);
                    }

                    array_push($chargesList, [
                        'label' => $textlineService->getUniversalTextline($labelTextLineId) . ' (' . $taxValue . ') ',
                        'price_view' => $charge->getCurrencySymbol() . ' ' . number_format($charge->getAmount(), 2, '.', ' '),
                        'price' => $charge->getAmount(),
                        'percent' => 0,
                        'type' => 'tax',
                        'class' => '',
                        'description' => $textlineService->getUniversalTextline($descriptionTextLineId),
                        'tax_included' => '',
                        'date' => $charge->getNightlyDate(),
                        'type_id' => $charge->getAddonsType(),
                    ]);
                    break;
                case BookingAddon::ADDON_TYPE_DISCOUNT:
                    array_push($chargesList, [
                        'label' => $textlineService->getUniversalTextline(1503),
                        'price_view' => $charge->getCurrencySymbol() . ' ' . number_format($charge->getAmount(), 2, '.', ' '),
                        'price' => $charge->getAmount(),
                        'percent' => 0,
                        'type'  => 'discount',
                        'class' => 'text-danger',
                        'description' => '',
                        'tax_included' => '',
                    ]);

                    break;
                case BookingAddon::ADDON_TYPE_CLEANING_FEE:
                    array_push($chargesList, [
                        'label' => $textlineService->getUniversalTextline(1497),
                        'price_view' => $charge->getCurrencySymbol() . ' ' . number_format($charge->getAmount(), 2, '.', ' '),
                        'price' => $charge->getAmount(),
                        'percent' => 0,
                        'type'  => 'fee',
                        'class' => 'text-danger',
                        'description' => $textlineService->getUniversalTextline(1498),
                        'tax_included' => '',
                    ]);
                    break;
            }
        }
        // Total
        array_push($chargesList, [
            'label' => $textlineService->getUniversalTextline(1305),
            'price_view' => $currencySymbol . ' ' . number_format($totalPrice, 2, '.', ' '),
            'price' => $totalPrice,
            'percent' => 0,
            'type' => 'total',
            'class' => '',
            'description' => '',
            'tax_included' => '',
        ]);

        return $chargesList;
    }

    /**
     *
     */
    public function getYYY()
    {

    }

}
