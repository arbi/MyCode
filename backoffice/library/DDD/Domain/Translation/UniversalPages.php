<?php

namespace DDD\Domain\Translation;

class UniversalPages
{
    protected $id;
    protected $name;
    
    public function exchangeArray($data)
    {
        $this->id     = (isset($data['id'])) ? $data['id'] : null;
        $this->name = (isset($data['name'])) ? $data['name'] : null;
    }
    
    public function getName() {
            return $this->name;
    }
    
    public function getId() {
            return $this->id;
    }
}