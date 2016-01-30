<?php

namespace CreditCard\Entity;

/**
 * Class RemoteData
 * @package CreditCard\Entity
 *
 * @author Tigran Petrosyan
 */
class RemoteData
{
    /**
     * @var string last 10 digits
     */
    protected $last10;

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

    public function exchangeArray($data)
    {
        $this->last10 = (isset($data['last10'])) ? $data['last10'] : null;
        $this->securityCode = (isset($data['security_code'])) ? $data['security_code'] : null;
        $this->holder = (isset($data['holder'])) ? $data['holder'] : null;
        $this->expirationYear   = (isset($data['exp_year'])) ? $data['exp_year'] : null;
        $this->expirationMonth  = (isset($data['exp_month'])) ? $data['exp_month'] : null;
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
     * @param string $last10
     */
    public function setLast10($last10)
    {
        $this->last10 = $last10;
    }

    /**
     * @return string
     */
    public function getLast10()
    {
        return $this->last10;
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
}
