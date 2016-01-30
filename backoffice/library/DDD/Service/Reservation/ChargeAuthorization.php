<?php

namespace DDD\Service\Reservation;

use CreditCard\Model\Token as TokenDAO;
use CreditCard\Service\Retrieve;
use DDD\Dao\Booking\Booking as ReservationsDAO;
use DDD\Domain\Booking\ChargeAuthorization\ChargeAuthorizationCreditCard;
use DDD\Service\ServiceBase;
use Library\Finance\CreditCard\CreditCardHelper;

/**
 * Class ChargeAuthorization
 * @package DDD\Service\Reservation
 *
 * @author Tigran Petrosyan
 */
class ChargeAuthorization extends ServiceBase
{
    const CHARGE_AUTHORIZATION_PAGE_STATUS_NOT_GENERATED = 0;
    const CHARGE_AUTHORIZATION_PAGE_STATUS_GENERATED = 1;
    const CHARGE_AUTHORIZATION_PAGE_STATUS_VIEWED = 2;
    const CHARGE_AUTHORIZATION_PAGE_STATUS_CLOSED = 4;

    /**
     * @param $reservationId
     * @param $pageToken
     * @param $status
     * @return bool
     */
    public function changeChargeAuthorizationPageStatus($reservationId, $pageToken, $status)
    {

        $cccaDao = $this->getServiceLocator()->get('dao_finance_ccca');

        $result = $cccaDao->update(
            [
                'status'         => $status,
                'reservation_id' => $reservationId
            ],
            ['page_token' => $pageToken]
        );

        return $result ? true : false;
    }

    /**
     * @param $pageToken
     * @return mixed
     */
    public function getInfoForCCCAPage($pageToken)
    {

        $cccaDao = $this->getServiceLocator()->get('dao_finance_ccca');
        return $cccaDao->getInfoForCCCAPage($pageToken);
    }

    /**
     * @param $reservationId
     * @param $ccId
     * @param $amount
     * @return array
     */
    public function generateChargeAuthorizationPageLink($reservationId, $ccId, $amount)
    {
        /**
         * @var ReservationsDAO $reservationsDao
         * @var CccaDAO $cccaDao
         */
        $reservationsDao = $this->getServiceLocator()->get('dao_booking_booking');
        $cccaDao         = $this->getServiceLocator()->get('dao_finance_ccca');
        $result          = [];

        $reservationData = $reservationsDao->fetchOne(
            ['id' => $reservationId],
            [
                'timestamp',
                'res_number'
            ]
        );

        $cccaData = $cccaDao->fetchOne(
            [
                'reservation_id' => $reservationId,
                'cc_id'          => $ccId
            ]
        );

        if ($cccaData) {
            $result['cccaId'] = $cccaData->getId();
            $cccaDao->save(
                [
                'amount' => $amount
                ],
                ['id' => $cccaData->getId()]
            );
            $result['hasCcca'] = true;
        } else {
            $token = $this->generatePageToken($reservationData->getResNumber());

            $result['cccaId'] = $cccaDao->save(
                [
                    'reservation_id' => $reservationId,
                    'page_token'     => $token,
                    'status'         => self::CHARGE_AUTHORIZATION_PAGE_STATUS_GENERATED,
                    'cc_id'          => $ccId,
                    'created_date'   => date('y-m-d H:i:s'),
                    'amount'         => $amount
                ]
            );
            $result['hasCcca'] = false;
        }

        return $result;
    }

    /**
     * @param $reservationNumber
     * @return string
     */
    private function generatePageToken($reservationNumber)
    {
        $algorithm = 'sha256';

        return hash($algorithm, microtime() . $reservationNumber . 'ccca_page');
    }

    /**
     * @param $reservationId
     * @return \DDD\Domain\Booking\ChargeAuthorization\ChargeAuthorizationCreditCard[]
     */
    public function getCreditCardsForAuthorization($reservationId)
    {
        /**
         * @var TokenDAO $tokenDao
         * @var Retrieve $retrieveService
         */
        $tokenDao = $this->getServiceLocator()->get('dao_cc_token');
        $retrieveService = $this->getServiceLocator()->get('service_retrieve_cc');

        $cards = $tokenDao->getCreditCardsForAuthorization($reservationId);

        $cardsForChargeAuthorization = [];

        foreach ($cards as $card) {
            $token = $card->getToken();
            $remoteData = $retrieveService->getRemoteData($token);

            $cardForChargeAuthorization = new ChargeAuthorizationCreditCard();
            $cardForChargeAuthorization->setId($card->getId());
            $cardForChargeAuthorization->setBrand($card->getBrand());
            $cardForChargeAuthorization->setLast4Digits(substr($remoteData->getLast10(), -4));
            $cardForChargeAuthorization->setHolder($remoteData->getHolder());

            $cardsForChargeAuthorization[] = $cardForChargeAuthorization;
        }

        return $cardsForChargeAuthorization;
    }

    /**
     * @param $ccId
     * @return ChargeAuthorizationCreditCard
     */
    public function getCreditCardDataForAuthorizationPage($ccId)
    {
        /**
         * @var Retrieve $retrieveService
         */
        $retrieveService = $this->getServiceLocator()->get('service_retrieve_cc');

        $card = $retrieveService->getCreditCard($ccId);

        $cardForChargeAuthorization = new ChargeAuthorizationCreditCard();

        $cardForChargeAuthorization->setId($card->getId());
        $cardForChargeAuthorization->setBrand($card->getBrand());
        $cardForChargeAuthorization->setLast4Digits(substr($card->getPan(), -4));
        $cardForChargeAuthorization->setHolder($card->getHolder());

        return $cardForChargeAuthorization;
    }
}
