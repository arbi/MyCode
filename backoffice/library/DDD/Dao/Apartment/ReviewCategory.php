<?php
namespace DDD\Dao\Apartment;

use Library\Utility\Debug;
use Zend\Db\Sql\Expression;
use Zend\Db\Sql\Select;
use Library\DbManager\TableGatewayManager;
use Library\Constants\Constants;
use Library\Constants\DbTables;
use Zend\Db\Sql\Where;

class ReviewCategory extends TableGatewayManager
{
    protected $table = DbTables::TBL_APARTMENT_REVIEW_CATEGORY;

    public function __construct($sm)
    {
        parent::__construct($sm, 'DDD\Domain\Apartment\ReviewCategory\ReviewCategory');
    }

    /**
     * 
     * @return \DDD\Domain\Apartment\ReviewCategory\ReviewCategory[]|\ArrayObject
     */
    public function getAllReviewCategories()
    {
        $this->setEntity(new \ArrayObject());

        $result = $this->fetchAll(function (Select $select) {
            $select->order([
                $this->getTable() . '.name ASC'
            ]);
        });

        return $result;
    }
    
    /**
    * @param int $categoryId
    * @return \ArrayObject
    */
   public function getReviewCategoryData($categoryId) 
   {
       $this->setEntity(new \ArrayObject());

       return $this->fetchOne(function (Select $select) use ($categoryId) {
           $select->where(['id' => $categoryId]);
       });
   }
   
}
