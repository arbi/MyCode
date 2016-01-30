<?php

namespace DDD\Domain\Task;

class Task
{
    protected $id;
    protected $creator_id;
    protected $creatorAvatar;
    protected $responsible_id;
    protected $responsibleAvatar;
    protected $verifierId;
    protected $verifierName;
    protected $verifierAvatar;
    protected $helper_id;
    protected $helper_name;
    protected $creation_date;
    protected $startDate;
    protected $endDate;
    protected $done_date;
    protected $task_status;
    protected $priority;
    protected $relatedTask;
    protected $resId;
    protected $resNumber;
    protected $taskType;
    protected $taskTypeId;
    protected $description;
    protected $comments;
    protected $creator_name;
    protected $responsible_name;
    protected $property_id;
    protected $property_name;
    protected $buildingId;
    protected $buildingName;
    protected $url;
    protected $title;
    protected $count;
    protected $teamId;
    protected $followingTeamId;
    protected $teamName;
    protected $apartment_group;
    protected $arrivalStatus;
    protected $apartment_name;
    protected $apartmentUnitNumber;
    protected $lastUpdateTime;
    protected $timezone;
    protected $reservationDateFrom;
    protected $city;
    protected $subtaskDescription;

    public function exchangeArray($data)
    {
        $this->id                  = (isset($data['id']))? $data['id']: null;
        $this->creator_id          = (isset($data['creator_id']))? $data['creator_id']: null;
        $this->creator_name        = (isset($data['creator_name']))? $data['creator_name']: null;
        $this->creatorAvatar       = (isset($data['creator_avatar']))? $data['creator_avatar']: null;
        $this->responsible_id      = (isset($data['responsible_id']))? $data['responsible_id']: null;
        $this->responsible_name    = (isset($data['responsible_name']))? $data['responsible_name']: null;
        $this->responsibleAvatar   = (isset($data['responsible_avatar']))? $data['responsible_avatar']: null;
        $this->verifierId          = (isset($data['verifier_id']))? $data['verifier_id']: null;
        $this->verifierName        = (isset($data['verifier_name']))? $data['verifier_name']: null;
        $this->verifierAvatar      = (isset($data['verifier_avatar']))? $data['verifier_avatar']: null;
        $this->helper_id           = (isset($data['helper_id']))? $data['helper_id']: null;
        $this->creation_date       = (isset($data['creation_date']))? $data['creation_date']: null;
        $this->startDate           = (isset($data['start_date']))? $data['start_date']: null;
        $this->endDate             = (isset($data['end_date']))? $data['end_date']: null;
        $this->done_date           = (isset($data['done_date']))? $data['done_date']: null;
        $this->task_status         = (isset($data['task_status']))? $data['task_status']: null;
        $this->priority            = (isset($data['priority']))? $data['priority']: null;
        $this->relatedTask         = (isset($data['related_task']))? $data['related_task']: null;
        $this->resId               = (isset($data['res_id']))? $data['res_id']: null;
        $this->resNumber           = (isset($data['res_number']))? $data['res_number']: null;
        $this->taskType            = (isset($data['task_type_name']))? $data['task_type_name']: null;
        $this->taskTypeId          = (isset($data['task_type']))? $data['task_type']: null;
        $this->description         = (isset($data['description']))? $data['description']: null;
        $this->comments            = (isset($data['comments']))? $data['comments']: null;
        $this->property_id         = (isset($data['property_id']))? $data['property_id']: null;
        $this->property_name       = (isset($data['property_name']))? $data['property_name']: null;
        $this->buildingId          = (isset($data['building_id']))? $data['building_id']: null;
        $this->buildingName        = (isset($data['building_name']))? $data['building_name']: null;
        $this->helper_name         = (isset($data['helper_name']))? $data['helper_name']: null;
        $this->url                 = (isset($data['url']))? $data['url']: null;
        $this->title               = (isset($data['title']))? $data['title']: null;
        $this->count               = (isset($data['count']))? $data['count']: null;
        $this->teamId              = (isset($data['team_id']))? $data['team_id']: null;
        $this->followingTeamId     = (isset($data['following_team_id']))? $data['following_team_id']: null;
        $this->teamName            = (isset($data['team_name']))? $data['team_name']: null;
        $this->apartment_group     = (isset($data['apartment_group']))? $data['apartment_group']: null;
        $this->arrivalStatus       = (isset($data['arrival_status']))? $data['arrival_status']: null;
        $this->apartment_name      = (isset($data['apartment_name']))? $data['apartment_name']: null;
        $this->apartmentUnitNumber = (isset($data['apartment_unit_number']))? $data['apartment_unit_number']: null;
        $this->timezone            = (isset($data['timezone']))? $data['timezone']: null;
        $this->reservationDateFrom = (isset($data['res_date_from']))? $data['res_date_from']: null;
        $this->lastUpdateTime      = (isset($data['last_update_time']))? $data['last_update_time']: null;
        $this->city                = (isset($data['city']))? $data['city']: null;
        $this->subtaskDescription  = (isset($data['subtask_description']))? $data['subtask_description']: null;
    }

    public function getUnit_number()
    {
        return $this->apartmentUnitNumber;
    }

    public function getApartmentName()
    {
        return $this->apartment_name;
    }

    public function getApartmentGroup()
    {
        return $this->apartment_group;
    }

    public function getTeamId()
    {
        return $this->teamId;
    }

    public function getFollowingTeamId()
    {
        return $this->followingTeamId;
    }

    public function getTeamName()
    {
        return $this->teamName;
    }

    public function getCount()
    {
        return $this->count;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function getUrl()
    {
        return $this->url;
    }

    public function getHelper_name()
    {
        return $this->helper_name;
    }

    public function getProperty_name()
    {
        return $this->property_name;
    }

    public function getProperty_id()
    {
        return $this->property_id;
    }

    public function getBuildingName()
    {
        return $this->buildingName;
    }

    public function getBuildingId()
    {
        return $this->buildingId;
    }

    public function getResponsibleName()
    {
        return $this->responsible_name;
    }

    public function getResponsibleAvatar()
    {
        return $this->responsibleAvatar;
    }

    public function getVerifierId()
    {
        return $this->verifierId;
    }

    public function getVerifierName()
    {
        return $this->verifierName;
    }

    public function getVerifierAvatar()
    {
        return $this->verifierAvatar;
    }

    public function getCreatorName()
    {
        return $this->creator_name;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getCreatorId()
    {
        return $this->creator_id;
    }

    public function getCreatorAvatar()
    {
        return $this->creatorAvatar;
    }

    public function getResponsibleId()
    {
        return $this->responsible_id;
    }
    public function getHelper_id()
    {
        return $this->helper_id;
    }

    public function getCreation_date()
    {
        return $this->creation_date;
    }

    public function getStartDate()
    {
        return $this->startDate;
    }

    public function getEndDate()
    {
        return $this->endDate;
    }

    public function getDone_date()
    {
        return $this->done_date;
    }

    public function getTask_status()
    {
        return $this->task_status;
    }

    public function getPriority()
    {
        return $this->priority;
    }

    public function getRelatedTask()
    {
        return $this->relatedTask;
    }

    public function getResId()
    {
        return $this->resId;
    }

    public function getTaskType()
    {
        return $this->taskType;
    }

    public function getTaskTypeId()
    {
        return $this->taskTypeId;
    }

    public function getDescription()
    {
        return $this->description;
    }

    public function getComments()
    {
        return $this->comments;
    }

    public function getResNumber()
    {
        return $this->resNumber;
    }

    public function getArrivalStatus()
    {
        return $this->arrivalStatus;
    }

    public function getLastUpdateTime()
    {
        return $this->lastUpdateTime;
    }

    /**
     * @return mixed
     */
    public function getTimezone()
    {
        return $this->timezone;
    }

    /**
     * @return mixed
     */
    public function getReservationDateFrom()
    {
        return $this->reservationDateFrom;
    }

    public function getCity()
    {
        return $this->city;
    }

    public function getSubtaskDescription()
    {
        return $this->subtaskDescription;
    }
}