<?php

namespace CreditCard\Service;

use CreditCard\Model\FraudCC;
use CreditCard\Model\FraudCCHashes;
use DDD\Service\ServiceBase;
use DDD\Dao\Booking\Booking as BookingDAO;

/**
 * Class Fraud
 * @package DDD\Service\CreditCard
 *
 * @author Tigran Petrosyan
 */
class Fraud extends ServiceBase
{
    /**
     * @param $ccId
     * @return bool
     */
    public function addCreditCardToBlackList($ccId)
    {
        /**
         * @var FraudCC $fraudCreditCardDao
         */
        $fraudCreditCardDao = $this->getServiceLocator()->get('dao_fraud_cc');

        /**
         * @var $creditCardRetrieveService Retrieve
         */
        $creditCardRetrieveService = $this->getServiceLocator()->get('service_retrieve_cc');

        $card = $creditCardRetrieveService->getCreditCard($ccId);

        $pan = $card->getPan();

        $hash = $this->composeCreditCardHashForBlackList($pan);

        $hashId = $this->checkCreditCardHashExistenceInBlackList($hash);

        if (!$hashId) {
            $hashId = $this->addCreditCardHashToBlackList($hash);
        }

        $fraudCreditCardDao->insert([
            'cc_id' => $ccId,
            'hash_id' => $hashId
        ]);

        return true;
    }

    /**
     * @param $hash
     * @return bool
     */
    public function checkCreditCardHashExistenceInBlackList($hash)
    {
        /**
         * @var FraudCCHashes $fraudCreditCardHashesDao
         */
        $fraudCreditCardHashesDao = $this->getServiceLocator()->get('dao_fraud_cc_hashes');

        $result = $fraudCreditCardHashesDao->fetchOne(
            [
                'hash' => $hash
            ],
            [
                'id'
            ]
        );

        if ($result) {
            return $result['id'];
        }

        return false;
    }

    /**
     * @param $hash
     * @return bool|int
     */
    public function addCreditCardHashToBlackList($hash)
    {
        /**
         * @var FraudCCHashes $fraudCreditCardHashesDao
         */
        $fraudCreditCardHashesDao = $this->getServiceLocator()->get('dao_fraud_cc_hashes');

        $fraudCreditCardHashesDao->insert([
            'hash' => $hash
        ]);

        $autoIncrementId = $fraudCreditCardHashesDao->getLastInsertValue();

        if ($autoIncrementId) {
            return true;
        }

        return $autoIncrementId;
    }

    /**
     * @param $pan string
     * @return string string
     */
    public function composeCreditCardHashForBlackList($pan)
    {
        return hash('sha256', $pan);
    }

    /**
     * @param $ccId
     * @return bool
     */
    public function removeCreditCardFromBlackList($ccId)
    {
        /**
         * @var FraudCC $fraudCreditCardDao
         */
        $fraudCreditCardDao = $this->getServiceLocator()->get('dao_fraud_cc');

        $result = $fraudCreditCardDao->fetchOne(
            ['cc_id' => $ccId],
            ['hash_id']
        );
        $hashId = $result['hash_id'];

        // delete
        $fraudCreditCardDao->delete([
            'cc_id' => $ccId
        ]);

        // if hash already unused delete it from hashes table
        $count = $fraudCreditCardDao->getCount(['hash_id' => $hashId]);

        if ($count['count'] == 0) {
            /**
             * @var FraudCCHashes $fraudCreditCardHashesDao
             */
            $fraudCreditCardHashesDao = $this->getServiceLocator()->get('dao_fraud_cc_hashes');

            $fraudCreditCardHashesDao->delete(['id' => $hashId]);
        }

        return true;
    }

    public function removeReservationCreditCardsFromBlackList($reservationId)
    {
        /**
         * @var $creditCardService Card
         */
        $reservationsDao = new BookingDAO($this->getServiceLocator(), '\ArrayObject');
        $creditCardService = $this->getServiceLocator()->get('service_card');

        $customerId = $reservationsDao->getCustomerIdByReservationId($reservationId);

        // When reservation was removed from blacklist manually, all credit cards should take status "Unknown"
        $creditCardService->changeCustomerCardStatuses($customerId, Card::CC_STATUS_UNKNOWN);

    }

    public function addReservationCreditCardsToBlackList($reservationId)
    {

    }
}
