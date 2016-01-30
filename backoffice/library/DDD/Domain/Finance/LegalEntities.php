<?php

namespace DDD\Domain\Finance;

/**
 * Class LegalEntities
 * @package DDD\Domain\Finance
 */
class LegalEntities
{
    /**
     * @var int
     */
    protected $id;

    /**
     * @var boolean
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
    protected $countryId;

        /**
     * @var string
     */
    protected $country;

    public function exchangeArray($data)
    {
        $this->id          = (isset($data['id'])) ? $data['id'] : null;
        $this->isActive    = (isset($data['active'])) ? $data['active'] : null;
        $this->name        = (isset($data['name'])) ? $data['name'] : null;
        $this->description = (isset($data['description'])) ? $data['description'] : null;
        $this->countryId   = (isset($data['country_id'])) ? $data['country_id'] : null;
        $this->country     = (isset($data['country'])) ? $data['country'] : null;
    }

    /**
     * @param string $description
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
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
     * @param boolean $isActive
     */
    public function setIsActive($isActive)
    {
        $this->isActive = $isActive;
    }

    /**
     * @return boolean
     */
    public function isActive()
    {
        return $this->isActive;
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
     * @param int $countryId
     */
    public function setCountryId($countryId)
    {
        $this->countryId = $countryId;
    }

    /**
     * @return int
     */
    public function getCountryId()
    {
        return $this->countryId;
    }

    /**
     * @return string
     */
    public function getCountry()
    {
        return $this->country;
    }
}
