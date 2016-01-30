<?php

namespace DDD\Domain\Accommodation;

class ProductDetails
{
    protected $id;
    
    public function exchangeArray($data)
    {
        $this->id			= (isset($data['id'])) ? $data['id'] : null;
    }
    
    public function getId() {
        return $this->id;
    }
}