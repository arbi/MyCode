<?php

namespace Library\Finance\CreditCard;

use Library\Finance\Base\CreditCardBase;

trait CreditCardHelper
{
    // Basically for card images
    private static $cardTypes = [
        CreditCardBase::VISA => 'visa',
        CreditCardBase::MASTERCARD => 'mastercard',
        CreditCardBase::AMEX => 'american-express',
        CreditCardBase::DISCOVER => 'discover',
        CreditCardBase::JCB => 'jcb',
        CreditCardBase::DINERS_CLUB => 'diners-club',
        CreditCardBase::DINERS_CLUB_US => 'diners-club-us',
        CreditCardBase::MASTERCARD_OR_DINERS_CLUB => 'mastercard-or-diners-club',
    ];

    private static $cardStatuses = [
        CreditCardBase::CARD_UNKNOWN => ['', 'Unknown'],
        CreditCardBase::CARD_VALID => ['status-approved', 'Valid'],
        CreditCardBase::CARD_INVALID => ['status-declined', 'Invalid'],
        CreditCardBase::CARD_TEST => ['status-declined', 'Test'],
        CreditCardBase::CARD_FRAUD => ['status-declined', 'Fraud'],
        CreditCardBase::CARD_DO_NOT_USE => ['status-declined', 'Do not Use'],
    ];

    /**
     * @param $ccTypeId
     * @param bool $ccNumber
     * @param bool $noCard
     * @return string
     */
    public static function getNameById($ccTypeId, $ccNumber = false, $noCard = false)
    {
        if (isset(self::$cardTypes[$ccTypeId])) {
            if ($ccNumber && in_array(substr($ccNumber, 0, 2),CreditCardBase::$notSureMasterOrDinersClubSuffixes)) {
                return self::$cardTypes[CreditCardBase::MASTERCARD_OR_DINERS_CLUB];
            }
            return self::$cardTypes[$ccTypeId];
        }
        return $noCard ? 'no-card' : 'unknown';
    }

    /**
     * @param int $cardStatusId
     * @return string
     */
    public static function getCardStatusNameById($cardStatusId)
    {
        if (isset(self::$cardStatuses[$cardStatusId])) {
            return self::$cardStatuses[$cardStatusId];
        }

        return 'Liquid form of Status';
    }

    /**
     * @return array
     */
    public static function getCardStatuses()
    {
        return self::$cardStatuses;
    }
}
