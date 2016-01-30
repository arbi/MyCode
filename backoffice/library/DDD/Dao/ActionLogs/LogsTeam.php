<?php

namespace DDD\Dao\ActionLogs;

use Zend\Db\Sql\Expression;
use Zend\Db\Sql\Select;
use Library\DbManager\TableGatewayManager;
use Library\Constants\DbTables;
use DDD\Service\Team\Team as TeamService;

class LogsTeam extends TableGatewayManager
{
    protected $table = DbTables::TBL_LOGS_TEAM;

    public function __construct($sm, $domain = '\ArrayObject')
    {
        parent::__construct($sm, $domain);
    }

    public function getUnresolvedCommentsForUser($uid)
    {
        $result = $this->fetchAll(function (Select $select) use ($uid) {
            $select->columns(['id', 'action_log_id']);
            $select->join(
                ['teams' => DbTables::TBL_TEAMS],
                $this->getTable() . '.team_id = teams.id',
                []
            );
            $select->join(
                ['team_members' => DbTables::TBL_TEAM_STAFF],
                new Expression('team_members.team_id = teams.id AND team_members.user_id = ' . $uid),
                []
            );
            $select->join(
                ['logs' => DbTables::TBL_ACTION_LOGS],
                'logs.id = ' . $this->getTable() . '.action_log_id',
                [
                    'date' => 'timestamp',
                    'view_message' => 'value',
                ]
            );

            $select->join(
                ['reservations' => DbTables::TBL_BOOKINGS],
                'logs.identity_id = reservations.id',
                ['res_number']
            );
            $select->group($this->getTable() . '.action_log_id');
            $select->where->notIn('team_members.type', [TeamService::STAFF_DIRECTOR, TeamService::STAFF_CREATOR]);
            $select->order('date DESC');
        });

        return $result;
    }

    public function getUnresolvedCommentsCountForUser($uid)
    {
        $result = $this->fetchOne(function (Select $select) use ($uid) {
            $select->columns(['count' => new Expression('COUNT(DISTINCT action_log_id)')]);
            $select->join(
                ['teams' => DbTables::TBL_TEAMS],
                $this->getTable() . '.team_id = teams.id',
                []
            );
            $select->join(
                ['team_members' => DbTables::TBL_TEAM_STAFF],
                new Expression('team_members.team_id = teams.id AND team_members.user_id = ' . $uid),
                []
            );
            $select->where->notIn('team_members.type', [TeamService::STAFF_DIRECTOR, TeamService::STAFF_CREATOR]);
        });
        return $result['count'];
    }
}
