<?php
namespace LibraryTest\DDD\Service\Booking;

use DDD\Domain\Booking\ChargeProcess;
use DDD\Service\Booking;
use DDD\Service\Taxes;
use Library\Constants\DbTables;
use Library\UnitTesting\BaseTest;
use Library\Utility\Helper;
use Zend\Db\Sql\Select;

class ChargeTest extends BaseTest
{
    /**
     * Testing GetToBeChargedItems method
     */
    public function testGetToBeChargedItems()
    {
        // get any reservation
        $isBookerPrice  = false;
        /**
         * @var \DDD\Dao\Booking\Booking $reservationDao
         */
        $reservationDao = $this->getApplicationServiceLocator()->get('dao_booking_booking');
//        $reservation    = $reservationDao->fetchOne(function(Select $select) {
//            $select->order('id DESC');
//        });

        $getReservationWithChargesAndBookedStatusQuery = new Select();
        $getReservationWithChargesAndBookedStatusQuery->join(
            [DbTables::TBL_CHARGE => 'charges'],
            'charges.reservation_id = ' . DbTables::TBL_BOOKINGS . '.id',
            [
                'charge_id' => 'id'
            ],
            Select::JOIN_INNER
        );
        $getReservationWithChargesAndBookedStatusQuery->where->isNotNull('charge_id');
        $getReservationWithChargesAndBookedStatusQuery->where->equalTo(
            DbTables::TBL_BOOKINGS . '.status', Booking::BOOKING_STATUS_BOOKED
        );
        $getReservationWithChargesAndBookedStatusQuery->order([
            DbTables::TBL_BOOKINGS . '.id' => 'DESC'
        ]);

        $reservation = $reservationDao->fetchOne($getReservationWithChargesAndBookedStatusQuery);

        $this->assertNotNull($reservation);

        // get textvine service
        /**
         * @var \DDD\Service\Textline $textlineService
         */
        $textlineService = $this->getApplicationServiceLocator()->get('service_textline');

        // check chargeProcess domain
        $reservation = $reservationDao->getDataForToBeCharged($reservation->getId());
        $this->assertInstanceOf('\DDD\Domain\Booking\ChargeProcess', $reservation);

        // check booking ticket
        $bookingTicketService = $this->getApplicationServiceLocator()->get('service_booking_booking_ticket');
        $this->assertInstanceOf('\DDD\Service\Booking\BookingTicket', $bookingTicketService);

        // check discount getters
        $this->assertTrue(
            method_exists($reservation, 'getPartnerId'),
            'Class does not have method getPartnerId'
        );
        $this->assertTrue(
            method_exists($reservation, 'getGuestEmail'),
            'Class does not have method getGuestEmail'
        );
        $discountParams = [];
        if ($reservation->getPartnerId()) {
            $discountParams = [
                'aff_id' => $reservation->getPartnerId(),
                'email' => $reservation->getGuestEmail(),
            ];
        }

        // check price getters
        $this->assertTrue(
            method_exists($reservation, 'getBookerPrice'),
            'Booking Object does not have method getBookerPrice'
        );
        $this->assertTrue(
            method_exists($reservation, 'getPrice'),
            'Booking Object does not have method getPrice'
        );
        if ($isBookerPrice) {
            $totalPrice = $reservation->getBookerPrice();
            $price      = $reservation->getBookerPrice();
        } else {
            $totalPrice = $reservation->getPrice();
            $price = $reservation->getPrice();
        }

        $nightCount     = Helper::getDaysFromTwoDate($reservation->getDateTo(), $reservation->getDateFrom());
        $currencySymbol = $reservation->getCurrencySymbol();

        // check night count
        $this->assertLessThan($nightCount, 0);

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

        /********************************************************************************************************/
        /********************************* Check discount charges ***********************************************/
        /********************************************************************************************************/
        $discountValidator = $bookingTicketService->validateAndCheckDiscountData($discountParams, false);
        $discounted = false;
        if (isset($discountValidator['discount_value']) && $discountValidator['valid'] &&
            isset($discountValidator['discount_value']) && ceil($discountValidator['discount_value'])) {
            $discountValue = $totalPrice * $discountValidator['discount_value'] / 100;
            $price      = $totalPrice - $discountValue;
            $discounted = true;
            $totalPrice = $totalPrice - $discountValue;

            array_push($chargesList, [
                'label'        => (isset($discountValidator['partner_name']) ?  $discountValidator['partner_name'] : '') . ' ' . $textlineService->getUniversalTextline(1503),
                'price_view'   => '- ' . $currencySymbol . number_format($discountValue, 2, '.', ' '),
                'price'        => $discountValue,
                'percent'      => 0,
                'type'         => 'discount',
                'class'        => 'text-danger',
                'description'  => '',
                'tax_included' => '',
            ]);
        }

        /********************************************************************************************************/
        /************************************** Check Tax Charges ***********************************************/
        /********************************************************************************************************/
        $currencyService = $this->getApplicationServiceLocator()->get('service_currency_currency');
        $this->assertInstanceOf('\DDD\Service\Currency\Currency', $currencyService);
        $currencyRate = 1;

        $this->assertTrue(
            method_exists($reservation, 'getGuestCurrency'),
            'Booking Object does not have method getGuestCurrency'
        );
        $this->assertTrue(
            method_exists($reservation, 'getApartmentCurrency'),
            'Booking Object does not have method getApartmentCurrency'
        );
        $this->assertTrue(
            method_exists($currencyService, 'getCurrencyConversionRate'),
            'Booking Object does not have method getCurrencyConversionRate'
        );
        if ($reservation->getGuestCurrency() != $reservation->getApartmentCurrency() && $isBookerPrice) {
            $currencyRate = $currencyService->getCurrencyConversionRate($reservation->getGuestCurrency(), $reservation->getApartmentCurrency());
        }

        $this->assertTrue(
            method_exists($reservation, 'getCityTotType'),
            'Booking Object does not have method getCityTotType'
        );
        $this->assertTrue(
            method_exists($reservation, 'getCityTot'),
            'Booking Object does not have method getCityTot'
        );
        $this->assertTrue(
            method_exists($reservation, 'getTotIncluded'),
            'Booking Object does not have method getTotIncluded'
        );
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
                $percent  = 0;
            }
            array_push($chargesList, [
                'label'        => $textlineService->getUniversalTextline(1259) .
                                  ' (' . $taxValue . ($reservation->getTotIncluded() == 1 ? ', ' . $textlineService->getUniversalTextline(1472) : '') . ') ' .
                                  ($taxDiscounted ? $textlineService->getUniversalTextline(1633) : ''),
                'price_view'   => $currencySymbol . number_format($cityTot, 2, '.', ' '),
                'price'        => $cityTot,
                'percent'      => $percent,
                'duration'     => $totDuration,
                'type'         => 'tax',
                'class'        => 'text-primary',
                'description'  => $textlineService->getUniversalTextline(1429),
                'tax_included' => $reservation->getTotIncluded() == 1 ? : '',
            ]);

            if ($reservation->getTotIncluded() != 1) {
                $totalPrice += $cityTot;
            }
        }

        $this->assertTrue(
            method_exists($reservation, 'getCityVatType'),
            'Booking Object does not have method getCityVatType'
        );
        $this->assertTrue(
            method_exists($reservation, 'getCityVat'),
            'Booking Object does not have method getCityVat'
        );
        $this->assertTrue(
            method_exists($reservation, 'getVatIncluded'),
            'Booking Object does not have method getVatIncluded'
        );
        if ($reservation->getCityVatType() > 0 && $reservation->getCityVat() > 0) {
            $taxDiscounted = false;
            $vatValue = $reservation->getCityVat();
            $vatDuration = ($reservation->getVatMaxDuration() ? min($reservation->getVatMaxDuration(), $nightCount) : $nightCount);
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
                $percent  = 0;
            }

            array_push($chargesList, [
                'label'        => $textlineService->getUniversalTextline(1260) .
                                  ' (' . $taxValue . ($reservation->getVatIncluded() == 1 ? ', ' . $textlineService->getUniversalTextline(1472) : '') . ') ' .
                                  ($taxDiscounted ? $textlineService->getUniversalTextline(1633) : ''),
                'price_view'   => $currencySymbol . number_format($cityVat, 2, '.', ' '),
                'price'        => $cityVat,
                'percent'      => $percent,
                'duration'     => $vatDuration,
                'type'         => 'tax',
                'class'        => 'text-primary',
                'description'  => $textlineService->getUniversalTextline(1430),
                'tax_included' => $reservation->getVatIncluded() == 1 ? : '',
            ]);

            if ($reservation->getVatIncluded() != 1) {
                $totalPrice += $cityVat;
            }
        }

        $this->assertTrue(
            method_exists($reservation, 'getCitySalesTaxType'),
            'Booking Object does not have method getCitySalesTaxType'
        );
        $this->assertTrue(
            method_exists($reservation, 'getCitySalesTax'),
            'Booking Object does not have method getCitySalesTax'
        );
        $this->assertTrue(
            method_exists($reservation, 'getCitySalesTaxType'),
            'Booking Object does not have method getCitySalesTaxType'
        );
        $this->assertTrue(
            method_exists($reservation, 'getSalesTaxIncluded'),
            'Booking Object does not have method getSalesTaxIncluded'
        );
        if ($reservation->getCitySalesTaxType() > 0 && $reservation->getCitySalesTax() > 0) {
            $taxDiscounted = false;
            $salesTaxValue = $reservation->getCitySalesTax();
            $salesTaxDuration = ($reservation->getSalesTaxMaxDuration() ? min($reservation->getSalesTaxMaxDuration(), $nightCount) : $nightCount);
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
                'label'        => $textlineService->getUniversalTextline(1261) .
                                  ' (' . $taxValue . ($reservation->getSalesTaxIncluded() == 1 ? ', ' . $textlineService->getUniversalTextline(1472) : '') . ') ' .
                                  ($taxDiscounted ? $textlineService->getUniversalTextline(1633) : ''),
                'price_view'   => $currencySymbol . number_format($citySalesTax, 2, '.', ' '),
                'price'        => $citySalesTax,
                'percent'      => $percent,
                'duration'     => $salesTaxDuration,
                'type'         => 'tax',
                'class'        => 'text-primary',
                'description'  => $textlineService->getUniversalTextline(1431),
                'tax_included' => $reservation->getSalesTaxIncluded() == 1 ? : '',
            ]);

            if ($reservation->getSalesTaxIncluded() != 1) {
                $totalPrice += $citySalesTax;
            }
        }

        $this->assertTrue(
            method_exists($reservation, 'getCityTaxType'),
            'Booking Object does not have method getCityTaxType'
        );
        $this->assertTrue(
            method_exists($reservation, 'getCityTax'),
            'Booking Object does not have method getCityTax'
        );
        $this->assertTrue(
            method_exists($reservation, 'getCityTaxType'),
            'Booking Object does not have method getCityTaxType'
        );
        $this->assertTrue(
            method_exists($reservation, 'getCityTaxIncluded'),
            'Booking Object does not have method getCityTaxIncluded'
        );
        if ($reservation->getCityTaxType() > 0 && $reservation->getCityTax() > 0) {
            $taxDiscounted = false;
            $cityTaxValue = $reservation->getCityTax();
            $cityTaxDuration = ($reservation->getCityTaxMaxDuration() ? min($reservation->getCityTaxMaxDuration(), $nightCount) : $nightCount);
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
            } else {
                $cityTax  = $cityTaxDuration * $cityTaxValue * $currencyRate;
                $taxValue = $currencySymbol . ' ' . number_format($cityTaxValue * $currencyRate, 2, '.', ' ') . ' ' . $textlineService->getUniversalTextline(1473);
                $percent = 0;
            }

            array_push($chargesList, [
                'label'        => $textlineService->getUniversalTextline(1262) .
                                  ' (' . $taxValue . ($reservation->getCityTaxIncluded() == 1 ? ', ' . $textlineService->getUniversalTextline(1472) : '') . ') ' .
                                  ($taxDiscounted ? $textlineService->getUniversalTextline(1633) : ''),
                'price_view'   => $currencySymbol . number_format($cityTax, 2, '.', ' '),
                'price'        => $cityTax,
                'percent'      => $percent,
                'duration'     => $cityTaxDuration,
                'type'         => 'tax',
                'class'        => 'text-primary',
                'description'  => $textlineService->getUniversalTextline(1432),
                'tax_included' => $reservation->getCityTaxIncluded() == 1 ? : '',
            ]);

            if ($reservation->getCityTaxIncluded() != 1) {
                $totalPrice += $cityTax;
            }
        }

        /********************************************************************************************************/
        /************************************** Check Parking taxes *********************************************/
        /********************************************************************************************************/
        $matches       = [];
        $parkingNights = false;
        if (preg_match('/Parking space \(per night: (?P<nights>\d+)n\)/', $reservation->getRemarks(), $matches)) {
            if (!empty($matches['nights'])) {
                $parkingNights = $matches['nights'];
            }
        }

        $accommodationDao        = $this->getApplicationServiceLocator()->get('dao_accommodation_accommodations');
        $this->assertInstanceOf('\DDD\Dao\Accommodation\Accommodations', $accommodationDao);
        $apartmentSpotsDao       = $this->getApplicationServiceLocator()->get('dao_apartment_spots');
        $this->assertInstanceOf('\DDD\Dao\Apartment\Spots', $apartmentSpotsDao);
        $reservationNightlyDao   = $this->getApplicationServiceLocator()->get('dao_booking_reservation_nightly');
        $this->assertInstanceOf('\DDD\Dao\Booking\ReservationNightly', $reservationNightlyDao);
        $apartmentGeneralService = $this->getApplicationServiceLocator()->get('service_apartment_general');
        $this->assertInstanceOf('\DDD\Service\Apartment\General', $apartmentGeneralService);

        $this->assertTrue(
            method_exists($reservation, 'getDateFrom'),
            'Booking Object does not have method getDateFrom'
        );
        $this->assertTrue(
            method_exists($reservation, 'getDateTo'),
            'Booking Object does not have method getDateTo'
        );
        $this->assertTrue(
            method_exists($reservation, 'getApartmentIdAssigned'),
            'Booking Object does not have method getApartmentIdAssigned'
        );
        $this->assertTrue(
            method_exists($apartmentSpotsDao, 'getApartmentSpots'),
            'ApartmentSpotsDao Object does not have method getApartmentSpots'
        );
        $this->assertTrue(
            method_exists($apartmentGeneralService, 'getApartmentTimezoneById'),
            'ApartmentGeneralService Object does not have method getApartmentTimezoneById'
        );
        $this->assertTrue(
            method_exists($accommodationDao, 'getAvailableSpotsInLotForApartmentForDateRangeByPriority'),
            'AccommodationDao Object does not have method getAvailableSpotsInLotForApartmentForDateRangeByPriority'
        );
        if ($parkingNights) {
            $resStartDate = $reservation->getDateFrom();
            $resEndDate   = $reservation->getDateTo();
            $date1        = date_create($resStartDate);
            $date2        = date_create($resEndDate);
            $dateDiff     = date_diff($date2, $date1)->d + 1;

            $nightData  = $reservationNightlyDao->fetchAll(['reservation_id' => $reservation->getId()], ['id', 'price', 'date', 'rate_name']);
            $nightlyArr = [];
            foreach ($nightData as $night) {
                array_push($nightlyArr, $night);
            }

            $parkingStart = $reservation->getDateFrom();
            $parkingEnd   = date('Y-m-j', strtotime($reservation->getDateFrom() . '+' . $parkingNights . ' days'));

            $newApartmentPreferedSpotId = [];
            $availableSpot              = [];
            $selectedSpot               = [];

            $apartmentPreferSpots = $apartmentSpotsDao->getApartmentSpots($reservation->getApartmentIdAssigned());
            if ($apartmentPreferSpots->count()) {
                foreach ($apartmentPreferSpots as $apartmentPreferSpot) {
                    array_push($newApartmentPreferedSpotId, $apartmentPreferSpot['spot_id']);
                }
                $reservationApartmentId = $reservation->getApartmentIdAssigned();
                $apartmentTimezone      = $apartmentGeneralService->getApartmentTimezoneById($reservationApartmentId)['timezone'];
                $datetime               = new \DateTime('now');
                $datetime->setTimezone(new \DateTimeZone($apartmentTimezone));
                $dateToday             = $datetime->format('Y-m-d');

                $availableSpot = $accommodationDao->getAvailableSpotsInLotForApartmentForDateRangeByPriority(
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

        /********************************************************************************************************/
        /************************************** Check Cleaning Fee **********************************************/
        /********************************************************************************************************/
        $apartmentMainService = $this->getApplicationServiceLocator()->get('service_apartment_main');
        $this->assertInstanceOf('\DDD\Service\Apartment\Main', $apartmentMainService);
        $cleaningFee = $apartmentMainService->getApartmentCleaningFeeInGuestCurrency($reservation->getApartmentId(), $reservation->getApartmentCurrency(), $reservation->getGuestCurrency(), $reservation->getCheckCurrency());
        if ($cleaningFee) {
            $cleaningFee = $cleaningFee * $currencyRate;
            $totalPrice += $cleaningFee;
            array_push($chargesList, [
                'label'        => $textlineService->getUniversalTextline(1497),
                'price_view'   => $currencySymbol . number_format($cleaningFee, 2, '.', ' '),
                'price'        => $cleaningFee,
                'percent'      => 0,
                'type'         => 'fee',
                'class'        => 'text-primary',
                'description'  => $textlineService->getUniversalTextline(1498),
                'tax_included' => '',
            ]);
        }

        $testTotal = 0;
        foreach ($chargesList as $charge) {
            if (!is_bool($charge['tax_included']) || !$charge['tax_included']) {
                if ($charge['type'] == 'discount') {
                    $testTotal -= $charge['price'];
                } else {
                    $testTotal += $charge['price'];
                }
            }
        }

        $this->assertEquals($testTotal, $totalPrice);
    }
}
