<?php

namespace LibraryTest\DDD\Service\Website;

use DDD\Dao\Location\City;
use DDD\Service\Booking\BookingTicket;
use Library\UnitTesting\BaseTest;
use Library\Utility\Helper;
use Library\Validator\ClassicValidator;

class BookingTest extends BaseTest
{
    /**
     * Test filter reservation data action
     */
    public function testFilterReservationData()
    {
        // check services and dao`s
        $websiteSearchService = $this->getApplicationServiceLocator()->get('service_website_search');
        $this->assertInstanceOf('\DDD\Service\Website\Search', $websiteSearchService);
        $cityDao = new City($this->getApplicationServiceLocator(), 'ArrayObject');
        $this->assertInstanceOf('\DDD\Dao\Location\City', $cityDao);

        $cityName       = 'Yerevan';
        $apartmentTitle = 'hollywood-al-pacino';
        $cityResponse   = $cityDao->getCityByName($cityName);

        $this->assertTrue(ClassicValidator::checkCityName($cityName), "City Name Validator haven't correct regex");
        $this->assertTrue(ClassicValidator::checkApartmentTitle($apartmentTitle), "Apartment Name Validator haven't correct regex");
        $this->assertArrayHasKey('timezone', $cityResponse);

        // check current date
        $diffHours   = 0;
        $boDiffHours = -24;

        $currentDate      = Helper::getCurrenctDateByTimezone($cityResponse['timezone'], 'd-m-Y', $diffHours);
        $currentDateForBo = Helper::getCurrenctDateByTimezone($cityResponse['timezone'], 'd-m-Y', $boDiffHours);

        $dateCurrent   = new \DateTime("now");
        $dateBoCurrent = new \DateTime("yesterday");

        $this->assertEquals($currentDate, $dateCurrent->format('d-m-Y'), 'Guest timezone logic is not correct');
        $this->assertEquals($currentDateForBo, $dateBoCurrent->format('d-m-Y'), 'Bo User timezone logic is not correct');
    }

    /**
     * Test booking process
     */
    public function testBookingProcess()
    {
        $bookingTicketService      = $this->getApplicationServiceLocator()->get('service_booking_booking_ticket');
        $channelManagerService     = $this->getApplicationServiceLocator()->get('service_channel_manager');
        $apartmentGroupService     = $this->getApplicationServiceLocator()->get('service_apartment_group');
        $reservationService        = $this->getApplicationServiceLocator()->get('service_reservation_main');
        $partnerService            = $this->getApplicationServiceLocator()->get('service_partners');
        $syncService               = $this->getApplicationServiceLocator()->get('service_queue_inventory_synchronization_queue');
        $currencyService           = $this->getApplicationServiceLocator()->get('service_currency_currency');

        $this->assertInstanceOf('\DDD\Service\Booking\BookingTicket', $bookingTicketService);
        $this->assertInstanceOf('\DDD\Service\ChannelManager', $channelManagerService);
        $this->assertInstanceOf('\DDD\Service\ApartmentGroup', $apartmentGroupService);
        $this->assertInstanceOf('\DDD\Service\Reservation\Main', $reservationService);
        $this->assertInstanceOf('\DDD\Service\Partners', $partnerService);
        $this->assertInstanceOf('\DDD\Service\Queue\InventorySynchronizationQueue', $syncService);
        $this->assertInstanceOf('\DDD\Service\Currency\Currency', $currencyService);

        // dummy data
        $resNumber = $bookingTicketService->generateResNumber();
        $timeStamp = date('Y-m-d H:i:s');

        $reservationData = [
            "apartment_id_assigned"     => 662,
            "apartment_id_origin"       => 662,
            "room_id"                   => 1366,
            "acc_name"                  => "Hollywood Al Pacino",
            "acc_country_id"            => 213,
            "acc_province_id"           => 19,
            "acc_city_id"               => 48,
            "acc_province_name"         => "California",
            "acc_city_name"             => "Hollywood Los Angeles",
            "acc_address"               => "1714 N McCadden Pl",
            "building_name"             => $apartmentGroupService->getBuildingName(662),
            "date_from"                 => date('Y-m-d'),
            "date_to"                   => date('Y-m-d', strtotime(' +1 day')),
            "currency_rate"             => $currencyService->getCurrencyConversionRate(Helper::getCurrency(), "USD"),
            "currency_rate_usd"         => $currencyService->getCurrencyConversionRate('USD', 'USD'),
            "booker_price"              => "249.00",
            "guest_currency_code"       => Helper::getCurrency(),
            "occupancy"                 => 2,
            "res_number"                => $resNumber,
            "timestamp"                 => $timeStamp,
            "apartment_currency_code"   => 'USD',
            "rateId"                    => "3536",
            "ki_page_status"            => BookingTicket::NOT_SEND_KI_PAGE_STATUS,
            "ki_page_hash"              => $bookingTicketService->generatePageHash($resNumber, $timeStamp),
            "review_page_hash"          => $bookingTicketService->generatePageHash($resNumber, 662),
            "remarks"                   => "",
            "guest_first_name"          => "Test",
            "guest_last_name"           => "PhpUnit",
            "guest_email"               => "test@ginosi.com",
            "guest_address"             => "Test Street 2",
            "guest_city_name"           => "Yerevan",
            "guest_country_id"          => 2,
            "guest_language_iso"        => 'en',
            "guest_zip_code"            => "12121",
            "guest_phone"               => "37499000000",
            "partner_ref"               => "",
            "partner_id"                => 5,
            "partner_name"              => "Staff",
            "partner_commission"        => 0,
            "model"                     => 2
        ];

        $customerData['email']            = $reservationData['guest_email'];
        $reservationData['customer_data'] = $customerData;

        $otherInfo = [
            'cc_provided'        => false,
            'availability'       => 0,
            'no_send_guest_mail' => true,
            'ratesData'          => $reservationService->getRateDataByRateIdDates($reservationData['rateId'], $reservationData['date_from'], $reservationData['date_to'])
        ];

        unset($reservationData['rateId']);

        $reservationId = $reservationService->registerReservation($reservationData, $otherInfo, true);
        $this->assertLessThan($reservationId, 0, 'Reservation is not correct or not available - [Apartment ID: 662]');

        $syncOutput = $syncService->push($reservationData['apartment_id_origin'], $reservationData['date_from'], $reservationData['date_to']);
        $this->assertTrue($syncOutput, 'Synchronization Queue is not correct');
    }

    /**
     * Test Booking reservation data
     */
    public function bookingReservationData()
    {
        $apartmentService = $this->getApplicationServiceLocator()->get('service_website_apartment');
        $currencyDao      = $this->getApplicationServiceLocator()->get('dao_currency_currency');

        $this->assertInstanceOf('\DDD\Service\Website\Apartment', $apartmentService);
        $this->assertInstanceOf('\DDD\Dao\Currency\Currency', $currencyDao);
    }
}
