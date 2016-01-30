<?php

namespace DDD\Domain\Task;

class Minimal
{
    protected $id;
    protected $priority;
    protected $title;

    public function exchangeArray($data)
    {
        $this->id       = (isset($data['id']))? $data['id']: null;
        $this->priority = (isset($data['priority']))? $data['priority']: null;
        $this->title    = (isset($data['title']))? $data['title']: null;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return int
     */
    public function getPriority()
    {
        return $this->priority;
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }
}