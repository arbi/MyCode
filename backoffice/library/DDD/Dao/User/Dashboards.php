<?php
namespace DDD\Dao\User;

use Library\DbManager\TableGatewayManager;
use Zend\Db\Sql\Select;
use Library\Constants\DbTables;

/**
 * @category core
 * @package dao
 * @subpackage backoffice_user
 * @author Tigran Petrosyan
 */
class Dashboards extends TableGatewayManager
{
	/**
	 * Main table
	 * @var string
	 */
    protected $table = DbTables::TBL_DASHBOARDS;

    /**
     * Constructor
     * @param ServiceLocatorAwareInterface $sm
     */
    public function __construct($sm) {
        parent::__construct($sm, 'DDD\Domain\User\Dashboard');
    }

	public function getDashboardsList() {
        $result = $this->fetchAll(function (Select $select) {
        	$select->where(array('active' => 1))
        			->order(array('name ASC'));
        });
        return $result;
    }
}
