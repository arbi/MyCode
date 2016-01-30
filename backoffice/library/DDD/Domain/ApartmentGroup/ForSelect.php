<?php

namespace DDD\Domain\ApartmentGroup;

class ForSelect
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
     * @var bool
     */
    protected $usageApartel;

    public function exchangeArray($data)
    {
        $this->id           = (isset($data['id'])) ? $data['id'] : null;
        $this->name         = (isset($data['name'])) ? $data['name'] : null;
        $this->usageApartel = (isset($data['usage_apartel'])) ? $data['usage_apartel'] : null;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
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
        return $this->name . ($this->usageApartel ? ' (Apartel)' : '');
    }
}
