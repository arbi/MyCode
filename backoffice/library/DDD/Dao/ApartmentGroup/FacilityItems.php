<?php
namespace DDD\Dao\ApartmentGroup;

use DDD\Domain\ApartmentGroup\BuildingFacilities;
use Library\DbManager\TableGatewayManager;
use Library\Constants\DbTables;
use Zend\Db\Sql\Select;
use Zend\Db\Sql\Expression;

class FacilityItems extends TableGatewayManager
{
    /**
	 * @access protected
	 * @var string
	 */
    protected $table = DbTables::TBL_BUILDING_FACILITY_ITEMS;

    /**
     * @access public
     * @param ServiceLocatorInterface $sm
     * @param string $domain
     */
    public function __construct($sm, $domain = 'DDD\Domain\ApartmentGroup\BuildingFacilities')
    {
        parent::__construct($sm, $domain);
    }
    
    public function getApartmentBuildingFacilities($apartmentId)
    {
        return $this->fetchAll(function (Select $select) use($apartmentId) {
            $columns = [
                'building_id',
                'facility_id'
            ];
            $select->join(
                ['pg' => DbTables::TBL_APARTMENTS],
                new Expression($this->getTable() . '.building_id = pg.building_id and pg.id = ' . $apartmentId),
                []
            );
            $select->join(
                ['bf' => DbTables::TBL_BUILDING_FACILITIES],
                $this->getTable() . '.facility_id = bf.id',
                ['facility_name' => 'name', 'textline_id']
            );
            $select->columns($columns);
        });
    }
}
