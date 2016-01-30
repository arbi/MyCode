<?php
namespace DDD\Dao\Apartment;

use Library\DbManager\TableGatewayManager;
use Library\Constants\DbTables;
use Zend\Db\Sql\Select;

class Furniture extends TableGatewayManager
{
	/**
	 * @access protected
	 * @var string
	 */
    protected $table = DbTables::TBL_APARTMENT_FURNITURE;

    public function getApartmentFurnitureList($apartmentId) {
    	$columns = [
    		'id'           => 'id',
    		'count'        => 'count',
    		'apartment_id' => 'apartment_id'
    	];

    	return $this->fetchAll(function (Select $select) use($columns, $apartmentId) {
    	
    		// fields from product_location table
    		$select->columns( $columns );
    		
    		$select->join(
                ['type' => DbTables::TBL_APARTMENT_FURNITURE_TYPES],
                $this->table . '.furniture_type_id = type.id',
                ['title' => 'title'],
                'LEFT'
                );
            
    		// $select->join(array('room' => DbTables::TBL_PRODUCT_TYPES), $this->table . '.apartment_id = room.apartment_id', array(), 'LEFT');
    		
    		$select->where([$this->table . '.apartment_id' => $apartmentId]);
    	});
    }

    public function getFurnitureLits($apartmentId) {
    	return $this->fetchAll(function (Select $select) use($apartmentId) {
    		$select->columns( 
                [
                    'id'    => 'id',
                    'count' => 'count'
                ] 
            );
    		
    		$select->join(
                ['type' => DbTables::TBL_APARTMENT_FURNITURE_TYPES], 
                $this->table . '.furniture_type_id = type.id', 
                ['title' => 'title'], 
                'LEFT'
            );
            
    		$select->where([$this->table . '.apartment_id' => $apartmentId]);
    	});
    }
    
    /**
     * @access public
     * @param ServiceLocatorInterface $sm
     * @param string $domain
     */
    public function __construct($sm, $domain = 'ArrayObject'){
        parent::__construct($sm, $domain);
    }
}