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
class Attachments extends TableGatewayManager
{
    protected $table = DbTables::TBL_TASK_ATTACHMENTS;

    public function __construct($sm)
    {
        parent::__construct($sm, 'DDD\Domain\Task\Attachments');
    }

    /**
     * @param int $attachmentId
     * @return \DDD\Domain\Task\Attachments
     */
    public function getAttachmentById($attachmentId)
    {
        return $this->fetchOne(function (Select $select) use($attachmentId) {
            $columns = ['id', 'task_id', 'file'];
            $select
                ->join(
                    ['tasks'=>DbTables::TBL_TASK],
                    $this->getTable() . '.task_id = tasks.id',
                    [
                        'path'  => new Expression('REPLACE(DATE(creation_date), "-", "/")'),
                    ]
                )
                ->where([$this->getTable() . '.id' => $attachmentId]);
        });
    }
}
