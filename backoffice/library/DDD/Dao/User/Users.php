<?php

namespace DDD\Dao\User;

use Library\DbManager\TableGatewayManager;
use Zend\Db\Sql\Select;
use Library\Constants\DbTables;

class Users extends TableGatewayManager
{
    /**
	 * @var string
	 */
	protected $table   = DbTables::TBL_BACKOFFICE_USER_GROUPS;

    /**
     *
     * @var Array $userGroupResult
     */
    protected $userGroups = [];

    public function __construct($sm, $domain = 'DDD\Domain\User\User')
    {
         parent::__construct($sm, $domain);
    }

    /**
     *
     * @param int $userId
     * @param int $groupId
     * @return boolean|array
     */
    public function getUsersGroup($userId, $groupId = false)
    {
        if (!array_key_exists($userId, $this->userGroups)) {
            $this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());

            $result = $this->fetchAll(function (Select $select) use ($userId) {
                $select->columns(['group_id']);

                $select->where->equalTo('user_id', $userId);
            });

            $this->userGroups[$userId] = iterator_to_array($result);
        }

        if ($groupId > 0) {
            foreach ($this->userGroups[$userId] as $group) {
                if ($group['group_id'] == $groupId) {
                    return true;
                }
            }
            
            return false;
        } else {
            return $this->userGroups[$userId];
        }
    }
}
