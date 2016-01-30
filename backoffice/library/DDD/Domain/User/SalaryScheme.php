<?php

namespace DDD\Domain\User;
/**
 * Class    SalaryScheme
 * @package DDD\Domain\User
 * @author  Harut Grigoryan
 */
class SalaryScheme
{
    protected $id;
    protected $name;
    protected $externalAccountId;
    protected $type;
    protected $payFrequencyType;
    protected $salary;
    protected $currencyId;
    protected $effectiveFrom;
    protected $effectiveTo;
    protected $creationDate;
    protected $status;

    /**
     * @param $data
     */
    public function exchangeArray($data)
    {
        $this->id                = (isset($data['id'])) ? $data['id'] : null;
        $this->name              = (isset($data['name'])) ? $data['name'] : null;
        $this->externalAccountId = (isset($data['external_account_id'])) ? $data['external_account_id'] : null;
        $this->type              = (isset($data['type'])) ? $data['type'] : null;
        $this->payFrequencyType  = (isset($data['pay_frequency_type'])) ? $data['pay_frequency_type'] : null;
        $this->salary            = (isset($data['salary'])) ? $data['salary'] : null;
        $this->currencyId        = (isset($data['currency_id'])) ? $data['currency_id'] : null;
        $this->effectiveFrom     = (isset($data['effective_from'])) ? $data['effective_from'] : null;
        $this->effectiveTo       = (isset($data['effective_to'])) ? $data['effective_to'] : null;
        $this->creationDate      = (isset($data['creation_date'])) ? $data['creation_date'] : null;
        $this->status            = (isset($data['status'])) ? $data['status'] : null;
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param $val
     * @return $this
     */
    public function setId($val)
    {
        $this->id = $val;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param $name
     * @return $this
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getExternalAccountId()
    {
        return $this->externalAccountId;
    }

    /**
     * @param  $externalAccountId
     * @return $this
     */
    public function setExternalAccountId($externalAccountId)
    {
        $this->externalAccountId = $externalAccountId;

        return $this;
    }

    /**
     * @return int
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param  $type
     * @return $this
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getPayFrequencyType()
    {
        return $this->payFrequencyType;
    }

    /**
     * @param  $payFrequencyType
     * @return $this
     */
    public function setPayFrequencyType($payFrequencyType)
    {
        $this->payFrequencyType = $payFrequencyType;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getSalary()
    {
        return $this->salary;
    }

    /**
     * @param  $salary
     * @return $this
     */
    public function setSalary($salary)
    {
        $this->salary = $salary;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getCurrencyId()
    {
        return $this->currencyId;
    }

    /**
     * @param  $currencyId
     * @return $this
     */
    public function setCurrencyId($currencyId)
    {
        $this->currencyId = $currencyId;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getEffectiveFrom()
    {
        return $this->effectiveFrom;
    }

    /**
     * @param  $effectiveFrom
     * @return $this
     */
    public function setEffectiveFrom($effectiveFrom)
    {
        $this->effectiveFrom = $effectiveFrom;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getEffectiveTo()
    {
        return $this->effectiveTo;
    }

    /**
     * @param  $effectiveTo
     * @return $this
     */
    public function setEffectiveTo($effectiveTo)
    {
        $this->effectiveTo = $effectiveTo;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param $status
     * @return $this
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getCreationDate()
    {
        return $this->creationDate;
    }

    /**
     * @param $creationDate
     * @return $this
     */
    public function setCreationDate($creationDate)
    {
        $this->creationDate = $creationDate;

        return $this;
    }

    /**
     * @return array
     */
    public function getArrayCopy()
    {
        return get_object_vars($this);
    }
}
