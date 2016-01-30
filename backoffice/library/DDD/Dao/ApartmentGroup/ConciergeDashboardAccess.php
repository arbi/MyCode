<?php
namespace DDD\Dao\ApartmentGroup;

use DDD\Domain\ApartmentGroup\ForSelect;
use Zend\Db\Sql\Select;
use Library\DbManager\TableGatewayManager;
use Library\Constants\DbTables;
use Library\Constants\Constants;

class ConciergeDashboardAccess extends TableGatewayManager
{
    protected $table = DbTables::TBL_CONCIERGE_DASHBOARD_ACCESS;

    public function __construct($sm, $domain = 'DDD\Domain\ApartmentGroup\ConciergeDashboardAccess')
    {
        parent::__construct($sm, $domain);
    }

    /**
     * @param $id
     * @return \Library\DbManager\Ambigous
     */
    public function getUserConciergeGroupsList($id)
    {
        $result = $this->fetchAll(function (Select $select) use($id)  {
            $select->where(array('user_id'=>(int)$id))
                ->join(array('apartment_group' => DbTables::TBL_APARTMENT_GROUPS) ,
                    $this->getTable().'.apartment_group_id = apartment_group.id', array())
                ->order(array('apartment_group.name ASC'));
        });
        return $result;
    }


    /**
     * @param $backofficeUserId
     * @return ForSelect[]
     */
    public function getUserAccessibleConciergeDashboards($backofficeUserId, $hasNotDevAccess)
    {
        $this->getResultSetPrototype()->setArrayObjectPrototype(new ForSelect());

        $result = $this->fetchAll(function (Select $select) use($backofficeUserId, $hasNotDevAccess) {

            $select
                ->join(
                    ['apartment_group' => DbTables::TBL_APARTMENT_GROUPS],
                    $this->getTable().'.apartment_group_id = apartment_group.id',
                    [
                        'id',
                        'name',
                        'usage_apartel'
                    ]
                )
                ->order(['apartment_group.name ASC'])
                ->where([
                    'apartment_group.usage_concierge_dashboard' => 1,
                    'apartment_group.active' => 1,
                    $this->getTable() . '.user_id' => $backofficeUserId
                ]);

                if ($hasNotDevAccess) {
                    $select->where->notEqualTo('apartment_group.id', Constants::TEST_APARTMENT_GROUP);
                }
        });

        return $result;
    }


}
