<?php

namespace DDD\Domain\Lock;
/**
 * Class Types
 * @package DDD\Domain\Lock
 * @author Hrayr Papikyan
 */
class Locks
{
    /**
     * @var int $id
     */
    protected $id;

    /**
     * @var int $typeId
     */
    protected $typeId;

    /**
     * @var string $name
     */
    protected $name;

    /**
     * @var string $description
     */
    protected $description;

    /**
     * @var int $usageApartment
     */
    protected $usageApartment;

    /**
     * @var int $usageBuilding
     */
    protected $usageBuilding;

    /**
     * @var int $usageParking
     */
    protected $usageParking;

    /**
     * @var string $typeName
     */
    protected $typeName;

    /**
     * @var string $explanation
     */
    protected $explanation;

    /**
     * @var int $isPhysical
     */
    protected $isPhysical;

    public function exchangeArray($data)
    {
        $this->id             = (isset($data['id'])) ? $data['id'] : null;
        $this->typeId         = (isset($data['type_id'])) ? $data['type_id'] : null;
        $this->name           = (isset($data['name'])) ? $data['name'] : null;
        $this->description    = (isset($data['description'])) ? $data['description'] : null;
        $this->usageApartment = (isset($data['usage_apartment'])) ? $data['usage_apartment'] : null;
        $this->usageBuilding  = (isset($data['usage_building'])) ? $data['usage_building'] : null;
        $this->usageParking   = (isset($data['usage_parking'])) ? $data['usage_parking'] : null;
        $this->typeName       = (isset($data['type_name'])) ? $data['type_name'] : null;
        $this->explanation    = (isset($data['explanation'])) ? $data['explanation'] : null;
        $this->isPhysical     = (isset($data['is_physical'])) ? $data['is_physical'] : null;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return int
     */
    public function getTypeId()
    {
        return $this->typeId;
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
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @return int
     */
    public function isUsefulForApartment()
    {
        return (int)$this->usageApartment;
    }

    /**
     * @return int
     */
    public function isUsefulForBuilding()
    {
        return (int)$this->usageBuilding;
    }

    /**
     * @return int
     */
    public function isUsefulForParking()
    {
        return (int)$this->usageParking;
    }

    /**
     * @return string
     */
    public function getTypeName()
    {
        return $this->typeName;
    }

    /**
     * @return string
     */
    public function getExplanation()
    {
        return $this->explanation;
    }

    /**
     * @return int
     */
    public function isPhysical()
    {
        return $this->isPhysical;
    }
}
