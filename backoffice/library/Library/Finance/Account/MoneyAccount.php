<?php

namespace Library\Finance\Account;

use Library\Finance\Base\Account;
use DDD\Dao\MoneyAccount\MoneyAccount as MoneyAccountDao;
use Library\Finance\Exception\ServiceLocatorNotDefinedException;
use Library\Finance\Exception\SupplierNotFoundException;
use Zend\ServiceManager\ServiceLocatorInterface;

class MoneyAccount extends Account
{
    /**
     * @var \DDD\Dao\Finance\MoneyAccount $customerDao
     */
    protected $moneyAccountDao;

    /**
     * @var int $possessor
     */
    protected $possessor; // aka responsible person id

    /**
     * @return int
     */
    public function getType()
    {
        return Account::TYPE_MONEY_ACCOUNT;
    }

    /**
     * @return bool|int
     */
    public function getCurrency()
    {
        $account = $this->getAccount();

        if ($account) {
            return $account['currency_id'];
        }

        return false;
    }

    /**
     * @param array $params
     * @return int Money Account Id
     */
    public function create(array $params)
    {
        // Save money account
        $this->accountId = $this->moneyAccountDao->save($params);

        // Save money account account for transactions
        $this->transactionAccountsDao->save([
            'type' => $this->getType(),
            'holder_id' => $this->accountId,
        ]);

        return $this->accountId;
    }

    /**
     * Pass money account parameters as much as you have.
     *
     * @param array $params
     * @throws SupplierNotFoundException
     * @return int
     */
    public function save(array $params)
    {
        if (is_null($this->accountId)) {
            throw new SupplierNotFoundException('Money Account not defined.');
        }

        return $this->moneyAccountDao->save($params, ['id' => $this->accountId]);
    }

    /**
     * @return void
     * @throws ServiceLocatorNotDefinedException
     */
    public function prepare()
    {
        $serviceLocator = $this->getServiceLocator();

        if ($serviceLocator instanceof ServiceLocatorInterface) {
            $this->moneyAccountDao = new MoneyAccountDao($serviceLocator, '\ArrayObject');
        } else {
            throw new ServiceLocatorNotDefinedException('Service Locator not defined.');
        }

        if (intval($this->accountId) > 0) {
            $this->fetchAccountData();
        }
    }

    /**
     * @return void
     * @throws \InvalidArgumentException
     */
    public function fetchAccountData()
    {
        if (intval($this->accountId) > 0) {
            $this->account = $this->moneyAccountDao->fetchOne(['id' => $this->accountId]);
        } else {
            throw new \InvalidArgumentException('Money account id is in bad format.');
        }
    }

    /**
     * @return string
     */
    public function getPossessorId()
    {
        return $this->account['responsible_person_id'];
    }
}
