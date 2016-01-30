<?php

namespace DDD\Domain\Translation;

class GetLang
{
    protected $id;
    protected $content;
    protected $type_id;
    
    
    public function exchangeArray($data)
    {
        $this->id     = (isset($data['id'])) ? $data['id'] : null;
        $this->content = (isset($data['content'])) ? $data['content'] : null;
        $this->type_id = (isset($data['page_id'])) ? $data['page_id'] : null;
    }
    
    public function getContent() {
            return $this->content;
    }
    
    public function getId() {
            return $this->id;
    }
    
    public function getTypeId() {
            return $this->type_id;
    }
}