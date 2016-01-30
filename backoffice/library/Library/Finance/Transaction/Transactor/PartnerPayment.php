<?php

namespace Library\Finance\Transaction\Transactor;

use DDD\Service\Booking\BankTransaction;
use DDD\Service\Finance\Budget;
use DDD\Service\Finance\Expense\ExpenseItemCategories;
use DDD\Service\Finance\TransactionAccount;
use DDD\Service\User;
use Library\ActionLogger\Logger;
use Library\Authentication\BackofficeAuthenticationService;
use Library\Finance\Base\Account;
use Library\Finance\Finance;
use Library\Finance\Process\Expense\Helper;
use Library\Finance\Process\Expense\Ticket;
use Library\Finance\Transaction\Transaction;
use Library\Utility\Currency;

class PartnerPayment extends Transaction
{
    /**
     * @var float $totalAmount
     */
    protected $totalAmount;

    /**
     * @var int $partnerId
     */
    protected $partnerId;

    /**
     * @var array $reservations
     */
    protected $reservations = [];

    /**
     * @var array $costs
     */
    protected $costs = [];

    /**
     * @return int Money Transaction Id
     * @throws \Exception
     */
    public function process()
    {
        /**
         * @var \DDD\Dao\Booking\Booking $bookingDao
         * @var \DDD\Dao\Booking\ChargeTransaction $bankTransactionDao
         * @var \Library\Authentication\BackofficeAuthenticationService $authenticationService
         * @var \DDD\Service\Booking\BookingTicket $bookingTicketService
         * @var \DDD\Domain\Booking\BookingTicket $bookingTicket
         * @var \DDD\Dao\Currency\Currency $currencyDao
         * @var Logger $logger
         */
        $authenticationService = $this->getServiceLocator()->get('library_backoffice_auth');
        $bookingTicketService = $this->getServiceLocator()->get('service_booking_booking_ticket');
        $bankTransactionDao = $this->getServiceLocator()->get('dao_booking_change_transaction');
        $currencyDao = $this->getServiceLocator()->get('dao_currency_currency');
        $bookingDao = $this->getServiceLocator()->get('dao_booking_booking');
        $logger = $this->getServiceLocator()->get('ActionLogger');
        $currencyUtility = new Currency($currencyDao);

        $transactionIdList = parent::processGeneralTransaction();

        if (count($transactionIdList) > 1) {
            throw new \RuntimeException('It is impossible to store more than one transaction id for expense and/or reservation transaction.');
        } else {
            $moneyTransactionId = array_shift($transactionIdList);
        }

        $totalAmount = abs($this->getAccountFrom()->getAmount());
        $bankCurrency = $this->getAccountFrom()->getCurrency();
        $currencyDomain = $currencyDao->fetchOne(['id' => $bankCurrency]);
        $bankCurrencyCode = $currencyDomain->getCode();

        // For partner payment operation, expense should be created automatically based on existing data
        $expenseId = $this->createExpense($moneyTransactionId);

        // Set as verified transaction
        $this->changeVerifyStatus($moneyTransactionId, self::IS_VERIFIED);

        if (count($this->getReservations())) {
            foreach ($this->getReservations() as $reservationId) {
                $bookingDao->setEntity(new \DDD\Domain\Booking\BookingTicket());
                $bookingTicket = $bookingDao->fetchOne(['id' => $reservationId]);

                if ($bookingTicket) {
                    $balances = $bookingTicketService->getSumAndBalanc($bookingTicket->getId());
                    $partnerBalanceInApartmentCurrency = $balances['partnerBalanceInApartmentCurrency'];
                    $partnerBalanceInCustomerCurrency = $balances['partnerBalanceInCustomerCurrency'];

                    // definitions
                    $apartmentId = $bookingTicket->getApartmentIdAssigned();
                    $accAmount = $partnerBalanceInApartmentCurrency;
                    $reservationDescriptionSuffix = '';
                    $reservationData = [
                        'partner_settled' => 1,
                    ];

                    if ($totalAmount > 0) {
                        // In case when everything ok, partner balance must be equal to zero
                        $reservationData['partner_balance'] = 0;

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
                        $reservationDescriptionSuffix = ' 0 amount of reservation means that general transaction\'s reservations is covering this reservation.';
                        $bankAmount = 0;
                    }

                    // Create transaction
                    $bankTransactionDao->save([
                        'money_transaction_id' => $moneyTransactionId,
                        'reservation_id' => $bookingTicket->getId(),
                        'date' => date('Y-m-d H:i:s'),
                        'user_id' => $authenticationService->getIdentity()->id,
                        'cache_user' => 0,
                        'type' => BankTransaction::BANK_TRANSACTION_TYPE_PAY,
                        'status' => BankTransaction::BANK_TRANSACTION_STATUS_APPROVED,
                        'money_account_id' => $this->getAccountFrom()->getAccountId(),
                        'exact_expense_id' => $expenseId,
                        'bank_rate' => null, // for now
                        'money_account_currency' => $bankCurrencyCode,
                        'bank_amount' => $bankAmount,
                        'acc_amount' => (-1) * $accAmount,
                        'customer_amount' => (-1) * $partnerBalanceInCustomerCurrency,
                        'apartment_id' => $apartmentId,
                        'money_direction' => BankTransaction::TRANSACTION_MONEY_DIRECTION_PARTNER_COLLECT,
                        'comment' => "Automatic partner payment transaction was created after marking ticket as \"Partner Settled\"
                                      Based on bank transaction of {$this->getAccountFrom()->getAmount()}{$bankCurrencyCode} amount.{$reservationDescriptionSuffix}",
                    ]);

                    // It is inessention here, but let it be
                    $this->setMinorTransferId($bankTransactionDao->lastInsertValue);

                    $bookingDao->update($reservationData, ['id' => $reservationId]);
                    $logger->save(Logger::MODULE_BOOKING, $bookingTicket->getId(), Logger::ACTION_PARTNER_SETTLED);
                }
//                else {
//                    throw new \Exception("Problem during requested operation for R# {$bookingTicket->getReservationNumber()}. Invalid R#");
//                }
            }
        }

        return $moneyTransactionId;
    }

    /**
     * @param int $moneyTransactionId
     * @return int
     */
    private function createExpense($moneyTransactionId)
    {
        $data = $this->constructExpenseData($moneyTransactionId);

        $finance = new Finance($this->getServiceLocator());
        $expenseTicket = $finance->getExpense();

        $expenseTicket->prepare($data['ticket']);

        if (count($data['items'])) {
            foreach ($data['items'] as $item) {
                $expenseTicket->addItem($item);
            }
        }

        $expenseTicket->addTransaction($data['transaction']);
        $expenseTicket->save();

        return $expenseTicket->getExpenseId();
    }

    /**
     * Array constructed exclusively to create expense for partner payment.
     * Structure can be different for other cases
     *
     * @param int $moneyTransactionId
     * @return array
     */
    private function constructExpenseData($moneyTransactionId)
    {
        /**
         * @var TransactionAccount $transactionAccountService
         */
        $transactionAccountService = $this->getServiceLocator()->get('service_finance_transaction_account');

        $partnerAccountId = $this->getPartnerId();
        $partnerType = Account::TYPE_PARTNER;

        $partnerTransactionAccountId = $transactionAccountService->getTransactionAccountIdByIdentity($partnerAccountId, $partnerType);
        $currencyId = $this->getAccountFrom()->getCurrency();
        $amount = abs($this->getAccountFrom()->getAmount());

        return [
            'ticket' => [
                'currencyId' => $currencyId,
                'purpose' => $this->getDescription(),
                'limit' => $this->getTotalAmount(),
                'budget' => Budget::BUDGET_OTA_COMMISSIONS,
                'balance' => [
                    'ticket' => $this->getTotalAmount() - $amount,
                    'deposit' => 0,
                    'item' => $this->getTotalAmount(),
                    'transaction' => -1 * $this->getAccountFrom()->getAmount(),
                ],
            ],
            'items' => $this->getItem($partnerTransactionAccountId, $currencyId),
            'transaction' => [
                'tmpId' => 'TMP001',
                'moneyTransactionId' => $moneyTransactionId,
                'accountFrom' => ['id' => $this->getAccountFrom()->getAccountId()],
                'accountTo' => [
                    'id' => $partnerAccountId,
                    'type' => $partnerType,
                    'transactionAccountId' => $partnerTransactionAccountId,
                ],
                'amount' => $amount,
                'date'   => $this->getTransactionDateFrom(),
            ],
        ];
    }

    /**
     * @param int $partnerTransactionAccountId
     * @param int $currencyId
     * @return array
     */
    private function getItem($partnerTransactionAccountId, $currencyId)
    {
        $itemList = [];

        foreach ($this->getCosts() as $cost) {
            array_push($itemList, [
                'transactionId' => 'TMP001',
                'accountId' => $partnerTransactionAccountId,
                'costCenters' => [
                    [
                        'id' => $cost['apartmentId'],
                        'currencyId' => $currencyId,
                        'type' => 'apartment',
                    ],
                ],
                'amount' => $cost['amount'],
                'currencyId' => $currencyId,
                'subCategoryId' => ExpenseItemCategories::SUB_CATEGORY_OTA,
                'type' => Helper::TYPE_PAY_AN_INVOICE,
                'status' => Helper::ITEM_STATUS_APPROVED,
            ]);
        }

        return $itemList;
    }

    /**
     * @param float $totalAmount
     */
    public function setTotalAmount($totalAmount)
    {
        $this->totalAmount = $totalAmount;
    }

    /**
     * @return float
     */
    public function getTotalAmount()
    {
        return $this->totalAmount;
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
    public function getReservations()
    {
        return $this->reservations;
    }

    /**
     * @param int $partner
     */
    public function setPartnerId($partner)
    {
        $this->partnerId = $partner;
    }

    /**
     * @return int
     */
    public function getPartnerId()
    {
        return $this->partnerId;
    }

    /**
     * @param array $cost
     * <code>
     * [
     *   'apartmentId' => $apartmentId,
     *   'cost' => $apartmentCost,
     * ]
     * </code>
     */
    public function setCost($cost)
    {
        array_push($this->costs, $cost);
    }

    /**
     * @return array
     */
    public function getCosts()
    {
        return $this->costs;
    }
}
