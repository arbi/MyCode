<?php
namespace DDD\Dao\Team;

use Library\DbManager\TableGatewayManager;
use Library\Constants\Constants;
use Library\Utility\Debug;
use Zend\Db\Sql\Select;
use Zend\Db\Sql\Expression;
use Library\Constants\DbTables;
use Library\Constants\Roles;

use DDD\Service\Team\Team as TeamService;
USE DDD\Service\Task as TaskService;

class TeamFrontierApartments extends TableGatewayManager
{
    protected $table = DbTables::TBL_TEAM_FRONTIER_APARTMENTS;
    public function __construct($sm)
    {
        parent::__construct($sm, 'DDD\Domain\Team\TeamFrontierApartments');
    }

    public function getFrontierTeamApartments($teamId)
    {
        return $this->fetchAll(function (Select $select) use($teamId){
            $select
                ->join(
                    ['apartments' => DbTables::TBL_APARTMENTS],
                    $this->getTable() . '.apartment_id = apartments.id',
                    ['apartment_name' => 'name'],
                    Select::JOIN_LEFT
                )
                ->where($this->getTable() . '.team_id =' . $teamId);
        });
    }

    public function getExtraInspectionTask()
    {
        $this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());
        return $this->fetchAll(function (Select $select) {
            $select
                ->join(
                    ['apartments' => DbTables::TBL_APARTMENTS],
                    new Expression($this->getTable() . '.apartment_id = apartments.id'),
                    ['building_id']
                )->join(
                    ['city' => DbTables::TBL_CITIES],
                    'city.id = apartments.city_id',
                    ['timezone']
                )->join(
                    ['team' => DbTables::TBL_TEAMS],
                    new Expression($this->getTable() . '.team_id = team.id AND team.extra_inspection = 1'),
                    []
                )->join(
                    ['res' => DbTables::TBL_BOOKINGS],
                    new Expression($this->getTable() . '.apartment_id = res.apartment_id_assigned AND res.status = 1 AND date_to = (SELECT r.date_to from ga_reservations AS r where r.status = 1 AND ' . $this->getTable() . '.apartment_id = r.apartment_id_assigned order by r.date_to desc limit 1)'),
                    [
                        'res_id' => 'id',
                        'date_to',
                        'pin',
                        'outside_door_code'
                    ]
                );
        });
    }

    public function checkDuplicateApartment($teamId, $apartmentId)
    {
        $this->resultSetPrototype->setArrayObjectPrototype(new \ArrayObject());

        return $this->fetchOne(function (Select $select) use ($teamId, $apartmentId) {
            $select->join(
                ['team' => DbTables::TBL_TEAMS],
                new Expression($this->getTable() . '.team_id = team.id'),
                []
            );

            $select->where
                ->notEqualTo('team.id', $teamId)
                ->equalTo($this->getTable() . '.apartment_id', $apartmentId)
                ->equalTo('team.is_disable', TeamService::IS_ACTIVE_TEAM)
                ->equalTo('team.usage_frontier', TeamService::IS_FRONTIER_TEAM);
        });
    }
}
