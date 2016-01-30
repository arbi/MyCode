<?php

namespace DDD\Domain\Apartel\RelTypeApartment;

class RelTypeApartment
{
    protected $id;
    protected $apartel_type_id;
    protected $apartment_id;

    public function exchangeArray($data)
    {
        $this->id = (isset($data['id'])) ? $data['id'] : null;
        $this->apartel_type_id = (isset($data['apartel_type_id'])) ? $data['apartel_type_id'] : null;
        $this->apartment_id = (isset($data['apartment_id'])) ? $data['apartment_id'] : null;
    }

    /**
     * @return mixed
     */
    public function getApartelTypeId()
    {
        return $this->apartel_type_id;
    }

    /**
     * @return mixed
     */
    public function getApartmentId()
    {
        return $this->apartment_id;
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }



}
