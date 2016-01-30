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

class TeamFrontierApartments extends ServiceBase
{
    protected $id;
    protected $apartmentId;
    protected $apartmentName;
    protected $teamId;

    public function exchangeArray($data)
    {
        $this->id          = (isset($data['id'])) ?
            $data['id'] : null;
        $this->apartmentId = (isset($data['apartment_id'])) ?
            $data['apartment_id'] : null;
        $this->apartmentName = (isset($data['apartment_name'])) ?
            $data['apartment_name'] : null;
        $this->teamId = (isset($data['team_id'])) ?
            $data['team_id'] : null;

    }

    public function getId() {
        return $this->id;
    }

    public function getApartmentId() {
        return $this->apartmentId;
    }

    public function getApartmentName() {
        return $this->apartmentName;
    }

    public function getTeamId() {
        return $this->teamId;
    }
}
