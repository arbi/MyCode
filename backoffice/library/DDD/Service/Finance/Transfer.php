<?php

namespace DDD\Service\Finance;

use DDD\Dao\Finance\Expense\Expenses;
use DDD\Dao\Finance\Transaction\PendingTransfer;
use DDD\Service\ServiceBase;
use Library\ActionLogger\Logger;
use Library\Constants\Constants;
use Library\Finance\Base\Account;
use Library\Finance\Exception\NotFoundException;
use Library\Finance\Exception\NotSupportedOperationException;
use Library\Finance\Transaction\Transactor\CustomerCollection;
use Library\Finance\Transaction\Transactor\Debit;
use Library\Finance\Transaction\Transactor\PartnerCollection;
use Library\Finance\Transaction\Transactor\PartnerPayment;
use Library\Finance\Transaction\Transactor\Transfer as TransferTransaction;
use Library\Finance\Transaction\Transaction;

class Transfer extends ServiceBase
{
    const TRANSACTION_TRANSFER = 'transfer';
    const TRANSACTION_PAY = 'pay';
    const TRANSACTION_RECEIVE = 'receive';
    const TRANSACTION_PARTNER_COLLECTION = 'partner-collection';
    const TRANSACTION_PARTNER_PAYMENT = 'partner-payment';
    const TRANSACTION_PSP = 'psp';

    protected $availableTransactionTypes = [
        self::TRANSACTION_TRANSFER,
        self::TRANSACTION_PAY,
        self::TRANSACTION_RECEIVE,
        self::TRANSACTION_PARTNER_COLLECTION,
        self::TRANSACTION_PARTNER_PAYMENT,
        self::TRANSACTION_PSP,
    ];

    /**
     * @param array $data
     * @return bool
     * @throws \Exception
     * @throws NotFoundException
     * @throws NotSupportedOperationException
     */
    public function makeTransfer($data)
    {
        try {
            $this->validateInputData($data);
            $accountData = $this->getAccountSpecificData($data);

            /**
             * @var \DDD\Dao\User\UserManager $usersDao
             */
            $usersDao = $this->getServiceLocator()->get('dao_user_user_manager');

            $usersDao->beginTransaction();

            switch ($data['account_type']) {
                case self::TRANSACTION_PARTNER_COLLECTION:
                    $transaction = new PartnerCollection($accountData['accountFrom'], $accountData['accountTo']);
                    $transaction->setReservations($data['res_numbers']);

                    break;
                case self::TRANSACTION_PARTNER_PAYMENT:
                    $transaction = new PartnerPayment($accountData['accountFrom'], $accountData['accountTo']);
                    $transaction->setReservations($data['reservations']);
                    $transaction->setTotalAmount($data['dist_total_amount']);
                    $transaction->setPartnerId($data['partners_to']);

                    foreach ($data['costs'] as $apartmentId => $amount) {
                        $transaction->setCost(['apartmentId' => $apartmentId, 'amount' => $amount,]);
                    }

                    break;
                case self::TRANSACTION_PAY: // aka Debit
                    $transaction = new Debit($accountData['accountFrom'], $accountData['accountTo']);

                    if (count($accountData['expenseList'])) {
                        foreach ($accountData['expenseList'] as $expense) {
                            $transaction->setExpense($expense['id'], $expense['amount']);
                        }
                    }

                    break;
                case self::TRANSACTION_PSP:
                    $transaction = new CustomerCollection($accountData['accountFrom'], $accountData['accountTo']);

                    $transaction->setTransactions($accountData['transactionIdList']);

                    break;
                default: // Transfer, Credit
                    $transaction = new TransferTransaction($accountData['accountFrom'], $accountData['accountTo']);
            }

            $transaction->setServiceLocator($this->getServiceLocator());
            $transaction->setAccountIdentity($accountData['accountFromIdentity'], $accountData['accountToIdentity']);
            $transaction->setTransactionDateFrom($accountData['dateFrom']);
            $transaction->setTransactionDateTo($accountData['dateTo']);
            $transaction->setAmountFrom($accountData['amountFrom']);
            $transaction->setAmountTo($accountData['amountTo']);
            $transaction->setDescription($accountData['description']);
            $transaction->setIsVerified();
            $transaction->prepare();

            $moneyTransactionId = $transaction->process();

            if (!empty($data['pending_transfer_id'])) {
                $this->deletePendingTransfer($data['pending_transfer_id']);
            }

            $usersDao->commitTransaction();
        } catch (\Exception $ex) {
            $usersDao->rollbackTransaction();

            if ($ex instanceof NotFoundException) {
                throw new NotFoundException($ex);
            }

            if ($ex instanceof NotSupportedOperationException) {
                throw new NotSupportedOperationException($ex);
            }

            throw new \Exception($ex);
        }

        return $moneyTransactionId;
    }

    /**
     * @param int $pendingTransferId
     */
    public function deletePendingTransfer($pendingTransferId)
    {
        /**
         * @var PendingTransfer $pendingTransferDao
         */
        $pendingTransferDao = $this->serviceLocator->get('dao_finance_transaction_pending_transfer');
        $pendingTransferDao->delete(['id' => $pendingTransferId]);
    }

    /**
     * @param array|\Zend\Stdlib\Parameters $data
     * @throws \Exception
     */
    private function validateInputData($data)
    {
        /**
         * @var Expenses $purchaseOrderDao
         */
        $purchaseOrderDao = $this->getServiceLocator()->get('dao_finance_expense_expenses');

        switch ($data['account_type']) {
            case self::TRANSACTION_PARTNER_COLLECTION:
                if (empty($data['res_numbers']) || !is_array($data['res_numbers']) || !count($data['res_numbers'])) {
                    throw new \Exception('Bad data provided.');
                }

                foreach ($data['res_numbers'] as $resNumber) {
                    if (empty($resNumber)) {
                        throw new \Exception('Reservation number is in bad format.');
                    }
                }

                break;
            case self::TRANSACTION_PARTNER_PAYMENT:
                if (empty($data['reservations']) || !is_array($data['reservations']) || !count($data['reservations'])) {
                    throw new \Exception('Bad data provided.');
                }

                if (empty($data['costs']) || !is_array($data['costs']) || !count($data['costs'])) {
                    throw new \Exception('Bad data provided.');
                }


                break;
            case self::TRANSACTION_PAY:
                if (empty($data['supplier_to_type']) || !in_array($data['supplier_to_type'], [
                        Account::TYPE_SUPPLIER,
                        Account::TYPE_PEOPLE,
                        Account::TYPE_PARTNER,
                    ])) {
                    throw new \Exception('Wrong supplier type selected. Please contact to developers.');
                }

                if (!count($data['expense_id_list']) || !count($data['expense_amount_list'])) {
                    throw new \Exception('Bad data provided.');
                }

                foreach ($data['expense_amount_list'] as $expenseId) {
                    if (empty($expenseId)) {
                        continue;
                    }

                    if (!is_numeric($expenseId)) {
                        throw new \Exception('Purchase Order id is in bad format.');
                    }
                }

                foreach ($data['expense_id_list'] as $expenseId) {
                    if (empty($expenseId)) {
                        continue;
                    }

                    if (!ctype_digit($expenseId)) {
                        throw new \Exception('Purchase Order id is in bad format.');
                    }

                    if (!$purchaseOrderDao->fetchOne(['id' => $expenseId], ['id'])) {
                        throw new \Exception("Purchase Order #{$expenseId} not found.");
                    }
                }

                break;
            case self::TRANSACTION_PSP:
                if (empty($data['transactions']) || !is_array($data['transactions']) || !count($data['transactions'])) {
                    throw new \Exception('Bad data provided.');
                }

                foreach ($data['transactions'] as $transactionId) {
                    if (!ctype_digit($transactionId)) {
                        throw new \Exception('Bad data provided.');
                    }
                }

                break;
            case self::TRANSACTION_RECEIVE:
                if (empty($data['supplier_from_type']) || !in_array($data['supplier_from_type'], [
                        Account::TYPE_SUPPLIER,
                        Account::TYPE_PEOPLE,
                        Account::TYPE_PARTNER,
                    ])) {
                    throw new \Exception('Wrong supplier type selected. Please contact to developers.');
                }

                break;
        }
    }

    /**
     * @param array $postData
     * @return array
     * @throws \RuntimeException
     */
    private function getAccountSpecificData($postData)
    {
        switch ($postData['account_type']) {
            case self::TRANSACTION_TRANSFER:
                $accountFrom = Transaction::ACCOUNT_MONEY_ACCOUNT;
                $accountTo = Transaction::ACCOUNT_MONEY_ACCOUNT;

                $accountFromIdentity = $postData['money_account_from'];
                $accountToIdentity = $postData['money_account_to'];

                $dateFrom = date('Y-m-d', strtotime($postData['date_from']));
                $dateTo = date('Y-m-d', strtotime($postData['date_to']));

                $amountFrom = doubleval($postData['amount_from']);
                $amountTo = doubleval($postData['amount_to']);

                break;
            case self::TRANSACTION_PAY:
                $accountFrom = Transaction::ACCOUNT_MONEY_ACCOUNT;

                if (isset($postData['supplier_to_type'])) {
                    switch ($postData['supplier_to_type']) {
                        case Account::TYPE_PARTNER:
                            $accountTo = Transaction::ACCOUNT_PARTNER;
                            break;
                        case Account::TYPE_SUPPLIER:
                            $accountTo = Transaction::ACCOUNT_SUPPLIER;
                            break;
                        case Account::TYPE_PEOPLE:
                            $accountTo = Transaction::ACCOUNT_PEOPLE;
                            break;
                    }
                }

                $accountFromIdentity = $postData['money_account_from'];
                $accountToIdentity = $postData['supplier_to'];

                $dateFrom = date('Y-m-d', strtotime($postData['date_from']));
                $dateTo = null;

                $expenseList = [];
                $expenseTotalAmount = 0;

                foreach ($postData['expense_id_list'] as $key => $expenseId) {
                    if (!$expenseId) {
                        continue;
                    }

                    if (empty($postData['expense_amount_list'][$key])) {
                        continue;
                    }

                    array_push($expenseList, [
                        'id' => $expenseId,
                        'amount' => $postData['expense_amount_list'][$key],
                    ]);

                    $expenseTotalAmount += $postData['expense_amount_list'][$key];
                }

                $amountFrom = doubleval($expenseTotalAmount);
                $amountTo = null;

                break;
            case self::TRANSACTION_RECEIVE:
                if (isset($postData['supplier_from_type'])) {
                    switch ($postData['supplier_from_type']) {
                        case Account::TYPE_PARTNER:
                            $accountFrom = Transaction::ACCOUNT_PARTNER;
                            break;
                        case Account::TYPE_SUPPLIER:
                            $accountFrom = Transaction::ACCOUNT_SUPPLIER;
                            break;
                        case Account::TYPE_PEOPLE:
                            $accountFrom = Transaction::ACCOUNT_PEOPLE;
                            break;
                    }
                }

                $accountTo = Transaction::ACCOUNT_MONEY_ACCOUNT;

                $accountFromIdentity = $postData['supplier_from'];
                $accountToIdentity = $postData['money_account_to'];

                $dateFrom = null;
                $dateTo = date('Y-m-d', strtotime($postData['date_to']));

                $amountFrom = null;
                $amountTo = doubleval($postData['amount_to']);

                break;
            case self::TRANSACTION_PARTNER_COLLECTION:
                $accountFrom = null;
                $accountTo = Transaction::ACCOUNT_MONEY_ACCOUNT;

                $accountFromIdentity = null;
                $accountToIdentity = $postData['money_account_to'];

                $dateFrom = null;
                $dateTo = date('Y-m-d', strtotime($postData['date_to']));

                $amountFrom = null;
                $amountTo = doubleval($postData['amount_to']);

                break;
            case self::TRANSACTION_PARTNER_PAYMENT:
                $accountFrom = Transaction::ACCOUNT_MONEY_ACCOUNT;
                $accountTo = null;

                $accountFromIdentity = $postData['money_account_from'];
                $accountToIdentity = null;

                $dateFrom = date('Y-m-d', strtotime($postData['date_from']));
                $dateTo = null;

                $amountFrom = doubleval($postData['amount_from']);
                $amountTo = null;

                break;
            case self::TRANSACTION_PSP:
                $accountFrom = null;
                $accountTo = Transaction::ACCOUNT_MONEY_ACCOUNT;

                $accountFromIdentity = null;
                $accountToIdentity = $postData['money_account_to'];

                $dateFrom = null;
                $dateTo = date('Y-m-d', strtotime($postData['date_from']));

                $amountFrom = null;
                $amountTo = doubleval($postData['amount_to']);

                $transactionIdList = $postData['transactions'];

                break;
            default:
                throw new \RuntimeException('Unknown account type.');
        }

        return [
            'accountTo' => $accountTo,
            'accountFrom' => $accountFrom,
            'accountFromIdentity' => $accountFromIdentity,
            'accountToIdentity' => $accountToIdentity,
            'description' => $postData['description'],
            'expenseList' => empty($expenseList) ? [] : $expenseList,
            'transactionIdList' => empty($transactionIdList) ? [] : $transactionIdList,
            'expenseId' => $postData['expense_id'],
            'amountFrom' => $amountFrom,
            'amountTo' => $amountTo,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
        ];
    }

    /**
     * @param array $data
     * @return bool
     */
    public function savePendingTransfer(array $data)
    {
        /**
         * @var PendingTransfer $pendingTransferDao
         */
        $pendingTransferDao = $this->getServiceLocator()->get('dao_finance_transaction_pending_transfer');

        $this->preparePendingTransferData($data);

        return $pendingTransferDao->save($data);
    }

    /**
     * @param array $data
     */
    private function preparePendingTransferData(array &$data) {
        if (count($data)) {
            foreach ($data as $index => $item) {
                if (empty($item)) {
                    unset($data[$index]);
                }
            }
        }

        $data['date_created'] = date('Y-m-d H:i:s');

        if (!empty($data['date_from'])) {
            $data['date_from'] = date('Y-m-d', strtotime($data['date_from']));
        }

        if (!empty($data['date_to'])) {
            $data['date_to'] = date('Y-m-d', strtotime($data['date_to']));
        }
    }

    /**
     * @return \Zend\Db\ResultSet\ResultSet|array[]
     */
    public function getPendingTransfers()
    {
        /**
         * @var PendingTransfer $pendingTransferDao
         */
        $pendingTransferDao = $this->getServiceLocator()->get('dao_finance_transaction_pending_transfer');

        return $pendingTransferDao->getPendingTransactions();
    }

    /**
     * @return int
     */
    public function getPendingTransferCount()
    {
        /**
         * @var PendingTransfer $pendingTransferDao
         */
        $pendingTransferDao = $this->getServiceLocator()->get('dao_finance_transaction_pending_transfer');

        return $pendingTransferDao->getTotalCount();
    }

    /**
     * @param int $transferId
     * @return array
     */
    public function getPendingTransfer($transferId)
    {
        /**
         * @var PendingTransfer $pendingTransferDao
         */
        $pendingTransferDao = $this->serviceLocator->get('dao_finance_transaction_pending_transfer');
        $result = $pendingTransferDao->fetchOne(['id' => $transferId]);

        if ($result) {
            if ($result['date_from']) {
                $result['date_from'] = date(Constants::GLOBAL_DATE_FORMAT, strtotime($result['date_from']));
            }

            if ($result['date_to']) {
                $result['date_to'] = date(Constants::GLOBAL_DATE_FORMAT, strtotime($result['date_to']));
            }
        }

        return $result ?: false;
    }
}
