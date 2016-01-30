<?php

namespace DDD\Domain\Finance;

/**
 * Class Psp
 * @package DDD\Domain\Finance
 *
 * @author Tigran Petrosyan
 */
class Psp
{
    /**
     * @var int
     */
    protected $id;

    /**
     * @var boolean
     */
    protected $authorization;

    /**
     * @var boolean
     */
    protected $errorCode;

    /**
     * @var boolean
     */
    protected $rrn;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $shortName;

    /**
     * @var int
     */
    protected $moneyAccountId;

    /**
     * @var boolean
     */
    protected $isActive;

    /**
     * @param $data
     */
    public function exchangeArray($data)
    {
        $this->id = (isset($data['id'])) ? $data['id'] : null;
        $this->authorization = (isset($data['authorization'])) ? $data['authorization'] : null;
        $this->errorCode = (isset($data['error_code'])) ? $data['error_code'] : null;
        $this->rrn = (isset($data['rrn'])) ? $data['rrn'] : null;
        $this->name = (isset($data['name'])) ? $data['name'] : null;
        $this->shortName = (isset($data['short_name'])) ? $data['short_name'] : null;
        $this->isActive = (isset($data['active'])) ? $data['active'] : null;
    }

    /**
     * @return boolean
     */
    public function getAuthorization()
    {
        return $this->authorization;
    }

    /**
     * @return boolean
     */
    public function getErrorCode()
    {
        return $this->errorCode;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return boolean
     */
    public function getIsActive()
    {
        return $this->isActive;
    }

    /**
     * @return int
     */
    public function getMoneyAccountId()
    {
        return $this->moneyAccountId;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return boolean
     */
    public function getRrn()
    {
        return $this->rrn;
    }

    /**
     * @return string
     */
    public function getShortName()
    {
        return $this->shortName;
    }
}
