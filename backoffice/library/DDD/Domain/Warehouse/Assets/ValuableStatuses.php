<?php

namespace DDD\Domain\Warehouse\Assets;

class ValuableStatuses
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

    public function exchangeArray($data)
    {
        $this->id = (isset($data['id'])) ? $data['id'] : null;
        $this->name = (isset($data['name'])) ? $data['name'] : null;

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
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

}