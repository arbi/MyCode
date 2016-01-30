<?php

namespace Library\Finance\Base;

use Library\Finance\Account\Customer;

trait CustomerHelper
{
    /**
     * @var string $key
     */
    protected $key;

    /**
     * @var \DDD\Dao\Finance\Customer $customerDao
     */
    protected $customerDao;

    /**
     * @var null|int $customerId
     */
    protected $customerId = null;

    /**
     * @var array|\Library\Finance\CreditCard\CreditCardEntity[] $creditCards
     */
    protected $creditCards = [];

    /**
     * @return bool|string
     */
    protected function getDate()
    {
        return date('Y-m-d H:i:s');
    }

    /**
     * @param string $ccNumber
     * @return string
     */
    protected function takeCCPart($ccNumber)
    {
        return substr($ccNumber, 0, -4);
    }

    /**
     * @param string $key
     */
    public function setKey($key)
    {
        $this->key = $key;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return "Key: {$this->key}, CustomerId: {$this->customerId}";
    }

    /**
     * @return bool
     */
    public function hasIdentity()
    {
        return (bool)$this->customerId;
    }
}
