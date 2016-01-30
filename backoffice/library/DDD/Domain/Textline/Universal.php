<?php

namespace DDD\Domain\Textline;

class Universal
{
    protected $id;
    protected $en;
    protected $enClean;

    public function exchangeArray($data)
    {
        $this->id      = (isset($data['id']))            ? $data['id']               : null;
        $this->en      = (isset($data['en']))            ? $data['en']               : null;
        $this->enClean = (isset($data['en_html_clean'])) ? $data['en_html_clean']               : null;
    }
    
    public function getId(){
        return $this->id;
    }
   
    public function getEn(){
        return $this->en;
    }
    public function getEnClean(){
        return $this->enClean;
    }
    
}