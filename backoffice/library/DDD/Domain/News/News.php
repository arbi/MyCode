<?php

namespace DDD\Domain\News;

class News
{
    protected $id;
    protected $en_title;
    protected $en;
    protected $date;
    protected $slug;
    
    public function exchangeArray($data)
    {
        $this->id     = (isset($data['id'])) ? $data['id'] : null;
        $this->en_title = (isset($data['en_title'])) ? $data['en_title'] : null;
        $this->en = (isset($data['en'])) ? $data['en'] : null;
        $this->date = (isset($data['date'])) ? $data['date'] : null;
        $this->slug = (isset($data['slug'])) ? $data['slug'] : null;
    }
    
    public function getDate() {
            return $this->date;
    }
    
    public function setDate($val) {
            $this->date = $val;
            return $this;
    }
    
    public function getEn() {
            return $this->en;
    }
    
    public function setEn($val) {
            $this->en = $val;
            return $this;
    }
    
    public function getEn_title() {
            return $this->en_title;
    }
    
    public function setEn_title($val) {
            $this->en_title = $val;
            return $this;
    }
    
    public function getId() {
            return $this->id;
    }
    
    public function setId($val) {
            $this->id = $val;
            return $this;
    }

    public function getSlug() {
            return $this->slug;
    }
    
    public function setSlug($val) {
            $this->slug = $val;
            return $this;
    }
    
}
