<?php

namespace DDD\Dao\User\Schedule;

use DDD\Service\Team\Team;
use Library\DbManager\TableGatewayManager;
use Library\Constants\DbTables;

use Zend\Db\Sql\Expression;
use Zend\Db\Sql\Select;
use Zend\Db\Sql\Where;

class Inventory extends TableGatewayManager
{
    protected $table = DbTables::TBL_USER_SCHEDULE_INVENTORY;

    public function __construct($sm, $domain = 'DDD\Domain\User\Schedule\Inventory')
    {
        parent::__construct($sm, $domain);
    }

    /**
     * @param int $teamId
     * @param string $from
     * @param string $to
     * @return \DDD\Domain\User\Schedule\Inventory[]
     */
    public function getScheduleTable($teamId, $from, $to, $scheduleTypeId = 0, $officeId = 0)
    {
        $result = $this->fetchAll(function (Select $select) use ($teamId, $from, $to, $scheduleTypeId, $officeId) {
            $where = new Where();

            if ($teamId) {
                $teamStaffTypes = Team::STAFF_MEMBER . ', ' . Team::STAFF_OFFICER . ', ' . Team::STAFF_MANAGER;
                $select->join(
                    ['team_staff' => DbTables::TBL_TEAM_STAFF],
                    new Expression('team_staff.team_id = ' . $teamId . ' AND team_staff.user_id = ' . $this->getTable() . '.user_id AND team_staff.type IN (' . $teamStaffTypes . ')'),
                    [],
                    Select::JOIN_INNER
                );
            }

            $select
                ->join(
                    ['users' => DbTables::TBL_BACKOFFICE_USERS],
                    $this->getTable() . '.user_id = users.id',
                    ['user_firstname' => 'firstname', 'user_lastname' => 'lastname', 'manager_id' => 'manager_id'],
                    Select::JOIN_INNER
                )
                ->join(
                    ['vacations' => DbTables::TBL_BACKOFFICE_USER_VACATIONS],
                    new Expression(
                        $this->getTable() . '.user_id = vacations.user_id'
                        . ' AND vacations.is_approved = 1'
                        . ' AND vacations.from <= ' . $this->getTable() . '.`date`'
                        . ' AND vacations.to >= ' . $this->getTable() . '.`date`'
                    ),
                    ['vacation_type' => 'type'],
                    Select::JOIN_LEFT
                );

            if ($from) {
                $where->greaterThanOrEqualTo('date', $from);
            }

            if ($to) {
                $where->lessThanOrEqualTo('date', $to);
            }

            if ($officeId) {
                $where->equalTo('office_id', $officeId);
            }

            if ($scheduleTypeId) {
                $where->equalTo('users.schedule_type', $scheduleTypeId);
            }
            $where->equalTo('users.disabled', 0);

            $select->where($where);

            $select->order(['firstname' => 'ASC', 'lastname' => 'ASC', $this->getTable() . '.date' => 'ASC']);
        });

        return $result;
    }

    /**
     * @param int $userId
     * @param string $from
     * @param string $to
     * @return \DDD\Domain\User\Schedule\Inventory[]
     */
    public function getUserScheduleInRange($userId, $from, $to)
    {
        $result = $this->fetchAll(function (Select $select) use ($userId, $from, $to) {
            $where = new Where();

            if ($from) {
                $where->greaterThanOrEqualTo('date', $from);
            }

            if ($to) {
                $where->lessThanOrEqualTo('date', $to);
            }

            $where->equalTo($this->getTable() . '.user_id', $userId);

            $select
                ->join(
                    ['users' => DbTables::TBL_BACKOFFICE_USERS],
                    $this->getTable() . '.user_id = users.id',
                    ['user_firstname' => 'firstname', 'user_lastname' => 'lastname'],
                    Select::JOIN_INNER
                )
                ->join(
                    ['vacations' => DbTables::TBL_BACKOFFICE_USER_VACATIONS],
                    new Expression(
                        $this->getTable() . '.user_id = vacations.user_id'
                        . ' AND vacations.is_approved = 1'
                        . ' AND vacations.from <= ' . $this->getTable() . '.`date`'
                        . ' AND vacations.to >= ' . $this->getTable() . '.`date`'
                    ),
                    ['vacation_type' => 'type'],
                    Select::JOIN_LEFT
                )
                ->where($where)
                ->order([$this->getTable() . '.date' => 'ASC']);
        });

        return $result;
    }

    /**
     * @return \DDD\Domain\User\Schedule\Inventory[]
     */
    public function getLastDates()
    {
        $result = $this->fetchAll(function (Select $select) {

            $select
                ->columns(['user_id', 'date' => new Expression('MAX(date)')])
                ->group('user_id')
                ->order([$this->getTable() . '.user_id' => 'ASC', $this->getTable() . '.date' => 'ASC']);
        });

        return $result;
    }

    /**
     * @param int $userId
     * @param \DateTime $datetime
     * @return bool
     */
    public function isUserWorking($userId, $datetime)
    {
        $result = $this->fetchOne(function (Select $select) use ($userId, $datetime) {

            $date = $datetime->format('Y-m-d');
            $time = $datetime->format('H:i');

            $select
                ->columns(['id'])
                ->where->expression('user_id = "' . $userId . '"
                    AND ' . $this->getTable() . '.date = "' . $date . '" AND (
                        (time_from1 <= "' . $time . '" AND time_to1 >= "' . $time . '")
                        OR
                        (time_from2 <= "' . $time . '" AND time_to2 >= "' . $time . '")
                    )', []);
        });

        return $result ? true : false;
    }

    /**
     * @param \DDD\Domain\User\VacationRequest $vacation
     */
    public function applyVacation($vacation)
    {
        $availability = $vacation->getTotal_number() ? max(1 - $vacation->getTotal_number(), 0) : 0;

        $where = new Where();
        $where
            ->equalTo('user_id', $vacation->getUser_id())
            ->greaterThanOrEqualTo('date', date('Y-m-d', strtotime($vacation->getFrom())))
            ->lessThanOrEqualTo('date', date('Y-m-d', strtotime($vacation->getTo())));

        $this->save(
            [
                'availability' => $availability,
                'is_changed' => 1
            ], $where
        );
    }

    /**
     * @param \DDD\Domain\User\VacationRequest $vacation
     */
    public function applyVacationCancellation($vacation)
    {
        $where = new Where();
        $where
            ->equalTo('user_id', $vacation->getUser_id())
            ->equalTo('is_changed', 1)
            ->greaterThanOrEqualTo('date', date('Y-m-d', strtotime($vacation->getFrom())))
            ->lessThanOrEqualTo('date', date('Y-m-d', strtotime($vacation->getTo())));

        $this->save(
            [
                'availability' => 1,
                'is_changed' => 0,
            ], $where
        );
    }
}
