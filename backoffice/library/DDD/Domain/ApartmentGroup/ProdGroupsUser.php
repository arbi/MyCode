<?php

namespace DDD\Domain\ApartmentGroup;

class ProdGroupsUser
{
    protected $id;
    protected $name;
    protected $usage_apartel;
    protected $isActive;

    public function exchangeArray($data)
    {
        $this->id            = (isset($data['id'])) ? $data['id'] : null;
        $this->name          = (isset($data['name'])) ? $data['name'] : null;
        $this->usage_apartel = (isset($data['usage_apartel'])) ? $data['usage_apartel'] : null;
        $this->isActive      = (isset($data['active']) ? $data['active'] : null);
    }

    public function getName()
    {
        return $this->name;
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

    public function isActive()
    {
        return $this->isActive;
    }

    public function setId($val)
    {
        $this->id = $val;
        return $this;
    }

    /**
     * @return string
     */
    public function getNameWithApartelUsage()
    {
        return $this->name . ($this->usage_apartel ? ' (Apartel)' : '');
    }
}
