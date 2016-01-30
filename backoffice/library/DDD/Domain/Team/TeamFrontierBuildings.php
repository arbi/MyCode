<?php

namespace DDD\Domain\Team;

use DDD\Dao\ApartmentGroup\ApartmentGroup as ApartmentGroupDAO;
use DDD\Dao\ApartmentGroup\ApartmentGroupItems as ApartmentGroupItemsDAO;
use DDD\Dao\ApartmentGroup\ConciergeView;
use DDD\Domain\ApartmentGroup\ApartmentGroup as ApartmentGroupDomain;
use DDD\Dao\User\UserManager;
use DDD\Service\ServiceBase;
use Library\ActionLogger\Logger;
use Library\Authentication\BackofficeAuthenticationService;
use Library\Constants\Objects;
use Library\Utility\Debug;
use Library\Utility\Helper;
use Zend\Db\Sql\Where;
use Zend\Db\Sql\Expression;
use Library\Constants\DbTables;

class TeamFrontierBuildings extends ServiceBase
{
    protected $id;
    protected $buildingId;
    protected $buildingName;
    protected $teamId;

    public function exchangeArray($data)
    {
        $this->id          = (isset($data['id'])) ?
            $data['id'] : null;
        $this->buildingId = (isset($data['building_id'])) ?
            $data['building_id'] : null;
        $this->buildingName = (isset($data['building_name'])) ?
            $data['building_name'] : null;
        $this->teamId = (isset($data['team_id'])) ?
            $data['team_id'] : null;

    }

    public function getId() {
        return $this->id;
    }

    public function getBuildingId() {
        return $this->buildingId;
    }

    public function getBuildingName() {
        return $this->buildingName;
    }

    public function getTeamId() {
        return $this->teamId;
    }
}
