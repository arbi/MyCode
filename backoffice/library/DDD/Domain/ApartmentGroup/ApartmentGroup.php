<?php

namespace DDD\Domain\ApartmentGroup;

class ApartmentGroup
{
    protected $id;
    protected $name;
    protected $email;
    protected $active;
    protected $usage_concierge_dashboard;
    protected $timezone;
    protected $firstname;
    protected $lastname;
    protected $group_manager_id;
    protected $fmanager;
    protected $lmanager;
    protected $usage_cost_center;
    protected $countryId;
    protected $country;
    protected $lockId;
    protected $buildingPhone;
    protected $pspId;
    protected $buildingSectionId;

    /**
     * @var int
     */
    protected $default_gem_id;

    /**
     * @access protected
     * @var boolean
     */
    protected $usage_building;

    /**
     * @var
     */
    protected $count;

    /**
     * @var boolean
     */
    protected $usage_apartel;

    /**
     * @var boolean
     */
    protected $usage_performance_group;

    protected $kiPageType;
    protected $assignedOfficeId;
    protected $apartmentEntryTextlineId;

    public function exchangeArray($data)
    {
        $this->id                         = (isset($data['id'])) ? $data['id'] : null;
        $this->name                       = (isset($data['name'])) ? $data['name'] : null;
        $this->email                      = (isset($data['email'])) ? $data['email'] : null;
        $this->active                     = (isset($data['active'])) ? $data['active'] : null;
        $this->usage_concierge_dashboard  = (isset($data['usage_concierge_dashboard'])) ? $data['usage_concierge_dashboard'] : null;
        $this->timezone                   = (isset($data['timezone'])) ? $data['timezone'] : null;
        $this->lastname                   = (isset($data['lastname'])) ? $data['lastname'] : null;
        $this->firstname                  = (isset($data['firstname'])) ? $data['firstname'] : null;
        $this->group_manager_id           = (isset($data['group_manager_id'])) ? $data['group_manager_id'] : null;
        $this->fmanager                   = (isset($data['fmanager'])) ? $data['fmanager'] : null;
        $this->lmanager                   = (isset($data['lmanager'])) ? $data['lmanager'] : null;
        $this->usage_cost_center          = (isset($data['usage_cost_center'])) ? $data['usage_cost_center'] : null;
        $this->usage_building             = (isset($data['usage_building'])) ? $data['usage_building'] : null;
        $this->count                      = (isset($data['count'])) ? $data['count'] : null;
        $this->usage_apartel              = (isset($data['usage_apartel'])) ? $data['usage_apartel'] : null;
        $this->usage_performance_group    = (isset($data['usage_performance_group'])) ? $data['usage_performance_group'] : null;
        $this->countryId                  = (isset($data['country_id'])) ? $data['country_id'] : null;
        $this->country                    = (isset($data['country'])) ? $data['country'] : null;
        $this->apartmentEntryTextlineId   = (isset($data['apartment_entry_textline_id'])) ? $data['apartment_entry_textline_id'] : null;
        $this->lockId                     = (isset($data['lock_id'])) ? $data['lock_id'] : null;
        $this->buildingPhone              = (isset($data['building_phone'])) ? $data['building_phone'] : null;
        $this->pspId                      = (isset($data['psp_id'])) ? $data['psp_id'] : null;
        $this->kiPageType                 = (isset($data['ki_page_type'])) ? $data['ki_page_type'] : null;
        $this->assignedOfficeId           = (isset($data['assigned_office_id'])) ? $data['assigned_office_id'] : null;
        $this->buildingSectionId          = (isset($data['building_section_id'])) ? $data['building_section_id'] : null;
    }

    public function getBuildingSectionId()
    {
        return $this->buildingSectionId;
    }

    public function getGroupManagerId()
    {
        return $this->group_manager_id;
    }

    public function getIsApartel()
    {
        return $this->usage_apartel;
    }

    public function getCount()
    {
        return $this->count;
    }

    public function setCount($val)
    {
        $this->count = $val;
        return $this;
    }

    /**
     * @return boolean
     */
    public function isBuilding()
    {
        return $this->usage_building;
    }

    public function getCostCenter()
    {
        return $this->usage_cost_center;
    }

    public function setCostCenter($val)
    {
        $this->usage_cost_center = $val;
        return $this;
    }

    public function getLmanager()
    {
        return $this->lmanager;
    }

    public function setLmanager($val)
    {
        $this->lmanager = $val;
        return $this;
    }

    public function getFmanager() {
        return $this->fmanager;
    }

    public function setFmanager($val) {
        $this->fmanager = $val;
        return $this;
    }

    public function getFirstName() {
        return $this->firstname;
    }

    public function setFirstName($val)
    {
        $this->firstname = $val;
        return $this;
    }

    public function getLastName()
    {
        return $this->lastname;
    }

    public function setLastName($val)
    {
        $this->lastname = $val;
        return $this;
    }

    public function setTimezone($val)
    {
        $this->timezone = $val;
        return $this;
    }

    public function getTimezone()
    {
        return $this->timezone;
    }

    public function getIsArrivalsDashboard()
    {
        return $this->usage_concierge_dashboard;
    }

    public function setIsArrivalsDashboard($val)
    {
        $this->usage_concierge_dashboard = $val;
        return $this;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getActive()
    {
        return $this->active;
    }

    public function getNameWithApartelUsage()
    {
        return $this->name . ($this->usage_apartel ? ' (Apartel)' : '');
    }

    public function setName($val)
    {
        $this->name = $val;
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

    /**
     * @return boolean
     */
    public function getIsPerformanceGroup()
    {
        return $this->usage_performance_group;
    }

    /**
     * @param boolean $isPerformanceGroup
     */
    public function setIsPerformanceGroup($isPerformanceGroup)
    {
        $this->usage_performance_group = $isPerformanceGroup;
    }

    public function getCountryId()
    {
        return $this->countryId;
    }

    public function setCountryId($countryId)
    {
        $this->countryId = $countryId;
        return $this;
    }

    public function getCountry()
    {
        return $this->country;
    }

    public function getEmail()
    {
        return $this->email;
    }

    public function setEmail($email)
    {
        $this->email = $email;
        return $this;
    }

    public function getApartmentEntryTextlineId()
    {
        return $this->apartmentEntryTextlineId;
    }

    public function getLockId()
    {
        return $this->lockId;
    }

    public function setEntryInstructionTextlineId($textlineId)
    {
        $this->entryInstructionTextlineId = $textlineId;
        return $this;
    }

    public function getBuildingPhone()
    {
        return $this->buildingPhone;
    }

    public function setBuildingPhone($buildingPhone)
    {
        $this->buildingPhone = $buildingPhone;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getPspId()
    {
        return $this->pspId;
    }

    /**
     * @return mixed
     */
    public function getKIPageType()
    {
        return $this->kiPageType;
    }

    /**
     * @return mixed
     */
    public function getAssignedOfficeId()
    {
        return $this->assignedOfficeId;
    }
}