<?php

namespace DDD\Service;

use CreditCard\Model\Token;
use CreditCard\Service\Card as CardService;
use CreditCard\Service\Card;
use DDD\Dao\Booking\BlackList;
use DDD\Dao\Booking\Booking as BookingDao;
use DDD\Dao\Booking\FraudDetection;

use Library\ActionLogger\Logger;
use Library\Constants\Objects;
use Library\Constants\TextConstants;
use Library\Constants\DbTables;
use Library\Finance\CreditCard\CreditCard;
use Library\Finance\Finance;

use Zend\Db\Sql\Select;

/**
 * Class Fraud
 * @package DDD\Service
 */
class Fraud extends ServiceBase
{
    const FRAUD_TYPE_EMAIL = 1;
    const FRAUD_TYPE_CC = 2;
    const FRAUD_TYPE_FULLNAME_PHONE = 3;
    const FRAUD_TYPE_FULLNAME_ADDRESS = 4;
    const FRAUD_TYPE_FULLNAME_HOLDERNAME = 5;
    const FRAUD_TYPE_COUNTRY_IP = 6;
    const FRAUD_TYPE_FULLNAME = 7;
    const FRAUD_TYPE_PHONE = 8;

    const FRAUD_VALUE_GREEN = 30;
    const FRAUD_VALUE_ORANGE = 65;

    /**
     * @param $reservationData \DDD\Domain\Booking\Booking
     * @param $creditCardData
     * @return bool
     */
    public function saveFraudForCreditCard($reservationData, $creditCardData)
    {
        /**
         * @var Logger $logger
         * @var FraudDetection $fraudDetectionDao
         * @var \DDD\Dao\Booking\BlackList $blackListDao
         * @var Booking $bookingDao
         */
        $fraudDetectionDao = $this->getServiceLocator()->get('dao_booking_fraud_detection');
        $logger = $this->getServiceLocator()->get('ActionLogger');

        try {
            /**
             * @var \CreditCard\Service\Fraud $fraudCreditCardService
             */
            $fraudCreditCardService = $this->getServiceLocator()->get('service_fraud_cc');

            $hash = $fraudCreditCardService->composeCreditCardHashForBlackList($creditCardData['number'], $creditCardData['month'], $creditCardData['year']);
            $hashId = $fraudCreditCardService->checkCreditCardHashExistenceInBlackList($hash);

            if ($hashId) {
                /**
                 * @var CardService $cardService
                 */
                $cardService = $this->getServiceLocator()->get('service_card');
                $cardService->changeCardStatus($creditCardData['id'], CardService::CC_STATUS_FRAUD);

                // Action Log
                $logger->save(Logger::MODULE_BOOKING, $reservationData->getId(), Logger::ACTION_BlACK_LIST, 'Credit Card Detected as Fraud');
            }

            // Detect holder name and fullName
            if (isset($creditCardData['holder']) && $this->removeSymbols($creditCardData['holder']) != $this->removeSymbols($reservationData->getGuestFirstName() . $reservationData->getGuestLastName())) {
                if (!$fraudDetectionDao->fetchOne(['reservation_id' => $reservationData->getId(), 'type' => self::FRAUD_TYPE_FULLNAME_HOLDERNAME])) {
                    $fraudDetectionDao->save([
                        'reservation_id' => $reservationData->getId(),
                        'type' => self::FRAUD_TYPE_FULLNAME_HOLDERNAME
                    ]);
                }
            }

            return true;
        } catch (\Exception $e) {
            $this->gr2logException($e, 'Cannot save fraud reservation', [
                'reservation_id' => $reservationData->getId(),
                'credit_card_id' => $creditCardData['id']
            ]);
        }

        return false;
    }

    /**
     * All Combination without CC
     *
     * @param bool|int $reservationId
     * @return bool
     */
    public function saveFraudByUpdate($reservationId = false)
    {
        if (!$reservationId) {
            $this->gr2crit('Cannot detect fraud Reservation', [
                'full_message'   => 'Cannot detect fraud Reservation',
                'reservation_id' => $reservationId
            ]);

            return false;
        }

        try {
            /**
             * @var $blackListDao BlackList
             */
            $blackListDao = $this->getServiceLocator()->get('dao_booking_black_list');
            $blackList = $blackListDao->getBlackListByReservationIdAndType($reservationId, [
                self::FRAUD_TYPE_EMAIL,
                self::FRAUD_TYPE_FULLNAME_PHONE,
                self::FRAUD_TYPE_FULLNAME_ADDRESS,
                self::FRAUD_TYPE_COUNTRY_IP,
                self::FRAUD_TYPE_FULLNAME,
                self::FRAUD_TYPE_PHONE,
            ]);

            if ($blackList->count()) {
                $data = $this->getFraudCombinationAndData($reservationId);

                if ($data) {
                    foreach ($blackList as $row) {
                        switch($row['type']) {
                            case  self::FRAUD_TYPE_EMAIL:
                                $hash = $data['guest_email'];
                                break;
                            case  self::FRAUD_TYPE_FULLNAME_PHONE:
                                $hash = $data['fullNamePhone'];
                                break;
                            case  self::FRAUD_TYPE_FULLNAME_ADDRESS:
                                $hash = $data['fullNameAddress'];
                                break;
                            case  self::FRAUD_TYPE_FULLNAME:
                                $hash = $data['fullName'];
                                break;
                            case  self::FRAUD_TYPE_PHONE:
                                $hash = $data['phone'];
                                break;
                        }

                        $blackListDao->save(['hash' => $hash], [
                            'id' => $row['id']
                        ]);
                    }
                }
            }

            return true;
        } catch (\Exception $e) {
            $this->gr2logException($e, 'Cannot save fraud Reservation', [
                'reservation_id' => $reservationId
            ]);
        }

        return false;
    }

    /**
     * @param $reservationId
     * @param bool|false $addCardToFraud
     * @return array
     */
    public function saveFraudManual($reservationId, $addCardToFraud = true)
    {
        /**
         * @var \DDD\Dao\Booking\Booking $reservationsDao
         * @var BlackList $blackListDao
         * @var Logger $logger
         * @var CardService $cardService
         */
        $blackListDao = $this->getServiceLocator()->get('dao_booking_black_list');
        $cardService = $this->getServiceLocator()->get('service_card');

        $data = $this->getFraudCombinationAndData($reservationId);
        $hashList = [];
        $error = '';
        if (!$data) {
            return [
                'status' => 'error',
                'msg' => TextConstants::ERROR,
            ];
        }

        // Ginosi reservation
        if (isset($data['reservation']['guest_email']) && strstr($data['reservation']['guest_email'], '@ginosi.com')) {
            return [
                'status' => 'error',
                'msg' => TextConstants::ERROR_IS_GINOSI,
            ];
        }

        // add card to fraud
        if ($addCardToFraud) {
            try {
                $reservationsDao = new BookingDao($this->getServiceLocator(), '\ArrayObject');

                $customerId = $reservationsDao->getCustomerIdByReservationId($reservationId);

                if ($customerId) {
                    $customerCreditCards = $cardService->getCreditCardsRemoteDataByCustomerId($customerId);

                    if (count($customerCreditCards) > 0) {
                        foreach ($customerCreditCards as $customerCreditCard) {
                            if ($customerCreditCard->getStatus() != CardService::CC_STATUS_FRAUD) {
                                $cardService->changeCardStatus($customerCreditCard->getId(), CardService::CC_STATUS_FRAUD);
                            }
                        }
                    }
                }
            } catch (\Exception $ex) {
                $error .= '<br>Credit Card was not added to Blacklist';
            }
        }

        foreach ($data as $key => $row) {
            if ($row) {
                $hashSave = false;

                switch ($key){
                    case 'guest_email':
                        $type = self::FRAUD_TYPE_EMAIL;
                        $hashSave = true;

                        break;
                    case 'fullName':
                        $type = self::FRAUD_TYPE_FULLNAME;
                        $hashSave = true;

                        break;
                    case 'phone':
                        $type = self::FRAUD_TYPE_PHONE;
                        $hashSave = true;

                        break;
                    case 'fullNamePhone':
                        $type = self::FRAUD_TYPE_FULLNAME_PHONE;
                        $hashSave = true;

                        break;
                    case 'fullNameAddress':
                        $type = self::FRAUD_TYPE_FULLNAME_ADDRESS;
                        $hashSave = true;

                        break;
                }

                if ($hashSave) {
                    $hashList[] = ['type' => $type, 'hash' => $row];
                }
            }
        }

        if (!empty($hashList)) {
            foreach ($hashList as $row) {
                $blackListDao->save([
                    'hash' => $row['hash'],
                    'type' => $row['type'],
                    'reservation_id' => $reservationId,
                ]);
            }

            $logger = $this->getServiceLocator()->get('ActionLogger');
            $logger->save(Logger::MODULE_BOOKING, $reservationId, Logger::ACTION_BlACK_LIST, 'Add to Black list');
        }

        if ($error) {
            $status = 'warning';
            $msg = '<br>' . TextConstants::SUCCESS_ADD_TO_BLACKLIST . $error;
        } else {
            $status = 'success';
            $msg = '<br>' . TextConstants::SUCCESS_ADD_TO_BLACKLIST;
        }

        return [
            'status' => $status,
            'msg' => $msg,
        ];
    }

    /**
     * @param int $reservationId
     * @return bool
     */
    public function removeFromFraud($reservationId)
    {
        /**
         * @var \DDD\Dao\Booking\Booking $bookingDao
         * @vra Fraud $fraudDetectionDao
         * @var BlackList $blackListDao
         * @var CardService $cardService
         */
        $blackListDao = $this->getServiceLocator()->get('dao_booking_black_list');
        $bookingDao = new BookingDao($this->getServiceLocator(), '\ArrayObject');
        $cardService = $this->getServiceLocator()->get('service_card');

        // Remove from Blacklist
        $blackListDao->delete(['reservation_id' => $reservationId]);

        // Remove from detection table (used for only holder/guest names mismatch)
        /**
         * @var $fraudDetectionDao FraudDetection
         */
        $fraudDetectionDao = $this->getServiceLocator()->get('dao_booking_fraud_detection');
        $fraudDetectionDao->delete(['reservation_id' => $reservationId]);

        $customerId = $bookingDao->getCustomerIdByReservationId($reservationId);

        /**
         * @var Token $tokenDao
         */
        $tokenDao = $this->getServiceLocator()->get('dao_cc_token');

        $customerFraudCreditCards = $tokenDao->fetchAll(
            [
                'customer_id' => $customerId,
                'status' => CardService::CC_STATUS_FRAUD
            ],
            [
                'id'
            ]
        );

        foreach ($customerFraudCreditCards as $fraudCreditCard) {
            $cardService->changeCardStatus($fraudCreditCard->getId(), CardService::CC_STATUS_UNKNOWN);
        }

        /**
         * @var Logger $logger
         */
        $logger = $this->getServiceLocator()->get('ActionLogger');
        $logger->save(Logger::MODULE_BOOKING, $reservationId, Logger::ACTION_BlACK_LIST, 'Remove from Black list');

        return [
            'status' => 'success',
            'msg' => TextConstants::SUCCESS_DELETE
        ];
    }

    /**
     * @param int $reservationId
     * @return array
     * @throws \Exception
     */
    public function getFraudForReservation($reservationId)
    {
        if(!$reservationId) {
            throw new \Exception('Invalid Data for Fraud detection');
        }
        $fraudValue = $blackListValue = 0; $fraudText = '';
        $linkTicket = '<a href="/booking/edit/%s" target="_blank">%s</a>' . "\n";

        /**
         * @var $fraudDetectionDao FraudDetection
         */

        // No Real Time Detection. Fraud Detection for Credit Card and HolderName <-> FullName
        $fraudDetectionDao = $this->getServiceLocator()->get('dao_booking_fraud_detection');
        $fraudCCData = $fraudDetectionDao->getFraudByReservationId($reservationId);

        if ($fraudCCData->count()) {
            foreach ($fraudCCData as $row) {
                switch($row['type']) {
                    case self::FRAUD_TYPE_FULLNAME_HOLDERNAME:
                        $fraudText .= TextConstants::FRAUD_NAME_HOLDER . '<br>';
                        $fraudValue += Objects::getFraudValue()['name_holder'];
                        break;
                }
            }
        }

        // credit cards with status "Fraud"
        // if there is one cc with status "Fraud" fraud score will be incremented by 100
        $reservationsDao = new BookingDao($this->getServiceLocator(), '\ArrayObject');

        $customerId = $reservationsDao->getCustomerIdByReservationId($reservationId);

        /**
         * @var Token $tokenDao
         */
        $tokenDao = $this->getServiceLocator()->get('dao_cc_token');

        $customerFraudCreditCards = $tokenDao->fetchAll(
            [
                'customer_id' => $customerId,
                'status' => CardService::CC_STATUS_FRAUD
            ],
            [
                'id'
            ]
        );

        if ($customerFraudCreditCards->count()) {
            $fraudText .= TextConstants::FRAUD_CREDIT_CARD . '<br>';
            $fraudValue += Objects::getFraudValue()['credit_card'];
        }

        // Real Time Fraud Detection from blacklist
        $data = $this->getFraudCombinationAndData($reservationId);
        /**
         * @var $blackListDao BlackList
         */
        $blackListDao = $this->getServiceLocator()->get('dao_booking_black_list');
        $blackListResult = $blackListDao->getBlackList([
            'fullName' => $data['fullName'],
            'fullNamePhone' => $data['fullNamePhone'],
            'fullNameAddress' => $data['fullNameAddress'],
            'email' => $data['guest_email'],
            'phone' => $data['phone'],
        ]);

        if ($blackListResult->count()) {
            $blackListArr = [];
            foreach ($blackListResult as $row) {
                $blackListArr[$row['type']] = $row;
            }

            // Filtering duplicates.
            if (!empty($blackListArr[self::FRAUD_TYPE_FULLNAME_PHONE])) {
                unset($blackListArr[self::FRAUD_TYPE_PHONE]);
                unset($blackListArr[self::FRAUD_TYPE_FULLNAME]);
            }

            foreach ($blackListArr as $row) {
                switch($row['type']) {
                    case self::FRAUD_TYPE_EMAIL:
                        $fraudText .= ($reservationId != $row['reservation_id']) ? sprintf($linkTicket, $row['res_number'], TextConstants::FRAUD_BLACKLIST_EMAIL)
                                                           : TextConstants::FRAUD_BLACKLIST_EMAIL . "\n";
                        $fraudText .= '<br>';
                        $blackListValue = Objects::getFraudValue()['black_list'];
                        break;
                    case self::FRAUD_TYPE_FULLNAME_PHONE:
                        $fraudText .= ($reservationId != $row['reservation_id']) ? sprintf($linkTicket, $row['res_number'], TextConstants::FRAUD_BLACKLIST_NSP)
                                                           : TextConstants::FRAUD_BLACKLIST_NSP . "\n";
                        $fraudText .= '<br>';
                        $blackListValue = Objects::getFraudValue()['black_list'];
                        break;
                    case self::FRAUD_TYPE_FULLNAME_ADDRESS:
                        $fraudText .= ($reservationId != $row['reservation_id']) ? sprintf($linkTicket, $row['res_number'], TextConstants::FRAUD_BLACKLIST_NSA)
                                                           : TextConstants::FRAUD_BLACKLIST_NSA . "\n";
                        $fraudText .= '<br>';
                        $blackListValue = Objects::getFraudValue()['black_list'];
                        break;
                    case self::FRAUD_TYPE_FULLNAME:
                        $fraudText .= ($reservationId != $row['reservation_id']) ? sprintf($linkTicket, $row['res_number'], TextConstants::FRAUD_BLACKLIST_NS)
                                                           : TextConstants::FRAUD_BLACKLIST_NS . "\n";
                        $fraudText .= '<br>';
                        $blackListValue = Objects::getFraudValue()['full_name'];
                        break;
                    case self::FRAUD_TYPE_PHONE:
                        $fraudText .= ($reservationId != $row['reservation_id']) ? sprintf($linkTicket, $row['res_number'], TextConstants::FRAUD_BLACKLIST_PHONE)
                                                           : TextConstants::FRAUD_BLACKLIST_PHONE . "\n";
                        $fraudText .= '<br>';
                        $blackListValue = Objects::getFraudValue()['phone'];
                        break;
                }
            }
        }

        // Fraud Detection Country IP
        $ipAddress = long2ip($data['reservation']['ip_address']);
        if(filter_var($ipAddress, FILTER_VALIDATE_IP) && $ipAddress != '127.0.0.1') {
            $geoLocationDao = $this->getServiceLocator()->get('dao_geolite_country_geolite_country');
            $countryID = $geoLocationDao->getCountryIDByIp(ip2long($ipAddress));

            if ($data['reservation']['guest_country_id'] != $countryID) {
                $fraudValue += Objects::getFraudValue()['country_ip'];
                $fraudText .= TextConstants::FRAUD_COUNTRY_IP . '<br>';
            }
        }

        $fraudValue += $blackListValue;
        if($fraudValue < self::FRAUD_VALUE_GREEN) {
            $class = 'label-default';
        } elseif($fraudValue >= self::FRAUD_VALUE_GREEN && $fraudValue < self::FRAUD_VALUE_ORANGE) {
            $class = 'label-warning';
        } else {
            $class = 'label-danger';
        }

        if($fraudValue == 0) {
            $fraudText = TextConstants::FRAUD_NONE . '<br>';
            $fraudValue = TextConstants::FRAUD_NONE;
        }
        return ['value' => $fraudValue,'text' => $fraudText, 'class' => $class];
    }

    /**
     * @param $reservationId
     * @return array|bool
     */
    private function getFraudCombinationAndData($reservationId)
    {
        $bookingDao = new BookingDao($this->getServiceLocator(), '\ArrayObject');

        $reservation = $bookingDao->fetchOne(function (Select $select) use ($reservationId) {
            $select
                ->columns([
                    'guest_email',
                    'guest_first_name',
                    'guest_last_name',
                    'guest_phone',
                    'guest_address',
                    'guest_city_name',
                    'guest_zip_code',
                    'guest_country_id',
                    'res_number'
                ])
                ->join(
                    ['customer_identity' => DbTables::TBL_CUSTOMER_IDENTITY],
                    DbTables::TBL_BOOKINGS . '.id = customer_identity.reservation_id',
                    ['ip_address'],
                    Select::JOIN_LEFT
                );

            $select->where->equalTo(DbTables::TBL_BOOKINGS . '.id', $reservationId);
        });

        if (!$reservation) {
            return false;
        }

        $phone           = $reservation['guest_phone'] ? $this->removeSymbols($reservation['guest_phone']) : '';
        $fullName        = $this->removeSymbols($reservation['guest_first_name'] . $reservation['guest_last_name']);
        $fullNamePhone   = $this->removeSymbols($reservation['guest_first_name'] . $reservation['guest_last_name'] . $reservation['guest_phone']);
        $fullNameAddress = $this->removeSymbols($reservation['guest_first_name'] . $reservation['guest_last_name'] . $reservation['guest_address'] .
            $reservation['guest_city_name'] .  $reservation['guest_zip_code'] . $reservation['guest_country_id']);

        return [
            'guest_email'     => $this->removeSymbols($reservation['guest_email']),
            'phone'           => $phone,
            'fullName'        => $fullName,
            'fullNamePhone'   => $fullNamePhone,
            'fullNameAddress' => $fullNameAddress,
            'reservation'     => $reservation,
        ];
    }

    /**
     * @param int $reservationId
     * @return bool
     */
    public function isFraudReservation($reservationId)
    {
        $reservationsDao = new BookingDao($this->getServiceLocator(), '\ArrayObject');

        $customerId = $reservationsDao->getCustomerIdByReservationId($reservationId);

        /**
         * @var Token $tokenDao
         */
        $tokenDao = $this->getServiceLocator()->get('dao_cc_token');

        $customerFraudCreditCards = $tokenDao->fetchAll(
            [
                'customer_id' => $customerId,
                'status' => CardService::CC_STATUS_FRAUD
            ],
            [
                'id'
            ]
        );

        /**
         * @var $blackListDao BlackList
         */
        $blackListDao = $this->getServiceLocator()->get('dao_booking_black_list');
        $data = $this->getFraudCombinationAndData($reservationId);
        $blackListResult = $blackListDao->getBlackList([
            'fullName' => $data['fullName'],
            'fullNamePhone' => $data['fullNamePhone'],
            'fullNameAddress' => $data['fullNameAddress'],
            'email' => $data['guest_email'],
            'phone' => $data['phone'],
        ]);

        if ($customerFraudCreditCards->count() || $blackListResult->count()) {
            return true;
        }
        return false;
    }

    /**
     * @param $value
     * @return string
     */
    private function removeSymbols($value){
        return md5(strtolower(preg_replace('/[^\p{L}\p{N}]/u', '', $value)));
    }
}
