<?php
namespace DDD\Dao\Team;

use Library\DbManager\TableGatewayManager;
use Library\Constants\Constants;
use Library\Utility\Debug;
use Zend\Db\Sql\Select;
use Zend\Db\Sql\Expression;
use Library\Constants\DbTables;
use Library\Constants\Roles;

use \DDD\Service\Team\Team as TeamService;

class TeamFrontierBuildings extends TableGatewayManager
{
    protected $table = DbTables::TBL_TEAM_FRONTIER_BUILDINGS;
    public function __construct($sm)
    {
        parent::__construct($sm, 'DDD\Domain\Team\TeamFrontierBuildings');
    }

    public function getFrontierTeamBuildings($teamId)
    {
        return $this->fetchAll(function (Select $select) use($teamId){
            $select
                ->join(
                    ['buildings' => DbTables::TBL_APARTMENT_GROUPS],
                    new Expression($this->getTable() . '.building_id = buildings.id AND buildings.usage_building = 1'),
                    ['building_name' => 'name'],
                    Select::JOIN_LEFT
                )
                ->where($this->getTable() . '.team_id =' . $teamId);
        });
    }
}
