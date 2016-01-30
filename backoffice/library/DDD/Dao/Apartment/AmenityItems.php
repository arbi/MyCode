<?php
namespace DDD\Dao\Apartment;

use DDD\Domain\Apartment\Amenities\ApartmentAmenities;
use Library\DbManager\TableGatewayManager;
use Library\Constants\DbTables;
use Zend\Db\Sql\Select;
use Zend\Db\Sql\Expression;

class AmenityItems extends TableGatewayManager
{
    /**
	 * @access protected
	 * @var string
	 */
    protected $table = DbTables::TBL_APARTMENT_AMENITY_ITEMS;

    /**
     * @access public
     * @param ServiceLocatorInterface $sm
     * @param string $domain
     */
    public function __construct($sm, $domain = 'DDD\Domain\Apartment\Amenities\ApartmentAmenities')
    {
        parent::__construct($sm, $domain);
    }
    
    public function getApartmentAmenities($apartmentId)
    {
        return $this->fetchAll(function (Select $select) use($apartmentId) {
            $columns = [
                'apartment_id',
                'amenity_id'
            ];
            $select->join(
                ['aa' => DbTables::TBL_APARTMENT_AMENITIES],
                new Expression($this->getTable() . '.amenity_id = aa.id and apartment_id = '.$apartmentId),
                ['amenity_name' => 'name', 'textline_id']
            );
            $select->columns($columns);
        });
    }
}
