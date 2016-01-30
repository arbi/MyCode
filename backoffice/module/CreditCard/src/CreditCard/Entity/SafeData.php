<?php

namespace CreditCard\Entity;

/**
 * Class SafeData
 * @package CreditCard\Entity
 *
 * @author Tigran Petrosyan
 */
class SafeData
{
    /**
     * @var credit card brand (Visa, Master, etc.)
     */
    protected $brand;

    /**
     * @var primary account number last 4 digits
     */
    protected $last4;

    /**
     * @var string card holder full name
     */
    protected $holder;

    public function exchangeArray($data)
    {
        $this->brand = (isset($data['brand'])) ? $data['brand'] : null;
        $this->last4 = (isset($data['last4'])) ? $data['last4'] : null;
        $this->holder = (isset($data['holder'])) ? $data['holder'] : null;
    }

    /**
     * @param \DDD\Domain\CreditCard\credit $brand
     */
    public function setBrand($brand)
    {
        $this->brand = $brand;
    }

    /**
     * @return \DDD\Domain\CreditCard\credit
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
     * @param \DDD\Domain\CreditCard\primary $last4
     */
    public function setLast4($last4)
    {
        $this->last4 = $last4;
    }

    /**
     * @return \DDD\Domain\CreditCard\primary
     */
    public function getLast4()
    {
        return $this->last4;
    }
}
