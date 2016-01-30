<?php

namespace Library\Finance\Transaction\Transactor;

use DDD\Domain\Booking\BookingTicket;
use DDD\Service\Booking\BankTransaction;
use Library\ActionLogger\Logger;
use Library\Constants\DbTables;
use Library\Finance\Transaction\Transaction;
use Library\Utility\Currency;

class PartnerCollection extends Transaction
{
    /**
     * @var array $reservations
     */
    protected $reservations = [];

    /**
     * @todo: Put all into callback
     *
     * @return bool
     * @throws \Exception
     */
    public function process()
    {
        /**
         * @var \DDD\Dao\Booking\Booking $bookingDao
         * @var \DDD\Dao\Booking\ChargeTransaction $bankTransactionDao
         * @var \Library\Authentication\BackofficeAuthenticationService $authenticationService
         * @var \DDD\Service\Booking\BookingTicket $bookingTicketService
         * @var \DDD\Dao\Currency\Currency $currencyDao
         * @var BookingTicket $bookingTicket
         * @var \DDD\Domain\Currency\Currency $currencyDomain
         * @var Logger $logger
         */
        $authenticationService = $this->getServiceLocator()->get('library_backoffice_auth');
        $bookingTicketService = $this->getServiceLocator()->get('service_booking_booking_ticket');
        $currencyDao = $this->getServiceLocator()->get('dao_currency_currency');
        $bookingDao = $this->getServiceLocator()->get('dao_booking_booking');
        $logger = $this->getServiceLocator()->get('ActionLogger');
        $currencyUtility = new Currency($currencyDao);
        $dao = $this->getDao();

        $transactionIdList = parent::processGeneralTransaction();

        if (count($transactionIdList) > 1) {
            throw new \RuntimeException('It is impossible to store more than one transaction id for expense transaction.');
        } else {
            $moneyTransactionId = array_shift($transactionIdList);
        }

        $totalAmount = abs($this->getAccountTo()->getAmount());
        $bankCurrency = $this->getAccountTo()->getCurrency();
        $currencyDomain = $currencyDao->fetchOne(['id' => $bankCurrency]);
        $bankCurrencyCode = $currencyDomain->getCode();

        if (count($this->getReservations())) {
            foreach ($this->getReservations() as $reservationId) {
                $bookingDao->setEntity(new BookingTicket());
                $bookingTicket = $bookingDao->fetchOne(['id' => $reservationId]);

                if (!$bookingTicket) {
                    throw new \Exception('Undefined reservation number.');
                }

                $resNumber = $bookingTicket->getReservationNumber();
                $bankAccountDetails = [
                    'currency_rate' => null,
                ];

                if (!$bankAccountDetails) {
                    throw new \Exception("Problem during requested operation for R# {$resNumber}&nbsp;&nbsp;Partner bank account does not set for this apartment");
                }

                $balances = $bookingTicketService->getSumAndBalanc($bookingTicket->getId());
                $partnerBalanceInApartmentCurrency = $balances['partnerBalanceInApartmentCurrency'];
                $partnerBalanceInCustomerCurrency = $balances['partnerBalanceInCustomerCurrency'];

                // definitions
                $accAmount = abs($partnerBalanceInApartmentCurrency);
                $reservationData = [
                    'partner_settled' => 1,
                    'partner_balance' => 0,
                ];

                if ($totalAmount > 0) {
                    if ($bookingTicket->getApartmentCurrencyCode() == $bankCurrencyCode) {
                        if ($accAmount <= $totalAmount) {
                            $bankAmount = $accAmount;
                            $totalAmount -= $accAmount;
                        } else {
                            $reservationData['partner_balance'] = $accAmount - $totalAmount;

                            $bankAmount = $totalAmount;
                            $accAmount = $totalAmount;
                            $totalAmount = 0;
                        }
                    } else {
                        $bankAmount = $currencyUtility->convert($accAmount, $bookingTicket->getApartmentCurrencyCode(), $bankCurrencyCode);

                        if ($bankAmount <= $totalAmount) {
                            $totalAmount -= $bankAmount;
                        } else {
                            $bankAmount = $totalAmount;
                            $accConvertedAmount = $currencyUtility->convert($totalAmount, $bankCurrencyCode, $bookingTicket->getApartmentCurrencyCode());

                            // In case of partly payment, partner balance cannot be equal to zero
                            $reservationData['partner_balance'] = $accAmount - $accConvertedAmount;

                            $accAmount = $accConvertedAmount;
                            $totalAmount = 0;
                        }
                    }
                } else {
                    $bankAmount = 0;
                }

                $dao->save([
                    'money_transaction_id' => $moneyTransactionId,
                    'reservation_id' => $bookingTicket->getId(),
                    'user_id' => $authenticationService->getIdentity()->id,
                    'date' => date('Y-m-d H:i:s'),
                    'cache_user' => 0,
                    'type' => BankTransaction::BANK_TRANSACTION_TYPE_COLLECT,
                    'status' => BankTransaction::BANK_TRANSACTION_STATUS_APPROVED,
                    'money_direction' => BankTransaction::TRANSACTION_MONEY_DIRECTION_PARTNER_COLLECT,
                    'money_account_currency' => $bankCurrencyCode,
                    'money_account_id' => $this->getAccountTo()->getAccountId(),
                    'bank_rate' => null, // for now
                    'bank_amount' => $bankAmount,
                    'acc_amount' => $accAmount,
                    'apartment_id' => $bookingTicket->getApartmentIdAssigned(),
                    'customer_amount' => (-1) * $partnerBalanceInCustomerCurrency,
                    'comment' => "Automatic partner transaction was created after marking ticket as \"Partner Settled\".
                                  Based on bank transaction of {$this->getAccountTo()->getAmount()} amount.",
                ]);

                // It is inessention here, but let it be
                $this->setMinorTransferId($dao->lastInsertValue);

                $bookingDao->update($reservationData, ['id' => $reservationId]);
                $logger->save(Logger::MODULE_BOOKING, $bookingTicket->getId(), Logger::ACTION_PARTNER_SETTLED);
            }
        }

        return $moneyTransactionId;
    }

    /**
     * @param array $reservations
     */
    public function setReservations($reservations)
    {
        $this->reservations = $reservations;
    }

    /**
     * @return array
     */
    protected function getReservations()
    {
        return $this->reservations;
    }
}
