<?php

namespace DDD\Dao\Finance\Budget;

use Library\Utility\Helper;
use Zend\Db\Sql\Expression;
use Zend\Db\Sql\Select;
use Library\DbManager\TableGatewayManager;
use Library\Constants\DbTables;
use DDD\Service\Finance\Budget as BudgetService;
use Zend\Db\Sql\Where;

class Budget extends TableGatewayManager
{
    protected $table = DbTables::TBL_BUDGETS;

    public function __construct($sm)
    {
        parent::__construct($sm, 'DDD\Domain\Finance\Budget\Budget');
    }

    /**
     * @param $params
     * @param $userId
     * @return array
     */
    public function getAllBudgets($params, $userId)
    {
        $this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());
        $where = new Where();

        if (isset($params['name']) && $params['name']) {
            $where->like($this->getTable() . '.name', $params['name'] . '%');
        }

        if (isset($params['status']) && $params['status']) {
            $where->equalTo($this->getTable() . '.status', $params['status']);
        }

        if (isset($params['user']) && $params['user']) {
            $where->equalTo($this->getTable() . '.user_id', $params['user']);
        }

        if (isset($params['period']) && $params['period']) {
            $dateRange = Helper::refactorDateRange($params['period']);
            $where->greaterThanOrEqualTo($this->getTable() . '.to', $dateRange['date_from']);
            $where->lessThanOrEqualTo($this->getTable() . '.from', $dateRange['date_to']);
        }

        if (isset($params['frozen']) && $params['frozen'] >= 0) {
            $where->equalTo($this->getTable() . '.frozen', $params['frozen']);
        }

        if (isset($params['archived']) && $params['archived'] >= 0) {
            $where->equalTo($this->getTable() . '.archived', $params['archived']);
        }

        if ($userId) {
            $where->equalTo($this->getTable() . '.user_id', $userId);
        }

        if (isset($params['department']) && $params['department'] >= 0) {
            $where->equalTo($this->getTable() . '.department_id', $params['department']);
        }

        if (isset($params['country']) && $params['country'] >= 0) {
            $where->equalTo($this->getTable() . '.country_id', $params['country']);
        }

        if (isset($params['global']) && $params['global'] >= 0) {
            $where->equalTo($this->getTable() . '.is_global', $params['global']);
        }

        $offset  = $params['iDisplayStart'];
        $limit   = $params['iDisplayLength'];
        $sortCol = $params['iSortCol_0'];
        $sortDir = $params['sSortDir_0'];

        $result = $this->fetchAll(function (Select $select) use ($offset, $limit, $sortCol, $sortDir, $where) {
            $sortColumns = [
                'status',
                'name',
                'department_name',
                'from',
                'amount',
                'balance',
                'user_name'
            ];
            $select->columns([
                'id',
                'name',
                'from',
                'to',
                'amount',
                'description',
                'status',
                'user_id',
                'department_id',
                'country_id',
                'is_global',
                'balance'
            ]);

            $select->join(
                ['users' => DbTables::TBL_BACKOFFICE_USERS],
                $this->getTable() . '.user_id = users.id',
                ['user_name' => new Expression('CONCAT(firstname, " ", lastname)')],
                Select::JOIN_LEFT
            );

            $select->join(
                ['teams' => DbTables::TBL_TEAMS],
                $this->getTable() . '.department_id = teams.id',
                ['department_name' => 'name'],
                Select::JOIN_LEFT
            );

            $select->where($where);

            $select
                ->group($this->getTable() . '.id')
                ->order($sortColumns[$sortCol].' '.$sortDir)
                ->offset((int)$offset)
                ->limit((int)$limit);
            $select->quantifier(new Expression('SQL_CALC_FOUND_ROWS'));
        });

        $statement   = $this->adapter->query('SELECT FOUND_ROWS() as total');
        $resultCount = $statement->execute();
        $row         = $resultCount->current();
        $total       = $row['total'];

        return  [
            'result' => $result,
            'total'  => $total,
        ];
    }

    /**
     * @param $budgetId
     * @param $userId
     * @return array|\ArrayObject|null
     */
   public function getBudgetData($budgetId, $userId = false)
   {
       $this->setEntity(new \ArrayObject());

       return $this->fetchOne(function (Select $select) use ($budgetId, $userId) {
           $select->columns([
               'name'          => 'name',
               'period'        => new Expression('CONCAT(`from`, " - ", `to`)'),
               'from'          => 'from',
               'to'            => 'to',
               'amount'        => 'amount',
               'balance'       => 'balance',
               'description'   => 'description',
               'status'        => 'status',
               'frozen'        => 'frozen',
               'archived'      => 'archived',
               'department_id' => 'department_id',
               'country_id'    => 'country_id',
               'is_global'     => 'is_global',
           ]);
           $select->where->equalTo('id', $budgetId);

           if ($userId) {
               $select->where->equalTo($this->getTable() . '.user_id', $userId);
           }
       });
   }

    /**
     * @param int $budgetId
     * @return array|\ArrayObject|null
     */
    public function getBudgetsForPO($budgetId)
    {
        $this->setEntity(new \ArrayObject());

        return $this->fetchAll(function (Select $select) use ($budgetId) {
            $select->columns([
                'id',
                'name',
                'balance',
                'from',
                'to'
            ]);

            $where = new Where();

            $where->nest
                ->equalTo('archived', BudgetService::BUDGET_UNARCHIVED)
                ->equalTo('frozen', BudgetService::BUDGET_UNFROZEN)
                ->equalTo('status', BudgetService::BUDGET_STATUS_APPROVED)
                ->unnest
                ->or
                ->nest
                ->equalTo('id', $budgetId)
                ->unnest;

            $select->where($where);
        });
    }

    /**
     * @param bool|false $startOfTheYear
     * @param bool|false $endOfTheYear
     * @return \Zend\Db\ResultSet\ResultSet
     */
    public function getBudgetsForChart($startOfTheYear = false, $endOfTheYear = false)
    {
       $this->setEntity(new \ArrayObject());
       return $this->fetchAll(function (Select $select) use ($startOfTheYear, $endOfTheYear){
           $select->columns([
               'name',
               'amount',
           ]);
           $select->where([
               'frozen' => BudgetService::BUDGET_UNFROZEN,
               'status' => BudgetService::BUDGET_STATUS_APPROVED,
               'archived' => BudgetService::BUDGET_UNARCHIVED,
           ]);

           if ($startOfTheYear != false) {
               $select->where->expression(
                   'DATE(`from`) >= DATE("' . $startOfTheYear . '") AND ' .
                   'DATE(`to`) <= DATE("' . $endOfTheYear . '")', []
               );
           }

       });
    }

    /**
     * @return array|\ArrayObject|null
     */
    public function getPendingBudgetCount()
    {
        $this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());
        $result = $this->fetchOne(function (Select $select) {
            $select->columns(['count' => new Expression('COUNT(*)')]);

            $select->where->equalTo($this->getTable().'.status', BudgetService::BUDGET_STATUS_PENDING);
        });

        return $result['count'];
    }

    /**
     * @return array|\ArrayObject|null
     */
    public function getPendingBudgets()
    {
        $this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());

        return $this->fetchAll(function (Select $select) {
            $select->columns([
                'id',
                'name',
                'from',
                'to',
                'amount',
                'status',
            ]);

            $select->join(
                ['users' => DbTables::TBL_BACKOFFICE_USERS],
                $this->getTable() . '.user_id = users.id',
                ['user_name' => new Expression('CONCAT(firstname, " ", lastname)')],
                Select::JOIN_LEFT
            );

            $select->where
                ->equalTo($this->getTable() . '.status', BudgetService::BUDGET_STATUS_PENDING)
                ->equalTo($this->getTable() . '.archived', BudgetService::BUDGET_UNARCHIVED);
        });
    }

}
