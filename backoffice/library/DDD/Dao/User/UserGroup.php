<?php
namespace DDD\Dao\User;

use Zend\Db\Sql\Select;
use Library\DbManager\TableGatewayManager;
use Library\Constants\DbTables;

class UserGroup extends TableGatewayManager
{
    protected $table = DbTables::TBL_GROUPS;
    public function __construct($sm)
    {
        parent::__construct($sm, 'DDD\Domain\User\UserGroup');
    }

    public function getGroupsList()
    {
        $result = $this->fetchAll(function (Select $select) {
            $select->order(['type ASC', 'name ASC']);
        });

        return $result;
    }

    public function getParentListByIds($ids){
        $result = $this->fetchAll(function (Select $select) use ($ids) {
                       $select->where->in('id', $ids)
                                     ->greaterThan('parent_id', 0);
                 });
        return $result;
    }
}