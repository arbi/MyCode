<?php

namespace DDD\Domain\ApartmentGroup;

class FrontierCard
{
    protected $id;
    protected $name;
    protected $apartmentName;
    protected $apartmentId;
    protected $unitNumber;
    protected $bedroomCount;

    public function exchangeArray($data)
    {
        $this->id     	     = (isset($data['id'])) ? $data['id'] : null;
        $this->name          = (isset($data['name'])) ? $data['name'] : null;
        $this->apartmentId   = (isset($data['apartment_id'])) ? $data['apartment_id'] : null;
        $this->apartmentName = (isset($data['apartment_name'])) ? $data['apartment_name'] : null;
        $this->unitNumber = (isset($data['unit_number'])) ? $data['unit_number'] : null;
        $this->bedroomCount = (isset($data['bedroom_count'])) ? $data['bedroom_count'] : null;
    }

    public function getId()
    {
    	return $this->id;
    }

    public function getName()
    {
    	return $this->name;
    }

    public function getApartmentId()
    {
    	return $this->apartmentId;
    }

    public function getApartmentName()
    {
    	return $this->apartmentName;
    }

    public function getUnitNumber()
    {
    	return $this->unitNumber;
    }

    /**
     * @return int
     */
    public function getBedroomCount()
    {
        return $this->bedroomCount;
    }


}