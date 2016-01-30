<?php

namespace DDD\Domain\Translation;

class UniversalView
{
    protected $id;
    protected $status;
    protected $type;
    protected $content;
    protected $count;
    protected $pageName;


    public function exchangeArray($data)
    {
        $this->id       = (isset($data['id'])) ? $data['id']               : null;
        $this->status   = (isset($data['status'])) ? $data['status']       : null;
        $this->type     = (isset($data['type'])) ? $data['type']           : null;
        $this->content  = (isset($data['content'])) ? $data['content']     : null;
        $this->count    = (isset($data['count'])) ? $data['count']         : null;
        $this->pageName = (isset($data['page_name'])) ? $data['page_name'] : null;
    }

    public function getCount() {
        return $this->count;
    }

    public function getId() {
        return $this->id;
    }

    public function getStatus() {
        return $this->status;
    }

    public function getType() {
        return $this->type;
    }

    public function getContent() {
        return $this->content;
    }

    public function getPageName() {
        return $this->pageName;
    }
}
