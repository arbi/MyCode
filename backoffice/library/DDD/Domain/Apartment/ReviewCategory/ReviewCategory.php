<?php

namespace DDD\Domain\Apartment\ReviewCategory;

class ReviewCategory
{
    /**
     *
     * @var int 
     */
   protected $id;
   /**
    *
    * @var string 
    */
   protected $name;
   /**
    *
    * @var int 
    */
   protected $type;
   
   public function exchangeArray($data) {
        $this->id = (isset($data['id'])) ? $data['id'] : null;
        $this->name = (isset($data['name'])) ? $data['name'] : null;
        $this->type = (isset($data['type'])) ? $data['type'] : null;
    }
    
    /**
     * 
     * @return int
     */
    public function getId() {
        return $this->id;
    }
    
    /**
     * 
     * @return string
     */
    public function getName() {
        return $this->name;
    }
    
    /**
     * 
     * @return int
     */
    public function getType() {
        return $this->type;
    }
}