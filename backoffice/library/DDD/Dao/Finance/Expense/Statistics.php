<?php

namespace DDD\Dao\Finance\Expense;

use DDD\Service\Finance\Expense\ExpenseCosts;
use Library\Finance\Process\Expense\Ticket;
use Zend\Db\Sql\Select;
use Library\DbManager\TableGatewayManager;
use Library\Constants\DbTables;
use Zend\Db\Sql\Expression;
use Zend\Db\Sql\Where;

class Statistics extends TableGatewayManager
{
    protected $table = DbTables::TBL_EXPENSE_COST;

    public function __construct($sm, $domain = '\ArrayObject')
    {
        parent::__construct($sm, $domain);
    }

    public function getTotalBudgetSummOfRunning($id)
    {
        return $this->fetchOne(function (Select $select) use ($id) {
            $select->columns(['sum' => new Expression('sum(item.amount)')]);
            $select->join(
                ['item' => DbTables::TBL_EXPENSE_ITEM],
                $this->getTable() . '.expense_item_id = item.id',
                [],
                'LEFT'
            );
            $select->where
                ->equalTo($this->getTable() . '.cost_center_id', $id)
                ->equalTo($this->getTable() . '.cost_center_type', ExpenseCosts::TYPE_APARTMENT)
                ->notEqualTo('item.is_startup', 1)
                ->expression('YEAR(item.date_created) >= YEAR(NOW())', []);
        });
    }

    /**
     * @param int $id
     * @return array|\ArrayObject|null
     */
    public function getTotalBudgetSummOfStartup($id)
    {
        return $this->fetchOne(function (Select $select) use ($id) {
            $select->columns(['sum' => new Expression('sum(item.amount)')]);
            $select->join(
                ['item' => DbTables::TBL_EXPENSE_ITEM],
                $this->getTable() . '.expense_item_id = item.id',
                [],
                'LEFT'
            );
            $select->where
                ->equalTo($this->getTable() . '.cost_center_id', $id)
                ->equalTo($this->getTable() . '.cost_center_type', ExpenseCosts::TYPE_APARTMENT)
                ->equalTo('item.is_startup', 1)
            ;
        });
    }

    /**
     * @param int $apartmentId
     * @param string $startDate
     * @param string $endDate
     *
     * @return array
     */
    public function getMonthlyCost($apartmentId, $startDate, $endDate)
    {
        $expenses = $this->fetchAll(function (Select $select) use ($apartmentId, $startDate, $endDate) {
            $nestedWhere = new Where();
            $nestedWhere
                ->lessThanOrEqualTo('item.period_from', $endDate)
                ->or
                ->greaterThanOrEqualTo('item.period_to', $startDate);
            $select
                ->columns([
                    'amount' => 'amount',
                ])
                ->join(
                    ['item' => DbTables::TBL_EXPENSE_ITEM],
                    $this->getTable() . '.expense_item_id = item.id',
                    [
                        'period_from',
                        'period_to',
                        'date_created',
                        'is_refund'
                    ],
                    Select::JOIN_LEFT
                )
                ->join(
                    ['expense' => DbTables::TBL_EXPENSES],
                    'item.expense_id = expense.id',
                    [],
                    Select::JOIN_LEFT
                )
                ->where
                    ->equalTo('expense.status', Ticket::STATUS_GRANTED)
                    ->equalTo($this->getTable() . '.cost_center_id', $apartmentId)
                    ->equalTo($this->getTable() . '.cost_center_type', ExpenseCosts::TYPE_APARTMENT)
                    ->addPredicate($nestedWhere);
        });

        $result = [];

        foreach ($expenses as $expense) {
            $monthStart = date("n", strtotime($expense['period_from']));
            $yearStart  = date("Y", strtotime($expense['period_from']));
            $monthEnd   = date("n", strtotime($expense['period_to']));
            $yearEnd    = date("Y", strtotime($expense['period_to']));
            $startInMonths = $monthStart + $yearStart * 12;
            $endInMonths = $monthEnd + $yearEnd * 12;
            $sign = $expense['is_refund'] ? -1 : 1;
            $expensePerMonth = $sign * $expense['amount'] / ($endInMonths - $startInMonths + 1);

            for ($iterator = $startInMonths; $iterator <= $endInMonths; $iterator++) {
                $tempMonth = $iterator % 12;
                $tempMonth = $tempMonth ? $tempMonth : 12;

                $currentYear = ($iterator - $tempMonth) / 12;
                $currentMonth = date('M', mktime(0, 0, 0, $tempMonth, 1) );
                $key = ($currentMonth . '_' . $currentYear);
                if (isset($result[$key])) {
                    $result[$key] += $expensePerMonth;
                } else {
                    $result[$key] = $expensePerMonth;
                }
            }
        }
        return $result;
    }
}
