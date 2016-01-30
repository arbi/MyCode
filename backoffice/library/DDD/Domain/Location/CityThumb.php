<?php

namespace DDD\Domain\Location;

class CityThumb
{
    protected $id;
    protected $thumb;
    
    public function exchangeArray($data) {
        $this->id     	= (isset($data['id'])) ? $data['id'] : null;
        $this->thumb    = (isset($data['thumb'])) ? $data['thumb'] : null;
    }
    
    public function getId() {
        return $this->id;
    }

    public function getThumb() {
        return $this->thumb;
    }
}

?>
