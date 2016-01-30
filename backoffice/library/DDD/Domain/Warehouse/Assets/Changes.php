<?php

namespace DDD\Domain\Warehouse\Assets;

class Changes
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
    protected $quantityChange;

    /**
     *
     * @var string
     */
    protected $actionDate;

    /**
     *
     * @var string
     */
    protected $categoryName;

    /**
     *
     * @var string
     */
    protected $locationName;

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

    /**
     *
     * @var int
     */
    protected $shipmentStatus;

    /**
     *
     * @var int
     */
    protected $assetId;


   public function exchangeArray($data) {
        $this->id                   = (isset($data['id'])) ? $data['id']                                         : null;
        $this->categoryId           = (isset($data['category_id'])) ? $data['category_id']                       : null;
        $this->userId               = (isset($data['user_id'])) ? $data['user_id']                               : null;
        $this->locationEntityType   = (isset($data['location_entity_type'])) ? $data['location_entity_type']     : null;
        $this->locationEntityId     = (isset($data['location_entity_id'])) ? $data['location_entity_id']         : null;
        $this->quantityChange       = (isset($data['quantity_change'])) ? $data['quantity_change']               : null;
        $this->actionDate           = (isset($data['action_date'])) ? $data['action_date']                       : null;

        $this->categoryName         = (isset($data['category_name'])) ? $data['category_name']                   : null;
        $this->locationName         = (isset($data['location_name'])) ? $data['location_name']                   : null;
        $this->firstnameLastUpdated = (isset($data['firstname_last_updated'])) ? $data['firstname_last_updated'] : null;
        $this->lastnameLastUpdated  = (isset($data['lastname_last_updated'])) ? $data['lastname_last_updated']   : null;
        $this->shipmentStatus       = (isset($data['shipment_status'])) ? $data['shipment_status']               : null;
        $this->assetId              = (isset($data['asset_id'])) ? $data['asset_id']                             : null;
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
    public function getQuantityChange()
    {
        return $this->quantityChange;
    }

    /**
     * @return string
     */
    public function getActionDate()
    {
        return $this->actionDate;
    }

    /**
     * @return string
     */
    public function getCategoryName()
    {
        return $this->categoryName;
    }

    /**
     * @return string
     */
    public function getLocationName()
    {
        return $this->locationName;
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

    /**
     * @return int
     */
    public function getShipmentStatus()
    {
        return $this->shipmentStatus;
    }

    /**
     * @return int
     */
    public function getAssetId()
    {
        return $this->assetId;
    }

}
