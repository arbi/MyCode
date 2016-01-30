<?php
namespace DDD\Dao\Apartment;

use Zend\Db\Sql\Expression;
use Zend\Db\Sql\Select;
use Library\DbManager\TableGatewayManager;
use Library\Constants\DbTables;
use Zend\Db\Sql\Where;

class ReviewCategoryRel extends TableGatewayManager
{
    protected $table = DbTables::TBL_APARTMENT_REVIEW_CATEGORY_REL;

    public function __construct($sm)
    {
        parent::__construct($sm, 'ArrayObject');
    }

    /**
     * @param $id
     * @return \Zend\Db\ResultSet\ResultSet
     */
    public function getAllReviewCategoryListByReviewId($id)
    {
        $this->setEntity(new \ArrayObject());

        $result = $this->fetchAll(function (Select $select) use ($id) {
            $select
                ->join(
                    ['category' => DbTables::TBL_APARTMENT_REVIEW_CATEGORY],
                    $this->getTable().'.apartment_review_category_id = category.id',
                    []
                )
                ->where([
                    $this->getTable() . '.review_id' => $id
                ])
                ->order(['category.name ASC']);
        });

        return $result;
    }

    /**
     * @param $apartmentId
     * @param $start
     * @param $end
     * @return \ArrayObject
     */
    public function getReviewCategoryCountByRange($apartmentId, $start, $end)
    {
        $this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());

        $sql = 'SELECT sub.* FROM (
                    SELECT
                        count(*)      AS count,
                        category.name AS `name`,
                        category.type AS `type`
                    FROM ga_apartment_review_category_rel
                    INNER JOIN ' . DbTables::TBL_PRODUCT_REVIEWS . ' AS review ON ga_apartment_review_category_rel.review_id = review.id
                    INNER JOIN ga_apartment_review_category AS category
                    ON ga_apartment_review_category_rel.apartment_review_category_id = category.id
                    WHERE review.apartment_id = ? AND review.date >= ? AND review.date <= ?
                    GROUP BY category.id
                ) as sub 
                ORDER BY sub.count DESC, sub.name ASC
                LIMIT 5;
                ';
        $statement = $this->adapter->createStatement($sql, array($apartmentId, $start, $end));
        $result = $statement->execute();
    	return $result;
    }

    /**
     * @param $reviewId
     * @param $categoryIds
     */
    public function add($reviewId,$categoryIds)
    {
        foreach ($categoryIds as $categoryId) {
            $this->save([
                'review_id' => $reviewId,
                'apartment_review_category_id' => $categoryId
            ]);
        }
    }

    /**
     * @param $reviewId
     * @param $categoryIds
     */
    public function remove($reviewId,$categoryIds)
    {
        $where = new Where();
        $where->equalTo('review_id', $reviewId)
            ->in('apartment_review_category_id', $categoryIds);
        $this->delete($where);
    }
}
