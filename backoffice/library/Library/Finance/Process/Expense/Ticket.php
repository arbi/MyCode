<?php

namespace Library\Finance\Process\Expense;

use Library\ActionLogger\Logger;
use Library\Authentication\BackofficeAuthenticationService;

class Ticket extends Helper
{
    /**
     * @param array $expenseData
     */
    public function prepare($expenseData)
    {
        $this->expenseTicketData = $this->applyExpenseTicketDataBinding($expenseData);
    }

    /**
     * @param array $itemData
     * @return Ticket
     */
    public function addItem($itemData)
    {
        $item = new Item($itemData);
        $item->setServiceLocator($this->getServiceLocator());

        array_push($this->items, $item);

        return $this;
    }

    /**
     * @param array $transactionData
     * @return Ticket
     */
    public function addTransaction($transactionData)
    {
        $transaction = new Transaction($transactionData);
        $transaction->setFinance($this->finance);

        array_push($this->transactions, $transaction);

        return $this;
    }

    /**
     * @throws \RuntimeException
     * @throws \Exception
     */
    public function save()
    {
        if ($this->validate()) {
            $dao = $this->getDao();

            try {
                $dao->beginTransaction();

                if ($this->getExpenseId()) {
                    $this->saveExactExpense($this->getExpenseId());
                } else {
                    $this->setExpenseId($this->saveExactExpense());
                }

                if ($this->getExpenseId()) {
                    $links = $this->saveTransactions();

                    $this->saveItems($links);

                    $dao->commitTransaction();
                } else {
                    new \Exception('Cannot save expense.');
                }
            } catch (\Exception $ex) {
                $dao->rollbackTransaction();

                // Rethrow an exception, because this try..catch used to rollback mysql transaction.
                throw new \Exception($ex);
            }
        } else {
            throw new \RuntimeException('Expense data invalid.');
        }
    }

    /**
     * @return int
     * @throws \RuntimeException
     */
    public function getCreatorId()
    {
        /**
         * @var BackofficeAuthenticationService $auth
         */
        $auth = $this->getServiceLocator()->get('library_backoffice_auth');
        $identity = $auth->getIdentity();

        if (!$identity) {
            throw new \RuntimeException('Not authenticated user.');
        }

        return $identity->id;
    }

    /**
     * @param int|null $expenseId
     * @return int
     * @throws \Exception
     */
    private function saveExactExpense($expenseId = null)
    {
        /**
         * @var Logger $logger
         */
        $logger = $this->getServiceLocator()->get('ActionLogger');
        $data = $this->getTicketData();

        if (count($data)) {
            $dao = $this->getDao();
            $where = [];
            $nullatenente = false;
            $loggerProperties = [];
            $commentNotEmpty = !empty($data['comment']);
            $preparedData = [
                'manager_id' => $data['managerId'],
                'currency_id' => $data['currencyId'],
                'title' => $data['title'],
                'purpose' => $data['purpose'],
                'limit' => $data['limit'],
                'expected_completion_date_start' => $data['expectedCompletionDateStart'],
                'expected_completion_date_end' => $data['expectedCompletionDateEnd'],
                'ticket_balance' => $data['balance']['ticket'],
                'deposit_balance' => $data['balance']['deposit'],
                'item_balance' => $data['balance']['item'],
                'transaction_balance' => $data['balance']['transaction'],
                'budget_id' => $data['budget'],
            ];

            // Revoke or Resubmit
            if ($data['resubmission']) {
                $nullatenente = true;
                $loggerProperties = [Logger::ACTION_RESUBMITTED, Logger::POSITIVE];
            } elseif ($data['limit'] < $data['balance']['item']) {
                $nullatenente = true;
                $loggerProperties = [Logger::ACTION_APPROVED, Logger::NEGATIVE];
            }

            if ($nullatenente) {
                $preparedData['status'] = self::STATUS_PENDING;
                $preparedData['finance_status'] = self::FIN_STATUS_NEW;
                $logger->save(Logger::MODULE_EXPENSE, $expenseId, $loggerProperties[0], $loggerProperties[1]);
            }

            /**
             * IF expenseId IS NULL THEN
             *      add expense
             * ELSE
             *      edit expense
             * ENDIF
             */
            if (is_null($expenseId)) {
                $preparedData['creator_id'] = $this->getCreatorId();
                $preparedData['date_created'] = $this->getCreationDate();
            } else {
                $where = ['id' => $expenseId];
            }

            // Save expense data locally to use for items, transactions and attachments
            $this->setTicketOriginalData($preparedData);

            // Save exact expense
            $dao->save($preparedData, $where);

            if (is_null($expenseId)) {
                $expenseId = $dao->lastInsertValue;
            }

            if ($commentNotEmpty) {
                $this->saveComment($expenseId, $data['comment']);
            }

            return $expenseId;
        }
    }

    /**
     * @param int $expenseId
     * @param string $comment
     * @throws \Exception
     */
    private function saveComment($expenseId, $comment)
    {
        /**
         * @var Logger $logger
         */
        $logger = $this->getServiceLocator()->get('ActionLogger');
        $result = $logger->save(Logger::MODULE_EXPENSE, $expenseId, Logger::ACTION_COMMENT, $comment);

        if ($result === false) {
            throw new \Exception('ActionLogger Error: ' . $logger->getErrorMessage());
        }
    }

    /**
     * @param array $links
     * @throws \Exception
     */
    private function saveItems($links)
    {
        if (!$this->identified()) {
            throw new \Exception('Expense not initiated. Expense Id not defined.');
        }

        if (count($this->items)) {
            foreach ($this->items as $item) {
                $data = $item->getData();

                if (isset($links[$data['transactionId']])) {
                    $data['transactionId'] = $links[$data['transactionId']];
                }

                $item->setData($data);
                $item->save($this);
            }
        }
    }

    /**
     * @throws \Exception
     */
    private function saveTransactions()
    {
        $links = [];

        if (!$this->identified()) {
            throw new \Exception('Expense not initiated. Expense Id not defined.');
        }

        if (count($this->transactions)) {
            foreach ($this->transactions as $transaction) {
                $data = $transaction->getData();

                $links[$data['tmpId']] = $transaction->save($this);
            }
        }


        return $links;
    }
}
