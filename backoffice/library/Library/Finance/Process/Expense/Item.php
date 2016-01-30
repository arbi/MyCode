<?php

namespace Library\Finance\Process\Expense;

use DDD\Dao\Finance\Expense;
use DDD\Service\Finance\Expense\ExpenseCosts;
use Library\Finance\Exception\NotFoundException;
use Library\Utility\Helper;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\Stdlib\ArrayObject;

class Item extends TicketElementAbstract
{
    /**
     * @param array $itemData
     * @param int|null $itemId
     * @throws \Exception
     */
    public function __construct(array $itemData, $itemId = null)
    {
        $this->setData($itemData);
        $this->setId($itemId);
        $this->detectMode();
    }

    /**
     * @param Ticket $expenseTicket
     * @throws NotFoundException
     */
    public function save(Ticket $expenseTicket)
    {
        if (!($this->getServiceLocator() instanceof ServiceLocatorInterface)) {
            throw new NotFoundException('Service locator not defined for expense item.');
        }

        $itemDao = $this->getItemDao();
        $costDao = $this->getCostDao();

        // Normalize values
        $this->prepare();

        $data = $this->getData();
        $itemAmount = null;
        $costCenters = [];
        $itemData = [];

        if ($this->getMode() == self::MODE_ADD) {
            $costCenters = $data['costCenters'];
            $itemAmount = $data['amount'];

            $itemData = [
                'transaction_id' => $data['transactionId'],
                'account_id' => $data['accountId'],
                'account_reference' => $data['accountReference'],
                'sub_category_id' => $data['subCategoryId'],
                'currency_id' => $data['currencyId'],
                'amount' => Helper::formatAmount($itemAmount),
                'is_startup' => $data['isStartup'],
                'is_deposit' => $data['isDeposit'],
                'is_refund' => $data['isRefund'],
                'comment' => $data['accountComment'],
                'type' => $data['type'],
                'status' => $data['status'],
            ];

            if ($data['period']['from']) {
                $itemData['period_from'] = $data['period']['from'];
                $itemData['period_to'] = $data['period']['to'];
            }
        }

        switch ($this->getMode()) {
            case self::MODE_ADD:
                $itemData['expense_id'] = $expenseTicket->getExpenseId();
                $itemData['creator_id'] = $expenseTicket->getCreatorId();
                $itemData['date_created'] = date('Y-m-d H:i:s');

                if (!$data['period']['from']) {
                    $itemData['period_from'] = $itemData['period_to'] = date('Y-m-d');
                }

                $expenseItemId = $itemDao->save($itemData);

                $this->setId($expenseItemId);
                if (count($costCenters)) {
                    $costCenterAmount = $itemAmount / count($costCenters);

                    foreach ($costCenters as $costCenter) {
                        $costDao->save([
                            'expense_item_id' => $expenseItemId,
                            'cost_center_id' => $costCenter['id'],
                            'cost_center_type' => $this->translateCCT($costCenter['type']),
                            'amount' => $this->calculateCostAmount($costCenterAmount, $data['currencyId'], $costCenter['currencyId'], $data['isRefund']),
                        ]);
                    }
                }

                break;
            case self::MODE_DELETE:
                $costDao->delete(['expense_item_id' => $this->getId()]);
                $itemDao->delete(['id' => $this->getId()]);

                break;
        }
    }

    /**
     * Depends on apartment's currency, cost amount should be recalculated.
     * As a rule - Cost currency = cost center currency
     *
     * @param float $sourceAmount Cost center amount in item currency
     * @param int $sourceCurrency Item currency
     * @param int $destinationCurrency Cost center currency
     * @return float
     * @throws \RuntimeException
     */
    private function calculateCostAmount($sourceAmount, $destinationCurrency, $sourceCurrency, $isRefund)
    {
        if (!($this->getServiceLocator() instanceof ServiceLocatorInterface)) {
            throw new \RuntimeException('Service Locator not defined');
        }

        if (!count($this->getCurrencies())) {
            $this->setCurrencies(
                $this->getServiceLocator()->get('CurrencyList')
            );
        }

        $currencyList = $this->getCurrenciesOptimized();
        $isRefundFactor = ($isRefund == 1) ? -1 : 1;
        return $isRefundFactor * $sourceAmount * $currencyList[$sourceCurrency] / $currencyList[$destinationCurrency];
    }

    /**
     * Normalize values
     *
     * @return void
     */
    private function prepare()
    {
        $data = $this->getData();

        if (empty($data['accountId'])) {
            $data['accountId'] = null;
        }

        if (empty($data['transactionId'])) {
            $data['transactionId'] = null;
        } else {
            if (preg_match('/^TMP/i', $data['transactionId'])) {
                $data['transactionId'] = null;
            }
        }

        if (empty($data['accountReference'])) {
            $data['accountReference'] = null;
        }

        if (empty($data['accountComment'])) {
            $data['accountComment'] = null;
        }

        if (empty($data['status'])) {
            $data['status'] = \Library\Finance\Process\Expense\Helper::ITEM_STATUS_APPROVED;
        }

        if (empty($data['type'])) {
            $data['type'] = \Library\Finance\Process\Expense\Helper::TYPE_DECLARE_AN_EXPENSE;
        }

        if (empty($data['subCategoryId'])) {
            $data['subCategoryId'] = null;
        }

        if (empty($data['period'])) {
            $data['period'] = [
                'from' => null,
                'to' => null,
            ];
        }

        if (empty($data['isStartup'])) {
            $data['isStartup'] = 0;
        } else {
            $data['isStartup'] = $data['isStartup'] ? 1 : 0;
        }

        if (empty($data['isDeposit'])) {
            $data['isDeposit'] = 0;
        } else {
            $data['isDeposit'] = $data['isDeposit'] ? 1 : 0;
        }

        if (empty($data['isRefund'])) {
            $data['isRefund'] = 0;
        } else {
            $data['isRefund'] = $data['isRefund'] ? 1 : 0;
        }

        $this->setData($data);
    }

    /**
     * Translate Cost Center Type (from string to int)
     *
     * @param string $type
     * @return int
     * @throws \Exception
     */
    private function translateCCT($type)
    {
        if (!in_array($type, ['apartment', 'officeSection', 'group'])) {
            throw new \Exception('Undefined cost center type - ' . $type);
        }

        if ($type == 'apartment') {
            $costCenterType = ExpenseCosts::TYPE_APARTMENT;
        } else {
            $costCenterType = ExpenseCosts::TYPE_OFFICE_SECTION;
        }

        return $costCenterType;
    }

    /**
     * @return Expense\ExpenseItem
     */
    private function getItemDao()
    {
        return new Expense\ExpenseItem($this->getServiceLocator());
    }

    /**
     * @return Expense\ExpenseCost
     */
    private function getCostDao()
    {
        return new Expense\ExpenseCost($this->getServiceLocator());
    }
}
