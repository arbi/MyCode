<?php

namespace DDD\Domain\Translation;

class ProductView
{
    protected $id;
    protected $status;
    protected $type;
    protected $content;
    protected $count;
    protected $descr_id;
    protected $location_id;
    protected $entityName;
    
    public function exchangeArray($data)
    {
        $this->id     = (isset($data['id'])) ? $data['id'] : null;
        $this->status = (isset($data['status'])) ? $data['status'] : null;
        $this->type = (isset($data['type'])) ? $data['type'] : null;
        $this->content = (isset($data['content'])) ? $data['content'] : null;
        $this->count = (isset($data['count'])) ? $data['count'] : null;
        $this->descr_id = (isset($data['descr_id'])) ? $data['descr_id'] : null;
        $this->location_id = (isset($data['location_id'])) ? $data['location_id'] : null;
        $this->entityName = (isset($data['entity_name'])) ? $data['entity_name'] : null;
    }

    public function getLocation_id() {
            return $this->location_id;
    }
    
    public function getDescr_id() {
            return $this->descr_id;
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

    /**
     * @return string | null
     */
    public function getEntityName()
    {
        return $this->entityName;
    }
}