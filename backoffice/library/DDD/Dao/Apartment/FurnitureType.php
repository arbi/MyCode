<?php
namespace DDD\Dao\Apartment;

use Library\DbManager\TableGatewayManager;
use Library\Constants\DbTables;
use Zend\Db\Sql\Select;

/**
 * @author Tigran Petrosyan
 */
class FurnitureType extends TableGatewayManager
{
	/**
	 * @access protected
	 * @var string
	 */
    protected $table = DbTables::TBL_APARTMENT_FURNITURE_TYPES;
    
    /**
     * @access public
     * @param ServiceLocatorInterface $sm
     * @param string $domain
     */
    public function __construct($sm, $domain = 'ArrayObject'){
        parent::__construct($sm, $domain);
    }

	public function getTypes() {
        $result = $this->fetchAll(function (Select $select) {
            $select->order('title ASC');
        });
        return $result; 
    }
}