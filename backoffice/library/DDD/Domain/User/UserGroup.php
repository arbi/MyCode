<?php

namespace DDD\Domain\User;

class UserGroup
{
    protected $id;
    protected $name;
    protected $description;
    protected $type;
    protected $parent_id;

    
    public function exchangeArray($data)
    {
        $this->id          = (isset($data['id'])) ? $data['id'] : null;
        $this->name        = (isset($data['name'])) ? $data['name'] : null;
        $this->description = (isset($data['description'])) ? $data['description'] : null;
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
    
    public function getDescription() {
            return $this->description;
    }
    
    public function setDescription($val) {
            $this->description = $val;
            return $this;
    }
    
    public function getId() {
            return $this->id;
    }
    
    public function setId($val) {
            $this->id = $val;
            return $this;
    }
}
