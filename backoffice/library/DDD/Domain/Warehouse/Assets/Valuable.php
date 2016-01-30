<?php

namespace DDD\Domain\Warehouse\Assets;

class Valuable
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
     * @var string
     */
    protected $serialNumber;

    /**
     *
     * @var string
     */
    protected $name;

    /**
     *
     * @var int
     */
    protected $assigneeId;

    /**
     *
     * @var string
     */
    protected $description;

    /**
     *
     * @var int
     */
    protected $status;

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
    protected $firstName;

    /**
     *
     * @var string
     */
    protected $lastName;

    /**
     *
     * @var string
     */
    protected $categoryName;

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
        $this->id = (isset($data['id'])) ? $data['id'] : null;
        $this->categoryId = (isset($data['category_id'])) ? $data['category_id'] : null;
        $this->userId = (isset($data['user_id'])) ? $data['user_id'] : null;
        $this->locationEntityType = (isset($data['location_entity_type'])) ? $data['location_entity_type'] : null;
        $this->locationEntityId = (isset($data['location_entity_id'])) ? $data['location_entity_id'] : null;
        $this->serialNumber = (isset($data['serial_number'])) ? $data['serial_number'] : null;
        $this->assigneeId = (isset($data['assignee_id'])) ? $data['assignee_id'] : null;
        $this->description = (isset($data['description'])) ? $data['description'] : null;
        $this->status = (isset($data['status'])) ? $data['status'] : null;
        $this->locationName = (isset($data['location_name'])) ? $data['location_name'] : null;
        $this->statusName = (isset($data['status_name'])) ? $data['status_name'] : null;
        $this->firstName = (isset($data['firstname'])) ? $data['firstname'] : null;
        $this->lastName = (isset($data['lastname'])) ? $data['lastname'] : null;
        $this->categoryName = (isset($data['category_name'])) ? $data['category_name'] : null;
        $this->name = (isset($data['name'])) ? $data['name'] : null;
        $this->firstnameLastUpdated = (isset($data['firstname_last_updated'])) ? $data['firstname_last_updated'] : null;
        $this->lastnameLastUpdated = (isset($data['lastname_last_updated'])) ? $data['lastname_last_updated'] : null;
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
     * @return string
     */
    public function getName()
    {
        return $this->name;
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
     * @return string
     */
    public function getSerialNumber()
    {
        return $this->serialNumber;
    }

    /**
     * @return int
     */
    public function getAssigneeId()
    {
        return $this->assigneeId;
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
    public function getStatus()
    {
        return $this->status;
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
    public function getStatusName()
    {
        return $this->statusName;
    }

    /**
     * @return string
     */
    public function getFirstName()
    {
        return $this->firstName;
    }

    /**
     * @return string
     */
    public function getLastName()
    {
        return $this->lastName;
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