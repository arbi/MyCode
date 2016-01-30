<?php

namespace CreditCard\Entity;

/**
 * Class CompleteData
 * @package CreditCard\Entity
 *
 * @author Tigran Petrosyan
 */
class CompleteData
{
    /**
     * @var int
     */
    protected $id;

    /**
     * @var int credit card brand (Visa, Master, etc.)
     */
    protected $brand;

    /**
     * @var string primary account number
     */
    protected $pan;

    /**
     * @var string primary account number last4
     */
    protected $last4Digits;

    /**
     * @var string security code (CVC, CVV, CVV2, etc.)
     */
    protected $securityCode;

    /**
     * @var string card holder full name
     */
    protected $holder;

    /**
     * @var string 2 digits expiration year
     */
    protected $expirationYear;

    /**
     * @var string 2 digits expiration month
     */
    protected $expirationMonth;

    /**
     * @var int
     */
    protected $status;

    /**
     * @var int
     */
    protected $partnerId;

    /**
     * @var int
     */
    protected $customerId;

    /**
     * @var int
     */
    protected $source;

    /**
     * @var string
     */
    protected $dateProvided;

    /**
     * @var bool
     */
    protected $isDefault;

    /**
     * @param $data []
     */
    public function exchangeArray($data)
    {
        $this->id = (isset($data['id'])) ? $data['id'] : null;
        $this->brand = (isset($data['brand'])) ? $data['brand'] : null;
        $this->pan = (isset($data['pan'])) ? $data['pan'] : null;
        $this->last4Digits = (isset($data['last_4_digits'])) ? $data['last_4_digits'] : null;
        $this->securityCode = (isset($data['security_code'])) ? $data['security_code'] : null;
        $this->holder = (isset($data['holder'])) ? $data['holder'] : null;
        $this->expirationYear = (isset($data['exp_year'])) ? $data['exp_year'] : null;
        $this->expirationMonth = (isset($data['exp_month'])) ? $data['exp_month'] : null;
        $this->status = (isset($data['status'])) ? $data['status'] : null;
        $this->partnerId = (isset($data['partner_id'])) ? $data['partner_id'] : null;
        $this->customerId = (isset($data['customer_id'])) ? $data['customer_id'] : null;
        $this->source = (isset($data['source'])) ? $data['source'] : null;
        $this->dateProvided = (isset($data['date_provided'])) ? $data['date_provided'] : null;
        $this->isDefault = (isset($data['is_default'])) ? $data['is_default'] : null;
    }

    /**
     * @param string $last4Digits
     */
    public function setLast4Digits($last4Digits)
    {
        $this->last4Digits = $last4Digits;
    }

    /**
     * @return string
     */
    public function getLast4Digits()
    {
        return $this->last4Digits;
    }

    /**
     * @param string $expirationMonth
     */
    public function setExpirationMonth($expirationMonth)
    {
        $this->expirationMonth = $expirationMonth;
    }

    /**
     * @return string
     */
    public function getExpirationMonth()
    {
        return $this->expirationMonth;
    }

    /**
     * @param string $expirationYear
     */
    public function setExpirationYear($expirationYear)
    {
        $this->expirationYear = $expirationYear;
    }

    /**
     * @return string
     */
    public function getExpirationYear()
    {
        return $this->expirationYear;
    }

    /**
     * @param int $brand
     */
    public function setBrand($brand)
    {
        $this->brand = $brand;
    }

    /**
     * @return int
     */
    public function getBrand()
    {
        return $this->brand;
    }

    /**
     * @param string $holder
     */
    public function setHolder($holder)
    {
        $this->holder = $holder;
    }

    /**
     * @return string
     */
    public function getHolder()
    {
        return $this->holder;
    }

    /**
     * @param string $pan
     */
    public function setPan($pan)
    {
        $this->pan = $pan;
    }

    /**
     * @return string
     */
    public function getPan()
    {
        return $this->pan;
    }

    /**
     * @param string $securityCode
     */
    public function setSecurityCode($securityCode)
    {
        $this->securityCode = $securityCode;
    }

    /**
     * @return string
     */
    public function getSecurityCode()
    {
        return $this->securityCode;
    }

    /**
     * @param int $partnerId
     */
    public function setPartnerId($partnerId)
    {
        $this->partnerId = $partnerId;
    }

    /**
     * @return int
     */
    public function getPartnerId()
    {
        return $this->partnerId;
    }

    /**
     * @param int $status
     */
    public function setStatus($status)
    {
        $this->status = $status;
    }

    /**
     * @return int
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param int $customerId
     */
    public function setCustomerId($customerId)
    {
        $this->customerId = $customerId;
    }

    /**
     * @return int
     */
    public function getCustomerId()
    {
        return $this->customerId;
    }

    /**
     * @param int $source
     */
    public function setSource($source)
    {
        $this->source = $source;
    }

    /**
     * @return int
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * @param string $dateProvided
     */
    public function setDateProvided($dateProvided)
    {
        $this->dateProvided = $dateProvided;
    }

    /**
     * @return string
     */
    public function getDateProvided()
    {
        return $this->dateProvided;
    }

    /**
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param boolean $isDefault
     */
    public function setIsDefault($isDefault)
    {
        $this->isDefault = $isDefault;
    }

    /**
     * @return boolean
     */
    public function getIsDefault()
    {
        return $this->isDefault;
    }
}
