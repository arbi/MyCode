<?php

namespace DDD\Dao\Finance\Expense;

use Library\Finance\Base\Account;
use Library\Finance\Process\Expense\Helper;
use Library\Finance\Process\Expense\Ticket;
use Zend\Db\Sql\Expression;
use Zend\Db\Sql\Select;
use Library\DbManager\TableGatewayManager;
use Library\Constants\DbTables;
use Zend\Db\Sql\Where;
use Zend\ServiceManager\ServiceLocatorInterface;

class Expenses extends TableGatewayManager
{
    protected $table = DbTables::TBL_EXPENSES;

    /**
     * @param ServiceLocatorInterface $sm
     * @param string $domain
     */
    public function __construct($sm, $domain = 'DDD\Domain\Finance\Expense\Expenses')
    {
        parent::__construct($sm, $domain);
    }

    /**
     * @param array|array[] $where
     * @param array $params
     * @return \Zend\Db\ResultSet\ResultSet|\DDD\Domain\Finance\Expense\Expenses[]|array[]
     */
    public function getExpenses($where, $params)
    {
        $driver = $this->getAdapter()->getDriver();
        $sortColumns = [
            'id',
            'date_created',
            'expected_completion_date_start',
            'expected_completion_date_end',
            'status',
            'finance_status',
            'ticket_balance',
            'expense_limit',
            DbTables::TBL_CURRENCY . '.code',
        ];

        $where = 'where ' . implode(' and ', $where);

        $limit = '';
        if (!is_null($params['iDisplayLength']) && !is_null($params['iDisplayStart'])) {
            $limit = "limit {$params['iDisplayLength']} offset {$params['iDisplayStart']}";
        }

        $order = '';
        if (!is_null($params['iSortCol_0']) && !is_null($params['sSortDir_0']) && array_key_exists($params['iSortCol_0'], $sortColumns)) {
            $order = $sortColumns[$params['iSortCol_0']] . ' ' . $params['sSortDir_0'];
        }

        $stmt = $driver->createStatement("
            select
                ga_expense.id,
                ga_expense.purpose,
                ga_expense.date_created,
                ga_expense.ticket_balance,
                ga_expense.deposit_balance,
                ga_expense.currency_id,
                ga_expense.status,
                ga_expense.finance_status,
                ga_expense.expected_completion_date_start,
                ga_expense.expected_completion_date_end,
                ga_expense.`limit` as expense_limit,
                ifnull(
                    ifnull(ga_booking_partners.partner_name, ga_suppliers.name),
                    concat(ga_bo_users.firstname, ' ', ga_bo_users.lastname)
                ) as account,
                ga_currency.code as currency_code,
                ga_transaction_accounts.type as account_type
            from ga_expense
                left join ga_currency on ga_expense.currency_id = ga_currency.id
                left join ga_expense_item on ga_expense.id = ga_expense_item.expense_id
                left join ga_transaction_accounts on ga_expense_item.account_id = ga_transaction_accounts.id
                left join ga_bo_users on ga_bo_users.id = ga_transaction_accounts.holder_id and ga_transaction_accounts.type = 5
                left join ga_suppliers on ga_suppliers.id = ga_transaction_accounts.holder_id and ga_transaction_accounts.type = 4
                left join ga_booking_partners on ga_booking_partners.gid = ga_transaction_accounts.holder_id and ga_transaction_accounts.type = 3
                left join ga_expense_item_sub_category on ga_expense_item.sub_category_id = ga_expense_item_sub_category.id
                left join ga_expense_transaction on ga_expense.id = ga_expense_transaction.expense_id
                left join ga_expense_cost on ga_expense_cost.expense_item_id = ga_expense_item.id
            {$where}
            group by ga_expense.id
            order by {$order}
            {$limit};
        ");

        return $stmt->execute();
    }

    /**
     * @param array|array[] $where
     * @return int
     */
    public function getCount($where = null)
    {
        $this->setEntity(new \ArrayObject());

        $where = 'where ' . implode(' and ', $where);

        $sql = "
            select count(*) as count from (
                select distinct ga_expense.id
                from ga_expense
                    left join ga_currency on ga_expense.currency_id = ga_currency.id
                    left join ga_expense_item on ga_expense.id = ga_expense_item.expense_id
                    left join ga_expense_item_sub_category on ga_expense_item.sub_category_id = ga_expense_item_sub_category.id
                    left join ga_expense_transaction on ga_expense.id = ga_expense_transaction.expense_id
                    left join ga_expense_cost on ga_expense_cost.expense_item_id = ga_expense_item.id
                {$where}
            ) as expenseticket;
        ";

        $stmt = $this->getAdapter()->getDriver()->createStatement($sql);
        $result = $stmt->execute();

        return $result->current()['count'];
    }

    /**
     * @param int $ticketId
     * @return array|bool
     */
    public function getTicketData($ticketId)
    {
        return $this->fetchOne(function(Select $select) use ($ticketId) {
            $select->columns([
                'id',
                'creator_id',
                'currency_id',
                'date_created',
                'manager_id',
                'limit',
                'expected_completion_date_start',
                'expected_completion_date_end',
                'deposit_balance',
                'ticket_balance',
                'item_balance',
                'transaction_balance',
                'title',
                'purpose',
                'status',
                'finance_status',
                'budget_id',
            ]);
            $select->join(
                ['creator' => DbTables::TBL_BACKOFFICE_USERS],
                $this->getTable() . '.creator_id = creator.id',
                ['ticket_creator' => new Expression('concat(creator.firstname, " ", creator.lastname)')],
                Select::JOIN_LEFT
            );
            $select->join(
                ['currency' => DbTables::TBL_CURRENCY],
                $this->getTable() . '.currency_id = currency.id',
                ['currency' => 'code'],
                Select::JOIN_LEFT
            );
            $select->where([$this->getTable() . '.id' => $ticketId]);
        });
    }

    /**
     * @param int $ticketId
     * @return \DDD\Domain\Finance\Expense\Expenses|array|bool
     */
    public function getTicketSimpleData($ticketId)
    {
        return $this->fetchOne(function (Select $select) use ($ticketId) {
            $select->where(['id' => $ticketId]);
        });
    }

    /**
     * @param $loggedInUserID
     * @return mixed
     */
    public function getMyActualPOCount($loggedInUserID)
    {
        $result = $this->fetchOne(function (Select $select) use ($loggedInUserID) {
            $select->columns(['count' => new Expression('count(*)')]);
            $select->where
                    ->equalTo('manager_id', $loggedInUserID)
                    ->equalTo('status', Ticket::STATUS_GRANTED)
                    ->equalTo('finance_status', Ticket::FIN_STATUS_NEW);
        });

        return $result['count'];
    }

    /**
     * @return int
     */
    public function getReadyToBeSettledPOCount()
    {
        $result = $this->fetchOne(function (Select $select) {
            $select->columns(['count' => new Expression('count(*)')]);
            $select->where([
                'finance_status' => Ticket::FIN_STATUS_READY,
            ]);
        });

        return $result['count'];
    }

    /**
     * @param $loggedInUserID
     * @return \Zend\Db\ResultSet\ResultSet
     */
    public function getMyActualPO($loggedInUserID)
    {
        return $this->fetchAll(function (Select $select) use ($loggedInUserID) {
            $select->columns(['id', 'expected_completion_date_start','expected_completion_date_end', 'purpose', 'title']);
            $select->join(
                ['users' => DbTables::TBL_BACKOFFICE_USERS],
                $this->getTable() . '.manager_id = users.id',
                ['manager' => new Expression('concat(users.firstname, " ", users.lastname)')],
                Select::JOIN_LEFT
            );
            $select->join(
                ['currency' => DbTables::TBL_CURRENCY],
                $this->getTable() . '.currency_id = currency.id',
                [
                    'ticket_balance' => new Expression('concat(' . $this->getTable() . '.ticket_balance, " ", currency.code)'),
                    'ticket_limit' => new Expression('concat(' . $this->getTable() . '.limit, " ", currency.code)')
                ],
                Select::JOIN_LEFT
            );

            $select->where
                ->equalTo($this->getTable() . '.manager_id', $loggedInUserID)
                ->equalTo($this->getTable() . '.status', Ticket::STATUS_GRANTED)
                ->equalTo($this->getTable() . '.finance_status', Ticket::FIN_STATUS_NEW);
        });
    }

    /**
     * @return int
     */
    public function getReadyToBeSettledPO()
    {
        return $this->fetchAll(function (Select $select) {
            $select->columns(['id', 'date_created', 'purpose']);
            $select->join(
                ['users' => DbTables::TBL_BACKOFFICE_USERS],
                $this->getTable() . '.manager_id = users.id',
                ['manager' => new Expression('concat(users.firstname, " ", users.lastname)')],
                Select::JOIN_LEFT
            );
            $select->join(
                ['currency' => DbTables::TBL_CURRENCY],
                $this->getTable() . '.currency_id = currency.id',
                ['ticket_balance' => new Expression('concat(' . $this->getTable() . '.ticket_balance, " ", currency.code)')],
                Select::JOIN_LEFT
            );
            $select->where([
                $this->getTable() . '.finance_status' => Ticket::FIN_STATUS_READY,
            ]);
        });
    }

    /**
     * @return int
     */
    public function getNotApprovedExpenseCount()
    {
        $result = $this->fetchOne(function (Select $select) {
            $select->columns(['count' => new Expression('count(*)')]);
            $select->where([
                'status' => Ticket::STATUS_PENDING,
            ]);
        });

        return $result['count'];
    }

    /**
     * @return \Zend\Db\ResultSet\ResultSet|array[]
     */
    public function getNotApprovedExpenses()
    {
        return $this->fetchAll(function (Select $select) {
            $select->columns([
                'id',
                'date_created',
                'purpose',
                'limit',
                'expected_completion_date_start',
                'expected_completion_date_end'
            ]);
            $select->join(
                ['currency' => DbTables::TBL_CURRENCY],
                $this->getTable() . '.currency_id = currency.id',
                ['symbol'],
                Select::JOIN_LEFT
            );
            $select->join(
                ['creator' => DbTables::TBL_BACKOFFICE_USERS],
                $this->getTable() . '.creator_id = creator.id',
                ['creator' => new Expression('concat(creator.firstname, " ", creator.lastname)')],
                Select::JOIN_LEFT
            );
            $select->join(
                ['manager' => DbTables::TBL_BACKOFFICE_USERS],
                $this->getTable() . '.manager_id = manager.id',
                ['manager' => new Expression('concat(manager.firstname, " ", manager.lastname)')],
                Select::JOIN_LEFT
            );
            $select->where([
                $this->getTable() . '.status' => Ticket::STATUS_PENDING,
            ]);
        });
    }

    /**
     * @param int $expenseId
     * @return array|bool
     */
    public function getTicketBalance($expenseId)
    {
        return $this->fetchOne(function (Select $select) use ($expenseId) {
            $select->columns(['id', 'ticket_balance', 'finance_status']);
            $select->join(
                ['currency' => DbTables::TBL_CURRENCY],
                $this->getTable() . '.currency_id = currency.id',
                ['currency' => 'code'],
                Select::JOIN_LEFT
            );
            $select->where([$this->getTable(). '.id' => $expenseId]);
        });
    }

    /**
     * @param int $managerId
     * @return \Zend\Db\ResultSet\ResultSet|array[]
     */
    public function getManagerPOs($managerId)
    {
        return $this->fetchAll(function (Select $select) use ($managerId) {
            $select->columns(['id', 'title', 'expected_completion_date_start','expected_completion_date_end', 'limit', 'item_balance']);
            $select->join(
                ['currency' => DbTables::TBL_CURRENCY],
                $this->table . '.currency_id = currency.id',
                ['code' => 'code'],
                Select::JOIN_INNER
            );
            $select->where
                ->equalTo($this->getTable() . '.manager_id', $managerId)
                ->equalTo($this->getTable() . '.status', Helper::STATUS_GRANTED)
                ->notIn($this->getTable() . '.finance_status', [Helper::FIN_STATUS_SETTLED, Helper::FIN_STATUS_READY]);
        });
    }

    /**
     * @param $ticketId
     * @return array|\ArrayObject|null
     */
    public function getBasicInfoForOrderManagement($ticketId)
    {
        $prototype = $this->getEntity();
        $this->setEntity(new \ArrayObject());
        $result =  $this->fetchOne(function (Select $select) use ($ticketId) {
            $select->columns(['id','status','limit', 'item_balance', 'currency_id']);
            $select->where->equalTo('id',$ticketId);
        });
        $this->setEntity($prototype);
        return $result;
    }

    /**
     * @param $ticketId
     * @return array|\ArrayObject|null
     */
    public function checkForCloseReview($ticketId)
    {
        $this->setEntity(new \ArrayObject());
        $result =  $this->fetchOne(function (Select $select) use ($ticketId) {
            $select->columns(['id', 'manager_id']);

            $select->where
                        ->equalTo('id',$ticketId)
                        ->equalTo('finance_status', Ticket::FIN_STATUS_NEW);
        });
        return $result;
    }

    /**
     * @param $ticketId
     * @return array|\ArrayObject|null
     */
    public function checkForSettle($ticketId)
    {
        $this->setEntity(new \ArrayObject());
        $result =  $this->fetchOne(function (Select $select) use ($ticketId) {
            $select->columns([
                'id',
                'currency_id',
                'budget_id',
                'remaining' => new Expression('`limit` - `item_balance`'),
            ]);

            $select->where
                        ->equalTo('id',$ticketId)
                        ->equalTo('finance_status', Ticket::FIN_STATUS_READY);
        });
        return $result;
    }

    /**
     * @param int $budgetId
     * @return \Zend\Db\ResultSet\ResultSet
     */
    public function getPOsByBudgetId($budgetId)
    {
        $entity = $this->getEntity();
        $this->setEntity(new \ArrayObject());
        $result = $this->fetchAll(function(Select $select) use ($budgetId) {
            $select->columns(['id','title','limit']);
            $select->join(
                ['currency' => DbTables::TBL_CURRENCY],
                $this->getTable() . '.currency_id = currency.id',
                ['currency' => 'code'],
                Select::JOIN_LEFT
            );
            $select->where->equalTo('budget_id', $budgetId);
        });
        $this->setEntity($entity);
        return $result;
    }

    /**
     * @param $id
     * @return array|\ArrayObject|null
     */
    public function getDataForRecalculation($id)
    {
        $entity = $this->getEntity();
        $this->setEntity(new \ArrayObject());
        $result = $this->fetchOne(function(Select $select) use ($id){
            $select->columns(['currency_id', 'item_balance', 'ticket_balance']);
            $select->where->equalTo('id', $id);
        });
        $this->setEntity($entity);
        return $result;
    }
}
