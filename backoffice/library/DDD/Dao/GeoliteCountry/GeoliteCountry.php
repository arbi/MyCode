<?php
namespace DDD\Dao\GeoliteCountry;

use Library\DbManager\TableGatewayManager;
use Library\Constants\DbTables;
use Zend\Db\Sql\Select;

class GeoliteCountry extends TableGatewayManager
{
	/**
	 * @access protected
	 * @var string
	 */
    protected $table = DbTables::TBL_GEOLITE_COUNTRY;
    
    /**
     * @access protected
     * @var string
     */
    protected $temporaryTable = DbTables::TBL_GEOLITE_COUNTRY_TEMP;
    
    /**
     * Constructor
     * @access public
     * @param ServiceLocatorInterface $sm
     * @param string $domain
     */
    public function __construct($sm, $domain = 'DDD\Domain\GeoliteCountry\GeoliteCountry') {
        parent::__construct($sm, $domain);
    }
    
    /**
     * @access public
     * @return boolean
     */
    public function createGeoliteCountryTemporaryTable() {
    	$sql = "CREATE TEMPORARY TABLE IF NOT EXISTS `".$this->temporaryTable."`(
                         `id` int(11) NOT NULL AUTO_INCREMENT,
                          `ip_start` varchar(100) NOT NULL,
                          `ip_end` varchar(100) NOT NULL,
                          `ip_num_start` varchar(100) NOT NULL,
                          `ip_num_end` varchar(100) NOT NULL,
                          `iso` varchar(10) NOT NULL,
                          `code` varchar(100) NOT NULL,
                          PRIMARY KEY (`id`))ENGINE=InnoDB";
    	
        $this->adapter->getDriver()->getConnection()->execute($sql);
    }
    
    /**
     * @access public
     * @param array $data
     */
    public function insertNewData($data) {
    	$sql = 'INSERT INTO ' . $this->temporaryTable . ' (
    				`ip_start` ,
    				`ip_end` ,
    				`ip_num_start` ,
    				`ip_num_end` ,
    				`iso` ,
    				`code`
				)
    			VALUES ';
    	$sql .= $data;
    	$sql = trim($sql, ',');
    	
    	$this->adapter->getDriver()->getConnection()->execute($sql);
    }
    
    /**
     * @access public
     * @return boolean
     */
    public function replaceGeoliteCountryTableWithTemporaryTable() {
    	$connection = $this->adapter->getDriver()->getConnection();
    	
    	$connection->execute('TRUNCATE ' . $this->table);
    	$connection->execute('INSERT INTO ' . $this->table . ' (SELECT * FROM ' . $this->temporaryTable . ')');
    }

    /**
     * Get country by ip address
     * @access public
     * @param string $ip
     * @return string
     */
    public function getCountryNameByIp($ip)
    {
    	$this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());
    	
        $result = $this->fetchOne(function (Select $select) use ($ip) {
        	$select->columns(['code']);
        	$select->where->Expression('ip_num_start <= ' . $ip . ' AND ip_num_end >= ' . $ip, []);
        });
        
        return $result['code'];
    }
   
    /**
     * Get country id by ip address
     * @access public
     * @param string $ip
     * @return int
     */
    public function getCountryIDByIp($ip)
    {
    	$this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());
    	 
    	$result = $this->fetchOne(function (Select $select) use ($ip) {
    		$select->columns(array());
    		$select->join(array('country_details' => DbTables::TBL_LOCATION_DETAILS), $this->getTable() . '.iso = country_details.iso', array());
    		$select->join(array('country' => DbTables::TBL_COUNTRIES), 'country.detail_id = country_details.id', array('id'));
    		$select->where->Expression('ip_num_start <= ' . $ip . ' AND ip_num_end >= ' . $ip, []);
    	});
    
    		return $result['id'];
    }
    
    /**
     * Get country ISO code by ip address
     * @access public
     * @param string $ip
     * @return int
     */
    public function getCountryIsoCodeByIp($ip) {
    	$this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());
    	 
    	$result = $this->fetchOne(function (Select $select) use ($ip) {
    		$select->columns(array());
    		$select->join(array('country_details' => DbTables::TBL_LOCATION_DETAILS), $this->getTable() . '.iso = country_details.iso', array('iso'));
            
    		$select->where->Expression('ip_num_start <= ' . $ip . ' AND ip_num_end >= ' . $ip, []);
    	});
        
        return $result['iso'];
    }

    /**
     * @param $ipAddress
     * @return array|\ArrayObject|null
     */
    public function getCountryDataByIp($ipAddress)
    {
        $this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());

        $result = $this->fetchOne(function (Select $select) use ($ipAddress) {
            $select->columns([
                'country_name' => 'code'
            ]);

            $select->join(
                ['country_details' => DbTables::TBL_LOCATION_DETAILS],
                $this->getTable() . '.iso = country_details.iso',
                [
                    'country_iso' => 'iso'
                ]
            );

            $select->join(
                ['country' => DbTables::TBL_COUNTRIES],
                'country.detail_id = country_details.id',
                ['country_id' => 'id']
            );

            $select->where->greaterThanOrEqualTo('ip_num_end', $ipAddress);
            $select->where->lessThanOrEqualTo('ip_num_start', $ipAddress);
        });

        return $result;
    }
}