<?php

/**
 * Description of Images
 *
 * @author tigran.tadevosyan
 */

namespace DDD\Dao\Accommodation;

use Library\Constants\DbTables;
use Zend\Db\Sql\Select;
use Library\DbManager\TableGatewayManager;

class Images extends TableGatewayManager
{
    protected $table = DbTables::TBL_APARTMENT_IMAGES;
    public function __construct($sm, $domain = 'DDD\Domain\Accommodation\Images')
    {
        parent::__construct($sm, $domain);
    }
    
    public function getFirstImage($apartmentId)
    {
		$result = $this->fetchOne(function (Select $select) use ($apartmentId) {
            $select->columns(array(
                'id',
                'apartment_id',
                'img1',
                ));
            
            $select->where
                    ->equalTo('apartment_id', $apartmentId);
		});
        
		return $result;
	}
}

?>
