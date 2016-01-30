<?php

namespace Library\Finance\Transaction\Transactor;

use DDD\Service\Currency\Currency;
use DDD\Service\MoneyAccount;
use DDD\Service\Notifications;
use Library\Finance\Transaction\Transaction;

class Transfer extends Transaction
{
    /**
     * @var int $moneyTransactionId
     */
    protected $moneyTransactionId = null;

    /**
     * @return bool
     */
    public function process()
    {
        $dao = $this->getDao();

        if (is_null($this->getMoneyTransactionId())) {
            $transactionIdList = parent::processGeneralTransaction();
        } else {
            $transactionIdList = [$this->getMoneyTransactionId()];
        }

        $moneyTransactionId = count($transactionIdList) ? $transactionIdList[0] : null;

        $dao->save([
            'money_transaction_id_1' => array_shift($transactionIdList),
            'money_transaction_id_2' => array_shift($transactionIdList),
            'creator_id' => $this->getUserId(),
            'account_id_from' => $this->getAccountFrom()->getTransactionAccountId(),
            'account_id_to' => $this->getAccountTo()->getTransactionAccountId(),
            'transaction_date_from' => $this->getTransactionDateFrom(),
            'transaction_date_to' => $this->getTransactionDateTo(),
            'amount_from' => $this->getAccountFrom()->getAmount(),
            'amount_to' => $this->getAccountTo()->getAmount(),
            'description' => $this->getDescription(),
            'creation_date' => $this->getCreationDate(),
        ]);

        $moneyAccountTypesThatNeedsToBeNotifiedOnTransfer = [
            MoneyAccount::TYPE_DEBIT_CARD,
            MoneyAccount::TYPE_PERSON,
            MoneyAccount::TYPE_CREDIT_CARD
        ];

        $accountTo = $this->getAccountTo();

        if (in_array($accountTo->getAccount()['type'], $moneyAccountTypesThatNeedsToBeNotifiedOnTransfer)) {
            /**
             * @var Notifications $notificationsService
             * @var Currency $currencyService
             */
            $notificationsService = $this->getServiceLocator()->get('service_notifications');
            $currencyService = $this->getServiceLocator()->get('service_currency_currency');

            $amountToCurrencyIsoCode = $currencyService->getCurrencyIsoCode($accountTo->getCurrency());

            $messageTemplate = 'A new transfer has just been made to account %s you\'re possessing. ' .
                                'Transfer amount: %s %s. ' .
                                'You will receive this amount on %s';

            $message = sprintf(
                $messageTemplate,
                $accountTo->getAccount()['name'],
                $accountTo->getAmount(),
                $amountToCurrencyIsoCode,
                $this->getTransactionDateTo()
            );

            $notificationData = [
                'recipient' => $accountTo->getPossessorId(),
                'sender' => Notifications::$transfer,
                'message' => $message
            ];

            $notificationsService->createNotification($notificationData);
        }

        $this->setMinorTransferId($dao->lastInsertValue);

        return $moneyTransactionId;
    }

    /**
     * @param int $moneyTransactionId
     */
    public function setMoneyTransactionId($moneyTransactionId)
    {
        $this->moneyTransactionId = $moneyTransactionId;
    }

    /**
     * @return int
     */
    protected function getMoneyTransactionId()
    {
        return $this->moneyTransactionId;
    }
}
