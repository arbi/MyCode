<?php

namespace Library\Finance\Account;

use DDD\Dao\Finance\Customer as CustomerDao;
use Library\Finance\Base\Account;
use Library\Finance\Base\CustomerHelper;
use Library\Finance\CreditCard\CreditCard;
use Library\Finance\CreditCard\CreditCardEntity;
use Library\Finance\Exception\CustomerNotFoundException;
use Library\Finance\Exception\ServiceLocatorNotDefinedException;
use Library\Finance\Finance;
use Library\Utility\Helper;
use Zend\ServiceManager\ServiceLocatorInterface;

class Customer extends Account
{
    use CustomerHelper;

    /**
     * @return int
     */
    public function getType()
    {
        return Account::TYPE_CUSTOMER;
    }

    /**
     * @param int $accountId
     */
    public function setAccountId($accountId)
    {
        $this->customerId = $accountId;
    }

    /**
     * @param int $accountId
     */
    public function getAccountById($accountId)
    {
        // not implemented yet
    }

    /**
     * Pass customer parameters as much as you have.
     *
     * @param array $params Only "email" and "token" for now
     *
     * @throws CustomerNotFoundException
     * @return int
     */
    public function save(array $params)
    {
        if (is_null($this->customerId)) {
            throw new CustomerNotFoundException('Customer not defined.');
        }

        if (isset($params['email'])) {
            $this->customerDao->save(['email' => $params['email']], ['id' => $this->customerId]);
        }
    }

    /**
     * @return void
     * @throws ServiceLocatorNotDefinedException
     */
    public function prepare()
    {
        $serviceLocator = $this->getServiceLocator();

        if ($serviceLocator instanceof ServiceLocatorInterface) {
            $this->customerDao =         new CustomerDao($serviceLocator, '\ArrayObject');
        } else {
            throw new ServiceLocatorNotDefinedException('Service Locator not defined.');
        }
    }
}
