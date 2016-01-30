<?php
namespace DDD\Dao\User;

use DDD\Service\User\Vacation;
use Zend\Db\Sql\Select;
use Zend\Db\Sql\Where;
use Zend\Db\Sql\Expression;

use Library\DbManager\TableGatewayManager;
use Library\Constants\DbTables;

/**
 * Class VacationRequest
 * @package DDD\Dao\User
 */
class VacationRequest extends TableGatewayManager
{
    protected $table = DbTables::TBL_BACKOFFICE_USER_VACATIONS;

    public function __construct($sm)
    {
        parent::__construct($sm, 'DDD\Domain\User\VacationRequest');
    }

    /**
     * @param $managerId
     * @return \DDD\Domain\User\VacationRequest[]
     */
    public function getTimeOffRequests($managerId)
    {
        $result = $this->fetchAll(function (Select $select) use($managerId) {
            $select
                ->columns([
                    'id',
                    'from',
                    'to',
                    'comment',
                    'total_number',
                    'type'
                ])
                ->join(
                    ['user' => DbTables::TBL_BACKOFFICE_USERS],
                    $this->getTable().'.user_id = user.id',
                    [
                        'user_id' => 'id',
                        'firstname',
                        'lastname',
                        'vacation_days',
                        'sick_days'
                    ])
                ->where([
                    'user.manager_id' => $managerId,
                    $this->getTable() . '.is_approved = ' . Vacation::VACATION_REQUEST_STATUS_PENDING
                ])
                ->order([
                    'user.id ASC',
                    $this->getTable() . '.from ASC'
                ]);
            });

        return $result;
    }

    /**
     * @param $managerId
     * @return int
     */
    public function getTimeOffRequestsCount($managerId)
    {
        $this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());

        $result = $this->fetchOne(function (Select $select) use($managerId) {
            $select
                ->columns([
                    'count' => new Expression('COUNT(*)')
                ])
                ->join(
                    ['user' => DbTables::TBL_BACKOFFICE_USERS],
                    $this->getTable() . '.user_id = user.id',
                    []
                )
                ->where([
                    'user.manager_id' => $managerId,
                    $this->getTable().'.is_approved = ' . Vacation::VACATION_REQUEST_STATUS_PENDING
                ]);
        });

        return $result['count'];
    }

    public function vacationRequestUpdate($id, $value, $user_id){
        $result = $this->fetchOne(function (Select $select) use($id, $user_id) {
            $select->join(array('user' => DbTables::TBL_BACKOFFICE_USERS) ,
                                $this->getTable().'.user_id = user.id', array('manager_id'=>'manager_id', 'userId'=>'id'))
                   ->where(array(//$this->getTable().'.is_approved = 2',
                                  $this->getTable().'.id' => $id));
        });
        return $result;
    }

    /**
     * @param $id
     * @return \DDD\Domain\User\VacationRequest
     */
    public function getVacationById($id)
    {
        $result = $this->fetchOne(function (Select $select) use($id) {
            $select
                ->join(
                    ['user' => DbTables::TBL_BACKOFFICE_USERS],
                    $this->getTable() . '.user_id = user.id',
                    [
                        'user_id' => 'id',
                        'firstname',
                        'lastname',
                        'manager_id'
                    ]
                )
                ->where([
                    $this->getTable() . '.id' =>  $id
                ]);
        });

        return $result;
    }

    /**
     * @param int $userId
     * @param string $from
     * @param string $to
     * @return \DDD\Domain\User\VacationRequest[]
     */
    public function getUsersApprovedVacationsInRange($userId, $from = '', $to = '')
    {
        $result = $this->fetchAll(function (Select $select) use($userId, $from, $to) {
            $where = new Where();
            $where
                ->equalTo($this->getTable() . '.user_id', $userId)
                ->equalTo($this->getTable() . '.is_approved', 1);

            if ($from) {
                $where->greaterThanOrEqualTo($this->getTable() . '.from', $from);
            }

            if ($to) {
                $where->lessThanOrEqualTo($this->getTable() . '.to', $to);
            }

            $select
                ->columns([
                    'id', 'from', 'to', 'total_number'
                ])
                ->where($where)
                ->order([$this->getTable() . '.from ASC']);
        });
        return $result;
    }

    /**
     * @return \DDD\Domain\User\VacationRequest[]
     */
    public function getApprovedNotResolvedVacations()
    {
        $result = $this->fetchAll(function (Select $select) {
            $where = new Where();
            $where
                ->equalTo($this->getTable() . '.is_approved', 1)
                ->notEqualTo($this->getTable() . '.is_resolved', 1);

            $select
                ->where($where)
                ->join(
                    ['user' => DbTables::TBL_BACKOFFICE_USERS],
                    $this->getTable() . '.user_id = user.id',
                    [
                        'user_id' => 'id',
                        'firstname',
                        'lastname',
                        'vacation_days',
                        'sick_days'
                    ]
                )
                ->order([$this->getTable() . '.from ASC']);
        });
        return $result;
    }

    public function resolveVacationRequest($id)
    {
        $this->save(['is_resolved' => 1], ['id' => $id]);
    }
}
