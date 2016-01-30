<?php
namespace DDD\Dao\Apartment;

use Library\DbManager\TableGatewayManager;
use Library\Constants\DbTables;
use Zend\Db\Sql\Select;
use Zend\Db\Sql\Expression;
use Zend\Db\Sql\Where;

/**
 * DAO class for apartment statistics
 * @author Tigran Petrosyan
 */
class Statistics extends TableGatewayManager
{
	/**
	 * @access protected
	 * @var string
	 */
    protected $table = DbTables::TBL_BOOKINGS;
    
    /**
     * @access protected
     * @var string
     */
    protected $partnersTable = DbTables::TBL_BOOKING_PARTNERS;
    
    /**
     * @access public
     * @param unknown $sm
     * @param string $domain
     */
    public function __construct($sm, $domain = 'DDD\Domain\Apartment\Statistics\BookingSources'){
        parent::__construct($sm, $domain);
    }
    
    /**
     * Get reservations count by partners
     * @access public
     * 
     * @param int $apartmentId
     * @return \DDD\Domain\Apartment\Statistics\BookingSources
     * @author Tigran Petrosyan
     */
    public function getApartmentBookingSources($apartmentId){
    	$result = $this->fetchAll(
    		function (Select $select) use($apartmentId) {
    			
    			$columns = array(
    				'count' => new Expression('COUNT(*)')
    			);
    			
            	$select	->columns($columns)
            			->join($this->partnersTable, $this->table . '.partner_id = ' . $this->partnersTable . '.gid', array('partner' => 'partner_name'))
                        ->order(array($this->partnersTable . '.gid ASC'))
                        ->group('partner_id');
            	
            	$where = new Where();
            	$where->equalTo($this->table . '.apartment_id_assigned', $apartmentId);
            	$select->where($where);
            });
        return $result;
    }
}