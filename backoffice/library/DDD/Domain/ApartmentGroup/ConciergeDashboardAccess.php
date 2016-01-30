<?php

namespace DDD\Domain\ApartmentGroup;

class ConciergeDashboardAccess
{
    /**
     * @var int
     */
    protected $id;

    /**
     * @var int
     */
    protected $userId;

    /**
     * @var int
     */
    protected $apartmentGroupId;
    
    public function exchangeArray($data)
    {
        $this->id               = (isset($data['id'])) ? $data['id'] : null;
        $this->userId           = (isset($data['user_id'])) ? $data['user_id'] : null;
        $this->apartmentGroupId = (isset($data['apartment_group_id'])) ? $data['apartment_group_id'] : null;
    }

    /**
     * @return int
     */
    public function getUserId()
    {
        return $this->userId;
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
    
    public function getId() {
            return $this->id;
    }
    
    public function setId($val) {
            $this->id = $val;
            return $this;
    }
}
