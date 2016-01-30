<?php

namespace DDD\Domain\ApartmentGroup;

/**
 * Class ApartmentGroupTableRow
 * @package DDD\Domain\ApartmentGroup
 *
 * @author Tigran Petrosyan
 */
class ApartmentGroupTableRow
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
     * @var
     */
    protected $count;

    /**
     * @var string
     */
    protected $managerFirstName;

    /**
     * @var string
     */
    protected $managerLastName;

    /**
     * @var boolean
     */
    protected $usage_cost_center;

    /**
     * @var boolean
     */
    protected $usage_concierge_dashboard;

    /**
     * @var boolean
     */
    protected $usage_building;

    /**
     * @var boolean
     */
    protected $usage_apartel;

    /**
     * @var boolean
     */
    protected $usage_performance_group;

    /**
     * @var boolean
     */
    protected $isActive;

    /**
     * @var int
     */
    protected $countryId;

    /**
     * @var string
     */
    protected $country;

    protected $group_manager_id;


    /**
     * @var int
     */
    protected $apartelId;

    public function exchangeArray($data)
    {
        $this->id                        = (isset($data['id'])) ? $data['id'] : null;
        $this->name                      = (isset($data['name'])) ? $data['name'] : null;
        $this->count                     = (isset($data['count'])) ? $data['count'] : null;
        $this->group_manager_id          = (isset($data['group_manager_id'])) ? $data['group_manager_id'] : null;
        $this->managerFirstName          = (isset($data['manager_first_name'])) ? $data['manager_first_name'] : null;
        $this->managerLastName           = (isset($data['manager_last_name'])) ? $data['manager_last_name'] : null;
        $this->usage_cost_center         = (isset($data['usage_cost_center'])) ? $data['usage_cost_center'] : null;
        $this->usage_concierge_dashboard = (isset($data['usage_concierge_dashboard'])) ? $data['usage_concierge_dashboard'] : null;
        $this->usage_building            = (isset($data['usage_building'])) ? $data['usage_building'] : null;
        $this->usage_apartel             = (isset($data['usage_apartel'])) ? $data['usage_apartel'] : null;
        $this->usage_performance_group   = (isset($data['usage_performance_group'])) ? $data['usage_performance_group'] : null;
        $this->isActive                  = (isset($data['active'])) ? $data['active'] : null;
        $this->countryId                 = (isset($data['country_id'])) ? $data['country_id'] : null;
        $this->country                   = (isset($data['country'])) ? $data['country'] : null;
        $this->apartelId                 = (isset($data['apartel_id'])) ? $data['apartel_id'] : null;
    }

    /**
     * @param mixed $count
     */
    public function setCount($count)
    {
        $this->count = $count;
    }

    /**
     * @return mixed
     */
    public function getCount()
    {
        return $this->count;
    }
    public function getManagerId()
    {
        return $this->group_manager_id;
    }

    /**
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param boolean $isApartel
     */
    public function setIsApartel($isApartel)
    {
        $this->usage_apartel = $isApartel;
    }

    /**
     * @return boolean
     */
    public function getIsApartel()
    {
        return $this->usage_apartel;
    }

    /**
     * @param boolean $isArrivalDashboard
     */
    public function setIsArrivalDashboard($isArrivalDashboard)
    {
        $this->usage_concierge_dashboard = $isArrivalDashboard;
    }

    /**
     * @return boolean
     */
    public function getIsArrivalDashboard()
    {
        return $this->usage_concierge_dashboard;
    }

    /**
     * @param boolean $isBuilding
     */
    public function setIsBuilding($isBuilding)
    {
        $this->usage_building = $isBuilding;
    }

    /**
     * @return boolean
     */
    public function getIsBuilding()
    {
        return $this->usage_building;
    }

    /**
     * @param boolean $isCostCenter
     */
    public function setIsCostCenter($isCostCenter)
    {
        $this->usage_cost_center = $isCostCenter;
    }

    /**
     * @return boolean
     */
    public function getIsCostCenter()
    {
        return $this->usage_cost_center;
    }

    /**
     * @param boolean $isPerformance
     */
    public function setIsPerformance($isPerformance)
    {
        $this->usage_performance_group = $isPerformance;
    }

    /**
     * @return boolean
     */
    public function getIsPerformance()
    {
        return $this->usage_performance_group;
    }

    /**
     * @param string $managerFirstName
     */
    public function setManagerFirstName($managerFirstName)
    {
        $this->managerFirstName = $managerFirstName;
    }

    /**
     * @return string
     */
    public function getManagerFirstName()
    {
        return $this->managerFirstName;
    }

    /**
     * @param string $managerLastName
     */
    public function setManagerLastName($managerLastName)
    {
        $this->managerLastName = $managerLastName;
    }

    /**
     * @return string
     */
    public function getManagerLastName()
    {
        return $this->managerLastName;
    }

    /**
     * @return string
     */
    public function getManagerFullName()
    {
        return $this->managerFirstName . ' ' . $this->managerLastName;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getNameWithApartelUsage()
    {
        return $this->name . ($this->usage_apartel ? ' (Apartel)' : '');
    }

    /**
     * @return string
     */
    public function isActive()
    {
        return $this->isActive;
    }

    /**
     * @return int
     */
    public function getCountryId()
    {
        return $this->countryId;
    }

    /**
     * @param $countryId
     * @return $this
     */
    public function setCountryId($countryId)
    {
        $this->countryId = $countryId;
        return $this;
    }

    /**
     * @return string
     */
    public function getCountry()
    {
        return $this->country;
    }

    /**
     * @return int
     */
    public function getApartelId()
    {
        return $this->apartelId;
    }
}
