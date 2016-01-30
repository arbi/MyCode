<?php
namespace DDD\Dao\Queue;

use Library\DbManager\TableGatewayManager;
use Library\Constants\DbTables;
use Zend\Db\ResultSet\ResultSet;
use Zend\Db\Sql\Select;
use Zend\Db\Sql\Where;
use Zend\Db\Sql\Expression;

use \DDD\Service\Queue\EmailQueue as EmailQueueService;


/**
 * Class InventorySynchronizationQueue
 * @package DDD\Dao\Queue
 */
class EmailQueue extends TableGatewayManager
{
    protected $table = DbTables::TBL_EMAIL_QUEUE;

    public function __construct($sm)
    {
        parent::__construct($sm, '\ArrayObject');
    }

    /**
     * @param int|bool $type
     * @return \ArrayObject
     */
    public function fetch($type = false)
    {
        return $this->fetchAll(function (Select $select) use ($type) {
            $where = new Where();

            $where->lessThanOrEqualTo('send_time', new Expression('NOW()'));

            if ($type) {
                $where->equalTo('type', $type);

                $select
                    ->join(
                        ['applicants' => DbTables::TBL_HR_APPLICANTS],
                        $this->getTable() . '.entity_id = applicants.id',
                        ['status', 'email', 'applicant_name' => new Expression('CONCAT(firstname, " ",  lastname)')],
                        Select::JOIN_INNER
                    )
                    ->join(
                        ['jobs' => DbTables::TBL_HR_JOBS],
                        'jobs.id = applicants.job_id',
                        ['position_title' => 'title'],
                        Select::JOIN_INNER
                    );
            }

            $select
                ->where($where)
                ->limit(EmailQueueService::MAX_SEND_COUNT);
        });
    }
}