<?php
namespace DDD\Dao\Warehouse;

use Library\Utility\Debug;
use Zend\Db\Sql\Expression;
use Zend\Db\Sql\Select;
use Library\DbManager\TableGatewayManager;
use Library\Constants\Constants;
use Library\Constants\DbTables;
use Zend\Db\Sql\Where;
use DDD\Service\Warehouse\Category as AssetsCategoryService;

class Category extends TableGatewayManager
{
    protected $table = DbTables::TBL_ASSET_CATEGORIES;

    public function __construct($sm)
    {
        parent::__construct($sm, 'DDD\Domain\Warehouse\Category\Category');
    }

    /**
     * @param $offset
     * @param $limit
     * @param $sortCol
     * @param $sortDir
     * @param $like
     * @param $all
     * @return array
     */
    public function getAllCategories($offset, $limit, $sortCol, $sortDir, $like, $all)
    {
        $this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());

        if ($all == '1') {
            $disable = ' AND ' . $this->getTable() . '.inactive = 0';
        } elseif ($all == '2') {
            $disable = ' AND ' . $this->getTable() . '.inactive = 1';
        } else {
            $disable = ' ';
        }

        $result = $this->fetchAll(function (Select $select) use ( $offset, $limit, $sortCol, $sortDir, $like, $disable) {
            $sortColumns = ['inactive', 'name', 'type_id'];

            $select->where($this->getTable() . '.name like "%' . $like . '%"'. $disable);
            $select
                ->group($this->getTable() . '.id')
                ->order($sortColumns[$sortCol].' '.$sortDir)
                ->offset((int)$offset)
                ->limit((int)$limit);
            $select->quantifier(new Expression('SQL_CALC_FOUND_ROWS'));
        });

        $statement = $this->adapter->query('SELECT FOUND_ROWS() as total');
        $resultCount  = $statement->execute();
        $row = $resultCount->current();
        $total = $row['total'];

        return  [
            'result' => $result,
            'total'  => $total
        ];
    }

    /**
     * @param array $typeList
     * @param bool|true $onlyActive
     * @return \DDD\Domain\Warehouse\Category\Category[]
     */
    public function getCategoriesByTypeList($typeList = [], $onlyActive = true, $selectedId = 0, $returnArray = false)
    {
        $entity = $this->getEntity();
        $this->setEntity(new \DDD\Domain\Warehouse\Category\Category);

        if ($returnArray) {
            $this->setEntity(new \ArrayObject());
        }

        $result = $this->fetchAll(function (Select $select) use ($typeList, $onlyActive, $selectedId) {
            $select->columns([
                'id',
                'name',
                'type' => 'type_id',
                'inactive'
            ]);

            $nestedWhere = new Where();
            $where = new Where();

            if (!empty($typeList)) {
                $nestedWhere->in('type_id', $typeList);
            }

            if ($onlyActive) {
                $nestedWhere->equalTo('inactive', AssetsCategoryService::CATEGORY_STATUS_ACTIVE);
            }

            if ($selectedId) {
                $where
                    ->equalTo('id', $selectedId)
                    ->orPredicate($nestedWhere);
            } else {
                $where = $nestedWhere;
            }

            $select
                ->where($where)
                ->order('type_id');
        });
        $this->setEntity($entity);
        return $result;
    }

    /**
     * @param $categoryId
     * @return array|\ArrayObject|null
     */
   public function getCategoryData($categoryId)
   {
       $this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());

       return $this->fetchOne(function (Select $select) use ($categoryId) {
           $select->columns([
               'name' => 'name',
               'type' => 'type_id',
               'inactive' => 'inactive'
           ]);
           $select->where(['id' => $categoryId]);
       });
   }

    /**
     * @param $storageId
     * @return array|\ArrayObject|null
     */
   public function getUnusedCategories($storageId)
   {
       $this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());

       return $this->fetchAll(function (Select $select) use ($storageId) {
           $select->columns([
               'id',
               'name',
           ]);

           $select->join(
               ['threshold' => DbTables::TBL_WM_THRESHOLD],
               new Expression($this->getTable() . '.id = threshold.asset_category_id AND threshold.storage_id = ' . $storageId),
               [],
               $select::JOIN_LEFT
           );

           $select->where->isNull('threshold.id')
                         ->equalTo($this->getTable() . '.type_id', \DDD\Service\Warehouse\Category::CATEGORY_TYPE_CONSUMABLE);
       });
   }


/** MOBILE REQUESTS **/

   /**
    * @param array $typeList
    * @return Array
    */
   public function getCategoriesList()
   {
       $entity = $this->getEntity();
       $this->setEntity(new \DDD\Domain\Warehouse\Category\Category);
       $this->setEntity(new \ArrayObject());

       $result = $this->fetchAll(function (Select $select) {
           $select->columns([
               'id',
               'name',
               'type' => 'type_id',
           ]);

           $select->join(
                ['sku' => DbTables::TBL_SKU],
                $this->getTable() . '.id = sku.asset_category_id',
                ['skuNames' => new Expression('GROUP_CONCAT(DISTINCT sku.name)')],
                $select::JOIN_LEFT

           )->join(
               ['aliase' => DbTables::TBL_ASSET_CATEGORY_ALIASES],
               $this->getTable() . '.id = aliase.asset_category_id',
               ['aliaseNames' => new Expression('GROUP_CONCAT(DISTINCT aliase.name)')],
               $select::JOIN_LEFT
           );

           $select->where->equalTo('inactive', AssetsCategoryService::CATEGORY_STATUS_ACTIVE);

           $select->order('type_id');
           $select->group($this->getTable() . '.id');
       });

       $this->setEntity($entity);
       return $result;
   }

   public function checkCategoryExist($name, $type)
   {
       $entity = $this->getEntity();

       $this->setEntity(new \ArrayObject());

       $result = $this->fetchOne(function (Select $select) use ($name, $type) {
           $select->columns(['id']);

           $select->where
            ->equalTo('name', $name)
            ->equalTo('type_id', $type);

       });

       $this->setEntity($entity);
       return $result;
   }

    /**
     * @param array $typeList
     * @return Array
     */
    public function getNewCategoriesList()
    {
        $entity = $this->getEntity();
        $this->setEntity(new \ArrayObject());

        $result = $this->fetchAll(function (Select $select) {

            $select->join(
                ['user' => DbTables::TBL_BACKOFFICE_USERS],
                $this->getTable() . '.creator_id = user.id',
                [ 'creator_name' => new Expression("CONCAT(user.firstname, ' ', user.lastname)") ],
                Select::JOIN_LEFT
            );

            $select->where
                ->equalTo('is_new', AssetsCategoryService::IS_NEW)
                ->equalTo('inactive', AssetsCategoryService::CATEGORY_STATUS_ACTIVE);
        });

        $this->setEntity($entity);
        return $result;
    }

    public function getNewAssetCategoriesCount()
    {
        $entity = $this->getEntity();
        $this->setEntity(new \ArrayObject());

        $result = $this->fetchOne(function (Select $select) {
            $select->columns(['count' => new Expression('COUNT(*)')]);
            $select->where
                ->equalTo('is_new', AssetsCategoryService::IS_NEW)
                ->equalTo('inactive', AssetsCategoryService::CATEGORY_STATUS_ACTIVE);
        });

        $this->setEntity($entity);

        return $result['count'];
    }

    /**
     * @param array $typeList
     * @param bool|true $onlyActive
     * @return \DDD\Domain\Warehouse\Category\Category[]
     */
    public function getCategoryNames($typeList = [], $exceptId = false)
    {
        $entity = $this->getEntity();
        $this->setEntity(new \DDD\Domain\Warehouse\Category\Category);

        $result = $this->fetchAll(function (Select $select) use ($typeList, $exceptId) {

            if (!empty($typeList)) {
                $select->where->in('type_id', $typeList);
            }

            $select->where->equalTo('inactive', AssetsCategoryService::CATEGORY_STATUS_ACTIVE);

            if ($exceptId) {
                $select->where->notEqualTo('id', $exceptId);
            }

            $select->order('name');
        });

        $this->setEntity($entity);

        return $result;
    }
}
