<?php

namespace CreditCard\Service;

use CreditCard\Entity\CompleteData;
use CreditCard\Model\CCCreationQueue as CCCreationQueueDAO;
use DDD\Service\ServiceBase;

/**
 * Class Queue
 * @package CreditCard\Service
 *
 * @author Tigran Petrosyan
 */
class Queue extends ServiceBase
{
    const MAX_ATTEMPTS = 1;

    const ITEMS_COUNT = 100;

    const CREDIT_CARD_EXISTENCE_NOT_EXIST = 1;
    const CREDIT_CARD_EXISTENCE_MATCHED = 2;
    const CREDIT_CARD_EXISTENCE_MATCHED_PAN_DIFFERENT_HOLDER = 4;
    const CREDIT_CARD_EXISTENCE_EXPIRATION_DATE_UPDATED = 8;
    const CREDIT_CARD_EXISTENCE_EXPIRATION_DATE_UPDATE_FAILED = 16;

    /**
     * @param $creditCardRawData
     * @return int
     */
    public function insert($creditCardRawData)
    {
        /**
         * @var CCCreationQueueDAO $creditCardCreationQueueDao
         */
        $creditCardCreationQueueDao = $this->getServiceLocator()->get('dao_cc_creation_queue');

        $creditCardCreationQueueDao->save([
            'date_inserted' => date('Y-m-d h:i:s'),
            'pan' => $creditCardRawData->getPan(),
            'holder' => $creditCardRawData->getHolder(),
            'security_code' => $creditCardRawData->getSecurityCode(),
            'exp_year' => $creditCardRawData->getExpirationYear(),
            'exp_month' => $creditCardRawData->getExpirationMonth(),
            'brand' => $creditCardRawData->getBrand(),
            'customer_id' => $creditCardRawData->getCustomerId(),
            'partner_id' => $creditCardRawData->getPartnerId(),
            'source' => $creditCardRawData->getSource(),
            'status' => $creditCardRawData->getStatus(),
        ]);

        return $creditCardCreationQueueDao->getLastInsertValue();
    }

    /**
     * @return \CreditCard\Entity\CompleteData[]
     */
    public function fetch()
    {
        /**
         * @var CCCreationQueueDAO $creditCardCreationQueueDao
         */
        $creditCardCreationQueueDao = $this->getServiceLocator()->get('dao_cc_creation_queue');

        $items = $creditCardCreationQueueDao->getItems(self::ITEMS_COUNT);

        return $items;
    }

    /**
     * @param $id
     * @return bool
     */
    public function remove($id)
    {
        /**
         * @var CCCreationQueueDAO $creditCardCreationQueueDao
         */
        $creditCardCreationQueueDao = $this->getServiceLocator()->get('dao_cc_creation_queue');

        $creditCardCreationQueueDao->delete([
            'id' => $id
        ]);

        return true;
    }

    /**
     * @param $creditCard CompleteData
     * @return int
     */
    public function checkCardExistenceBeforeStore($creditCard)
    {
        /**
         * @var Card $cardService
         * @var Encrypt $encryptionService
         */
        $cardService = $this->getServiceLocator()->get('service_card');
        $encryptionService = $this->getServiceLocator()->get('service_encrypt');

        $customerId = $creditCard->getCustomerId();
        $pan = $encryptionService->decrypt($creditCard->getPan(), '');
        $panLength = strlen($pan);
        $firstDigitsCount = $panLength - 10;
        $firstDigits = substr($pan, 0, $firstDigitsCount);
        $customerCreditCards = $cardService->getCustomerCreditCardsForExistenceCheck($customerId);

        foreach ($customerCreditCards as $customerCreditCard) {
            if ($customerCreditCard['pan_first_digits'] === $firstDigits) {
                /**
                 * @var Retrieve $retrieveService
                 */
                $retrieveService = $this->getServiceLocator()->get('service_retrieve_cc');

                $matchedCreditCard = $retrieveService->getCreditCard($customerCreditCard['id']);

                // primary account number matched with one of existing credit cards
                if ($pan === $matchedCreditCard->getPan()) {
                    $expirationMonth = $encryptionService->decrypt($creditCard->getExpirationMonth(), '');
                    $expirationYear = $encryptionService->decrypt($creditCard->getExpirationYear(), '');
                    $holderName = $encryptionService->decrypt($creditCard->getHolder(), '');

                    // holder name is not match
                    // so we are going to create a new credit card and set matched with PAN cc status to "Do Not Use"
                    if ($matchedCreditCard->getHolder() !== $holderName) {
                        $cardService->changeCardStatus($customerCreditCard['id'], Card::CC_STATUS_DO_NOT_USE);

                        return self::CREDIT_CARD_EXISTENCE_MATCHED_PAN_DIFFERENT_HOLDER;
                    } else {
                        // holder name also matched, going to check for expiration date

                        if ($matchedCreditCard->getExpirationMonth() === $expirationMonth && $matchedCreditCard->getExpirationYear() === $expirationYear) {
                            return self::CREDIT_CARD_EXISTENCE_MATCHED;
                        } else {
                            /**
                             * @var Update $updateCreditCardService
                             */
                            $updateCreditCardService = $this->getServiceLocator()->get('service_update_cc');

                            $result = $updateCreditCardService->updateRemoteData($customerCreditCard['token'], $expirationMonth, $expirationYear);

                            if ($result) {
                                return self::CREDIT_CARD_EXISTENCE_EXPIRATION_DATE_UPDATED;
                            } else {
                                return self::CREDIT_CARD_EXISTENCE_EXPIRATION_DATE_UPDATE_FAILED;
                            }
                        }
                    }
                }
            }
        }

        return self::CREDIT_CARD_EXISTENCE_NOT_EXIST;
    }

    /**
     * @param $itemId
     * @return bool
     */
    public function incrementAttemptsCount($itemId)
    {
        /**
         * @var CCCreationQueueDAO $creditCardCreationQueueDao
         */
        $creditCardCreationQueueDao = $this->getServiceLocator()->get('dao_cc_creation_queue');

        $creditCardCreationQueueDao->update(
            ['attempts' => 1],
            ['id' => $itemId]
        );

        return true;
    }
}
