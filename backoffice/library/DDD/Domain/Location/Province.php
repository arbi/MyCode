<?php

namespace DDD\Domain\Location;

use DDD\Domain\Location\LocationAbstract;

class Province extends LocationAbstract
{
    protected $id;
    protected $name;
    
    public function exchangeArray($data) {
		parent::exchangeArray($data);
        
        $this->name      = (isset($data['name']))     ? $data['name']      : null;
	}
    
    public function getId() {
        return $this->id;
    }

    public function getName() {
        return $this->name;
    }
}
