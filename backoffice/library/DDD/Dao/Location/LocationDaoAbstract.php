<?php
namespace DDD\Dao\Location;

use Library\DbManager\TableGatewayManager;
use Library\Constants\DbTables;
use Zend\Db\Sql\Select;

/**
 * Base class for all locations DAO classes
 * @package core
 * @subpackage core_dao
 * @author Tigran Petrosyan
 */
abstract class LocationDaoAbstract extends TableGatewayManager
{
	/**
	 * Main table, for example 'cities'
	 * @access protected
	 * @var string
	 */
    protected $table;

    /**
     * Geolocation details table, same for all types of location
     * @access protected
     * @var string
     */
    protected $detailsTable = DbTables::TBL_LOCATION_DETAILS;

    /**
     * Parent location table, for example 'provinces' for 'cities'
     * @access protected
     * @var string
     */
    protected $parentTable;

    /**
     * Parent location id field, for example 'province_id' for cities
     * @var string
     */
    protected $parentIDField;

    /**
     * Parent location table, for example 'poi' for 'cities'
     * @access protected
     * @var string
     */
    protected $childTable;

    /**
     * Main fields of any location
     * @access protected
     * @var array
     */
    protected $mainColumns = array();

    /**
     * Details table fields
     * @access protected
     * @var array
     */
    protected $detailsColumns = array();

    /**
     * Constructor
     * @param ServiceLocatorInterface $sm
     * @param string $domain
     * @param string $table
     * @param string $parentTable
     * @param string $parentIDField
     * @param string $childTable
     */
    public function __construct($sm, $domain, $table, $parentTable, $parentIDField, $childTable) {
    	// set table names
        $this->table         = $table;
        $this->parentTable   = $parentTable;
        $this->parentIDField = $parentIDField;
        $this->childTable    = $childTable;

    	// define location main fields
        $this->mainColumns['id']         = 'id';
        $this->mainColumns['details_id'] = 'detail_id';
        $this->mainColumns['parent_id']  = $this->parentIDField;

    	// define location details fields
    	$this->detailsColumns = [
			'latitude' => 'latitude',
			'longitude' => 'longitude',
			'name' => 'name'
    	];

    	// parent constructor call
        parent::__construct($sm, $domain);
    }

    public function getLocationByID($id) {
    	$result = $this->fetchAll(
    			function (Select $select) {
    				$select->columns($this->mainColumns)
    				->join(array('details' => $this->detailsTable), $this->table.'.detail_id = details.id', $this->detailsColumns)
    				->where(array('id' => $id));
    			}
    	);

    	return $result;
    }

    public function getLocationsWithBasicDetails() {
    	$result = $this->fetchAll(
    			function (Select $select) {
    				$select->columns($this->mainColumns)
						->join(array('details' => $this->detailsTable), $this->table.'.detail_id = details.id', $this->detailsColumns)
						->order(array('name ASC'));

    			}
    	);

    	return $result;
    }

    public function getActiveLocationsWithBasicDetails() {
    	$result = $this->fetchAll(
			function (Select $select) {
				$select->columns($this->mainColumns)
						->join(array('details' => $this->detailsTable), $this->table.'.detail_id = details.id', $this->detailsColumns)
						->order(array('name ASC'));
			}
    	);

    	return $result;
    }

    /**
     * @access public
     * @return int
     */
    public function getParentLocationID($id) {

    }

    /**
     * @access public
     */
    public function getParentLocationWithBasicDetails() {

    }

    /**
     * @access public
     * @return boolean
     */
    public function hasChilds() {

    }

    /**
     * @access public
     * @return boolean
     */
    public function hasActiveChilds() {

    }

    /**
     * @access public
     */
    public function getChildLocationsWithBasicDetails() {

    }
}