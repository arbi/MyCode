<?php

namespace CreditCard\Service;

use CreditCard\Entity\CompleteData;
use CreditCard\Model\Token as TokenDAO;
use CreditCard\Rest\RestRequest;
use CreditCard\Service\SaltGenerator;

use DDD\Dao\Booking\Booking;
use DDD\Service\ServiceBase;

use Library\ActionLogger\Logger;
use Library\Constants\DomainConstants;

/**
 * Class Store
 * @package DDD\Service\CreditCard
 *
 * @author Tigran Petrosyan
 */
class Store extends ServiceBase
{
    /**
     * @param  CompleteData $creditCardRawData
     * @return string
     * @throws \CreditCard\Rest\Exception
     * @throws \CreditCard\Rest\InvalidArgumentException
     * @throws \Exception
     */
    public function store($creditCardRawData)
    {
        /**
         * @var Logger $logger
         * @var Booking $bookingDao
         * @var \DDD\Domain\Booking\Booking $reservation
         * @var Encrypt $encryptService
         * @var SaltGenerator $saltGenerator
         */
        $logger           = $this->getServiceLocator()->get('ActionLogger');
        $bookingDao       = $this->getServiceLocator()->get('dao_booking_booking');
        $encryptService   = $this->getServiceLocator()->get('service_encrypt');
        $saltGenerator  = $this->getServiceLocator()->get('service_salt_generator');

        $pan              = $encryptService->decrypt($creditCardRawData->getPan(), '');
        $panLength        = strlen($pan);
        $last10Digits     = substr($pan, -10);
        $firstDigitsCount = $panLength - 10;
        $firstDigits      = substr($pan, 0, $firstDigitsCount);
        $holder           = $encryptService->decrypt($creditCardRawData->getHolder(), '');
        $securityCode     = $encryptService->decrypt($creditCardRawData->getSecurityCode(), '');
        $expirationMonth  = $encryptService->decrypt($creditCardRawData->getExpirationMonth(), '');
        $expirationYear   = $encryptService->decrypt($creditCardRawData->getExpirationYear(), '');

        /**
         * @var TokenDAO $tokenDao
         */
        $tokenDao = $this->getServiceLocator()->get('dao_cc_token');

        $reservation = $bookingDao->fetchOne(
            ['customer_id' => $creditCardRawData->getCustomerId()],
            [
                'id',
                'guest_first_name',
                'guest_last_name'
            ]
        );

        if (DomainConstants::BO_DOMAIN_NAME != "backoffice.ginosi.com") {

            $salt      = 'sample_sal';
            $token     = 'sample_token';
            $encrypted = $encryptService->encrypt($firstDigits, $salt);

            $tokenDao->insert([
                'first_digits' => $encrypted,
                'brand'        => $creditCardRawData->getBrand(),
                'partner_id'   => $creditCardRawData->getPartnerId(),
                'customer_id'  => $creditCardRawData->getCustomerId(),
                'source'       => $creditCardRawData->getSource(),
                'status'       => $creditCardRawData->getStatus(),
                'is_default'   => is_null($creditCardRawData->getIsDefault()) ? 0 : $creditCardRawData->getIsDefault(),
                'date_provided'=> $creditCardRawData->getDateProvided(),
                'salt'         => $salt,
                'token'        => $token
            ]);

            return $token;

        } else {

            $salt      = $saltGenerator->generateSalt();
            $encrypted = $encryptService->encrypt($firstDigits, $salt);

            $request = new RestRequest(
                'PUT',
                [
                    'last10'        => $last10Digits,
                    'security_code' => $securityCode,
                    'holder'        => $holder,
                    'exp_month'     => $expirationMonth,
                    'exp_year'      => $expirationYear,
                ]
            );

            $config      = $this->getServiceLocator()->get('Config');
            $apiEndpoint = $config['vault-configuration']['storage_api_endpoint'];
            $apiPort     = $config['vault-configuration']['storage_api_port'];
            $apiKey      = $config['vault-configuration']['storage_api_key'];

            $request->setUrl($apiEndpoint);
            $request->setPort($apiPort);
            $request->setApiKey($apiKey);

            $request->execute();

            $responseInfo = $request->getResponseInfo();
            if ($responseInfo['http_code'] == 201) {

                $responseBody = $request->getResponseBody();

                $responseArray = json_decode($responseBody);
                $token         = $responseArray->token;

                $tokenDao->insert([
                    'token'        => $token,
                    'first_digits' => $encrypted,
                    'brand'        => $creditCardRawData->getBrand(),
                    'partner_id'   => $creditCardRawData->getPartnerId(),
                    'customer_id'  => $creditCardRawData->getCustomerId(),
                    'source'       => $creditCardRawData->getSource(),
                    'status'       => $creditCardRawData->getStatus(),
                    'is_default'   => is_null($creditCardRawData->getIsDefault()) ? 0 : $creditCardRawData->getIsDefault(),
                    'date_provided'=> $creditCardRawData->getDateProvided(),
                    'salt'         => $salt
                ]);

                $ccId = $tokenDao->getLastInsertValue();

                $newCreditCardAction = 0;

                switch ($creditCardRawData->getSource()) {
                    case Card::CC_SOURCE_CHANNEL_RESERVATION_SYSTEM:
                        $newCreditCardAction = Logger::ACTION_NEW_CC_CHANNEL_RESERVATION_SYSTEM;
                        break;
                    case Card::CC_SOURCE_CHANNEL_MODIFICATION_SYSTEM:
                        $newCreditCardAction = Logger::ACTION_NEW_CC_FROM_CHANNEL_MODIFICATION_SYSTEM;
                        break;
                    case Card::CC_SOURCE_WEBSITE_GUEST:
                        $newCreditCardAction = Logger::ACTION_NEW_CC_FROM_WEBSITE_GUEST;
                        break;
                    case Card::CC_SOURCE_WEBSITE_EMPLOYEE:
                        $newCreditCardAction = Logger::ACTION_NEW_CC_FROM_WEBSITE_EMPLOYEE;
                        break;
                    case Card::CC_SOURCE_WEBSITE_RESERVATION_GUEST:
                        $newCreditCardAction = Logger::ACTION_NEW_CC_FROM_WEBSITE_RESERVATION_GUEST;
                        break;
                    case Card::CC_SOURCE_WEBSITE_RESERVATION_EMPLOYEE:
                        $newCreditCardAction = Logger::ACTION_NEW_CC_FROM_WEBSITE_RESERVATION_EMPLOYEE;
                        break;
                    case Card::CC_SOURCE_FRONTIER_DASHBOARD_EMPLOYEE:
                        $newCreditCardAction = Logger::ACTION_NEW_CC_FROM_FRONTIER_DASHBOARD_EMPLOYEE;
                        break;
                }

                if ($reservation) {
                    $logger->save(Logger::MODULE_BOOKING, $reservation->getId(), $newCreditCardAction, $ccId);
                }

                /**
                 * @var \DDD\Service\Fraud $serviceFraud
                 */
                $serviceFraud = $this->getServiceLocator()->get('service_fraud');

                // CC fraud check
                $serviceFraud->saveFraudForCreditCard(
                    $reservation,
                    [
                        'id' => $ccId,
                        'number' => $pan,
                        'year' => $expirationMonth,
                        'month' => $expirationYear,
                        'holder' => $holder,
                    ]
                );

                /**
                 * @var \DDD\Service\Booking\ReservationIssues $reservationIssuesService
                 */
                $reservationIssuesService = $this->getServiceLocator()->get('service_booking_reservation_issues');

                $expirationDate = ((strlen($expirationYear) === 2) ? '20' . $expirationYear : $expirationYear)
                    . '-' . $expirationMonth;

                // check CC issues
                $reservationIssuesService->checkReservationIssues(
                    $reservation->getId(),
                    [
                        'cc_date' => $expirationDate,
                        'clear_old_cc_issue' => true
                    ]
                );

                return $token;
            } else {
                $this->gr2crit("Cannot store new credit card in KAS",
                    [
                        'reservation_id' => $reservation->getId(),
                        'response_status' => $responseInfo['http_code']
                    ]
                );

                return false;
            }
        }
    }

    public function storeLocalData($firstDigits)
    {

    }

    public function storeRemoteData($last10Digits)
    {

    }
}
