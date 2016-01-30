<?php

namespace DDD\Dao\Finance\Expense;

use DDD\Service\Finance\Expense\ExpenseCosts;
use Library\Finance\Process\Expense\Ticket;
use Zend\Db\Sql\Delete;
use Zend\Db\Sql\Expression;
use Zend\Db\Sql\Select;
use Library\DbManager\TableGatewayManager;
use Library\Constants\DbTables;
use Zend\ServiceManager\ServiceLocatorInterface;

class ExpenseCost extends TableGatewayManager
{
    protected $table = DbTables::TBL_EXPENSE_COST;

    /**
     * @param ServiceLocatorInterface $sm
     * @param string $domain
     */
    public function __construct($sm, $domain = '\ArrayObject') {
        parent::__construct($sm, $domain);
    }

    /**
     * @param int $itemId
     * @return \Zend\Db\ResultSet\ResultSet|array[]
     */
    public function getItemCostCenters($itemId)
    {
        return $this->fetchAll(function(Select $select) use ($itemId) {
            $select->columns([
                'type' => 'cost_center_type',
                'id' => new Expression('ifnull(apartments.id, office_sections.id)'),
                'name' => new Expression('ifnull(apartments.name, office_sections.name)'),
                'label' => new Expression('ifnull(offices.name, "Apartment")'),
                'currency_id' => new Expression('ifnull(countries.currency_id, apartments.currency_id)'),
            ]);
            $select->join(
                ['apartments' => DbTables::TBL_APARTMENTS],
                new Expression("apartments.id = {$this->getTable()}.cost_center_id and {$this->getTable()}.cost_center_type = " . ExpenseCosts::TYPE_APARTMENT),
                [],
                Select::JOIN_LEFT
            );
            $select->join(
                ['office_sections' => DbTables::TBL_OFFICE_SECTIONS],
                new Expression("office_sections.id = {$this->getTable()}.cost_center_id and {$this->getTable()}.cost_center_type = " . ExpenseCosts::TYPE_OFFICE_SECTION),
                [],
                Select::JOIN_LEFT
            );
            $select->join(
                ['offices' => DbTables::TBL_OFFICES],
                new Expression("offices.id = office_sections.office_id and {$this->getTable()}.cost_center_type = " . ExpenseCosts::TYPE_OFFICE_SECTION),
                [],
                Select::JOIN_LEFT
            );
            $select->join(
                ['countries' => DbTables::TBL_COUNTRIES],
                new Expression("countries.id = offices.country_id and {$this->getTable()}.cost_center_type = " . ExpenseCosts::TYPE_OFFICE_SECTION),
                [],
                Select::JOIN_LEFT
            );
            $select->where([$this->getTable() . '.expense_item_id' => $itemId]);
        });
    }

    /**
     * @param array $itemIdList
     */
    public function deleteItemCosts($itemIdList)
    {
        $delete = new Delete($this->getTable());
        $delete->where->in('expense_item_id', $itemIdList);
    }

    /**
     * @param int $apartmentId
     * @return \Zend\Db\ResultSet\ResultSet|array[]
     */
    public function getApartmentCosts($apartmentId)
    {
        return $this->fetchAll(function (Select $select) use ($apartmentId) {
            $select->columns([
                'id' => new Expression('concat(expense.id, "-", ' . $this->getTable() . '.id)'),
                'amount'
            ]);
            $select->join(
                ['item' => DbTables::TBL_EXPENSE_ITEM],
                $this->getTable() . '.expense_item_id = item.id',
                ['date' => 'date_created'],
                Select::JOIN_LEFT
            );
            $select->join(
                ['expense' => DbTables::TBL_EXPENSES],
                'expense.id = item.expense_id',
                ['purpose', 'expense_id' => 'id'],
                Select::JOIN_LEFT
            );
            $select->join(
                ['sub_category' => DbTables::TBL_EXPENSE_ITEM_SUB_CATEGORIES],
                'item.sub_category_id = sub_category.id',
                ['category' => 'name'],
                Select::JOIN_LEFT
            );
            $select->where([
                $this->getTable() . '.cost_center_id' => $apartmentId,
                $this->getTable() . '.cost_center_type' => ExpenseCosts::TYPE_APARTMENT,
            ]);
            $select->where->notEqualTo('expense.status', Ticket::STATUS_DECLINED);
        });
    }

    /**
     * @param int $officeId
     * @param array $datatableParams
     * @return \Zend\Db\ResultSet\ResultSet|array[]
     */
    public function getOfficeCosts($officeId, $datatableParams = [], $onlyCount = false)
    {
        $sortColumns = [
            'id',
            'category',
            'date',
            'currency_code',
            'amount'
        ];

        return $this->fetchAll(function (Select $select) use ($officeId, $sortColumns, $datatableParams, $onlyCount) {
            $select->columns([
                'id'    => new Expression('concat(expense.id, "-", ' . $this->getTable() . '.id)'),
                'amount'
            ]);
            $select->join(
                ['office_section' => DbTables::TBL_OFFICE_SECTIONS],
                $this->getTable() .'.cost_center_id = office_section.id',
                [],
                Select::JOIN_INNER
            );
            $select->join(
                ['item' => DbTables::TBL_EXPENSE_ITEM],
                $this->getTable() . '.expense_item_id = item.id',
                ['date' => 'date_created'],
                Select::JOIN_LEFT
            );

            $select->join(
                ['expense' => DbTables::TBL_EXPENSES],
                'expense.id = item.expense_id',
                [
                    'purpose',
                    'expense_id' => 'id'
                ],
                Select::JOIN_LEFT
            );

            $select->join(
                ['currency' => DbTables::TBL_CURRENCY],
                'currency.id = expense.currency_id',
                ['currency_code' => 'code'],
                Select::JOIN_LEFT
            );

            $select->join(
                ['sub_category' => DbTables::TBL_EXPENSE_ITEM_SUB_CATEGORIES],
                'item.sub_category_id = sub_category.id',
                ['category' => 'name'],
                Select::JOIN_LEFT
            );
            $select->where([
                'office_section.office_id' => $officeId,
                $this->getTable() . '.cost_center_type' => ExpenseCosts::TYPE_OFFICE_SECTION,
            ]);

            $select->where
                ->notEqualTo('expense.status', Ticket::STATUS_DECLINED);

            if (!empty($datatableParams['sSearch'])) {
                $select->where
                    ->nest()
                    ->like('sub_category.name', '%'.$datatableParams['sSearch'].'%')
                    ->or
                    ->like('expense.purpose', '%'.$datatableParams['sSearch'].'%')
                    ->or
                    ->equalTo($this->getTable().'.amount', $datatableParams['sSearch'])
                    ->or
                    ->equalTo('expense.id', $datatableParams['sSearch'])
                    ->unnest();
            }

            if (!$onlyCount && !empty($datatableParams['iSortCol_0'])) {
                $select->order($sortColumns[$datatableParams['iSortCol_0']] . ' '. $datatableParams['sSortDir_0']);
            }

            if (!$onlyCount && !empty($datatableParams['iDisplayStart']) && $datatableParams['iDisplayStart'] > 0) {
                $select->offset((int)$datatableParams['iDisplayStart']);
            }

            if (!$onlyCount && !empty($datatableParams['iDisplayLength'])) {
                $select->limit((int)$datatableParams['iDisplayLength']);
            }
        });
    }

    /**
     * Used to replace /ginosi/backoffice/library/DDD/Dao/Expense/ExpenseCost.php::getTotalBudgetSummOfStartup($id)
     *
     * @param int $apartmentId
     * @return \Zend\Db\ResultSet\ResultSet|array[]
     */
    public function getTotalBudgetSummOfStartup($apartmentId)
    {
        return $this->fetchOne(function (Select $select) use ($apartmentId) {
            $select->columns(['sum' => new Expression("ifnull(sum({$this->getTable()}.amount), 0)")]);
            $select->join(
                ['item' => DbTables::TBL_EXPENSE_ITEM],
                $this->getTable() . '.expense_item_id = item.id',
                [],
                Select::JOIN_LEFT
            );
            $select->join(
                ['expense' => DbTables::TBL_EXPENSES],
                'item.expense_id = expense.id',
                [],
                Select::JOIN_LEFT
            );
            $select->where([
                'item.is_startup' => 1,
                $this->getTable() . '.cost_center_type' => ExpenseCosts::TYPE_APARTMENT,
                $this->getTable() . '.cost_center_id' => $apartmentId,
            ]);
            $select->where->notEqualTo('expense.status', Ticket::STATUS_DECLINED);
        });
    }

    /**
     * Used to replace /ginosi/backoffice/library/DDD/Dao/Expense/ExpenseCost.php::getTotalBudgetSummOfRunning($id)
     *
     * @param int $apartmentId
     * @return \Zend\Db\ResultSet\ResultSet|array[]
     */
    public function getTotalBudgetSummOfRunning($apartmentId)
    {
        return $this->fetchOne(function (Select $select) use ($apartmentId) {
            $select->columns(['sum' => new Expression("ifnull(sum({$this->getTable()}.amount), 0)")]);
            $select->join(
                ['item' => DbTables::TBL_EXPENSE_ITEM],
                $this->getTable() . '.expense_item_id = item.id',
                [],
                Select::JOIN_LEFT
            );
            $select->join(
                ['expense' => DbTables::TBL_EXPENSES],
                'item.expense_id = expense.id',
                [],
                Select::JOIN_LEFT
            );
            $select->where->notEqualTo('item.is_startup', 1);
            $select->where([
                $this->getTable() . '.cost_center_type' => ExpenseCosts::TYPE_APARTMENT,
                $this->getTable() . '.cost_center_id' => $apartmentId,
            ]);
            $select->where->expression('YEAR(item.date_created) >= YEAR(NOW())', []);
            $select->where->notEqualTo('expense.status', Ticket::STATUS_DECLINED);
        });
    }

    /**
     * Used to replace /ginosi/backoffice/library/DDD/Dao/Expense/ExpenseCost.php::getMonthlyCost($apartment_id,$startDate,$endDate)
     *
     * @param int $apartmentId
     * @param string $startDate
     * @param string $endDate
     * @return \Zend\Db\ResultSet\ResultSet|array[]
     */
    public function getMonthlyCost($apartmentId, $startDate, $endDate)
    {
        $finalResult = [];
        $result = $this->fetchAll(function (Select $select) use ($apartmentId, $startDate, $endDate) {
            $select->columns([
                'amount' => new Expression("ifnull(sum({$this->getTable()}.amount), 0)"),
                'month_name' => new Expression('MONTHNAME(item.date_created)'),
            ]);
            $select->join(
                ['item' => DbTables::TBL_EXPENSE_ITEM],
                $this->getTable() . '.expense_item_id = item.id',
                ['date' => 'date_created'],
                Select::JOIN_LEFT
            );
            $select->join(
                ['expense' => DbTables::TBL_EXPENSES],
                'item.expense_id = expense.id',
                [],
                Select::JOIN_LEFT
            );
            $select->where([
                $this->getTable() . '.cost_center_type' => ExpenseCosts::TYPE_APARTMENT,
                $this->getTable() . '.cost_center_id' => $apartmentId,
            ]);
            $select->where
                ->greaterThanOrEqualTo('item.date_created', $startDate)
                ->lessThanOrEqualTo('item.date_created', $endDate);
            $select->group([new Expression('MONTH(item.date_created)'), new Expression('YEAR(item.date_created)')]);
            $select->where->notEqualTo('expense.status', Ticket::STATUS_DECLINED);
        });

        foreach ($result as $value) {
            $month = date("M_Y", strtotime($value['date']));
            $finalResult[$month] = $value['amount'];
        }

        return $finalResult;
    }

    /**
     * @param int $apartmentId
     * @param int $offset
     * @param int $limit
     * @param int $sortCol
     * @param string $sortDir
     * @param string $like
     * @return array
     */
    public function getApartmentCostsForDatatable($apartmentId, $offset, $limit, $sortCol, $sortDir, $like = '')
    {
        $this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());

        $result = $this->fetchAll(function (Select $select) use ($apartmentId, $offset, $limit, $sortCol, $sortDir, $like) {
            $sortColumns = [
                'id',
                'category',
                'date',
                'amount'
            ];

            $select->columns([
                'id' => new Expression('concat(expense.id, "-", ' . $this->getTable() . '.id)'),
                'amount'
            ]);

            $select->join(
                ['item' => DbTables::TBL_EXPENSE_ITEM],
                $this->getTable() . '.expense_item_id = item.id',
                ['date' => 'date_created'],
                Select::JOIN_LEFT
            );

            $select->join(
                ['expense' => DbTables::TBL_EXPENSES],
                'expense.id = item.expense_id',
                ['purpose', 'expense_id' => 'id'],
                Select::JOIN_LEFT
            );

            $select->join(
                ['sub_category' => DbTables::TBL_EXPENSE_ITEM_SUB_CATEGORIES],
                'item.sub_category_id = sub_category.id',
                ['category' => 'name'],
                Select::JOIN_LEFT
            );

            $select->where
                ->equalTo($this->getTable() . '.cost_center_id', $apartmentId)
                ->equalTo($this->getTable() . '.cost_center_type',  ExpenseCosts::TYPE_APARTMENT)
                ->notEqualTo('expense.status', Ticket::STATUS_DECLINED);

            if (!empty($like)) {
                $select->where
                    ->nest()
                    ->like('expense.purpose', '%' . $like . '%')
                    ->or
                    ->like('sub_category.name', '%' . $like . '%')
                    ->unnest();
            }

            $select
                ->order($sortColumns[$sortCol] . ' ' . $sortDir)
                ->offset((int)$offset)
                ->limit((int)$limit);

            $select->quantifier(new Expression('SQL_CALC_FOUND_ROWS'));
        });

        $statement      = $this->adapter->query('SELECT FOUND_ROWS() as total');
        $resultCount    = $statement->execute();
        $currentRow     = $resultCount->current();
        $totalCount     = $currentRow['total'];

        return [
            'costs_data'   => $result,
            'total_count'   => $totalCount
        ];
    }
}
