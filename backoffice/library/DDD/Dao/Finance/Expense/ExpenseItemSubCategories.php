<?php

namespace DDD\Dao\Finance\Expense;

use Library\DbManager\TableGatewayManager;
use Library\Constants\DbTables;
use Zend\Db\Sql\Select;
use Zend\ServiceManager\ServiceLocatorInterface;

class ExpenseItemSubCategories extends TableGatewayManager
{
    protected $table = DbTables::TBL_EXPENSE_ITEM_SUB_CATEGORIES;

    /**
     * @param ServiceLocatorInterface $sm
     * @param string $domain
     */
    public function __construct($sm, $domain = 'DDD\Domain\Finance\Expense\ExpenseItemSubCategories')
    {
        parent::__construct($sm, $domain);
    }

    /**
     * @return \Zend\Db\ResultSet\ResultSet|array
     */
    public function getActiveSubCategories()
    {
        $this->setEntity(new \ArrayObject());

        return $this->fetchAll(function(Select $select) {
            $select->columns(['id', 'name']);
            $select->join(
                ['category' => DbTables::TBL_EXPENSE_ITEM_CATEGORIES],
                $this->getTable() . '.category_id = category.id',
                ['category_id' => 'id', 'category_name' => 'name'],
                Select::JOIN_LEFT
            );
            $select->where([
                $this->getTable() . '.is_active' => 1,
                'category.is_active' => 1,
            ]);
            $select->order(['category_id ASC', 'name ASC']);
        });
    }

    public function getSubCategoriesByCategoryId($categoryId, $status = false)
    {
        $result = $this->fetchAll(function (Select $select) use ($categoryId, $status) {
            $select->where
                ->equalTo($this->getTable() . '.category_id', $categoryId);

            if ($status) {
                switch ($status) {
                    case 1: $isActive = 1; break;
                    case 2: $isActive = 0; break;
                }

                $select->where
                    ->equalTo($this->getTable() . '.is_active', $isActive);

            }

            $select->order($this->getTable() . '.name ASC');
        });

        return $result;
    }
}
