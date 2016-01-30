<?php

namespace DDD\Domain\Accommodation;

class Images
{
    protected $id;
    protected $apartment_id;
    protected $img1;
    
    public function exchangeArray($data)
    {
        $this->id           = (isset($data['id'])) ? $data['id'] : null;
        $this->apartment_id       = (isset($data['apartment_id'])) ? $data['apartment_id'] : null;
        $this->img1         = (isset($data['img1'])) ? $data['img1'] : null;
    }
    
    public function getId() {
        return $this->id;
    }

    public function getApartmentId() {
        return $this->apartment_id;
    }

    public function getImg1() {
        return $this->img1;
    }
}
