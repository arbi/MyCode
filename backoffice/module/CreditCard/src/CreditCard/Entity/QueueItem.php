<?php

namespace CreditCard\Entity;

class QueueItem
{
    /**
     * @var int credit card brand (Visa, Master, etc.)
     */
    protected $brand;

    /**
     * @var string primary account number
     */
    protected $pan;

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

    public function exchangeArray($data)
    {
        $this->brand = (isset($data['brand'])) ? $data['brand'] : null;
        $this->pan = (isset($data['pan'])) ? $data['pan'] : null;
        $this->securityCode = (isset($data['security_code'])) ? $data['security_code'] : null;
        $this->holder = (isset($data['holder'])) ? $data['holder'] : null;
        $this->expirationYear = (isset($data['exp_year'])) ? $data['exp_year'] : null;
        $this->expirationMonth = (isset($data['exp_month'])) ? $data['exp_month'] : null;
        $this->status = (isset($data['status'])) ? $data['status'] : null;
        $this->partnerId = (isset($data['partner_id'])) ? $data['partner_id'] : null;
        $this->customerId = (isset($data['customer_id'])) ? $data['customer_id'] : null;
        $this->source = (isset($data['source'])) ? $data['source'] : null;
    }

    /**
     * @return int
     */
    public function getBrand()
    {
        return $this->brand;
    }

    /**
     * @return int
     */
    public function getCustomerId()
    {
        return $this->customerId;
    }

    /**
     * @return string
     */
    public function getExpirationMonth()
    {
        return $this->expirationMonth;
    }

    /**
     * @return string
     */
    public function getPan()
    {
        return $this->pan;
    }

    /**
     * @return string
     */
    public function getHolder()
    {
        return $this->holder;
    }

    /**
     * @return string
     */
    public function getExpirationYear()
    {
        return $this->expirationYear;
    }

    /**
     * @return int
     */
    public function getPartnerId()
    {
        return $this->partnerId;
    }

    /**
     * @return string
     */
    public function getSecurityCode()
    {
        return $this->securityCode;
    }

    /**
     * @return int
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * @return int
     */
    public function getStatus()
    {
        return $this->status;
    }
}
