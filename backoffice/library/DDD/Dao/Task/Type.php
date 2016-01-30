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
 */
class Type extends TableGatewayManager
{
    protected $table = DbTables::TBL_TASK_TYPE;

    public function __construct($sm)
    {
        parent::__construct($sm, 'DDD\Domain\Task\Type');
    }
}
