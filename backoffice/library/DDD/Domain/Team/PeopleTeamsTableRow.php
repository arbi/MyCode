<?php

namespace DDD\Domain\Team;

/**
 * Class PeopleTeamsTableRow
 * @package DDD\Domain\Team
 *
 * @author Tigran Petrosyan
 */
class PeopleTeamsTableRow
{
    /**
     * @var int
     */
    protected $id;

    /**
     * @var bool
     */
    protected $isActive;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $description;

    /**
     * @var int
     */
    protected $size;

    /**
     * @var bool
     */
    protected $usageDepartment;

    /**
     * @var bool
     */
    protected $usageNotifiable;

    /**
     * @var bool
     */
    protected $usageFrontier;

    /**
     * @var bool
     */
    protected $usageSecurity;

    /**
     * @var bool
     */
    protected $usageTaskable;

    /**
     * @var bool
     */
    protected $usageProcurement;

    /**
     * @var bool
     */
    protected $usageHiring;

    /**
     * @var bool
     */
    protected $usageStorage;

    /**
     * @param $data
     */
    public function exchangeArray($data)
    {
        $this->id               = (isset($data['id'])) ? $data['id'] : null;
        $this->isActive         = (isset($data['is_active'])) ? $data['is_active'] : null;
        $this->size             = (isset($data['size'])) ? $data['size'] : null;
        $this->name             = (isset($data['name'])) ? $data['name'] : null;
        $this->description      = (isset($data['description'])) ? $data['description'] : null;
        $this->usageDepartment  = (isset($data['usage_department'])) ? $data['usage_department'] : null;
        $this->usageNotifiable  = (isset($data['usage_notifiable'])) ? $data['usage_notifiable'] : null;
        $this->usageFrontier    = (isset($data['usage_frontier'])) ? $data['usage_frontier'] : null;
        $this->usageSecurity    = (isset($data['usage_security'])) ? $data['usage_security'] : null;
        $this->usageTaskable    = (isset($data['usage_taskable'])) ? $data['usage_taskable'] : null;
        $this->usageProcurement = (isset($data['usage_procurement'])) ? $data['usage_procurement'] : null;
        $this->usageHiring      = (isset($data['usage_hiring'])) ? $data['usage_hiring'] : null;
        $this->usageStorage     = (isset($data['usage_storage'])) ? $data['usage_storage'] : null;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return boolean
     */
    public function getIsActive()
    {
        return $this->isActive;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return int
     */
    public function getSize()
    {
        return $this->size;
    }

    /**
     * @return boolean
     */
    public function getUsageDepartment()
    {
        return $this->usageDepartment;
    }

    /**
     * @return boolean
     */
    public function getUsageFrontier()
    {
        return $this->usageFrontier;
    }

    /**
     * @return boolean
     */
    public function getUsageHiring()
    {
        return $this->usageHiring;
    }

    /**
     * @return boolean
     */
    public function getUsageNotifiable()
    {
        return $this->usageNotifiable;
    }

    /**
     * @return boolean
     */
    public function getUsageProcurement()
    {
        return $this->usageProcurement;
    }

    /**
     * @return boolean
     */
    public function getUsageSecurity()
    {
        return $this->usageSecurity;
    }

    /**
     * @return boolean
     */
    public function getUsageStorage()
    {
        return $this->usageStorage;
    }

    /**
     * @return boolean
     */
    public function getUsageTaskable()
    {
        return $this->usageTaskable;
    }
}
