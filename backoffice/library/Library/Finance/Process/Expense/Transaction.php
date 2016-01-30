<?php

namespace Library\Finance\Process\Expense;

use Library\Finance\Finance;
use Library\Finance\Transaction\Transaction as GeneralTransaction;
use Library\Finance\Transaction\Transactor;

class Transaction extends TicketElementAbstract
{
    /**
     * @var Finance $finance
     */
    private $finance;

    /**
     * If false then expense transaction is not virtual and should make additional money_transaction otherwise
     * it is a virtual transaction and it means that money_transaction already exists and it should be linked to
     * this expense transaction.
     *
     * @var bool|int
     */
    protected $moneyTransactionId = false;

    /**
     * @param array $transactionData
     * @param int|null $transactionId
     * @throws \Exception
     */
    public function __construct(array $transactionData, $transactionId = null)
    {
        $this->setData($transactionData);
        $this->setId($transactionId);
        $this->detectMode();

        // If special attribute not passed to $transactionData then it is a standard transaction.
        // For additional information look at self::$moneyTransactionId comment
        $this->detectVirtuality();
    }

    /**
     * @param Ticket $expenseTicket
     * @return bool|void
     * @throws \Exception
     */
    public function save(Ticket $expenseTicket)
    {
        /**
         * @var Transactor\Expense $transaction
         */
        if (!($this->finance instanceof Finance)) {
            throw new \Exception('Finance not defined for expense transaction.');
        }

        $transactionData = $this->getData();
        $transaction = new Transactor\Expense(
            GeneralTransaction::ACCOUNT_MONEY_ACCOUNT,
            GeneralTransaction::getAccountTypeById(
                $transactionData['accountTo']['type']
            )
        );
        $transaction->setServiceLocator($expenseTicket->getServiceLocator());
        $transaction->setMode($this->getMode());

        switch ($this->getMode()) {
            case self::MODE_ADD:
                $transaction->setAccountIdentity($transactionData['accountFrom']['id'], $transactionData['accountTo']['id']);
                $transaction->setIsRefund(isset($transactionData['isRefund']) ? $transactionData['isRefund'] : 0);
                $transaction->setTransactionAccountId($transactionData['accountTo']['transactionAccountId']);
                $transaction->setTransactionDate(isset($transactionData['date']) ? $transactionData['date']: date('Y-m-d H:i:s'));
                $transaction->setDescription("Expense Transaction #{$expenseTicket->getExpenseId()}");
                $transaction->setExpenseId($expenseTicket->getExpenseId());
                $transaction->setAmount($transactionData['amount']);

                // If transaction is virtual it shouldn't have money_transaction_id
                if ($this->getMoneyTransactionId() !== false) {
                    $transaction->setIsVirtual();
                    $transaction->setMoneyTransactionId(
                        $this->getMoneyTransactionId()
                    );
                }

                $transaction->prepare();

                break;
            case self::MODE_EDIT:
                $isVerified = $transactionData['isVerified'];
                $isVoided = $transactionData['isVoided'];

                if (!$isVerified && !$isVoided) {
                    throw new \RuntimeException('Impossible to modify transaction.');
                }

                $transaction->setTransactionId($this->getId());

                if ($isVerified) {
                    $transaction->setIsVerified();
                    $transaction->setVerifierId($expenseTicket->getCreatorId());
                }

                if ($isVoided) {
                    $transaction->setIsVoid();
                    $transaction->setVerifierId($expenseTicket->getCreatorId());
                }

                $transaction->prepare();

                break;
            case self::MODE_DELETE:
                throw new \RuntimeException('Impossible to delete transaction.');

                break;
        }

        return $transaction->process();
    }

    /**
     * @return void
     */
    protected function detectVirtuality()
    {
        $data = $this->getData();

        if (!empty($data['moneyTransactionId'])) {
            $this->setMoneyTransactionId($data['moneyTransactionId']);
        }
    }

    /**
     * @param Finance $finance
     */
    public function setFinance(Finance $finance)
    {
        $this->finance = $finance;
    }

    /**
     * @return int|bool
     */
    protected function getMoneyTransactionId()
    {
        return $this->moneyTransactionId;
    }

    /**
     * @param int $moneyTransactionId
     */
    protected function setMoneyTransactionId($moneyTransactionId)
    {
        $this->moneyTransactionId = $moneyTransactionId;
    }
}
