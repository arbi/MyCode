<?php
namespace DDD\Dao\User;

use Zend\Db\Sql\Select;
use Library\DbManager\TableGatewayManager;
use Library\Constants\DbTables;

class UserDashboards extends TableGatewayManager
{
	/**
	 * Main table
	 * @var string
	 */
    protected $table = DbTables::TBL_BACKOFFICE_USER_DASHBOARDS;

    /**
     *
     * @var Array $userDashboards
     */
    protected $userDashboards = [];

    /**
     * Constructor
     * @param ServiceLocatorAwareInterface $sm
     */
    public function __construct($sm) {
        parent::__construct($sm, 'DDD\Domain\User\UserDashboards');
    }

    /**
     * @param int $backofficeUserID
     * @return Ambigous <\Library\DbManager\Ambigous, \Zend\Db\ResultSet\ResultSet, NULL, \Zend\Db\ResultSet\ResultSetInterface>
     */
    public function getUserDashboardList($backofficeUserID) {
        $result = $this->fetchAll(function (Select $select) use($backofficeUserID) {
            $select
            	->where(['user_id' => (int)$backofficeUserID])
                ->join(
                	['dashboard' => DbTables::TBL_DASHBOARDS],
                    $this->table . '.dashboard_id = dashboard.id',
                    []
            	)
                ->order(array('dashboard.name ASC'));
        });
        return $result;
    }

    /**
     * @param int $backofficeUserID
     * @return multitype:NULL
     */
    public function getUserDashboardArray($backofficeUserID) {
       	$result = $this->fetchAll(function (Select $select) use($backofficeUserID) {
       		$select->where(['user_id' => (int)$backofficeUserID]);
        });
        $dashboards = [];
        foreach ($result as $row) {
        	/* @var  */
            $dashboards[] = $row->getDashboardID();
        }
        return $dashboards;
    }

    /**
     *
     * @param int $userId
     * @param int $dashboardId
     * @return boolean|array
     */
    public function getUserDashboards($userId, $dashboardId)
    {
        if (!array_key_exists($userId, $this->userDashboards)) {
            $this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());

            $result = $this->fetchAll(function (Select $select) use ($userId) {
                $select->columns(['dashboard_id']);

                $select->where->equalTo('user_id', $userId);
            });

            $this->userDashboards[$userId] = iterator_to_array($result);
        }

        if ($dashboardId > 0) {
            foreach ($this->userDashboards[$userId] as $dashboard) {
                if ($dashboard['dashboard_id'] == $dashboardId) {
                    return true;
                }
            }

            return false;
        } else {
            return $this->userDashboards[$userId];
        }
    }
}