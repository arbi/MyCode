<?php

namespace DDD\Dao\Finance\Expense;

use DDD\Service\Finance\Expense\ExpenseItemCategories;
use Library\DbManager\TableGatewayManager;
use Library\Constants\DbTables;
use Library\Finance\Base\Account;
use Library\Finance\Process\Expense\Helper;
use Zend\Db\Sql\Expression;
use Zend\Db\Sql\Select;
use Zend\Db\Sql\Where;
use Zend\ServiceManager\ServiceLocatorInterface;

class ExpenseItem extends TableGatewayManager
{
    protected $table = DbTables::TBL_EXPENSE_ITEM;

    /**
     * @param ServiceLocatorInterface $sm
     * @param string $domain
     */
    public function __construct($sm, $domain = '\ArrayObject')
    {
        parent::__construct($sm, $domain);
    }

    /**
     * @param Where $where
     * @return \Zend\Db\ResultSet\ResultSet|array[]
     */
    public function getItems(Where $where)
    {
        return $this->fetchAll(function(Select $select) use ($where) {
            $select->columns([
                '*',
                'supplier_account_id' => new Expression('
                    ifnull(
                        ifnull(partner.gid, supplier.id),
                        people.id
                    )
                '),
                'account_name' => new Expression('
                    ifnull(
                        ifnull(partner.partner_name, supplier.name),
                        concat(people.firstname, " ", people.lastname)
                    )
                '),
            ]);
            $select->join(
                ['expense' => DbTables::TBL_EXPENSES],
                $this->getTable() . '.expense_id = expense.id',
                [
                    'po_currency_id' => 'currency_id',
                    'finance_status',
                    'po_manager_id' => 'manager_id'
                ],
                Select::JOIN_LEFT
            );
            $select->join(
                ['sub_category' => DbTables::TBL_EXPENSE_ITEM_SUB_CATEGORIES],
                $this->getTable() . '.sub_category_id = sub_category.id',
                [
                    'category_id' => 'category_id',
                    'sub_category_name' => 'name'
                ],
                Select::JOIN_LEFT
            );
            $select->join(
                ['category' => DbTables::TBL_EXPENSE_ITEM_CATEGORIES],
                'category.id = sub_category.category_id',
                ['category_name' => 'name'], Select::JOIN_LEFT
            );
            $select->join(
                ['users' => DbTables::TBL_BACKOFFICE_USERS],
                $this->getTable() . '.creator_id = users.id',
                ['creator_name' => new Expression('concat(users.firstname, " ", users.lastname)')],
                Select::JOIN_LEFT
            );
            $select->join(
                ['accounts' => DbTables::TBL_TRANSACTION_ACCOUNTS],
                $this->getTable() . '.account_id = accounts.id',
                ['account_type' => 'type'],
                Select::JOIN_LEFT
            );
            $select->join(
                ['people' => DbTables::TBL_BACKOFFICE_USERS],
                new Expression('people.id = accounts.holder_id and accounts.type = ' . Account::TYPE_PEOPLE),
                [],
                Select::JOIN_LEFT
            );
            $select->join(
                ['supplier' => DbTables::TBL_SUPPLIERS],
                new Expression('supplier.id = accounts.holder_id and accounts.type = ' . Account::TYPE_SUPPLIER),
                [],
                Select::JOIN_LEFT
            );
            $select->join(
                ['partner' => DbTables::TBL_BOOKING_PARTNERS],
                new Expression('partner.gid = accounts.holder_id and accounts.type = ' . Account::TYPE_PARTNER),
                [],
                Select::JOIN_LEFT
            );
            $select->join(
                ['cost' => DbTables::TBL_EXPENSE_COST],
                $this->getTable() . '.id = cost.expense_item_id',
                [],
                Select::JOIN_LEFT
            );
            $select->join(
                ['currency' => DbTables::TBL_CURRENCY],
                $this->getTable() . '.currency_id = currency.id',
                ['currency' => 'code'],
                Select::JOIN_LEFT
            );
            $select->where($where);
            $select->order(['date_created DESC', 'id DESC']);
            $select->group('id');
        })->buffer();
    }

    /**
     * @param $iDisplayStart
     * @param $iDisplayLength
     * @param $where
     * @param $sortCol
     * @param $sortDir
     * @return array
     */
    public function getItemsForDataTable( $iDisplayStart,
                                          $iDisplayLength,
                                          $where,
                                          $sortCol,
                                          $sortDir)
    {
        $result = $this->fetchAll(function(Select $select) use ($iDisplayStart, $where, $iDisplayLength, $sortCol, $sortDir) {
            $sortColumns = [
                'date_created',
                'period_from',
                'account_name',
                'account_reference',
                'period_to',
                'category_name',
                'sub_category_name',
                'amount',
                'currency',
                'comment',
                'type'
            ];
            $select->columns([
                '*',
                'supplier_account_id' => new Expression('
                    ifnull(
                        ifnull(partner.gid, supplier.id),
                        people.id
                    )
                '),
                'account_name' => new Expression('
                    ifnull(
                        ifnull(partner.partner_name, supplier.name),
                        concat(people.firstname, " ", people.lastname)
                    )
                '),
            ]);
            $select->join(
                ['sub_category' => DbTables::TBL_EXPENSE_ITEM_SUB_CATEGORIES],
                $this->getTable() . '.sub_category_id = sub_category.id',
                [
                    'category_id' => 'category_id',
                    'sub_category_name' => 'name'
                ],
                Select::JOIN_LEFT
            );
            $select->join(
                ['category' => DbTables::TBL_EXPENSE_ITEM_CATEGORIES],
                'category.id = sub_category.category_id',
                ['category_name' => 'name'], Select::JOIN_LEFT
            );
            $select->join(
                ['users' => DbTables::TBL_BACKOFFICE_USERS],
                $this->getTable() . '.creator_id = users.id',
                ['creator_name' => new Expression('concat(users.firstname, " ", users.lastname)')],
                Select::JOIN_LEFT
            );
            $select->join(
                ['users2' => DbTables::TBL_BACKOFFICE_USERS],
                $this->getTable() . '.manager_id = users2.id',
                ['manager_name' => new Expression('concat(users.firstname, " ", users.lastname)')],
                Select::JOIN_LEFT
            );
            $select->join(
                ['accounts' => DbTables::TBL_TRANSACTION_ACCOUNTS],
                $this->getTable() . '.account_id = accounts.id',
                ['account_type' => 'type'],
                Select::JOIN_LEFT
            );
            $select->join(
                ['people' => DbTables::TBL_BACKOFFICE_USERS],
                new Expression('people.id = accounts.holder_id and accounts.type = ' . Account::TYPE_PEOPLE),
                [],
                Select::JOIN_LEFT
            );
            $select->join(
                ['supplier' => DbTables::TBL_SUPPLIERS],
                new Expression('supplier.id = accounts.holder_id and accounts.type = ' . Account::TYPE_SUPPLIER),
                [],
                Select::JOIN_LEFT
            );
            $select->join(
                ['partner' => DbTables::TBL_BOOKING_PARTNERS],
                new Expression('partner.gid = accounts.holder_id and accounts.type = ' . Account::TYPE_PARTNER),
                [],
                Select::JOIN_LEFT
            );
            $select->join(
                ['cost' => DbTables::TBL_EXPENSE_COST],
                $this->getTable() . '.id = cost.expense_item_id',
                [],
                Select::JOIN_LEFT
            );
            $select->join(
                ['currency' => DbTables::TBL_CURRENCY],
                $this->getTable() . '.currency_id = currency.id',
                ['currency' => 'code'],
                Select::JOIN_LEFT
            );
            $select->where($where);

            $select->quantifier(new Expression('SQL_CALC_FOUND_ROWS'));
            if (   $iDisplayLength !== null
                && $iDisplayStart  !== null
            ) {
                $select->limit((int)$iDisplayLength);
                $select->offset((int)$iDisplayStart);
            }
            $select->order($sortColumns[$sortCol] . ' ' . $sortDir);
            $select->group('id');
        });
        $statement = $this->adapter->query('SELECT FOUND_ROWS() as total');
        $result2   = $statement->execute();
        $row       = $result2->current();
        $total     = $row['total'];

        return [
            'result' => $result,
            'total'  => $total
        ];
    }

    /**
     * @param $where
     * @return \Zend\Db\ResultSet\ResultSet
     */
    public function getItemsCountToDownload($where)
    {
        $result = $this->fetchAll(function(Select $select) use ($where) {
            $select
                ->columns(['id']);
            $select->join(
                ['sub_category' => DbTables::TBL_EXPENSE_ITEM_SUB_CATEGORIES],
                $this->getTable() . '.sub_category_id = sub_category.id',
                [],
                Select::JOIN_LEFT
            );
            $select->join(
                ['category' => DbTables::TBL_EXPENSE_ITEM_CATEGORIES],
                'category.id = sub_category.category_id',
                [],
                Select::JOIN_LEFT
            );
            $select->join(
                ['users' => DbTables::TBL_BACKOFFICE_USERS],
                $this->getTable() . '.creator_id = users.id',
                [],
                Select::JOIN_LEFT
            );
            $select->join(
                ['users2' => DbTables::TBL_BACKOFFICE_USERS],
                $this->getTable() . '.manager_id = users2.id',
                [],
                Select::JOIN_LEFT
            );
            $select->join(
                ['accounts' => DbTables::TBL_TRANSACTION_ACCOUNTS],
                $this->getTable() . '.account_id = accounts.id',
                [],
                Select::JOIN_LEFT
            );
            $select->join(
                ['people' => DbTables::TBL_BACKOFFICE_USERS],
                new Expression('people.id = accounts.holder_id and accounts.type = ' . Account::TYPE_PEOPLE),
                [],
                Select::JOIN_LEFT
            );
            $select->join(
                ['supplier' => DbTables::TBL_SUPPLIERS],
                new Expression('supplier.id = accounts.holder_id and accounts.type = ' . Account::TYPE_SUPPLIER),
                [],
                Select::JOIN_LEFT
            );
            $select->join(
                ['partner' => DbTables::TBL_BOOKING_PARTNERS],
                new Expression('partner.gid = accounts.holder_id and accounts.type = ' . Account::TYPE_PARTNER),
                [],
                Select::JOIN_LEFT
            );
            $select->join(
                ['cost' => DbTables::TBL_EXPENSE_COST],
                $this->getTable() . '.id = cost.expense_item_id',
                [],
                Select::JOIN_LEFT
            );
            $select->join(
                ['currency' => DbTables::TBL_CURRENCY],
                $this->getTable() . '.currency_id = currency.id',
                [],
                Select::JOIN_LEFT
            );
            $select->where($where);

            $select->group($this->getTable() . '.id');
        });
        return $result;
    }

    /**
     * @param $where
     * @return \Zend\Db\ResultSet\ResultSet
     */
    public function getItemsCsvForDownload($where)
    {
        $result = $this->fetchAll(function(Select $select) use ($where) {
            $select->columns([
                '*',
                'supplier_account_id' => new Expression('
                    ifnull(
                        ifnull(partner.gid, supplier.id),
                        people.id
                    )
                '),
                'account_name' => new Expression('
                    ifnull(
                        ifnull(partner.partner_name, supplier.name),
                        concat(people.firstname, " ", people.lastname)
                    )
                '),
            ]);
            $select->join(
                ['sub_category' => DbTables::TBL_EXPENSE_ITEM_SUB_CATEGORIES],
                $this->getTable() . '.sub_category_id = sub_category.id',
                [
                    'category_id' => 'category_id',
                    'sub_category_name' => 'name'
                ],
                Select::JOIN_LEFT
            );
            $select->join(
                ['category' => DbTables::TBL_EXPENSE_ITEM_CATEGORIES],
                'category.id = sub_category.category_id',
                ['category_name' => 'name'], Select::JOIN_LEFT
            );
            $select->join(
                ['users' => DbTables::TBL_BACKOFFICE_USERS],
                $this->getTable() . '.creator_id = users.id',
                ['creator_name' => new Expression('concat(users.firstname, " ", users.lastname)')],
                Select::JOIN_LEFT
            );
            $select->join(
                ['users2' => DbTables::TBL_BACKOFFICE_USERS],
                $this->getTable() . '.manager_id = users2.id',
                ['manager_name' => new Expression('concat(users.firstname, " ", users.lastname)')],
                Select::JOIN_LEFT
            );
            $select->join(
                ['accounts' => DbTables::TBL_TRANSACTION_ACCOUNTS],
                $this->getTable() . '.account_id = accounts.id',
                ['account_type' => 'type'],
                Select::JOIN_LEFT
            );
            $select->join(
                ['people' => DbTables::TBL_BACKOFFICE_USERS],
                new Expression('people.id = accounts.holder_id and accounts.type = ' . Account::TYPE_PEOPLE),
                [],
                Select::JOIN_LEFT
            );
            $select->join(
                ['supplier' => DbTables::TBL_SUPPLIERS],
                new Expression('supplier.id = accounts.holder_id and accounts.type = ' . Account::TYPE_SUPPLIER),
                [],
                Select::JOIN_LEFT
            );
            $select->join(
                ['partner' => DbTables::TBL_BOOKING_PARTNERS],
                new Expression('partner.gid = accounts.holder_id and accounts.type = ' . Account::TYPE_PARTNER),
                [],
                Select::JOIN_LEFT
            );
            $select->join(
                ['cost' => DbTables::TBL_EXPENSE_COST],
                $this->getTable() . '.id = cost.expense_item_id',
                [],
                Select::JOIN_LEFT
            );
            $select->join(
                ['currency' => DbTables::TBL_CURRENCY],
                $this->getTable() . '.currency_id = currency.id',
                ['currency' => 'code'],
                Select::JOIN_LEFT
            );
            $select->where($where);

            $select->group('id');
        });

        return  $result;

    }


    /**
     * @param @param array $where
     * @return \Zend\Db\ResultSet\ResultSet|\DDD\Domain\Finance\Expense\Expenses[]|array[]
     */
    public function getExpensesToDownload($where)
    {
        $driver = $this->getAdapter()->getDriver();
        $where = 'where ' . implode(' and ', $where);
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
                ga_currency.code as currency_code
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
        ");

        return $stmt->execute();
    }

    /**
     * @param $where
     * @return \Zend\Db\Adapter\Driver\ResultInterface
     */
    public function getExpensesCountToDownload($where)
    {
        $driver = $this->getAdapter()->getDriver();
        $where = 'where ' . implode(' and ', $where);
        $stmt = $driver->createStatement("
            select
                ga_expense.id
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
        ");

        return $stmt->execute();
    }

    /**
     * @param int $itemId
     * @return array|\ArrayObject|null
     */
    public function getItemDetails($itemId)
    {
        return $this->fetchOne(function (Select $select) use ($itemId) {
            $select->columns([
                'id',
                'expense_id',
                'creator_id',
                'manager_id',
                'amount',
                'currency_id',
                'sub_category_id',
                'is_refund',
                'is_deposit',
                'is_startup',
                'type',
                'status',
                'comment',
                'tmp_money_account_id',
                'tmp_transaction_date',
                'period' => new Expression('concat(period_from, " - ", period_to)'),
                'reference' => 'account_reference',
                'date_created' => new Expression("date({$this->getTable()}.date_created)"),
                'account_name' => new Expression('
                    ifnull(
                        ifnull(partner.partner_name, supplier.name),
                        concat(people.firstname, " ", people.lastname)
                    )
                '),
                'account_id' => new Expression('
                    ifnull(
                        ifnull(partner.gid, supplier.id), people.id
                    )
                '),
            ]);
            $select->join(
                ['expense' => DbTables::TBL_EXPENSES],
                $this->getTable() . '.expense_id = expense.id',
                [
                    'ticket_balance',
                    'deposit_balance',
                    'item_balance',
                    'expense_currency_id' => 'currency_id',
                    'finance_status',
                    'po_manager_id' => 'manager_id'
                ],
                Select::JOIN_LEFT
            );
            $select->join(
                ['attachment' => DbTables::TBL_EXPENSE_ITEM_ATTACHMENTS],
                $this->getTable() . '.id = attachment.item_id',
                ['attachment_id' => 'id'],
                Select::JOIN_LEFT
            );
            $select->join(
                ['accounts' => DbTables::TBL_TRANSACTION_ACCOUNTS],
                $this->getTable() . '.account_id = accounts.id',
                [
                    'unique_id' => 'id',
                    'account_type' => 'type',
                ],
                Select::JOIN_LEFT
            );
            $select->join(
                ['people' => DbTables::TBL_BACKOFFICE_USERS],
                new Expression('people.id = accounts.holder_id and accounts.type = ' . Account::TYPE_PEOPLE),
                [],
                Select::JOIN_LEFT
            );
            $select->join(
                ['users_for_manager' => DbTables::TBL_BACKOFFICE_USERS],
                new Expression($this->getTable() . '.manager_id = users_for_manager.id'),
                ['manager_firstname' => 'firstname', 'manager_lastname' => 'lastname'],
                Select::JOIN_LEFT
            );
            $select->join(
                ['supplier' => DbTables::TBL_SUPPLIERS],
                new Expression('supplier.id = accounts.holder_id and accounts.type = ' . Account::TYPE_SUPPLIER),
                [],
                Select::JOIN_LEFT
            );
            $select->join(
                ['partner' => DbTables::TBL_BOOKING_PARTNERS],
                new Expression('partner.gid = accounts.holder_id and accounts.type = ' . Account::TYPE_PARTNER),
                [],
                Select::JOIN_LEFT
            );
            $select->join(
                ['users' => DbTables::TBL_BACKOFFICE_USERS],
                $this->getTable() . '.creator_id = users.id',
                ['creator_name' => new Expression('CONCAT(users.firstname, " ", users.lastname)')],
                Select::JOIN_INNER
            );
            $select->join(
                ['money_account' => DbTables::TBL_MONEY_ACCOUNT],
                $this->getTable() . '.tmp_money_account_id = money_account.id',
                ['money_account_name' => 'name'],
                Select::JOIN_LEFT
            );
            $select->join(
                ['currency' => DbTables::TBL_CURRENCY],
                'money_account.currency_id = currency.id',
                ['money_account_currency' => 'code'],
                Select::JOIN_LEFT
            );
            $select->join(
                ['bank' => DbTables::TBL_BANK],
                'money_account.bank_id = bank.id',
                ['money_account_bank' => 'name'],
                Select::JOIN_LEFT
            );
            $select->where([$this->getTable() . '.id' => $itemId]);
        });
    }

    /**
     * @param int $userId
     * @return int
     */
    public function getNotApprovedItemsCount($userId)
    {
        $result = $this->fetchOne(function (Select $select) use ($userId) {
            $select->columns(['count' => new Expression('count(*)')]);
            $select->where
                ->equalTo($this->getTable() . '.creator_id', $userId)
                ->in($this->getTable() . '.status', [Helper::ITEM_STATUS_PENDING, Helper::ITEM_STATUS_REJECTED]);
        });

        return $result['count'];
    }

    /**
     * @param int $userId
     * @return \Zend\Db\ResultSet\ResultSet|array[]
     */
    public function getNotApprovedItems($userId)
    {
        return $this->fetchAll(function (Select $select) use ($userId) {
            $select->columns(['id', 'date_created', 'comment', 'type', 'status']);
            $select->join(
                ['currency' => DbTables::TBL_CURRENCY],
                $this->getTable() . '.currency_id = currency.id',
                ['amount' => new Expression('concat(' . $this->getTable() . '.amount, " ", currency.code)')],
                Select::JOIN_LEFT
            );
            $select->where
                ->equalTo($this->getTable() . '.creator_id', $userId)
                ->in($this->getTable() . '.status', [Helper::ITEM_STATUS_PENDING, Helper::ITEM_STATUS_REJECTED]);
        });
    }

    /**
     * @param int $managerId
     * @return \Zend\Db\ResultSet\ResultSet|array[]
     */
    public function getManagersPendingItems($managerId)
    {
        return $this->fetchAll(function (Select $select) use ($managerId) {
            $select->columns(['id', 'date_created', 'comment', 'type']);
            $select->join(
                ['creator' => DbTables::TBL_BACKOFFICE_USERS],
                $this->getTable() . '.creator_id = creator.id',
                ['creator' => new Expression('concat(creator.firstname, " ", creator.lastname)')],
                Select::JOIN_LEFT
            );
            $select->join(
                ['currency' => DbTables::TBL_CURRENCY],
                $this->getTable() . '.currency_id = currency.id',
                ['amount' => new Expression('concat(' . $this->getTable() . '.amount, " ", currency.code)')],
                Select::JOIN_LEFT
            );
            $select->where([
                $this->getTable() . '.manager_id' => $managerId,
                $this->getTable() . '.status' => Helper::ITEM_STATUS_PENDING,
            ]);
        });
    }

    /**
     * @param int $managerId
     * @return \Zend\Db\ResultSet\ResultSet|array[]
     */
    public function getManagersPendingItemsCount($managerId)
    {
        $result = $this->fetchOne(function (Select $select) use ($managerId) {
            $select->columns(['count' => new Expression('count(*)')]);
            $select->where([
                'manager_id' => $managerId,
                'status' => Helper::ITEM_STATUS_PENDING,
            ]);
        });

        return $result['count'];
    }

    /**
     * @return \Zend\Db\ResultSet\ResultSet|array[]
     */
    public function getItemsAwaitingTransfer()
    {
        return $this->fetchAll(function (Select $select) {
            $select->columns([
                'id',
                'comment',
                'date_created' => new Expression("date({$this->getTable()}.date_created)"),
                'account_name' => new Expression('
                    ifnull(
                        ifnull(partner.partner_name, supplier.name),
                        concat(people.firstname, " ", people.lastname)
                    )
                '),
            ]);
            $select->join(
                ['currency' => DbTables::TBL_CURRENCY],
                $this->getTable() . '.currency_id = currency.id',
                ['amount' => new Expression('concat(' . $this->getTable() . '.amount, " ", currency.code)')],
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
            $select->join(
                ['accounts' => DbTables::TBL_TRANSACTION_ACCOUNTS],
                $this->getTable() . '.account_id = accounts.id',
                ['account_type' => 'type'],
                Select::JOIN_LEFT
            );
            $select->join(
                ['people' => DbTables::TBL_BACKOFFICE_USERS],
                new Expression('people.id = accounts.holder_id and accounts.type = ' . Account::TYPE_PEOPLE),
                [],
                Select::JOIN_LEFT
            );
            $select->join(
                ['supplier' => DbTables::TBL_SUPPLIERS],
                new Expression('supplier.id = accounts.holder_id and accounts.type = ' . Account::TYPE_SUPPLIER),
                [],
                Select::JOIN_LEFT
            );
            $select->join(
                ['partner' => DbTables::TBL_BOOKING_PARTNERS],
                new Expression('partner.gid = accounts.holder_id and accounts.type = ' . Account::TYPE_PARTNER),
                [],
                Select::JOIN_LEFT
            );
            $select->where([
                'status' => Helper::ITEM_STATUS_APPROVED,
                'expense_id' => null,
            ]);
        });
    }

    /**
     * @return \Zend\Db\ResultSet\ResultSet|array[]
     */
    public function getItemsAwaitingTransferCount()
    {
        $result = $this->fetchOne(function (Select $select) {
            $select->columns(['count' => new Expression('count(*)')]);
            $select->where([
                'status' => Helper::ITEM_STATUS_APPROVED,
                'expense_id' => null,
            ]);
        });

        return $result['count'];
    }

    /**
     * @return int
     */
    public function getUnpaidInvoicesCount()
    {
        $result = $this->fetchOne(function (Select $select) {
            $select->columns(['count' => new Expression('count(*)')]);
            $select->where([
                'is_paid' => 0,
                'transaction_id' => null,
                'type'    => Helper::TYPE_PAY_AN_INVOICE,
                'status' => Helper::ITEM_STATUS_APPROVED
            ]);
        });

        return $result['count'];
    }


    /**
     * @return \Zend\Db\ResultSet\ResultSet|array[]
     */
    public function getUnpaidInvoices()
    {
        return $this->fetchAll(function (Select $select) {
            $select->columns(['id', 'date_created', 'comment', 'type', 'status', 'expense_id']);
            $select->join(
                ['currency' => DbTables::TBL_CURRENCY],
                $this->getTable() . '.currency_id = currency.id',
                ['amount' => new Expression('concat(' . $this->getTable() . '.amount, " ", currency.code)')],
                Select::JOIN_LEFT
            );
            $select->where([
                'is_paid' => 0,
                'transaction_id' => null,
                'type' => Helper::TYPE_PAY_AN_INVOICE,
                'status' => Helper::ITEM_STATUS_APPROVED
            ]);

        });
    }

    public function getRawItemData($id)
    {
        return $this->fetchOne(function (Select $select) use ($id){
            $select->join(
                ['cost' => DbTables::TBL_EXPENSE_COST],
                $this->getTable() . '.id = cost.expense_item_id',
                ['costs' => new Expression('GROUP_CONCAT(concat(cost.cost_center_id, "_", cost.cost_center_type))')],
                Select::JOIN_LEFT
            );

            $select->where->equalTo($this->getTable() . '.id', $id);
        });
    }

    /**
     * @param $itemId
     * @return array|\ArrayObject|null
     */
    public function getItemAmountAndCurrencyIdById($itemId)
    {
        return $this->fetchOne(function(Select $select) use ($itemId) {
            $select->columns(['amount', 'currency_id', 'expense_id', 'date_created']);
            $select->where->equalTo('id', $itemId);
        });
    }
}
