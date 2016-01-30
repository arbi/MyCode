<?php
namespace DDD\Dao\User;

use Zend\Db\Sql\Select;
use Library\DbManager\TableGatewayManager;
use Library\Constants\DbTables;

class UserGroups extends TableGatewayManager
{
    protected $table = DbTables::TBL_BACKOFFICE_USER_GROUPS;
    public function __construct($sm)
    {
        parent::__construct($sm, 'DDD\Domain\User\UserGroups');
    }
    
    public function getUserGroupList($id)
    {
        $result = $this->fetchAll(function (Select $select) use ($id) {
            $select->join(
                ['groups' => DbTables::TBL_GROUPS],
                $this->getTable().'.group_id = groups.id',
                [
                    'name',
                    'type',
                    'parent_id',
                ]
            );

            $select->where(['user_id'=>(int)$id]);
            
            $select->order(['groups.type ASC', 'groups.name ASC']);
        });
        
        return $result;
    }
    
    public function getUsersByGroupId($groupId)
    {
		$result = $this->fetchAll(function (Select $select) use ($groupId) {
            
            $select->columns([
                'user_id'
            ]);
            
            $select->where
                    ->equalTo('group_id', $groupId);
		});
        
		return $result;
	}

    public function getUserGroupListSimplified($id)
    {
        $result = $this->fetchAll(function (Select $select) use ($id) {
            $select->columns(['group_id']);
            $select->where(['user_id'=>(int)$id]);
        });
        return $result;
    }

}