<?php

namespace DDD\Domain\Translation;

class Edit
{
    protected $id;
    protected $content;
    protected $type;
    protected $type_id;
    protected $other;
    protected $description;

    public function exchangeArray($data)
    {
        $this->id          = (isset($data['id'])) ? $data['id']           : null;
        $this->content     = (isset($data['content'])) ? $data['content'] : null;
        $this->other       = (isset($data['other'])) ? $data['other']     : null;
        $this->type        = (isset($data['type'])) ? $data['type']       : null;
        $this->type_id     = (isset($data['type_id'])) ? $data['type_id'] : null;
        $this->description = (isset($data['description'])) ? $data['description'] : null;
    }

    public function getType() {
            return $this->type;
    }

    public function getTypeId() {
            return $this->type_id;
    }

    public function getOther() {
            return $this->other;
    }

    public function getContent() {
            return $this->content;
    }

    public function getId() {
            return $this->id;
    }

    public function getDescription() {
        return $this->description;
    }
}
