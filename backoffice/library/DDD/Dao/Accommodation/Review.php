<?php

/**
 * Description of Review
 *
 * @author tigran.tadevosyan
 */

namespace DDD\Dao\Accommodation;

use Library\Constants\DbTables;
use Zend\Db\Sql\Select;
use Library\DbManager\TableGatewayManager;
use Zend\Db\Sql\Expression;

class Review extends TableGatewayManager
{
    protected $table = DbTables::TBL_PRODUCT_REVIEWS;

    public function __construct($sm, $domain = 'DDD\Domain\Review\ReviewBase')
    {
        parent::__construct($sm, $domain);
    }
    
    public function getPendingReviews()
    {
		$result = $this->fetchAll(function (Select $select) {
            $select->columns(array('id', 'res_number', 'score', 'liked', 'dislike', 'apartment_id'))
                    //, 'total_score' => new Expression('AVG('.$this->getTable().'.score)')
                    ->order('date DESC');
            
            $select->where
                    ->equalTo($this->getTable().'.status', '0');
            
            $select->join(
                    DbTables::TBL_APARTMENTS,
                    $this->getTable().'.apartment_id = '.DbTables::TBL_APARTMENTS.'.id',
                    array(
                        'acc_name'      => 'name',
                        'total_score'   => 'score',
                    ));
            
		});
        
        
		return $result;
	}

    public function getPendingReviewsCount()
    {
        $this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());
		$result = $this->fetchOne(function (Select $select) {
            $select->columns(['count' => new Expression('COUNT(*)')]);

            $select->where->equalTo($this->getTable().'.status', '0');

            $select->join(
                DbTables::TBL_APARTMENTS,
                $this->getTable().'.apartment_id = '.DbTables::TBL_APARTMENTS.'.id',
                []
            );

		});


		return $result['count'];
	}
    
    public function getProductReviews($apartmentId)
    {
		$result = $this->fetchOne(function (Select $select) use ($apartmentId) {
            $select->columns(array('avgScore' => new Expression('AVG(score)')));
            $lastTwoYears = date('Y-m-d', strtotime('-2 year'));
            $select->where
                    ->equalTo('apartment_id', $apartmentId)
                    ->greaterThan('score', 0)
                    ->expression('DATE(' . $this->getTable() .'.date) >= "' . $lastTwoYears .'"', []);
		});
        
		return $result;
	}

    /**
     * @param $apartmentId
     * @return \Library\DbManager\Ambigous
     */
    public function getReviews($apartmentId) {
		$result = $this->fetchAll(function (Select $select) use ($apartmentId) {
             $select->join(
                    DbTables::TBL_BOOKINGS,
                    $this->table . '.res_id = ' . DbTables::TBL_BOOKINGS . '.id',
                    [ 'date_from' ],
                    Select::JOIN_LEFT);
            $select->where
                    ->equalTo($this->getTable().'.apartment_id', $apartmentId);
            $select->order($this->getTable().'.date desc');
		});
        
		return $result;
	}
    
}