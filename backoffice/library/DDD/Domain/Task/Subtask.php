<?php

namespace DDD\Domain\Task;

class Subtask
{
    protected $id;
    protected $description;
    protected $status;

    public function exchangeArray($data)
    {
        $this->description = (isset($data['description']))? $data['description']: null;
        $this->status = (isset($data['status']))? $data['status']: null;
        $this->id = (isset($data['id']))? $data['id']: null;
    }

    public function getDescription()
    {
        return $this->description;
    }

    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }
}