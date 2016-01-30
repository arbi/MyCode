<?php

namespace Library\Finance\Account;

use Library\Finance\Base\Account;
use DDD\Dao\Finance\Supplier as SupplierDao;
use Library\Finance\Exception\ServiceLocatorNotDefinedException;
use Library\Finance\Exception\SupplierNotFoundException;
use Zend\ServiceManager\ServiceLocatorInterface;

class Supplier extends Account
{
    /**
     * @var \DDD\Dao\Finance\Supplier $supplierDao
     */
    protected $supplierDao;

    /**
     * @return int
     */
    public function getType()
    {
        return Account::TYPE_SUPPLIER;
    }

    /**
     * @param int $accountId
     * @throws \InvalidArgumentException
     */
    public function getAccountById($accountId)
    {
        if (intval($accountId) > 0) {
            $this->account = $this->supplierDao->fetchOne(['id' => $accountId]);
        } else {
            throw new \InvalidArgumentException('Supplier account id is in bad format.');
        }
    }

    /**
     * @param array $params
     * @return int Supplier Id
     */
    public function create(array $params)
    {
        // Save supplier
        $this->accountId = $this->supplierDao->save($params);

        // Save supplier account for transactions
        $this->transactionAccountsDao->save([
            'type' => $this->getType(),
            'holder_id' => $this->accountId,
        ]);

        return $this->accountId;
    }

    /**
     * Pass supplier parameters as much as you have.
     *
     * @param array $params
     * @throws SupplierNotFoundException
     * @return int
     */
    public function save(array $params)
    {
        if (is_null($this->accountId)) {
            throw new SupplierNotFoundException('Supplier not defined.');
        }

        return $this->supplierDao->save($params, ['id' => $this->accountId]);
    }

    /**
     * @return void
     * @throws ServiceLocatorNotDefinedException
     */
    public function prepare()
    {
        $serviceLocator = $this->getServiceLocator();

        if ($serviceLocator instanceof ServiceLocatorInterface) {
            $this->supplierDao = new SupplierDao($serviceLocator, '\ArrayObject');
        } else {
            throw new ServiceLocatorNotDefinedException('Service Locator not defined.');
        }
    }
}
