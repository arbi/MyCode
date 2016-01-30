<?php

namespace DDD\Domain\Warehouse\Assets;

class Consumable
{
    /**
     *
     * @var int
     */
   protected $id;

    /**
     *
     * @var int
     */
    protected $categoryId;

    /**
     *
     * @var int
     */
    protected $userId;

    /**
     *
     * @var int
     */
    protected $locationEntityType;

    /**
     *
     * @var int
     */
    protected $locationEntityId;

    /**
     *
     * @var int
     */
    protected $quantity;

    /**
     *
     * @var string
     */
    protected $description;

    /**
     *
     * @var string
     */
    protected $categoryName;

    /**
     *
     * @var int
     */
    protected $runningOut;

    /**
     *
     * @var int
     */
    protected $threshold;

    /**
     *
     * @var string
     */
    protected $locationName;

    /**
     *
     * @var string
     */
    protected $statusName;

    /**
     *
     * @var string
     */
    protected $firstnameLastUpdated;

    /**
     *
     * @var string
     */
    protected $lastnameLastUpdated;


    public function exchangeArray($data)
    {
        $this->id                   = (isset($data['id'])) ? $data['id']                                         : null;
        $this->categoryId           = (isset($data['category_id'])) ? $data['category_id']                       : null;
        $this->userId               = (isset($data['user_id'])) ? $data['user_id']                               : null;
        $this->locationEntityType   = (isset($data['location_entity_type'])) ? $data['location_entity_type']     : null;
        $this->locationEntityId     = (isset($data['location_entity_id'])) ? $data['location_entity_id']         : null;
        $this->quantity             = (isset($data['quantity'])) ? $data['quantity']                             : null;
        $this->description          = (isset($data['description'])) ? $data['description']                       : null;
        $this->threshold            = (isset($data['threshold'])) ? $data['threshold']                           : null;
        $this->categoryName         = (isset($data['category_name'])) ? $data['category_name']                   : null;
        $this->locationName         = (isset($data['location_name'])) ? $data['location_name']                   : null;
        $this->statusName           = (isset($data['status_name'])) ? $data['status_name']                       : null;
        $this->firstnameLastUpdated = (isset($data['firstname_last_updated'])) ? $data['firstname_last_updated'] : null;
        $this->lastnameLastUpdated  = (isset($data['lastname_last_updated'])) ? $data['lastname_last_updated']   : null;
    }

    /**
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return int
     */
    public function getCategoryId()
    {
        return $this->categoryId;
    }

    /**
     * @return int
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * @return int
     */
    public function getLocationEntityType()
    {
        return $this->locationEntityType;
    }

    /**
     * @return int
     */
    public function getLocationEntityId()
    {
        return $this->locationEntityId;
    }

    /**
     * @return int
     */
    public function getQuantity()
    {
        return $this->quantity;
    }


    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @return string
     */
    public function getCategoryName()
    {
        return $this->categoryName;
    }

    /**
     * @return int
     */
    public function getRunningOut()
    {
        if (is_null($this->threshold)) {
            return '<span class="label label-warning">Not Set</span>';
        }
        if ($this->threshold >= $this->quantity) {
            return '<span class="label label-danger">Yes</span>';
        }
        return '<span class="label label-success">No</span>';
    }

    /**
     * @return string
     */
    public function getLocationName()
    {
        return $this->locationName;
    }

    /**
     * @return int
     */
    public function getThreshold()
    {
        return $this->threshold;
    }

    /**
     * @return string
     */
    public function getStatusName()
    {
        return $this->statusName;
    }

    /**
     * @return string
     */
    public function getFirstnameLastUpdated()
    {
        return $this->firstnameLastUpdated;
    }

    /**
     * @return string
     */
    public function getLastnameLastUpdated()
    {
        return $this->lastnameLastUpdated;
    }

    public function getLastUpdaterFullName()
    {
        return $this->firstnameLastUpdated . ' ' . $this->lastnameLastUpdated;
    }
}
