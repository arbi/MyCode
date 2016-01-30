<?php

namespace DDD\Domain\Team;

/**
 * Class ForSelect
 * @package DDD\Domain\Team
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

    public function exchangeArray($data)
    {
        $this->id   = (isset($data['id'])) ? $data['id'] : null;
        $this->name = (isset($data['name'])) ? $data['name'] : null;
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
}
