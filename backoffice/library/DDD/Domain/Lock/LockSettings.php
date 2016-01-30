<?php

namespace DDD\Domain\Lock;
/**
 * Class Types
 * @package DDD\Domain\Lock
 * @author Hrayr Papikyan
 */
class LockSettings
{
    /**
     * @var int $id
     */
    protected $id;

    /**
     * @var int $lockId
     */
    protected $lockId;

    /**
     * @var int $settingItemId
     */
    protected $settingItemId;

    /**
     * @var string $value
     */
    protected $value;

    /**
     * @var string $settingName
     */
    protected $settingName;

    /**
     * @var int $settingType
     */
    protected $settingType;

    /**
     * @var int $isSettingRequired
     */
    protected $isSettingRequired;


    public function exchangeArray($data)
    {
        $this->id                = (isset($data['id'])) ? $data['id'] : null;
        $this->lockId            = (isset($data['lock_id'])) ? $data['id'] : null;
        $this->settingItemId     = (isset($data['setting_item_id'])) ? $data['setting_item_id'] : null;
        $this->value             = (isset($data['value'])) ? $data['value'] : null;
        $this->settingName       = (isset($data['setting_name'])) ? $data['setting_name'] : null;
        $this->settingType       = (isset($data['setting_type'])) ? $data['setting_type'] : null;
        $this->isSettingRequired = (isset($data['setting_required'])) ? $data['setting_required'] : null;
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
    public function getLockId()
    {
        return $this->lockId;
    }

    /**
     * @return int
     */
    public function getSettingItemId()
    {
        return $this->settingItemId;
    }

    /**
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @return string
     */
    public function getSettingName()
    {
        return $this->settingName;
    }

    /**
     * @return int
     */
    public function getSettingType()
    {
        return $this->settingType;
    }

    /**
     * @return int
     */
    public function isSettingRequired()
    {
        return $this->isSettingRequired;
    }
}
