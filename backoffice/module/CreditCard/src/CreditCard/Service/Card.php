<?php

namespace CreditCard\Service;

use CreditCard\Entity\CompleteData;
use CreditCard\Model\Token as TokenDAO;
use CreditCard\Model\Token;
use DDD\Service\Partners;
use DDD\Service\ServiceBase;
use Library\Constants\Objects;
use Library\Finance\CreditCard\CreditCard;
use Library\Finance\CreditCard\CreditCardValidator;

/**
 * Class Card
 * @package CreditCard\Service
 *
 * @author Tigran Petrosyan
 */
class Card extends ServiceBase
{
    // Card Types
    const CC_BRAND_UNKNOWN = 0;
    const CC_BRAND_VISA = 1;
    const CC_BRAND_MASTERCARD = 2;
    const CC_BRAND_AMEX = 3;
    const CC_BRAND_DISCOVER = 4;
    const CC_BRAND_JCB = 5;
    const CC_BRAND_DINERS_CLUB = 6;
    const CC_BRAND_DINERS_CLUB_US = 7;
    const CC_BRAND_MASTERCARD_OR_DINERS_CLUB = 8;

    // Card Statuses
    const CC_STATUS_UNKNOWN = 1;
    const CC_STATUS_VALID = 2;
    const CC_STATUS_INVALID = 3;
    const CC_STATUS_TEST = 4;
    const CC_STATUS_FRAUD = 5;
    const CC_STATUS_DO_NOT_USE = 6;

    // Source
    const CC_SOURCE_CHANNEL_RESERVATION_SYSTEM = 1;
    const CC_SOURCE_CHANNEL_MODIFICATION_SYSTEM = 2;
    const CC_SOURCE_WEBSITE_GUEST = 3;
    const CC_SOURCE_WEBSITE_EMPLOYEE = 4;
    const CC_SOURCE_WEBSITE_RESERVATION_GUEST = 5;
    const CC_SOURCE_WEBSITE_RESERVATION_EMPLOYEE = 6;
    const CC_SOURCE_FRONTIER_DASHBOARD_EMPLOYEE = 7;

    /**
     * @param $source
     * @return string
     */
    public static function getCreditCardSourceName($source)
    {
        switch ($source) {
            case self::CC_SOURCE_WEBSITE_GUEST:
            case self::CC_SOURCE_WEBSITE_EMPLOYEE:
            case self::CC_SOURCE_WEBSITE_RESERVATION_GUEST:
            case self::CC_SOURCE_WEBSITE_RESERVATION_EMPLOYEE:
                return 'Website';
                break;
            case self::CC_SOURCE_CHANNEL_RESERVATION_SYSTEM:
            case self::CC_SOURCE_CHANNEL_MODIFICATION_SYSTEM:
                return 'Cubilis';
                break;
            case self::CC_SOURCE_FRONTIER_DASHBOARD_EMPLOYEE:
                return 'Frontier';
                break;
            default:
                return 'Unknown Source';
                break;
        }
    }

    /**
     * @return array
     */
    public static function getExpirationMonthOptions()
    {
        $options = [];
        $options[0] = '-- Please Select --';

        for ($month = 1; $month <= 12; $month++) {
            $options[$month] = $month;
        }

        return $options;
    }

    /**
     * @return array
     */
    public static function getExpirationYearOptions()
    {
        $options = [];
        $options[0] = '-- Please Select --';

        $currentYear = date('Y');
        $limitYear = $currentYear + 16;

        for ($year = $currentYear; $year <= $limitYear; $year++) {
            $options[$year] = $year;
        }

        return $options;
    }

    /**
     * @param $creditCardId
     * @return int
     *
     * @author Tigran Petrosyan
     */
    public function getCardStatus($creditCardId)
    {
        /**
         * @var TokenDAO $tokenDao
         */
        $tokenDao = $this->getServiceLocator()->get('dao_cc_token');

        $status = $tokenDao->getStatus($creditCardId);

        return $status;
    }

    /**
     * @param $creditCardId
     * @return int
     *
     * @author Tigran Petrosyan
     */
    public function getCardPartnerBusinessModel($creditCardId)
    {
        /**
         * @var TokenDAO $tokenDao
         */
        $tokenDao = $this->getServiceLocator()->get('dao_cc_token');

        $businessModel = $tokenDao->getPartnerBusinessModel($creditCardId);

        return $businessModel;
    }

    /**
     * @param $ccId
     * @param $partnerId
     * @return bool
     */
    public function changePartnerId($ccId, $partnerId)
    {
        /**
         * @var TokenDAO $tokenDao
         */
        $tokenDao = $this->getServiceLocator()->get('dao_cc_token');

        $tokenDao->save(
            ['partner_id' => $partnerId],
            ['id' => $ccId]
        );

        return true;
    }

    /**
     * @param $creditCardId
     * @param $status
     * @return bool
     *
     * @author Tigran Petrosyan
     */
    public function changeCardStatus($creditCardId, $status)
    {
        /**
         * @var TokenDAO $tokenDao
         * @var Fraud $fraudCreditCardService
         */
        $tokenDao = $this->getServiceLocator()->get('dao_cc_token');
        $fraudCreditCardService = $this->getServiceLocator()->get('service_fraud_cc');

        $statusBeforeUpdate = $this->getCardStatus($creditCardId);

        $tokenDao->save(
            ['status' => $status],
            ['id' => $creditCardId]
        );

        if ($status == self::CC_STATUS_FRAUD) {
            $fraudCreditCardService->addCreditCardToBlackList($creditCardId);
        } else {
            if ($statusBeforeUpdate == self::CC_STATUS_FRAUD) {
                $fraudCreditCardService->removeCreditCardFromBlackList($creditCardId);
            }
        }

        return true;
    }

    /**
     * @param $customerId
     * @param $status
     * @return bool
     */
    public function changeCustomerCardStatuses($customerId, $status)
    {
        /**
         * @var TokenDAO $tokenDao
         */
        $tokenDao = $this->getServiceLocator()->get('dao_cc_token');

        $tokenDao->save(
            ['status' => $status],
            ['customer_id' => $customerId]
        );

        return true;
    }

    /**
     * @param $rawData
     * @return int
     */
    public function processCreditCardData($rawData)
    {
        $partnerId = Partners::PARTNER_WEBSITE;
        /**
         * @var \CreditCard\Service\Encrypt $encryptService
         */
        $encryptService = $this->getServiceLocator()->get('service_encrypt');

        // Detect partner business model
        if (isset($rawData['partner_id']) && $rawData['partner_id']) {
            /**
             * @var \DDD\Dao\Partners\Partners $partnerDao
             */
            $partnerDao = $this->getServiceLocator()->get('dao_partners_partners');
            $partnerData = $partnerDao->getPartnerModel($rawData['partner_id']);
            if ($partnerData && $partnerData->getBusinessModel() == Partners::BUSINESS_MODEL_GINOSI_COLLECT_PARTNER) {
                $partnerId = $rawData['partner_id'];
            }
        }

        // Save CC Details
        $creditCard = new CompleteData();

        $pan = isset($rawData['number']) ? $rawData['number'] : '';
        $creditCard->setPan($encryptService->encrypt($pan, ''));

        $holder = isset($rawData['holder']) ? $rawData['holder'] : '';
        $creditCard->setHolder($encryptService->encrypt($holder, ''));

        $cvc = isset($rawData['cvc']) ? $rawData['cvc'] : '';
        $creditCard->setSecurityCode($encryptService->encrypt($cvc, ''));

        if (isset($rawData['year']) && strlen($rawData['year']) == 4) {
            $rawData['year'] = substr($rawData['year'], 2, 2);
        }

        $year = isset($rawData['year']) ? $rawData['year'] : '';
        $creditCard->setExpirationYear($encryptService->encrypt($year, ''));

        $month = isset($rawData['month']) ? $rawData['month'] : '';
        $creditCard->setExpirationMonth($encryptService->encrypt($month, ''));

        // Card Type
        $creditCardValidator = new CreditCardValidator();
        $cardType = $creditCardValidator->getCardTypeByNumber(
            substr($pan, 0, 6)
        );

        $creditCard->setBrand(0);
        if ($cardType) {
            $creditCardType = Objects::getCreditCardId($cardType);
            $creditCard->setBrand($creditCardType);
        }

        $creditCard->setSource($rawData['source']);
        $creditCard->setStatus(self::CC_STATUS_UNKNOWN);
        $creditCard->setPartnerId($partnerId);
        $creditCard->setCustomerId($rawData['customer_id']);
        $creditCard->setDateProvided(date('Y-m-d h:i:s'));


        // Save cc details locally
//        $this->gr2info('Successfully created CC data from ' . ucfirst($params['source_provider']), ['module' => 'Finance']);
//        $ccId = $creditCard->getId();

        /**
         * @var Queue $creditCardCreationQueueService
         */
        $creditCardCreationQueueService = $this->getServiceLocator()->get('service_card_creation_queue');

        $cardId = $creditCardCreationQueueService->insert($creditCard);

        return $cardId;

//        $this->gr2info('Successfully send to Processing Queue from ' . ucfirst($params['source_provider']), ['module' => 'Finance']);
//
//        $this->gr2info('Customer or CC create/update end from ' . ucfirst($params['source_provider']), ['module' => 'Finance']);
//
//        $logger->save(Logger::MODULE_BOOKING, $reservationId, Logger::ACTION_NEW_CC_DETAILS, $cardId);


//        $this->gr2logException($e, 'Cannot update CC data from Frontier');
//        $this->gr2debug($e->getMessage(), [
//            'reservation_id'              => $reservationId,
//            'customer_id'                 => $reservationData['customer_id'],
//            'credit_card_source_provider' => 'frontier'
//        ]);
    }


    /**
     * @param $customerId
     * @return array
     */
    public function getCustomerCreditCardsForReservationTicket($customerId)
    {
        /**
         * @var Token $tokenDao
         * @var Encrypt $encryptionService
         */
        $tokenDao = $this->getServiceLocator()->get('dao_cc_token');
        $encryptionService = $this->getServiceLocator()->get('service_encrypt');

        $cards = $tokenDao->getCustomerCreditCards($customerId);

        $preparedCreditCards = [];
        $hasValidCard = false;

        // Credit card Approved
        if ($cards->count()) {
            foreach ($cards as $card) {
                if (!$hasValidCard) {
                    $hasValidCard = true;
                }

                $preparedCard = [];

                $preparedCard['cc_id']          = $card->getId();
                $preparedCard['cc_number']      = $encryptionService->decrypt($card->getFirstDigits(), $card->getSalt()) . 'XX XXXX XXXX';
                $preparedCard['cc_holder_name'] = '----- -----';
                $preparedCard['cc_cvc']         = 'XXX';
                $preparedCard['cc_exp_year']    = 'XX';
                $preparedCard['cc_exp_month']   = 'XX';
                $preparedCard['is_default']     = $card->getIsDefault();
                $preparedCard['partner_name']   = $card->getPartnerName();
                $preparedCard['card_status']    = $card->getStatus();
                $preparedCard['card_type']      = $card->getBrand();
                $preparedCard['source']         = $card->getSource();
                $preparedCard['partner_id']     = $card->getPartnerId();
                $preparedCard['date_provided']  = $card->getDateProvided();

                /**
                 * @todo transaction status
                 */
                $preparedCard['transaction_status'] = 0;
                $preparedCreditCards[] = $preparedCard;
            }
        }

        return [
            'card_list' => $preparedCreditCards,
            'hasValidCard' => $hasValidCard,
        ];
    }

    /**
     * @param $customerId
     * @return array
     */
    public function getCustomerCreditCardsForFrontier($customerId)
    {
        /**
         * @var Token $tokenDao
         * @var Encrypt $encryptionService
         */
        $tokenDao = $this->getServiceLocator()->get('dao_cc_token');
        $encryptionService = $this->getServiceLocator()->get('service_encrypt');

        $cards = $tokenDao->getCustomerCreditCardsForFrontier($customerId);

        $preparedCreditCards = [];

        // Credit card Approved
        if ($cards->count()) {
            foreach ($cards as $card) {
                $preparedCard = [];

                $preparedCard['cc_id']           = $card->getId();
                $preparedCard['cc_number']       = $encryptionService->decrypt($card->getFirstDigits(), $card->getSalt()) . 'XX XXXX XXXX';
                $preparedCard['is_default']      = $card->getIsDefault();
                $preparedCard['partner_name']    = $card->getPartnerName();
                $preparedCard['card_status']     = $card->getStatus();
                $preparedCard['card_type']       = $card->getBrand();
                $preparedCard['source']          = $card->getSource();
                /**
                 * @todo transaction status
                 */
                $preparedCard['transaction_status'] = 0;
                $preparedCreditCards[] = $preparedCard;
            }
        }

        return $preparedCreditCards;
    }

    /**
     * @param $customerId
     * @return array
     */
    public function getCustomerCreditCardsForExistenceCheck($customerId)
    {
        /**
         * @var Token $tokenDao
         * @var Encrypt $encryptionService
         */
        $tokenDao = $this->getServiceLocator()->get('dao_cc_token');
        $encryptionService = $this->getServiceLocator()->get('service_encrypt');

        $cards = $tokenDao->getCustomerCreditCardsForExistenceCheck($customerId);

        $preparedCreditCards = [];

        if ($cards->count()) {
            foreach ($cards as $card) {
                $preparedCard = [];

                $preparedCard['id'] = $card['id'];
                $preparedCard['brand'] = $card['brand'];
                $preparedCard['token'] = $card['token'];
                $preparedCard['pan_first_digits'] = $encryptionService->decrypt($card['first_digits'], $card['salt']);

                $preparedCreditCards[] = $preparedCard;
            }
        }

        return $preparedCreditCards;
    }

    /**
     * @param $customerId
     * @return CompleteData[]
     */
    public function getCreditCardsRemoteDataByCustomerId($customerId)
    {
        /**
         * @var Token $tokenDao
         * @var Retrieve $retrieveService
         */
        $tokenDao = $this->getServiceLocator()->get('dao_cc_token');
        $retrieveService = $this->getServiceLocator()->get('service_retrieve_cc');

        $creditCardIds = $tokenDao->fetchAll(
            ['customer_id' => $customerId],
            [
                'id'
            ]
        );

        $creditCards = [];

        foreach ($creditCardIds as $creditCardId) {
            $creditCard = $retrieveService->getCreditCard($creditCardId->getId());

            $pan = $creditCard->getPan();
            $last4 = substr($pan, -4);
            $creditCard->setPan('');
            $creditCard->setLast4Digits($last4);
            $creditCard->setId($creditCardId->getId());

            $creditCards[] = $creditCard;
        }

        return $creditCards;
    }
}
