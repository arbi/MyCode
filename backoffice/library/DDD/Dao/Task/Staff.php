<?php
namespace DDD\Dao\Task;

use Library\DbManager\TableGatewayManager;
use Library\Constants\Constants;
use Zend\Db\Sql\Select;
use Zend\Db\Sql\Expression;
use Library\Constants\DbTables;
use Zend\Db\Sql\Where;
use DDD\Service\Task as TaskService;

/**
 * Class Staff
 * @package DDD\Dao\Task
 *
 *
 */
class Staff extends TableGatewayManager
{
    protected $table = DbTables::TBL_TASK_STAFF;

    public function __construct($sm)
    {
        parent::__construct($sm, 'DDD\Domain\Task\Staff');
    }

    /**
     * @param $taskId
     * @return \DDD\Domain\Task\Staff[]
     */
    public function getTaskFollowers($taskId)
    {
        return $this->fetchAll(function (Select $select) use($taskId) {
            $select
                ->join(
                    ['users'=>DbTables::TBL_BACKOFFICE_USERS],
                    $this->getTable() . '.user_id = users.id',
                    [
                        'id',
                        'name'  => new Expression("CONCAT(users.firstname, ' ', users.lastname)"),
                        'avatar'
                    ]
                )
                ->where(['task_id' => $taskId, 'type' => TaskService::STAFF_FOLLOWER]);
        });
    }

    /**
     * @param $taskId
     * @return \DDD\Domain\Task\Staff[]
     */
    public function getTaskHelpers($taskId)
    {
        return $this->fetchAll(function (Select $select) use($taskId) {
            $select
                ->join(
                    ['users' => DbTables::TBL_BACKOFFICE_USERS],
                    $this->getTable() . '.user_id = users.id',
                    [
                        'id',
                        'name' => new Expression("CONCAT(users.firstname, ' ', users.lastname)"),
                        'avatar'
                    ]
                )
                ->where(['task_id' => $taskId, 'type' => TaskService::STAFF_HELPER]);
        });
    }

    /**
     * @param $taskId
     * @return \DDD\Domain\Task\Staff[]
     */
    public function getTaskStaff($taskId)
    {
        return $this->fetchAll(function (Select $select) use($taskId) {
            $where = new Where();
            $where
                ->equalTo('task_id', $taskId)
                ->notEqualTo('type', TaskService::STAFF_CREATOR);

            $select
                ->join(
                    ['users' => DbTables::TBL_BACKOFFICE_USERS],
                    $this->getTable() . '.user_id = users.id',
                    [
                        'id',
                        'name' => new Expression("CONCAT(users.firstname, ' ', users.lastname)"),
                        'avatar'
                    ]
                )
                ->where($where)
                ->order('type', 'ASC');
        });
    }

    public function getStaffTasks($userId)
    {
        return $this->fetchAll(
            function (Select $select) use($userId) {
                $select
                    ->join(
                        ['task' => DbTables::TBL_TASK],
                        $this->getTable() . '.task_id = task.id',
                        ['task_status', 'start_date']
                    )->where
                        ->notIn('task.task_status', [TaskService::STATUS_VERIFIED, TaskService::STATUS_CANCEL])
                        ->notEqualTo($this->getTable() . '.type', TaskService::STAFF_CREATOR)
                        ->equalTo($this->getTable() . '.user_id', $userId);
            }
        );
    }
}
