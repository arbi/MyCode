<?php

namespace DDD\Dao\Finance\Expense;

use Library\DbManager\TableGatewayManager;
use Library\Constants\DbTables;
use Zend\Db\Sql\Select;
use Zend\ServiceManager\ServiceLocatorInterface;

class ExpenseItemCategories extends TableGatewayManager
{
    /**
     * @var string
     */
    protected $table = DbTables::TBL_EXPENSE_ITEM_CATEGORIES;

    /**
     * @param ServiceLocatorInterface $sm
     * @param string $domain
     */
    public function __construct($sm, $domain = 'DDD\Domain\Finance\Expense\ExpenseItemCategories')
    {
        parent::__construct($sm, $domain);
    }

    public function getCategoryList(
        $offset, $limit, $sortCol, $sortDir, $like, $all = '1'
    ) {
        if ($all === '1') {
            $whereAll = 'AND is_active = 1';
        } elseif ($all === '2') {
            $whereAll = 'AND is_active = 0';
        } else {
            $whereAll = ' ';
        }
        $columns = ['is_active', 'name', 'description', 'id'];

        $result = $this->fetchAll(
            function (Select $select) use (
                $offset, $limit, $sortCol, $sortDir,
                $like, $whereAll, $columns
            ) {
                $select->where("(name like '%".$like."%'
                    OR description like '%".$like."%')
                    $whereAll");

                $select
                    ->columns($columns)
                    ->order($columns[$sortCol].' '.$sortDir)
                    ->offset((int)$offset)
                    ->limit((int)$limit);
            }
        );

        return $result;
    }

    public function getCategoryCount($like, $all = '1')
    {
        if ($all === '1') {
            $whereAll = 'AND is_active = 1';
        } elseif ($all === '2') {
            $whereAll = 'AND is_active = 0';
        } else {
            $whereAll = ' ';
        }

        $columns = ['name'];

        $result = $this->fetchAll(
            function (Select $select) use ($like, $whereAll, $columns) {
                $select->where("(name like '".$like."%'
                    OR description like '".$like."%')
                    $whereAll");

                $select->columns($columns);
            }
        );
        return $result->count();
    }

    public function getCategoryById($id)
    {
        $result = $this->fetchOne(function (Select $select) use ($id) {
            $select->where
               ->equalTo('id', $id);
        });

        return $result;
    }

    public function getForSelect()
    {
        return $this->fetchAll(function (Select $select) {
            $select
                ->columns(['id', 'name'])
                ->order('name');
        });
    }

    public function getCategory($id)
    {
        return $this->fetchOne(function (Select $select) use($id) {
            $select
                ->columns(['name'])
                ->where(['id' => $id]);
        });
    }

    public function checkTitle($name, $id){
        $result = $this->fetchOne(
            function (Select $select) use($name, $id) {
                $select->where(['name' => $name]);
                if($id > 0) {
                    $select->where('id <> '. (int)$id);
                }
            }
        );
        return !empty($result) ? $result : null;
    }

    public function getExpenseItemCategoriesDao()
    {
        $this->setEntity(new \ArrayObject());

        return $this->fetchAll(function(Select $select) {
            $select->columns(['id', 'name']);
            $select->join(
                ['sub_category' => DbTables::TBL_EXPENSE_ITEM_SUB_CATEGORIES],
                $this->getTable() . '.id = sub_category.category_id',
                [
                    'sub_id' => 'id',
                    'sub_name' => 'name',
                ],
                Select::JOIN_LEFT
            );
            $select->order('id');
        });
    }
}
