<?php

namespace DDD\Domain\User;

class UserGroups
{
    protected $id;
    protected $user_id;
    protected $group_id;
    protected $name;
    protected $type;
    protected $parent_id;
    
    
    public function exchangeArray($data)
    {
        $this->id     = (isset($data['id'])) ? $data['id'] : null;
        $this->user_id = (isset($data['user_id'])) ? $data['user_id'] : null;
        $this->group_id  = (isset($data['group_id'])) ? $data['group_id'] : null;
        $this->name = (isset($data['name'])) ? $data['name'] : null;
        $this->type = (isset($data['type'])) ? $data['type'] : null;
        $this->parent_id   = (isset($data['parent_id'])) ? $data['parent_id'] : null;
    }

    public function getParentId() {
        return $this->parent_id;
    }

    public function getType() {
        return $this->type;
    }

    public function getName() {
            return $this->name;
    }
    
    public function setName($val) {
            $this->name = $val;
            return $this;
    }
    
    public function getGroup_id() {
            return $this->group_id;
    }
    
    public function setGroup_id($val) {
            $this->group_id = $val;
            return $this;
    }

    public function getUserId()
    {
        return $this->user_id;
    }
    
    public function getId() {
            return $this->id;
    }
    
    public function setId($val) {
            $this->id = $val;
            return $this;
    }
}
