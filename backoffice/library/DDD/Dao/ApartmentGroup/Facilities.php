<?php
namespace DDD\Dao\ApartmentGroup;

use DDD\Domain\ApartmentGroup\Facility;
use Library\DbManager\TableGatewayManager;
use Library\Constants\DbTables;
use Zend\Db\Sql\Select;

class Facilities extends TableGatewayManager {
    /**
	 * @access protected
	 * @var string
	 */
    protected $table = DbTables::TBL_BUILDING_FACILITIES;

    /**
     * @access public
     * @param ServiceLocatorInterface $sm
     * @param string $domain
     */
    public function __construct($sm, $domain = 'DDD\Domain\ApartmentGroup\Facility'){
        parent::__construct($sm, $domain);
    }

    /**
     * @return array
     */
    public function getAllPossibleBuildingFacilitiesArray()
    {
        $result = $this->fetchAll(function (Select $select) {
            $columns = ['id', 'name'];
            $select->columns($columns);
        });

        $resultArray = [];
        foreach ($result as $row) {
            $resultArray[$row->getId()] = ['name' => $row->getname(), 'isPresent' => false];
        }
        return $resultArray;
    }


}
