<?php

namespace Library\Constants;

use DDD\Service\Booking;
use DDD\Service\Booking\BookingTicket;
use DDD\Service\Apartment\ReviewCategory;
use Library\ActionLogger\Logger;

class Objects
{

    const PRODUCT_STATUS_SANDBOX              = 1;
    const PRODUCT_STATUS_REGISTRATION         = 2;
    const PRODUCT_STATUS_REVIEW               = 3;
    const PRODUCT_STATUS_LIVEANDSELLIG        = 5;
    const PRODUCT_STATUS_SUSPENDED            = 8;
    const PRODUCT_STATUS_DISABLED             = 9;
    const PRODUCT_STATUS_SELLINGNOTSEARCHABLE = 10;
    const PRODUCT_STATUS_LIVE_IN_UNIT         = 11;
    const PRODUCT_STATUS_SELLING              = 1000;

    const ADD = 'add';
    const MINUS = 'minus';

    public static function getProductStatuses()
    {
        return array(
            self::PRODUCT_STATUS_SANDBOX              => 'Sandbox',
            self::PRODUCT_STATUS_REGISTRATION         => 'Registration',
            self::PRODUCT_STATUS_REVIEW               => 'Review',
            self::PRODUCT_STATUS_LIVEANDSELLIG        => 'Live and Selling',
            self::PRODUCT_STATUS_SELLINGNOTSEARCHABLE => 'Selling not Searchable',
            self::PRODUCT_STATUS_SUSPENDED            => 'Suspended',
            self::PRODUCT_STATUS_LIVE_IN_UNIT         => 'Live-in Unit',
            self::PRODUCT_STATUS_DISABLED             => 'Disabled',
        );
    }

    public static function getProductStatusGroups()
    {
        return [
            self::PRODUCT_STATUS_SANDBOX              => [self::PRODUCT_STATUS_SANDBOX],
            self::PRODUCT_STATUS_REGISTRATION         => [self::PRODUCT_STATUS_REGISTRATION],
            self::PRODUCT_STATUS_REVIEW               => [self::PRODUCT_STATUS_REVIEW],
            self::PRODUCT_STATUS_LIVEANDSELLIG        => [self::PRODUCT_STATUS_LIVEANDSELLIG],
            self::PRODUCT_STATUS_SELLINGNOTSEARCHABLE => [self::PRODUCT_STATUS_SELLINGNOTSEARCHABLE],
            self::PRODUCT_STATUS_SUSPENDED            => [self::PRODUCT_STATUS_SUSPENDED],
            self::PRODUCT_STATUS_LIVE_IN_UNIT         => [self::PRODUCT_STATUS_LIVE_IN_UNIT],
            self::PRODUCT_STATUS_DISABLED             => [self::PRODUCT_STATUS_DISABLED],
            self::PRODUCT_STATUS_SELLING              => [self::PRODUCT_STATUS_LIVEANDSELLIG, self::PRODUCT_STATUS_SELLINGNOTSEARCHABLE],
        ];
    }

    private static $creditCards = [
        1  => ['Visa', 'VI'],
        2  => ['Mastercard', 'MC'],
        3  => ['American Express', 'AmericanExpress', 'American_Express', 'AX'],
        4  => ['Discover', 'DS'],
        5  => ['JCB'],
        6  => ['Diners_Club', 'Diners_Club_US', 'DC'],
    ];

    public static function getTimezoneOptions()
    {
        $timeZoneOptions = [];

        foreach (\DateTimeZone::listIdentifiers() as $tz) {
            $gmtTimezone          = new \DateTimeZone('GMT');
            $timezone             = new \DateTime($tz, $gmtTimezone);
            $timezoneOffset       = $timezone->format('P');
            $modTz                = str_replace('/', ' / ', str_replace('_', ' ', $tz));
            $timeZoneOptions[$tz] = $modTz . ' GMT' . $timezoneOffset;
        }

        return $timeZoneOptions;
    }

    public static function getShift($id = null)
    {
        $shift = [
            1 => 'Work Schedule',
            2 => 'Availability Schedule',
        ];

        if ($id != null) {
            if (isset($shift[$id])) {
                return $shift[$id];
            } else {
                return 'None';
            }
        }

        return $shift;
    }

    public static function getTime()
    {
        return [
            '00:00' => '00:00', '00:30' => '00:30', '01:00' => '01:00', '01:30' => '01:30', '02:00' => '02:00', '02:30' => '02:30', '03:00' => '03:00',
            '03:30' => '03:30', '04:00' => '04:00', '04:30' => '04:30', '05:00' => '05:00', '05:30' => '05:30', '06:00' => '06:00', '06:30' => '06:30',
            '07:00' => '07:00', '07:30' => '07:30', '08:00' => '08:00', '08:30' => '08:30', '09:00' => '09:00', '09:30' => '09:30', '10:00' => '10:00',
            '10:30' => '10:30', '11:00' => '11:00', '11:30' => '11:30', '12:00' => '12:00', '12:30' => '12:30', '13:00' => '13:00', '13:30' => '13:30',
            '14:00' => '14:00', '14:30' => '14:30', '15:00' => '15:00', '15:30' => '15:30', '16:00' => '16:00', '16:30' => '16:30', '17:00' => '17:00',
            '17:30' => '17:30', '18:00' => '18:00', '18:30' => '18:30', '19:00' => '19:00', '19:30' => '19:30', '20:00' => '20:00', '20:30' => '20:30',
            '21:00' => '21:00', '21:30' => '21:30', '22:00' => '22:00', '22:30' => '22:30', '23:00' => '23:00', '23:30' => '23:30', '24:00' => '24:00',
        ];
    }

    public static function getTimes()
    {
        return [
            '00:00:00' => '00:00:00', '00:30:00' => '00:30:00', '01:00:00' => '01:00:00', '01:30:00' => '01:30:00', '02:00:00' => '02:00:00', '02:30:00' => '02:30:00', '03:00:00' => '03:00:00',
            '03:30:00' => '03:30:00', '04:00:00' => '04:00:00', '04:30:00' => '04:30:00', '05:00:00' => '05:00:00', '05:30:00' => '05:30:00', '06:00:00' => '06:00:00', '06:30:00' => '06:30:00',
            '07:00:00' => '07:00:00', '07:30:00' => '07:30:00', '08:00:00' => '08:00:00', '08:30:00' => '08:30:00', '09:00:00' => '09:00:00', '09:30:00' => '09:30:00', '10:00:00' => '10:00:00',
            '10:30:00' => '10:30:00', '11:00:00' => '11:00:00', '11:30:00' => '11:30:00', '12:00:00' => '12:00:00', '12:30:00' => '12:30:00', '13:00:00' => '13:00:00', '13:30:00' => '13:30:00',
            '14:00:00' => '14:00:00', '14:30:00' => '14:30:00', '15:00:00' => '15:00:00', '15:30:00' => '15:30:00', '16:00:00' => '16:00:00', '16:30:00' => '16:30:00', '17:00:00' => '17:00:00',
            '17:30:00' => '17:30:00', '18:00:00' => '18:00:00', '18:30:00' => '18:30:00', '19:00:00' => '19:00:00', '19:30:00' => '19:30:00', '20:00:00' => '20:00:00', '20:30:00' => '20:30:00',
            '21:00:00' => '21:00:00', '21:30:00' => '21:30:00', '22:00:00' => '22:00:00', '22:30:00' => '22:30:00', '23:00:00' => '23:00:00', '23:30:00' => '23:30:00', '24:00:00' => '24:00:00',
        ];
    }

    public static function getMonths()
    {
        return ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    }

    public static function getMonthLongNames()
    {
        return ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
    }

    public static function getPaymentTypes()
    {
        return [
            'manual'    => 'Manual Payment',
            'automatic' => 'Direct Debit',
        ];
    }

    public static function getCurrencySign($code)
    {
        $signs = [
            'AMD' => '֏',
            'GBP' => '£',
            'EUR' => '€',
            'USD' => '$',
        ];

        if (array_key_exists($code, $signs)) {
            return $signs[$code];
        }

        return $code;
    }

    public static function getTranslationCategory()
    {
        return [
            1 => 'Universal',
            2 => 'Location',
            3 => 'Product',
        ];
    }

    public static function getTranslationStatus()
    {
        return [
            0 => '-- All Status --',
            1 => 'New',
            2 => 'Changed',
            3 => 'Done',
        ];
    }

    public static function getTranslationSkills()
    {
        return [
            9  => 'am',
            10 => 'ru',
            12 => 'fr',
            15 => 'ge',
            13 => 'de',
            14 => 'it',
        ];
    }

    public static function getTranslatePrioritetType()
    {
        return [
            'u'     => ['am' => 1, 'fr' => 3, 'de' => 3, 'ru' => 3, 'it' => 3, 'ge' => 1],
            'p_key' => ['am' => 1, 'fr' => 3, 'de' => 3, 'ru' => 3, 'it' => 3, 'ge' => 1],
            'p'     => ['am' => 0, 'fr' => 2, 'de' => 0, 'ru' => 2, 'it' => 0, 'ge' => 0],
            'l'     => ['am' => 0, 'fr' => 1, 'de' => 0, 'ru' => 1, 'it' => 0, 'ge' => 0],
        ];
    }

    public static function getChargeType()
    {
        return [
            3  => 'Cash',
            8  => 'Cash Refund',
            5  => 'Chargeback Dispute',
            4  => 'Chargeback Fraud',
            6  => 'Chargeback Other',
            1  => 'Collect from Card',
            13  => 'Deposited to Bank',
            2  => 'Refund to Card',
            14  => 'Salary Deduction',
            7  => 'Validation',
        ];
    }

    protected static $transactionTypes = [
        1  => 'Collect',
        2  => 'Refund',
        3  => 'Cash',
        4  => 'CB Fraud',
        5  => 'CB Dispute',
        6  => 'CB Other',
        7  => 'Validation',
        8  => 'Cash Refund',
        9  => 'Pay',
        10 => 'CB Pending',
        12 => 'CB Resolved',
        13 => 'Bank Deposit',
        14 => 'Salary Deduction',
    ];

    public static function getChargeTypeForView()
    {
        return self::$transactionTypes;
    }

    public static function getTransactionTypeById($id)
    {
        if (array_key_exists($id, self::$transactionTypes)) {
            return self::$transactionTypes[$id];
        }

        return '';
    }

    public static function getChargeTypeById($id)
    {
        $types = self::getChargeTypeForView();

        return isset($types[$id]) ? $types[$id] : 'Unknown';
    }

    public static function getTerminalList()
    {
        return [
            1 => 'Terminal A',
            2 => 'Terminal B',
        ];
    }

    public static function getFraudValue()
    {
        return [
            'black_list'  => 100,
            'credit_card' => 100,
            'country_ip'  => 20,
            'name_holder' => 20,
            'full_name' => 20,
            'phone' => 60
        ];
    }

    public static function getWorksHoursType()
    {
        return [
            1 => 'Every',
            2 => 'Every Other',
        ];
    }

    public static function getVacationType()
    {
        return [
            1 => 'Vacation',
            2 => 'Personal',
            3 => 'Sick',
            4 => 'Unpaid Leave',
        ];
    }

    public static function getShiftDay()
    {
        return [
            1 => 'First',
            2 => 'Second',
        ];
    }

    public static function getBookingStatusMapping()
    {
        return [
            Booking::BOOKING_STATUS_BOOKED  => Logger::VALUE_BOOKED,
            Booking::BOOKING_STATUS_CANCELLED_MOVED  => Logger::VALUE_CANCELED_MOVED,
            Booking::BOOKING_STATUS_CANCELLED_BY_CUSTOMER  => Logger::VALUE_CANCELED_BY_CUSTOMER,
            Booking::BOOKING_STATUS_CANCELLED_BY_GINOSI  => Logger::VALUE_CANCELED_BY_GINOSI,
            Booking::BOOKING_STATUS_CANCELLED_INVALID => Logger::VALUE_CANCELED_INVALID,
            Booking::BOOKING_STATUS_CANCELLED_TEST_BOOKING => Logger::VALUE_CANCELED_TEST,
            Booking::BOOKING_STATUS_CANCELLED_FRAUDULANT => Logger::VALUE_CANCELED_FRAUDULANT,
            Booking::BOOKING_STATUS_CANCELLED_UNWANTED => Logger::VALUE_CANCELED_UNWANTED,
            Booking::BOOKING_STATUS_CANCELLED_NOSHOW => Logger::VALUE_CANCELLED_NO_SHOW,
            Booking::BOOKING_STATUS_CANCELLED_PENDING => Logger::VALUE_CANCELED_UNKNOWN,
            Booking::BOOKING_STATUS_CANCELLED_EXCEPTION => Logger::VALUE_CANCELED_EXCEPTION,
        ];
    }

    public static function getCreditCardStatuses()
    {
        return [
            BookingTicket::CC_STATUS_UNKNOWN => 'Unknown',
            BookingTicket::CC_STATUS_VALID   => 'Valid',
            BookingTicket::CC_STATUS_INVALID => 'Invalid',
        ];
    }

    public static function getCreditCardStatusById($id)
    {
        $statuses = self::getCreditCardStatuses();

        return isset($statuses[$id]) ? $statuses[$id] : '-- Undefined --';
    }

    public static function getCreditCardId($cardName)
    {
        foreach (self::$creditCards as $key => $row) {
            if (in_array($cardName, $row)) {
                return $key;
            }
        }

        return false;
    }

    public static function getStatisticMenu()
    {
        return [
            'index'  => 'Yearly Overview',
            'budget' => 'Budget',
        ];
    }

    public static function getGuestList($textlines, $notShow = false)
    {
        $guest = [];

        for ($i = 1; $i <= 7; $i++) {
            $guestValue = ($i == 7) ? "+{$i}" : $i;
            $guest[$i]  = $guestValue . (
                (!$notShow) ? (
                    ($i == 1) ? ' ' . $textlines['guest'] : ' ' . $textlines['guests']
                    ) : ''
                );
        }

        return $guest;
    }

    public static function getOTADistributionStatusList()
    {
        return [
            0 => 'Unknown',
            1 => 'Pending',
            2 => 'Selling',
            3 => 'Issue',
            4 => 'Bad Url',
        ];
    }

    public static function getApartmentReviewCategoryStatus()
    {
        return [
            0                              => '-- Choose Type --',
            ReviewCategory::STATUS_LIKE    => 'Like',
            ReviewCategory::STATUS_DISLIKE => 'Dislike',
        ];
    }

}
