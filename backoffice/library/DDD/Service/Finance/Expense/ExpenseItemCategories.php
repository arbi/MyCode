<?php

namespace DDD\Service\Finance\Expense;

use DDD\Service\ServiceBase;

class ExpenseItemCategories extends ServiceBase
{
    const TYPE_CATEGORY = 1;
    const TYPE_SUB_CATEGORY = 2;

    const SUB_CATEGORY_OTA = 5; // Marketing -> OTA Commissions

    /**
     * @var \DDD\Dao\Finance\Expense\ExpenseItemCategories|\DDD\Dao\Finance\Expense\ExpenseItemSubCategories
     */
    protected $dao;

    /**
     * @param array $data
     * @param int|null $id
     *
     * @return bool
     */
    public function saveCategory($data, $id = null)
	{
		$dao = $this->getExpenseItemCategoriesDao();

		if (is_null($id)) {
            $dao->save($data);
		} else {
            $dao->save($data, ['id' => $id]);
		}

        return true;
	}

	/**
     * @param int $start
     * @param int $limit
     * @param string $sortCol
     * @param string $sortDir
     * @param string $search
     * @param string $all
     * @return \DDD\Domain\Finance\Expense\ExpenseItemCategories[]|\ArrayObject
     */
    public function getCategoryList($start, $limit, $sortCol, $sortDir, $search, $all)
    {
		$dao = $this->getExpenseItemCategoriesDao();

		return $dao->getCategoryList(
			$start,
			$limit,
			$sortCol,
			$sortDir,
			$search,
			$all
		);
    }

    public function getCategories()
    {
        /**
         * @var \DDD\Domain\Finance\Expense\ExpenseItemCategories[]|\ArrayObject $result
         */
        $dao = $this->getExpenseItemCategoriesDao();
        $result = $dao->fetchAll(['is_active' => 1]);
        $categoryList = [];

        if ($result->count()) {
            foreach ($result as $category) {
                $categoryList[$category->getId()] = $category->getName();
            }
        }

        return $categoryList;
    }

    public function getActiveSubCategoryList()
    {
        $dao = $this->getExpenseItemSubCategoriesDao();
        $result = $dao->getActiveSubCategories();
        $categoryList = [];

        if ($result->count()) {
            foreach ($result as $sub) {
                if (!isset($categoryList[$sub['category_id']])) {
                    $categoryList[$sub['category_id']] = [
                        'name' => $sub['category_name'],
                        'sub' => [],
                    ];
                }

                $categoryList[$sub['category_id']]['sub'][] = [
                    'id' => $sub['id'],
                    'name' => $sub['name'],
                ];
            }
        }

        return $categoryList;
    }

    public function getCategoryAndSubCategoryList()
    {
        $dao = $this->getExpenseItemCategoriesDao();
        $categories = $dao->getExpenseItemCategoriesDao();
        $categoryList = [];
        $categoryId = 0;
        $counter = 0;

        if ($categories->count()) {
            foreach ($categories as $category) {
                $type = is_null($category['sub_id']) ? self::TYPE_CATEGORY : self::TYPE_SUB_CATEGORY;
                $name = is_null($category['sub_id']) ? $category['name'] : $category['sub_name'];
                $id = is_null($category['sub_id']) ? $category['id'] : $category['sub_id'];

                if ($categoryId != $category['id'] && $type == self::TYPE_SUB_CATEGORY) {
                    array_push($categoryList, [
                        'id' => $category['id'],
                        'name' => $category['name'],
                        'type' => self::TYPE_CATEGORY,
                        'order' => ++$counter,
                    ]);
                }

                $categoryId = $category['id'];

                array_push($categoryList, [
                    'id' => $id,
                    'name' => $name,
                    'type' => $type,
                    'order' => ++$counter,
                ]);
            }
        }

        return $categoryList;
    }

    /**
     * @param string $search
     * @param string|int $all
     * @return int
     */
    public function getcategoryCount($search, $all)
    {
		$dao = $this->getExpenseItemCategoriesDao();

        return $dao->getCategoryCount($search, $all);
    }

    /**
     * @param int $id
     * @return \DDD\Domain\Finance\Expense\ExpenseItemCategories|bool
     */
    public function getCategoryById($id)
    {
		$dao = $this->getExpenseItemCategoriesDao();

        return $dao->getCategoryById($id);
    }

    /**
     * @param int $categoryId
     * @param int $status
     * @return bool
     */
    public function changeStatus($categoryId, $status)
    {
        $dao = $this->getExpenseItemCategoriesDao();
        $dao->save(['is_active' => (int)$status], ['id' => $categoryId]);

		return true;
    }

    /**
     * @param string $name
     * @param int $id
     * @return \DDD\Domain\Finance\Expense\ExpenseItemCategories|bool
     */
    public function checkTitle($name, $id)
    {
        $dao = $this->getExpenseItemCategoriesDao();

        return $dao->checkTitle($name, $id);
    }

    /**
     * @return \DDD\Dao\Finance\Expense\ExpenseItemCategories
     */
    public function getExpenseItemCategoriesDao()
    {
        if (empty($this->_dao_expense_category)) {
            $this->dao = $this->getServiceLocator()->get('dao_finance_expense_expense_item_categories');
        }

        return $this->dao;
    }

    /**
     * @return \DDD\Dao\Finance\Expense\ExpenseItemSubCategories
     */
    public function getExpenseItemSubCategoriesDao()
    {
        if (empty($this->_dao_expense_category)) {
            $this->dao = $this->getServiceLocator()->get('dao_finance_expense_expense_item_sub_categories');
        }

        return $this->dao;
    }
}
