<?php

namespace DDD\Domain\Task;

class Attachments
{
    private $id;
    private $taskId;
    private $file;
    private $path;

    public function exchangeArray($data)
    {
        $this->id       = (isset($data['id']))? $data['id']: null;
        $this->taskId   = (isset($data['task_id']))? $data['task_id']: null;
        $this->file     = (isset($data['file']))? $data['file']: null;
        $this->path     = (isset($data['path']))? $data['path']: null;
    }

    public function getId() {
        return $this->id;
    }

    public function getTaskId() {
        return $this->taskId;
    }

    public function getFile() {
        return $this->file;
    }

    public function getPath() {
        return $this->path;
    }
}