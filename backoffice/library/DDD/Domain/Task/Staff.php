<?php

namespace DDD\Domain\Task;

class Staff
{
    protected $id;
    protected $userId;
    protected $name;
    protected $type;
    protected $avatar;
    protected $taskId;
    protected $taskStatus;
    protected $startDate;

    public function exchangeArray($data)
    {
        $this->id         = (isset($data['id']))? $data['id']: null;
        $this->userId     = (isset($data['user_id']))? $data['user_id']: null;
        $this->type       = (isset($data['type']))? $data['type']: null;
        $this->name       = (isset($data['name']))? $data['name']: null;
        $this->avatar     = (isset($data['avatar']))? $data['avatar']: null;
        $this->taskId     = (isset($data['task_id']))? $data['task_id']: null;
        $this->taskStatus = (isset($data['task_status']))? $data['task_status']: null;
        $this->startDate  = (isset($data['start_date']))? $data['start_date']: null;
    }

    public function getIId() {
        return $this->id;
    }

    public function getId() {
        return $this->userId;
    }

    public function getType() {
        return $this->type;
    }

    public function getAvatar() {
        return $this->avatar;
    }

    public function getName() {
        return $this->name;
    }

    public function getTaskId() {
        return $this->taskId;
    }

    public function getTaskStatus() {
        return $this->taskStatus;
    }

    public function getStartDate() {
        return $this->startDate;
    }
}