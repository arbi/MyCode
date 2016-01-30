<?php
namespace DDD\Dao\Task;

use Library\DbManager\TableGatewayManager;
use Zend\Db\Sql\Select;
use Zend\Db\Sql\Expression;
use Library\Constants\DbTables;
use Zend\Db\Sql\Where;

/**
 * Class Tag
 * @package DDD\Dao\Tag
 */
class Tag extends TableGatewayManager
{
    protected $table = DbTables::TBL_TASK_TAG;

    public function __construct($sm)
    {
        parent::__construct($sm, 'DDD\Domain\Tag\Tag');
    }

    public function getTagsAttachedToTask($taskId)
    {
        $columns = ['tag_id'];
        return $this->fetchAll(function (Select $select) use ($taskId,  $columns) {
            $select->where->equalTo($this->getTable() . '.task_id', $taskId);

            $select
                ->columns($columns);
        });
    }


}
