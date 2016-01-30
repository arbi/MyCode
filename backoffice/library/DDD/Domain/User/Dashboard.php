<?php

namespace DDD\Domain\User;

class Dashboard
{
	/**
	 * @var int
	 */
    protected $id;
    
    /**
     * @var string
     */
    protected $name;
    
    /**
     * @var boolean
     */
    protected $active;
    
    /**
     * @param array $data
     */
    public function exchangeArray($data) {
        $this->id     	= (isset($data['id'])) ? $data['id'] : null;
        $this->name 	= (isset($data['name'])) ? $data['name'] : null;
        $this->active	= (isset($data['active'])) ? $data['active'] : null;
    }

	/**
	 * @return number
	 */
    public function getId() {
    	return $this->id;
    }
    
    /**
     * @param int $val
     * @return \DDD\Domain\User\Dashboard
     */
    public function setId($id) {
    	$this->id = $id;
    	return $this;
    }
    
    /**
     * @return string
     */
    public function getName() {
    	return $this->name;
    }
    
    public function setName($name) {
    	$this->name = $name;
    	return $this;
    }
    
    /**
     * @return boolean
     */
    public function isActive() {
            return $this->active;
    }
}
