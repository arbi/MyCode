<?php
namespace DDD\Dao\Apartment;

use DDD\Domain\Apartment\Amenities\Amenity;
use Library\DbManager\TableGatewayManager;
use Library\Constants\DbTables;
use Zend\Db\Sql\Select;

class Amenities extends TableGatewayManager {
    /**
	 * @access protected
	 * @var string
	 */
    protected $table = DbTables::TBL_APARTMENT_AMENITIES;

    /**
     * @access public
     * @param ServiceLocatorInterface $sm
     * @param string $domain
     */
    public function __construct($sm, $domain = 'DDD\Domain\Apartment\Amenities\Amenity'){
        parent::__construct($sm, $domain);
    }
}
