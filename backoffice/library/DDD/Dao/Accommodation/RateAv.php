<?php

namespace DDD\Dao\Accommodation;

use Library\Constants\DbTables;
use Library\Constants\Constants;
use Library\DbManager\TableGatewayManager;

use Zend\Db\Sql\Select;
use Zend\Db\Sql\Expression;

use DDD\Service\Apartment\Rate as RateService;

class RateAv extends TableGatewayManager
{
    protected $table = DbTables::TBL_APARTMENT_INVENTORY;

    public function __construct($sm, $domain = '')
    {
        parent::__construct($sm, $domain);
    }

    public function unsoldDays($id) {
		return $this->fetchOne(function (Select $select) use($id) {
			 $select->columns( array(
                'count' => new Expression('count(*)'))
             );
             $select->join(array('rates' => DbTables::TBL_APARTMENT_RATES),  'rates.id = '.$this->getTable().'.rate_id', array());
             $select->where
                    ->equalTo('rates.type', RateService::TYPE1)
                    ->and
                    ->NotEqualTo($this->getTable().'.availability', 0)
                    ->and
                    ->equalTo($this->getTable().'.apartment_id', $id)
                    ->and
                    ->expression($this->getTable().".date  BETWEEN DATE_FORMAT(NOW(),'%Y-%m-%d ')  AND
						(DATE_FORMAT(NOW(),'%Y-%m-%d ') + INTERVAL 9 day)", []);
		});
	}
}
