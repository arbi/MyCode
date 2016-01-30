<?php

namespace DDD\Domain\User;

class UserSchedule
{
    protected $id;
    protected $firstname;
    protected $lastname;
    protected $manager_id;
    
    public function exchangeArray($data)
    {
        $this->id     = (isset($data['id'])) ? $data['id'] : null;
        $this->firstname  = (isset($data['firstname'])) ? $data['firstname'] : null;
        $this->lastname  = (isset($data['lastname'])) ? $data['lastname'] : null;
        $this->manager_id = (isset($data['manager_id'])) ? $data['manager_id'] : null;
    }

    public function getManager_id() {
        return $this->manager_id;
    }

    public function getId() {
        return $this->id;
    }

    public function getFirstName() {
        return $this->firstname;
    }

    public function getLastName() {
        return $this->lastname;
    }


}
