<?php

namespace DDD\Domain\Warehouse\Storage;

class Storage
{
    /**
     *
     * @var int
     */
    protected $id;
    /**
     *
     * @var string
     */
    protected $name;
    /**
     *
     * @var int
     */
    protected $city_id;

    /**
     * @var string
     */
    protected $city_name;

    public function exchangeArray($data)
    {
        $this->id           = (isset($data['id'])) ? $data['id'] : null;
        $this->name         = (isset($data['name'])) ? $data['name'] : null;
        $this->city_id      = (isset($data['city_id'])) ? $data['city_id'] : null;
        $this->city_name    = (isset($data['city_name'])) ? $data['city_name'] : null;
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
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     *
     * @return int
     */
    public function getCityId()
    {
        return $this->city_id;
    }

    /**
     * @return string
     */
    public function getCityName()
    {
        return $this->city_name;
    }
}