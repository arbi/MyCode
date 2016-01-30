<?php

namespace CreditCard\Entity;

/**
 * Class LocalData
 * @package CreditCard\Entity
 *
 * @author Tigran Petrosyan
 */
class LocalData
{
    /**
     * @var int
     */
    protected $id;

    /**
     * @var credit card brand (Visa, Master, etc.)
     */
    protected $brand;

    /**
     * @var primary account number without last 10 digits
     */
    protected $firstDigits;

    /**
     * @var cc unique token
     */
    protected $token;

    /**
     * @var string
     */
    protected $salt;

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

    public function exchangeArray($data)
    {
        $this->id = (isset($data['id'])) ? $data['id'] : null;
        $this->brand = (isset($data['brand'])) ? $data['brand'] : null;
        $this->firstDigits = (isset($data['first_digits'])) ? $data['first_digits'] : null;
        $this->token = (isset($data['token'])) ? $data['token'] : null;
        $this->salt = (isset($data['salt'])) ? $data['salt'] : null;
        $this->status = (isset($data['status'])) ? $data['status'] : null;
        $this->partnerId = (isset($data['partner_id'])) ? $data['partner_id'] : null;
        $this->customerId = (isset($data['customer_id'])) ? $data['customer_id'] : null;
        $this->source = (isset($data['source'])) ? $data['source'] : null;
        $this->dateProvided = (isset($data['date_provided'])) ? $data['date_provided'] : null;
    }

    /**
     * @param \CreditCard\Entity\credit $brand
     */
    public function setBrand($brand)
    {
        $this->brand = $brand;
    }

    /**
     * @return \CreditCard\Entity\credit
     */
    public function getBrand()
    {
        return $this->brand;
    }

    /**
     * @param \CreditCard\Entity\primary $firstDigits
     */
    public function setFirstDigits($firstDigits)
    {
        $this->firstDigits = $firstDigits;
    }

    /**
     * @return \CreditCard\Entity\primary
     */
    public function getFirstDigits()
    {
        return $this->firstDigits;
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
     * @param \CreditCard\Entity\cc $token
     */
    public function setToken($token)
    {
        $this->token = $token;
    }

    /**
     * @return \CreditCard\Entity\cc
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * @param string $salt
     */
    public function setSalt($salt)
    {
        $this->salt = $salt;
    }

    /**
     * @return string
     */
    public function getSalt()
    {
        return $this->salt;
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
}
