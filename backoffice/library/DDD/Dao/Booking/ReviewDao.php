<?php

namespace DDD\Dao\Booking;

use Library\DbManager\TableGatewayManager;
use Zend\Db\Sql\Select;
use Library\Constants\DbTables;

class ReviewDao extends TableGatewayManager
{
    protected $table = DbTables::TBL_PRODUCT_REVIEWS;

    public function __construct($sm, $domain = 'DDD\Domain\Review\ReviewBase')
    {
        parent::__construct($sm, $domain);
    }

    /**
     * Check exists Review
     *
     * @param string $resNumber
     * @return \DDD\Domain\Review\ReviewBase
     */
    public function getReviewByReservationNumber($resNumber)
    {
        $result = $this->fetchOne(function (Select $select) use ($resNumber) {

            $select->columns(array(
                'id',
            ));

            $select->where
                ->equalTo('res_number', $resNumber);
        });

        return $result;
    }
}
