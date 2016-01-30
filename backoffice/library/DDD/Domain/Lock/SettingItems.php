<?php

namespace DDD\Domain\Lock;
/**
 * Class Types
 * @package DDD\Domain\Lock
 * @author Hrayr Papikyan
 */
class SettingItems
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
     * @var int $isRequired
     */
    protected $isRequired;

    /**
     * @var string $inputType
     */
    protected $inputType;

    public function exchangeArray($data)
    {
        $this->id            = (isset($data['id'])) ? $data['id'] : null;
        $this->name          = (isset($data['name'])) ? $data['name'] : null;
        $this->isRequired    = (isset($data['is_required'])) ? $data['is_required'] : null;
        $this->inputType     = (isset($data['input_type'])) ? $data['input_type'] : null;
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
     * @return int
     */
    public function isRequired()
    {
        return (int)$this->isRequired;
    }

    /**
     * @return string
     */
    public function getInputType()
    {
        return $this->inputType;
    }}
