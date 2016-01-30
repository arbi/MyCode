<?php
namespace DDD\Dao\Team;

use Library\DbManager\TableGatewayManager;
use Library\Constants\Constants;
use Library\Utility\Debug;
use Zend\Db\Sql\Select;
use Zend\Db\Sql\Where;
use Zend\Db\Sql\Expression;
use Library\Constants\DbTables;
use Library\Constants\Roles;

use \DDD\Service\Team\Team as TeamService;
use Zend\Stdlib\ArrayObject;

class TeamStaff extends TableGatewayManager
{
    protected $table = DbTables::TBL_TEAM_STAFF;
    public function __construct($sm)
    {
        parent::__construct($sm, 'DDD\Domain\Team\TeamStaff');
    }

    /**
     * @param int $teamId
     * @return \DDD\Domain\Team\TeamStaff[]
     */
    public function getTeamMemberList($teamId)
    {
        return $this->fetchAll(function (Select $select) use($teamId){
            $select
                ->join(
                    ['team' => DbTables::TBL_TEAMS],
                    $this->getTable() . '.team_id = team.id',
                    [],
                    Select::JOIN_LEFT
                )
                ->join(
                    ['user' => DbTables::TBL_BACKOFFICE_USERS],
                    $this->getTable() . '.user_id = user.id',
                    ['firstname', 'lastname', 'avatar'],
                    Select::JOIN_LEFT
                )
                ->where($this->getTable() . '.team_id =' . $teamId)
                ->where($this->getTable() . '.type =' . TeamService::STAFF_MEMBER);
        });
    }

    /**
     * @param int $teamId
     * @return \DDD\Domain\Team\TeamStaff[]
     */
    public function getTeamOfficerList($teamId)
    {
        return $this->fetchAll(function (Select $select) use($teamId){
            $select
                ->join(
                    ['team' => DbTables::TBL_TEAMS],
                    $this->getTable() . '.team_id = team.id',
                    [],
                    Select::JOIN_LEFT
                )
                ->join(
                    ['user' => DbTables::TBL_BACKOFFICE_USERS],
                    $this->getTable() . '.user_id = user.id',
                    ['firstname', 'lastname', 'avatar'],
                    Select::JOIN_LEFT
                )
                ->where($this->getTable() . '.team_id =' . $teamId)
                ->where($this->getTable() . '.type =' . TeamService::STAFF_OFFICER);
        });
    }

    /**
     * @param int $teamId
     * @return \Zend\Db\ResultSet\ResultSet|\DDD\Domain\Team\TeamStaff[]
     */
    public function getTeamManagerAndOfficerList($teamId)
    {
        return $this->fetchAll(function (Select $select) use ($teamId) {
            $select->columns(['user_id']);
            $select->where
                ->equalTo('team_id', $teamId)
                ->in('type', [TeamService::STAFF_MANAGER, TeamService::STAFF_OFFICER]);
        });
    }

    /**
     * @param int $teamId
     * @return \DDD\Domain\Team\TeamStaff[]
     */
    public function getTeamManagerList($teamId)
    {
        return $this->fetchAll(function (Select $select) use($teamId){
            $select
                ->join(
                    ['team' => DbTables::TBL_TEAMS],
                    $this->getTable() . '.team_id = team.id',
                    [],
                    Select::JOIN_LEFT
                )
                ->join(
                    ['user' => DbTables::TBL_BACKOFFICE_USERS],
                    $this->getTable() . '.user_id = user.id',
                    ['firstname', 'lastname'],
                    Select::JOIN_LEFT
                )
                ->where($this->getTable() . '.team_id =' . $teamId)
                ->where($this->getTable() . '.type =' . TeamService::STAFF_MANAGER);
        });
    }



    public function getTeamCreator($teamId)
    {
        return $this->fetchOne(function (Select $select) use($teamId){
            $select
                ->join(
                    ['team' => DbTables::TBL_TEAMS],
                    $this->getTable() . '.team_id = team.id',
                    [],
                    Select::JOIN_LEFT
                )
                ->join(
                    ['user' => DbTables::TBL_BACKOFFICE_USERS],
                    $this->getTable() . '.user_id = user.id',
                    ['firstname', 'lastname'],
                    Select::JOIN_LEFT
                )
                ->where($this->getTable() . '.team_id =' . $teamId)
                ->where($this->getTable() . '.type =' . TeamService::STAFF_CREATOR);
        });
    }

    public function getTeamDirector($teamId)
    {
        return $this->fetchOne(function (Select $select) use($teamId){
            $select
                ->join(
                    ['team' => DbTables::TBL_TEAMS],
                    $this->getTable() . '.team_id = team.id',
                    [],
                    Select::JOIN_LEFT
                )
                ->join(
                    ['user' => DbTables::TBL_BACKOFFICE_USERS],
                    $this->getTable() . '.user_id = user.id',
                    ['firstname', 'lastname'],
                    Select::JOIN_LEFT
                )
                ->where($this->getTable() . '.team_id =' . $teamId)
                ->where($this->getTable() . '.type =' . TeamService::STAFF_DIRECTOR);
        });
    }

    /**
     * @param $userId
     * @param $teamId
     * @return bool
     */
    public function isUserInTeam($userId, $teamId)
    {
        $result = $this->fetchOne(function (Select $select) use($userId, $teamId) {
            $where = new Where();
            $where
                ->equalTo('user_id', $userId)
                ->equalTo('team_id', $teamId)
                ->notIn('type', [TeamService::STAFF_CREATOR, TeamService::STAFF_DIRECTOR]);
            $select
                ->columns(['id'])
                ->where($where);
        });

        if ($result) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @param int $userId
     * @param int $teamId
     * @return int|bool
     */
    public function getUserPositionInTeam($userId, $teamId)
    {
        $result = $this->fetchAll(function (Select $select) use($userId, $teamId) {
            $where = new Where();
            $where
                ->equalTo('user_id', $userId)
                ->equalTo('team_id', $teamId)
                ->notIn('type', [TeamService::STAFF_CREATOR, TeamService::STAFF_DIRECTOR]);
            $select
                ->columns(['type'])
                ->where($where)
                ->order(['type' => 'DESC']);
        });
        return $result;
    }

    /**
     * @param int $userId
     * @param int $teamId
     * @param array $withRole
     * @return \DDD\Domain\Team\TeamStaff
     */
    public function isTeamStaff($userId, $teamId, $withRole = [])
    {
        $result = $this->fetchOne(function (Select $select) use($userId, $teamId, $withRole){
            $select->where
                ->equalTo('team_id', $teamId)
                ->and
                ->equalTo('user_id', $userId);

            if (!empty($withRole)) {
                $select->where
                    ->in('type', $withRole);
            }

            $select->order(['type DESC']);
        });

        return $result;
    }
}
