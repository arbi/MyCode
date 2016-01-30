<?php

namespace DDD\Service\Finance;

use DDD\Service\ServiceBase;
use Library\Constants\Constants;
use Library\Utility\Helper;

class Budget extends ServiceBase
{
    const BUDGET_NULL_ID = 1;
    const BUDGET_OTA_COMMISSIONS = 5; // OTA Commisions and Advertising by Commercial Team

    const BUDGET_STATUS_DRAFT = 1;
    const BUDGET_STATUS_PENDING = 2;
    const BUDGET_STATUS_APPROVED = 3;
    const BUDGET_STATUS_REJECTED = 4;

    public static $budgetStatuses = [
        self::BUDGET_STATUS_DRAFT => 'Draft',
        self::BUDGET_STATUS_PENDING => 'Pending',
        self::BUDGET_STATUS_APPROVED => 'Approved',
        self::BUDGET_STATUS_REJECTED => 'Rejected',
    ];

    const BUDGET_ARCHIVED = 1;
    const BUDGET_UNARCHIVED = 0;

    const BUDGET_FROZEN = 1;
    const BUDGET_UNFROZEN = 0;

    public static $budgetFrozen = [
        self::BUDGET_FROZEN => 'Frozen',
        self::BUDGET_UNFROZEN => 'Unfrozen',
    ];

    public static $budgetArchived = [
        self::BUDGET_ARCHIVED => 'Archived',
        self::BUDGET_UNARCHIVED => 'Unarchived',
    ];

    const BUDGET_CURRENCY = ' EUR';

    /**
     * @param array $params
     * @param $userId
     * @return array
     */
    public function getDatatableData(array $params, $userId)
    {
        /**
         * @var \DDD\Dao\Finance\Budget\Budget $budgetDao
         */
        $budgetDao  = $this->getServiceLocator()->get('dao_finance_budget_budget');
        $result     = $budgetDao->getAllBudgets($params, $userId);
        $data       = [];
        $budgetList = $result['result'];
        $total      = $result['total'];

        if ($budgetList->count()) {
            foreach ($budgetList as $budget) {
                $balanceClass = ($budget['balance'] < 0) ? 'color-danger' : 'color-success';
                array_push($data, [
                    isset(self::$budgetStatuses[$budget['status']]) ? self::$budgetStatuses[$budget['status']] : '',
                    $budget['name'],
                    isset($budget['department_name']) ? $budget['department_name'] : '',
                    $budget['from'] && $budget['to'] ? date(Constants::GLOBAL_DATE_FORMAT, strtotime($budget['from'])) . ' - ' . date(Constants::GLOBAL_DATE_FORMAT, strtotime($budget['to'])) : '',
                    $budget['amount'] . self::BUDGET_CURRENCY,
                    '<span class="' . $balanceClass . '">' .$budget['balance'] . self::BUDGET_CURRENCY . '</span>',
                    $budget['user_name'],
                    '<a class="btn btn-xs btn-primary" href="/finance/budget/edit/' . $budget['id'] . '" data-html-content="Edit"></a>'
                ]);
            }
        }

        return [
            'iTotalRecords'        => $total,
            'iTotalDisplayRecords' => $total,
            'iDisplayStart'        => $params['iDisplayStart'],
            'iDisplayLength'       => $params['iDisplayLength'],
            'aaData'               => $data,
        ];
    }

    /**
     * @param $budgetId
     * @param $userId
     * @return array|\ArrayObject|null
     */
    public function getBudgetData($budgetId, $userId)
    {
        /**
         * @var \DDD\Dao\Finance\Budget\Budget $budgetDao
         */
        $budgetDao = $this->getServiceLocator()->get('dao_finance_budget_budget');

        $budgetData = $budgetDao->getBudgetData($budgetId, $userId);
        if ($budgetData) {
            $budgetData['period'] = date(Constants::GLOBAL_DATE_FORMAT, strtotime($budgetData['from'])) .
                ' - ' . date(Constants::GLOBAL_DATE_FORMAT, strtotime($budgetData['to']));
        } else {
            $budgetData = [];
        }
        return $budgetData;
    }

    /**
     * @param $data
     * @param $budgetId
     * @return int
     */
    public function saveBudget($data, $budgetId)
    {
        /** @var \DDD\Dao\Finance\Budget\Budget $budgetDao */
        $budgetDao = $this->getServiceLocator()->get('dao_finance_budget_budget');
        $dateRange = Helper::refactorDateRange($data['period']);

        if ($budgetId) {
            // Apply amount changes to balance
            $oldData = $budgetDao->getBudgetData($budgetId);
            $balance = $oldData['balance'] + $data['amount'] - $oldData['amount'];
        } else {
            // Starting balance is the same as amount
            $balance = $data['amount'];
        }

        $params = [
            'name'          => $data['name'],
            'status'        => $data['status'],
            'from'          => $dateRange['date_from'],
            'to'            => $dateRange['date_to'],
            'amount'        => $data['amount'],
            'balance'       => $balance,
            'description'   => $data['description'],
            'department_id' => $data['is_global'] ? null : $data['department_id'],
            'is_global'     => $data['is_global'],
        ];

        if ($data['country_id'] > 0) {
            $params['country_id'] = $data['country_id'];
        } else {
            $params['country_id'] = null;
        }

        if ($budgetId) {
            $budgetDao->save($params, ['id'=>$budgetId]);
        } else {
            $auth = $this->getServiceLocator()->get('library_backoffice_auth');
            $userId = $auth->getIdentity()->id;
            $params['user_id'] = $userId;
            $budgetId = $budgetDao->save($params);
        }

		return $budgetId;
	}

    /**
     * @param $budgetId
     * @param $frozen
     * @return int
     */
    public function frozen($budgetId, $frozen)
    {
        /**
         * @var \DDD\Dao\Finance\Budget\Budget $budgetDao
         */
        $budgetDao = $this->getServiceLocator()->get('dao_finance_budget_budget');

        return $budgetDao->save(['frozen' => $frozen], ['id' => $budgetId]);
    }

    /**
     * @param $budgetId
     * @param $archive
     * @return int
     */
    public function archive($budgetId, $archive)
    {
        /**
         * @var \DDD\Dao\Finance\Budget\Budget $budgetDao
         */
        $budgetDao = $this->getServiceLocator()->get('dao_finance_budget_budget');

        return $budgetDao->save(['archived' => $archive], ['id' => $budgetId]);
    }

    /**
     * @param $budgetId
     * @return array
     */
    public function getBudgetsForPO($budgetId = false)
    {
        /**
         * @var \DDD\Dao\Finance\Budget\Budget $budgetDao
         */
        $budgetDao = $this->getServiceLocator()->get('dao_finance_budget_budget');
        $budgets = $budgetDao->getBudgetsForPO($budgetId);
        $budgetList = [];

        if ($budgets->count()) {
            foreach ($budgets as $budget) {
                $budgetPeriod = (is_null($budget['from']) || is_null($budget['to'])) ? ''
                    : ' (' . date(Constants::GLOBAL_DATE_FORMAT, strtotime($budget['from'])) .
                    ' - ' . date(Constants::GLOBAL_DATE_FORMAT, strtotime($budget['to'])) . ')';
                $budgetList[$budget['id']] = $budget['name'] . $budgetPeriod;
            }
        }

        return $budgetList;
    }

    /**
     * @param int $year
     * @return array
     */
    public function getBudgetsForChart($year)
    {
        /** @var \DDD\Dao\Finance\Budget\Budget $budgetDao */
        $budgetDao = $this->getServiceLocator()->get('dao_finance_budget_budget');

        if ($year != 0) {
            $startOfTheYear = $year . '-' . '01-01';
            $endOfTheYear   = $year . '-' . '12-31';
        } else {
            $startOfTheYear = $endOfTheYear = false;
        }

        $budgets = $budgetDao->getBudgetsForChart($startOfTheYear, $endOfTheYear);
        $budgetList = [];

        if ($budgets->count()) {
            foreach ($budgets as $budget) {
                $budgetList[] = [
                    'name' => $budget['name'],
                    'y' => $budget['amount'],
                    'yWithComma' => number_format($budget['amount'], 0, '',',')
                ];
            }
        }

        return $budgetList;
    }

    /**
     * @return array
     */
    public function getPendingBudgetCount()
    {
        /**
         * @var \DDD\Dao\Finance\Budget\Budget $budgetDao
         */
        $budgetDao = $this->getServiceLocator()->get('dao_finance_budget_budget');
        return $budgetDao->getPendingBudgetCount();
    }

    /**
     * @return array
     */
    public function getPendingBudgets()
    {
        /**
         * @var \DDD\Dao\Finance\Budget\Budget $budgetDao
         */
        $budgetDao = $this->getServiceLocator()->get('dao_finance_budget_budget');
        return $budgetDao->getPendingBudgets();
    }

    /**
     * @param $budgetId
     * @param $status
     * @return int
     */
    public function changeStatus($budgetId, $status)
    {
        /**
         * @var \DDD\Dao\Finance\Budget\Budget $budgetDao
         */
        $budgetDao = $this->getServiceLocator()->get('dao_finance_budget_budget');

        return $budgetDao->save(['status' => $status], ['id' => $budgetId]);
    }
}
