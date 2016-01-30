<?php

namespace DDD\Domain\ApartmentGroup;

class ApartmentGroupItems
{
    protected $id;
    protected $apartmentName;
    protected $apartmentGroupId;
    protected $apartmentId;
    
    public function exchangeArray($data)
    {
        $this->id = (isset($data['id'])) ? $data['id'] : null;
        $this->apartmentName = (isset($data['apartment_name'])) ? $data['apartment_name'] : null;
        $this->apartmentGroupId = (isset($data['apartment_group_id'])) ? $data['apartment_group_id'] : null;
        $this->apartmentId = (isset($data['apartment_id'])) ? $data['apartment_id'] : null;
    }
    
    public function getApartmentId()
    {
        return $this->apartmentId;
    }
    
    public function setApartmentId($val)
    {
        $this->apartmentId = $val;
        return $this;
    }
    
    public function getApartmentName()
    {
        return $this->apartmentName;
    }
    
    public function setApartmentName($val)
    {
        $this->apartmentName = $val;
        return $this;
    }
    
    public function getApartmentGroupId()
    {
        return $this->apartmentGroupId;
    }
    
    public function setApartmentGroupId($val)
    {
        $this->apartmentGroupId = $val;
        return $this;
    }
    
    public function getId()
    {
        return $this->id;
    }
    
    public function setId($val)
    {
        $this->id = $val;
        return $this;
    }
}
