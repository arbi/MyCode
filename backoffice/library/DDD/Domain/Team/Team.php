<?php

namespace DDD\Domain\Team;

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

class Team extends ServiceBase
{
    protected $id;
    protected $name;
    protected $creatorId;
    protected $createdDate;
    protected $modifiedDate;
    protected $firstname;
    protected $lastname;
    protected $count;
    protected $description;
    protected $isDepartment;
    protected $isCommentNotifiable;
    protected $isFrontier;
    protected $isTaskable;
    protected $usageSecurity;
    protected $usageHiring;
    protected $usageStorage;
    protected $teamDirectorId;
    protected $staffType;
    protected $isDisabled;
    protected $userName;
    protected $creatorName;
    protected $isPermanent;
    protected $extraInspection;
    protected $timezone;

    public function exchangeArray($data)
    {
        $this->id                  = (isset($data['id'])) ? $data['id'] : null;
        $this->name                = (isset($data['name'])) ? $data['name'] : null;
        $this->creatorId           = (isset($data['creator_id'])) ? $data['creator_id'] : null;
        $this->createdDate         = (isset($data['created_date'])) ? $data['created_date'] : null;
        $this->firstname           = (isset($data['firstname'])) ? $data['firstname'] : null;
        $this->lastname            = (isset($data['lastname'])) ? $data['lastname'] : null;
        $this->count               = (isset($data['count'])) ? $data['count'] : null;
        $this->description         = (isset($data['description'])) ? $data['description'] : null;
        $this->modifiedDate        = (isset($data['modified_date'])) ? $data['modified_date'] : null;
        $this->isDepartment        = (isset($data['usage_department'])) ? $data['usage_department'] : null;
        $this->isCommentNotifiable = (isset($data['usage_notifiable'])) ? $data['usage_notifiable'] : null;
        $this->isFrontier          = (isset($data['usage_frontier'])) ? $data['usage_frontier'] : null;
        $this->isTaskable          = (isset($data['usage_taskable'])) ? $data['usage_taskable'] : null;
        $this->usageSecurity       = (isset($data['usage_security'])) ? $data['usage_security'] : null;
        $this->usageHiring         = (isset($data['usage_hiring'])) ? $data['usage_hiring'] : null;
        $this->usageStorage        = (isset($data['usage_storage'])) ? $data['usage_storage'] : null;
        $this->teamDirectorId      = (isset($data['director_id'])) ? $data['director_id'] : null;
        $this->isDisabled          = (isset($data['is_disable'])) ? $data['is_disable'] : null;
        $this->userName            = (isset($data['user_name'])) ? $data['user_name'] : null;
        $this->creatorName         = (isset($data['creator_name'])) ? $data['creator_name'] : null;
        $this->staffType           = (isset($data['staff_type'])) ? $data['staff_type'] : null;
        $this->isPermanent         = (isset($data['is_permanent'])) ? $data['is_permanent'] : null;
        $this->extraInspection     = (isset($data['extra_inspection'])) ? $data['extra_inspection'] : null;
        $this->timezone            = (isset($data['timezone'])) ? $data['timezone'] : null;
    }

    public function getId()
    {
        return $this->id;
    }

    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    public function getCreatorId()
    {
        return $this->creatorId;
    }

    public function setCreatorId($creatorId)
    {
        $this->creatorId = $creatorId;
        return $this;
    }

    public function getCreatedDate()
    {
        return $this->createdDate;
    }

    public function setCreatedDate($createdDate)
    {
        $this->createdDate = $createdDate;
        return $this;
    }

    public function getFirstName()
    {
        return $this->firstname;
    }

    public function getLastName()
    {
        return $this->lastname;
    }

    public function getCount()
    {
        return $this->count;
    }


    public function getDescription()
    {
        return $this->description;
    }

    public function setDescription($description)
    {
        $this->description = $description;
        return $this;
    }

    public function getTeamDirectorId()
    {
        return $this->teamDirectorId;
    }

    public function getModifiedDate()
    {
        return $this->modifiedDate;
    }

    public function setModifiedDate($modifiedDate)
    {
        $this->modifiedDate = $modifiedDate;
        return $this;
    }

    public function getIsDepartment()
    {
        return $this->isDepartment;
    }

    public function setIsDepartment($isDepartment)
    {
        $this->isDepartment = $isDepartment;
        return $this;
    }

    public function isCommentNotifiable()
    {
        return $this->isCommentNotifiable;
    }

    public function isFrontier()
    {
        return $this->isFrontier;
    }

    public function isTaskable()
    {
        return $this->isTaskable;
    }

    public function getIsDisabled()
    {
        return $this->isDisabled;
    }

    public function setIsDisabled($isDisabled)
    {
        $this->isDisabled = $isDisabled;
        return $this;
    }

    public function getUserName()
    {
        return $this->userName;
    }

    public function getCreatorName()
    {
        return $this->creatorName;
    }

    public function getStaffType()
    {
        return $this->staffType;
    }

    public function getUsageSecurity()
    {
        return $this->usageSecurity;
    }

    public function setUsageSecurity($usageSecurity)
    {
        $this->usageSecurity = $usageSecurity;
        return $this;
    }

    public function getUsageHiring()
    {
        return $this->usageHiring;
    }

    public function setUsageHiring($usageHiring)
    {
        $this->usageHiring = $usageHiring;
        return $this;
    }

    public function isPermanent()
    {
        return $this->isPermanent;
    }

    public function getExtraInspection()
    {
        return $this->extraInspection;
    }

    public function setExtraInspection($extraInspection)
    {
        $this->extraInspection = $extraInspection;
        return $this;
    }

    public function getTimezone()
    {
        return $this->timezone;
    }

    public function getUsageStorage()
    {
        return $this->usageStorage;
    }

    public function setUsageStorage($usageStorage)
    {
        $this->usageStorage = $usageStorage;
        return $this;
    }
}
