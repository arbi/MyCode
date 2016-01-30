<?php

namespace DDD\Domain\Lock;
/**
 * Class ForSelect
 * @package DDD\Domain\Lock
 * @author Hrayr Papikyan
 */
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

    protected $typeName;
    protected $isPhysical;

    public function exchangeArray($data)
    {
        $this->id         = (isset($data['id'])) ? $data['id'] : null;
        $this->name       = (isset($data['name'])) ? $data['name'] : null;
        $this->typeName   = (isset($data['type_name'])) ? $data['type_name'] : null;
        $this->isPhysical = (isset($data['is_physical'])) ? $data['is_physical'] : null;
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

    public function getTypeName()
    {
        return $this->typeName;
    }

    public function isPhysical()
    {
        return $this->isPhysical;
    }

}
