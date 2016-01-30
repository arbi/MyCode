<?php

namespace Console\Controller;

use CreditCard\Service\Queue;
use CreditCard\Service\Store;
use Library\Controller\ConsoleBase;

/**
 * Class CreditCardCreationQueueController
 * @package Console\Controller
 */
class CreditCardCreationQueueController extends ConsoleBase
{
    const MAX_ATTEMPTS = 1;

    public function indexAction()
    {
        $this->initCommonParams($this->getRequest());

        $action = $this->getRequest()->getParam('mode', 'show');

        switch ($action) {
            case 'execute': $this->execute();
                break;
            default :
                echo 'Type correct command' . PHP_EOL;
        }
    }

    public function execute()
    {
        /**
         * @var Queue $creditCardCreationQueueService
         * @var Store $storeService
         */
        $creditCardCreationQueueService = $this->getServiceLocator()->get('service_card_creation_queue');
        $storeService = $this->getServiceLocator()->get('service_store_cc');

        $items = $creditCardCreationQueueService->fetch();

        foreach ($items as $item) {

            $existenceStatus = $creditCardCreationQueueService->checkCardExistenceBeforeStore($item);

            switch ($existenceStatus) {
                case Queue::CREDIT_CARD_EXISTENCE_NOT_EXIST:
                    $token = $storeService->store($item);

                    if ($token) {
                        $this->outputMessage('Existence: Does not exist. Credit card stored successfully for customer ID: ' . $item->getCustomerId());

                        $creditCardCreationQueueService->remove($item->getId());
                    } else {
                        $creditCardCreationQueueService->incrementAttemptsCount($item->getId());
                        $this->outputMessage('Cannot store credit card');
                    }
                    break;
                case Queue::CREDIT_CARD_EXISTENCE_MATCHED:
                    $creditCardCreationQueueService->remove($item->getId());
                    $this->outputMessage('Existence: Exist. Credit card removed from Queue');
                    break;
                case Queue::CREDIT_CARD_EXISTENCE_MATCHED_PAN_DIFFERENT_HOLDER:
                    $token = $storeService->store($item);

                    if ($token) {
                        $this->outputMessage('Existence: PAN matched, different holder. Credit card stored successfully for customer ID: ' . $item->getCustomerId());

                        $creditCardCreationQueueService->remove($item->getId());
                    } else {
                        $creditCardCreationQueueService->incrementAttemptsCount($item->getId());
                        $this->outputMessage('Cannot store credit card');
                    }
                    break;
                case Queue::CREDIT_CARD_EXISTENCE_EXPIRATION_DATE_UPDATED:
                    $creditCardCreationQueueService->remove($item->getId());
                    $this->outputMessage('Existence: PAN matched, holder matched, different expiration dates. Credit card expiration date updated successfully. Removed from Queue');
                    break;
            }
        }
    }
}
