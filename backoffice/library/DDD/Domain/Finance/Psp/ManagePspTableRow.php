<?php

namespace DDD\Domain\Finance\Psp;

/**
 * Class ManagePspTableRow
 * @package DDD\Domain\Psp
 *
 * @author Tigran Petrosyan
 */
class ManagePspTableRow
{
    /**
     * @var int
     */
    protected $id;

    /**
     * @var boolean
     */
    protected $active;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $shortName;

    /**
     * @var string
     */
    protected $moneyAccountName;

    /**
     * @var
     */
    protected $batch;

    /**
     * @var int
     */
    protected $status;

    /**
     * @param $data
     */
    public function exchangeArray($data)
    {
        $this->id               = (isset($data['id'])) ? $data['id'] : null;
        $this->name             = (isset($data['name'])) ? $data['name'] : null;
        $this->shortName        = (isset($data['short_name'])) ? $data['short_name'] : null;
        $this->active           = (isset($data['active'])) ? $data['active'] : null;
        $this->moneyAccountName = (isset($data['money_account_name'])) ? $data['money_account_name'] : null;
        $this->batch            = (isset($data['batch'])) ? $data['batch'] : null;
        $this->status           = (isset($data['status'])) ? $data['status'] : null;
    }

    public function getBatch() {
        return $this->batch;
    }

    /**
     * @return boolean
     */
    public function getActive()
    {
        return $this->active;
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
    public function getMoneyAccountName()
    {
        return $this->moneyAccountName;
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
    public function getShortName()
    {
        return $this->shortName;
    }

    /**
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }
}
