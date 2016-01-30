<?php

namespace DDD\Domain\Task;

class Type
{
    protected $id;
    protected $name;
    protected $group;
    protected $order;
    protected $associatedTeamId;
    protected $subtasks;
    protected $autoVerifiable;

    public function exchangeArray($data)
    {
        $this->id = (isset($data['id']))? $data['id']: null;
        $this->name   = (isset($data['name']))? $data['name']: null;
        $this->group   = (isset($data['group']))? $data['group']: null;
        $this->order = (isset($data['order']))? $data['order']: null;
        $this->associatedTeamId = (isset($data['associated_team_id']))? $data['associated_team_id']: null;
        $this->subtasks = (isset($data['default_subtasks']))? $data['default_subtasks']: null;
        $this->autoVerifiable = (isset($data['auto_verifiable']))? $data['auto_verifiable']: null;
    }

    public function getId() {
        return $this->id;
    }

    public function getName() {
        return $this->name;
    }

    public function getGroup() {
        return $this->group;
    }

    public function getOrder() {
        return $this->order;
    }

    public function getAssociatedTeamId() {
        return $this->associatedTeamId;
    }

    public function getSubtasks() {
        return $this->subtasks;
    }

    public function isAutoVerifiable()
    {
        return $this->autoVerifiable;
    }
}