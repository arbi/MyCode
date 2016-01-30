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
class Subtask extends TableGatewayManager
{
    protected $table = DbTables::TBL_TASK_SUBTASK;

    public function __construct($sm)
    {
        parent::__construct($sm, 'DDD\Domain\Task\Subtask');
    }

    /**
     * @param $taskId
     * @return \DDD\Domain\Task\Subtask[]
     */
    public function getTaskSubtasks($taskId)
    {
        return $this->fetchAll(function (Select $select) use($taskId) {
            $select->where(['task_id' => $taskId]);
        });
    }

    public function getSubtaskIdLike($taskId, $like)
    {
        $prototype = $this->resultSetPrototype->getArrayObjectPrototype();
        $this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());
        $res = $this->fetchOne(function (Select $select) use($taskId, $like) {
            $select->where
            ->equalTo($this->getTable() . '.task_id', $taskId)
            ->like($this->getTable() . '.description', '%' . $like . '%');
        });
        $this->resultSetPrototype->setArrayObjectPrototype($prototype);
        return $res['id'];
    }

    public function getSubtaskLike($taskId, $like)
    {
        $prototype = $this->resultSetPrototype->getArrayObjectPrototype();
        $this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());
        $res = $this->fetchOne(function (Select $select) use($taskId, $like) {
            $select->where
                ->equalTo($this->getTable() . '.task_id', $taskId)
                ->like($this->getTable() . '.description', '%' . $like . '%');
        });
        $this->resultSetPrototype->setArrayObjectPrototype($prototype);
        return $res;
    }

    public function changeSubtaskOccupancy($taskId, $occupancy)
    {
        $where = new Where();
        $where->like('description', '%Occupancy:%')
            ->equalTo('task_id', $taskId);

        $this->save(
            ['description' =>  'Occupancy: ' . $occupancy],
            $where
        );
    }
}
