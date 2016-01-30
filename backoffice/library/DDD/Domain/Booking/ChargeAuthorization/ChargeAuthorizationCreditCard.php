<?php

namespace DDD\Domain\Booking\ChargeAuthorization;

/**
 * Class ChargeAuthorizationCreditCard
 * @package DDD\Domain\Booking\ChargeAuthorization
 *
 * @author Tigran Petrosyan
 */
class ChargeAuthorizationCreditCard
{
    /**
     * @var int
     */
    protected $id;

    /**
     * @var string
     */
    protected $brand;

    /**
     * @var string
     */
    protected $last4Digits;

    /**
     * @var string
     */
    protected $holder;

    /**
     * @var string
     */
    protected $token;

    public function exchangeArray($data)
    {
        $this->id = (isset($data['id'])) ? $data['id'] : null;
        $this->brand = (isset($data['brand'])) ? $data['brand'] : null;
        $this->last4Digits = (isset($data['last_4_digits'])) ? $data['last_4_digits'] : null;
        $this->holder = (isset($data['holder'])) ? $data['holder'] : null;
        $this->token = (isset($data['token'])) ? $data['token'] : null;
    }

    /**
     * @param string $brand
     */
    public function setBrand($brand)
    {
        $this->brand = $brand;
    }

    /**
     * @return string
     */
    public function getBrand()
    {
        return $this->brand;
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
     * @param string $token
     */
    public function setToken($token)
    {
        $this->token = $token;
    }

    /**
     * @return string
     */
    public function getToken()
    {
        return $this->token;
    }
}
