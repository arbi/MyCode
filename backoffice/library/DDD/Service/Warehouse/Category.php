<?php

namespace DDD\Service\Warehouse;

use DDD\Dao\Warehouse\SKU;
use DDD\Service\ServiceBase;

use Library\Constants\TextConstants;
use Library\ActionLogger\Logger;

use Zend\Db\Sql\Where;

class Category extends ServiceBase
{
    const CATEGORY_TYPE_CONSUMABLE = 1;
    const CATEGORY_TYPE_VALUABLE   = 2;

    const CATEGORY_STATUS_ACTIVE   = 0;
    const CATEGORY_STATUS_INACTIVE = 1;

    const IS_NEW     = 1;
    const IS_NOT_NEW = 0;

    public static $categoryTypes = [
        self::CATEGORY_TYPE_CONSUMABLE => 'Consumable',
        self::CATEGORY_TYPE_VALUABLE   => 'Valuable',
    ];

    /**
     * @param $offset
     * @param $limit
     * @param $sortCol
     * @param $sortDir
     * @param $like
     * @param int $all
     * @return array
     */
    public function getDatatableData($offset, $limit, $sortCol, $sortDir, $like, $all = 1)
    {
        /**
         * @var \DDD\Dao\Warehouse\Category $categoryDao
         */
        $categoryDao = $this->getServiceLocator()->get('dao_warehouse_category');
        $result = $categoryDao->getAllCategories($offset, $limit, $sortCol, $sortDir, $like, $all);
        $data = [];
        $categoryList = $result['result'];
        $total = $result['total'];
        if ($categoryList->count()) {
            foreach ($categoryList as $category) {
                array_push($data, [
                    ($category['inactive'] == self::CATEGORY_STATUS_ACTIVE)
                        ? '<span class="label label-success">Active</span>'
                        : '<span class="label label-danger">Inactive</span>',
                    $category['name'],
                    self::$categoryTypes[$category['type_id']],
                    '<a class="btn btn-xs btn-primary" href="/warehouse/category/edit/' . $category['id'] . '" data-html-content="Edit"></a>'
                ]);
            }
        }

        return [
            'data' => $data,
            'total' => $total,
        ];

    }

    /**
     * @param array $typeList
     * @param bool|true $onlyActive
     * @param int $selectedId
     * @return \DDD\Domain\Warehouse\Category\Category[]
     */
    public function getCategories($typeList = [], $onlyActive = true, $selectedId = 0, $exceptId = false)
    {
        /** @var \DDD\Dao\Warehouse\Category $categoryDao */
        $categoryDao = $this->getServiceLocator()->get('dao_warehouse_category');
        return $categoryDao->getCategoriesByTypeList($typeList, $onlyActive, $selectedId, $exceptId, false);
    }

    /**
     * @param $categoryId
     * @return array|\ArrayObject
     */
    public function getCategoryData($categoryId)
    {
        /**
         * @var \DDD\Dao\Warehouse\Category $categoryDao
         */
        $categoryDao = $this->getServiceLocator()->get('dao_warehouse_category');

        $categoryData = $categoryDao->getCategoryData($categoryId);
        return $categoryData ? $categoryData : [];
    }

    /**
     * @param $categoryData
     * @param $categoryId
     * @return bool
     */
    public function saveCategory($categoryData, $categoryId = 0)
    {
        /**
         * @var \DDD\Dao\Warehouse\Category $categoryDao
         * @var SKU $skuDao
         */
        $categoryDao = $this->getServiceLocator()->get('dao_warehouse_category');
        $skuDao      = $this->getServiceLocator()->get('dao_warehouse_sku');
        $auth        = $this->getServiceLocator()->get('library_backoffice_auth');

        $userId = $auth->getIdentity()->id;

        try {
            $categoryDao->beginTransaction();
            if (isset($categoryData['sku_names']) && count($categoryData['sku_names'])) {
                foreach ($categoryData['sku_names'] as $sku) {
                    $sku = trim($sku);

                    if (empty($sku)) {
                        continue;
                    }

                    if ($skuDao->fetchOne(['name' => trim($sku)], ['id'])) {
                        throw new \RuntimeException(TextConstants::UNIQUE_SKU . '. SKU# ' . $sku);
                    }
                }
            }

            $params = [
                'name'       => $categoryData['name'],
                'type_id'    => $categoryData['type'],
                'creator_id' => $userId
            ];

            if ($categoryId) {
                $categoryDao->save($params, ['id' => $categoryId]);
            } else {
                $categoryId = $categoryDao->save($params);
            }

            if (isset($categoryData['sku_names']) && count($categoryData['sku_names'])) {
                foreach ($categoryData['sku_names'] as $sku) {
                    if (empty($sku)) {
                        continue;
                    }

                    $skuDao->save([
                        'name'              => trim($sku),
                        'asset_category_id' => $categoryId,
                    ]);
                }
            }

            if (isset($categoryData['aliases']) && count($categoryData['aliases'])) {
                /** @var \DDD\Dao\Warehouse\Alias $assetCategoryAliasesDao */
                $assetCategoryAliasesDao = $this->getServiceLocator()->get('dao_warehouse_alias');
                $existingAliases         = $this->getAliases($categoryId);

                foreach ($categoryData['aliases'] as $submittedAliasId => $submittedAliasName) {
                    if (empty(trim($submittedAliasName))) {
                        continue;
                    }

                    if (array_key_exists($submittedAliasId, $existingAliases)) {
                        if (trim($submittedAliasName) == $existingAliases[$submittedAliasId]) {
                            continue;
                        }

                        $assetCategoryAliasesDao->save([
                            'name' => $submittedAliasName,
                        ], ['id' => $submittedAliasId]);
                        unset($existingAliases[$submittedAliasId]);
                    } else {
                        $assetCategoryAliasesDao->save([
                            'name'              => $submittedAliasName,
                            'asset_category_id' => $categoryId,
                        ]);
                    }
                }

                if (count($existingAliases)) {
                    $deleteWhere = new Where();
                    $deleteWhere->in('id', array_keys($existingAliases));
                    $assetCategoryAliasesDao->delete($deleteWhere);
                }
            }

            $categoryDao->commitTransaction();
        } catch (\Exception $e) {
            $categoryDao->rollbackTransaction();
            return $e;
        }

		return $categoryId;
	}

    /**
     * @param $categoryId
     * @param $status
     * @return int
     */
    public function changeStatus($categoryId, $status)
    {
        /**
         * @var \DDD\Dao\Warehouse\Category $categoryDao
         */
        $categoryDao = $this->getServiceLocator()->get('dao_warehouse_category');

        if ($status) {
            /** @var \DDD\Dao\Warehouse\Alias $assetCategoryAliasesDao */
            $assetCategoryAliasesDao = $this->getServiceLocator()->get('dao_warehouse_alias');
            $assetCategoryAliasesDao->delete(['asset_category_id', $categoryId]);
        }

        return $categoryDao->save(['inactive' => $status], ['id'=>$categoryId]);
    }

    /**
     * @param int $categoryId
     * @return array
     */
    public function getCategroySKUList($categoryId)
    {
        /**
         * @var SKU $skuDao
         */
        $skuDao = $this->getServiceLocator()->get('dao_warehouse_sku');
        $result = $skuDao->fetchAll(['asset_category_id' => $categoryId]);
        $skuList = [];

        if ($result->count()) {
            foreach ($result as $sku) {
                array_push($skuList, $sku['name']);
            }
        }

        return $skuList;
    }

    /**
     * @param int $assetCategoryId
     * @return array
     */
    public function getAliases($assetCategoryId)
    {
        /** @var \DDD\Dao\Warehouse\Alias $assetCategoryAliasesDao */
        $assetCategoryAliasesDao = $this->getServiceLocator()->get('dao_warehouse_alias');
        $aliases = $assetCategoryAliasesDao->fetchAll(['asset_category_id' => $assetCategoryId]);
        $aliasArr = [];

        if ($aliases->count()) {
            foreach ($aliases as $alias) {
                $aliasArr[$alias['id']] = [
                    $alias['name']
                ];
            }
        }

        return $aliasArr;
    }

    public function getCategoriesList()
    {
        /**
         * @var \DDD\Dao\Warehouse\Category $categoryDao
         */
        $categoryDao  = $this->getServiceLocator()->get('dao_warehouse_category');
        $categoryList = $categoryDao->getCategoriesList();

        $categories = [];
        foreach ($categoryList as $category) {
            $category['skues']   = explode(',', $category['skuNames']);
            $category['aliases'] = explode(',', $category['aliaseNames']);
            $categories[]        = $category;
            unset($category['skuNames']);
        }

        return $categories;
    }

    public function getNewCategoriesList()
    {
        /**
         * @var \DDD\Dao\Warehouse\Category $categoryDao
         */
        $categoryDao  = $this->getServiceLocator()->get('dao_warehouse_category');
        $categoryList = $categoryDao->getNewCategoriesList();

        return $categoryList;
    }

    public function archiveCategory($id)
    {
        /**
         * @var \DDD\Dao\Warehouse\Category $categoryDao
         */
        $categoryDao = $this->getServiceLocator()->get('dao_warehouse_category');
        $result      = $categoryDao->save(['is_new' => self::IS_NOT_NEW], ['id' => $id]);

        return $result;
    }

    public function getNewAssetCategoriesCount()
    {
        /**
         * @var \DDD\Dao\Warehouse\Category $categoryDao
         */
        $categoryDao = $this->getServiceLocator()->get('dao_warehouse_category');
        return $categoryDao->getNewAssetCategoriesCount();
    }

    public function mergeCategory($fromCategoryId, $toCategoryId, $aliasName, $type)
    {
        try {
            $auth          = $this->getServiceLocator()->get('library_backoffice_auth');
            $orderDao      = $this->getServiceLocator()->get('dao_wh_order_order');
            $consumableDao = $this->getServiceLocator()->get('dao_warehouse_asset_consumable');
            $valuableDao   = $this->getServiceLocator()->get('dao_warehouse_asset_valuable');
            $categoryDao   = $this->getServiceLocator()->get('dao_warehouse_category');
            $logger        = $this->getServiceLocator()->get('ActionLogger');
            $usersDao      = $this->getServiceLocator()->get('dao_user_user_manager');
            $skuDao        = $this->getServiceLocator()->get('dao_warehouse_sku');
            $aliasDao      = $this->getServiceLocator()->get('dao_warehouse_alias');


            $userId = $auth->getIdentity()->id;
            $user   = $usersDao->fetchOne(['id' => $userId]);

            $orderDao->beginTransaction();

            $orderDao->save(['asset_category_id' => $toCategoryId], ['asset_category_id' => $fromCategoryId]);

            if ($type == self::CATEGORY_TYPE_CONSUMABLE) {
                $consumableDao->save(['category_id' => $toCategoryId], ['category_id' => $fromCategoryId]);
            } else {
                $valuableDao->save(['category_id' => $toCategoryId], ['category_id' => $fromCategoryId]);
            }

            $skuDao->save(['asset_category_id' => $toCategoryId], ['asset_category_id' => $fromCategoryId]);
            $categoryDao->delete(['id' => $fromCategoryId]);
            $aliasDao->delete(['asset_category_id' => $fromCategoryId]);

            $this->addAlias($toCategoryId, $aliasName);

            $logger->save(
                Logger::MODULE_ASSET_CATEGORY,
                $toCategoryId,
                Logger::ACTION_MERGE_CATEGORY,
                '<b>' . $user->getFullName() . '</b> merged category <b>' . $aliasName . '</b> with this category',
                $userId
            );

            $orderDao->commitTransaction();

            return true;
        } catch (\Exception $e) {
            $orderDao->rollbackTransaction();
            return false;
        }
    }

    public function addAlias($categoryId, $aliasName)
    {
        /** @var \DDD\Dao\Warehouse\Alias $assetCategoryAliasesDao */
        $assetCategoryAliasesDao = $this->getServiceLocator()->get('dao_warehouse_alias');

        $exists = $assetCategoryAliasesDao->fetchOne(['asset_category_id' => $categoryId, 'name' => $aliasName]);

        if (!$exists) {
            $assetCategoryAliasesDao->save(['asset_category_id' => $categoryId, 'name' => $aliasName]);
        }

        return true;
    }

    public function getCategoryNames($typeList = [], $exceptId = false)
    {
        /** @var \DDD\Dao\Warehouse\Category $categoryDao */
        $categoryDao = $this->getServiceLocator()->get('dao_warehouse_category');
        return $categoryDao->getCategoryNames($typeList, $exceptId);
    }
}
