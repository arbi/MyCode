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

class TeamStaff extends ServiceBase
{
    protected $id;
    protected $firstname;
    protected $lastname;
    protected $userId;
    protected $teamId;
    protected $type;

    /**
     * @var string|null $avatar
     */
    protected $avatar;

    public function exchangeArray($data)
    {
        $this->id        = (isset($data['id'])) ? $data['id'] : null;
        $this->firstname = (isset($data['firstname'])) ? $data['firstname'] : null;
        $this->lastname  = (isset($data['lastname'])) ? $data['lastname'] : null;
        $this->userId    = (isset($data['user_id'])) ? $data['user_id'] : null;
        $this->teamId    = (isset($data['team_id'])) ? $data['team_id'] : null;
        $this->type      = (isset($data['type'])) ? $data['type'] : null;
        $this->avatar    = (isset($data['avatar'])) ? $data['avatar'] : null;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getFirstName()
    {
        return $this->firstname;
    }

    public function getLastName()
    {
        return $this->lastname;
    }

    public function getUserId()
    {
        return $this->userId;
    }

    public function setUserId($userId)
    {
        $this->userId = $userId;
        return $this;
    }

    public function getTeamId()
    {
        return $this->teamId;
    }

    public function getType()
    {
        return $this->type;
    }

    public function getFullName()
    {
        return $this->firstname . ' ' . $this->lastname;
    }

    /**
     * @return null|string
     */
    public function getAvatar()
    {
        return $this->avatar;
    }
}
