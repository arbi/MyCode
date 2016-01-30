<?php

namespace DDD\Domain\Lock;
/**
 * Class Types
 * @package DDD\Domain\Lock
 * @author Hrayr Papikyan
 */
class Types
{
    /**
     * @var int $id
     */
    protected $id;

    /**
     * @var string $name
     */
    protected $name;

    /**
     * @var string $explanation
     */
    protected $explanation;

    public function exchangeArray($data)
    {
        $this->id            = (isset($data['id'])) ? $data['id'] : null;
        $this->name          = (isset($data['name'])) ? $data['name'] : null;
        $this->explanation   = (isset($data['explanation'])) ? $data['explanation'] : null;
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
    public function getExplanation()
    {
        return $this->explanation;
    }
}
