<?php
namespace DDD\Dao\Apartment;

use Library\DbManager\TableGatewayManager;
use Library\Constants\DbTables;
use Zend\Db\Sql\Select;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\Db\Sql\Expression;

/**
 * DAO class for apartment reviews
 * @author Tigran Petrosyan
 */
class Review extends TableGatewayManager
{
	/**
	 * @access protected
	 * @var string
	 */
    protected $table = DbTables::TBL_PRODUCT_REVIEWS;

    /**
     * @access public
     * @param ServiceLocatorInterface $sm
     * @param string $domain
     */
    public function __construct($sm, $domain = 'ArrayObject'){
        parent::__construct($sm, $domain);
    }

    /**
     * Get apartment reviews
     * @access public
     *
     * @param int $apartmentId
     */
    public function getApartmentReviews($apartmentId){
    	$result = $this->fetchAll(
    		function (Select $select) use($apartmentId) {
    			/*
    			 * @TODO
    			 */
            });
        return $result->buffer();
    }
    
    /**
     * Get apartment reviews
     * @access public
     *
     * @param int $apartmentId
     */
    public function calculateReviewScore($apartmentId){
    	$result = $this->fetchAll(
    		function (Select $select) use($apartmentId) {
    			/*
    			 * @TODO
    			 */
    		}
		);
    	
    	return $result->buffer();
    }
    
            
    public function getApartelReviews($apartmentId, $limit, $offset, $showAll)
    {
		$result = $this->fetchAll(function (Select $select) use ($apartmentId, $limit, $offset, $showAll) {
            $select->columns([
                                'user_name',
                                'city',
                                'user_email',
                                'score',
                                'liked',
                                'date',
                                'country_id'
                             ]);
            $select->where
                    ->equalTo($this->getTable().'.apartment_id', $apartmentId)
                    ->notEqualTo($this->getTable().'.liked', '')
                    ->equalTo($this->getTable().'.status', '3');
            $select->order($this->getTable().'.date desc');
            if(!$showAll) {
                $select->limit((int)$limit);
                $select->offset((int)$offset);
            } else {
                $select->limit(50);
            }
            $select->quantifier(new Expression('SQL_CALC_FOUND_ROWS'));
		});
        $statement = $this->adapter->query('SELECT FOUND_ROWS() as total');
        $result2 = $statement->execute();
        $row = $result2->current();
        $total = $row['total'];
        return ['result'=>$result, 'total'=>$total];
	}
            
    public function apartmentReviewCount($apartmentId)
    {
		$result = $this->fetchOne(function (Select $select) use ($apartmentId) {
            $select->columns([
                                'count'=> new Expression('COUNT(*)'),
                             ]);
            $select->where
                    ->equalTo($this->getTable().'.apartment_id', $apartmentId)
                    ->notEqualTo($this->getTable().'.liked', '')
                    ->equalTo($this->getTable().'.status', '3');
        });    
        return $result;
	}
              
    public function apartmentAvgReviewSore($apartmentId, $start = false, $end = false)
    {
		$result = $this->fetchOne(function (Select $select) use ($apartmentId, $start, $end) {
            $select->columns([
                                'avg_score'=> new Expression('AVG(score)'),
                             ]);
            $select->where
                    ->equalTo($this->getTable().'.apartment_id', $apartmentId);
            if($start && $end) {
                 $select->where
                ->expression('DATE(' . $this->getTable() .'.date) >= "' . $start .'" AND ' .
                         'DATE(' . $this->getTable() .'.date) <= "' . $end .'"',[]);
            }
        });
        return $result;
	}

    /**
     * @param $where
     * @param bool|false $iDisplayStart
     * @param bool|false $iDisplayLength
     * @param bool|false $sortCol
     * @param bool|false $sortDir
     * @return array
     */
    public function getSearchResult(
        $where,
        $iDisplayStart = false,
        $iDisplayLength  = false,
        $sortCol  = false,
        $sortDir  = false
    )
    {
        $prototype = $this->getEntity();
        $this->setEntity(new \DDD\Domain\Apartment\Review\View);
        $result = $this->fetchAll(function(Select $select) use ($where, $iDisplayStart, $iDisplayLength, $sortCol, $sortDir) {
            $sortColumns = [
                'resservations.res_number',
                $this->getTable() . '.score',
                $this->getTable() .  '.date',
                $this->getTable() .  '.status'
            ];
            if ($iDisplayStart !== false) {
                $columns = [
                    'id',
                    'res_number',
                    'date',
                    'status',
                    'liked',
                    'dislike',
                    'score'
                ];
            } else {
                $columns = [
                    'date',
                    'score'
                ];
            }

            $select->columns($columns);

            if ($iDisplayStart !== false) {
                $selectJoinedColumns = ['apartment_name' => 'name', 'apartment_id' => 'id'];
            } else {
                $selectJoinedColumns = [];
            }

                $select->join(
                ['apartments' => DbTables::TBL_APARTMENTS],
                $this->getTable() . '.apartment_id = apartments.id',
                $selectJoinedColumns,
                Select::JOIN_INNER
            )
            ->join(
                ['apartment_groups_items' => DbTables::TBL_APARTMENT_GROUP_ITEMS],
                'apartment_groups_items.apartment_id = apartments.id',
                [],
                Select::JOIN_LEFT
            );
            if ($iDisplayStart !== false) {
                $selectJoinedColumns = [];
            } else {
                $selectJoinedColumns = ['date_from'];
            }
            $select->join(
                ['reservations' => DbTables::TBL_BOOKINGS],
                $this->getTable() . '.res_id = reservations.id',
                $selectJoinedColumns,
                Select::JOIN_INNER
            )
            ->join(
                ['review_category_rel' => DbTables::TBL_APARTMENT_REVIEW_CATEGORY_REL],
                $this->getTable() . '.id = review_category_rel.review_id',
                [],
                Select::JOIN_LEFT
            );
            $select->where($where);
            if ($iDisplayStart  !== false) {
                $select->quantifier(new Expression('SQL_CALC_FOUND_ROWS'));
            }

            if (   $iDisplayLength !== null
                && $iDisplayStart  !== null
                && $iDisplayStart  !== false
            ) {
                $select->limit((int)$iDisplayLength);
                $select->offset((int)$iDisplayStart);
            }

            if ($iDisplayStart  !== false) {
                $select->order($sortColumns[$sortCol] . ' ' . $sortDir);
            } else {
                $select->order('reservations.date_from ASC');
            }

            $select->group($this->getTable() . '.id');
        });
        if ($iDisplayStart  !== false) {
            $statement = $this->adapter->query('SELECT FOUND_ROWS() as total');
            $result2   = $statement->execute();
            $row       = $result2->current();
            $total     = $row['total'];
        } else {
            $total     = false;
        }

        $return = [
            'result' => $result,
            'total'  => $total
        ];
        $this->setEntity($prototype);
        return $return;

    }
    
}
