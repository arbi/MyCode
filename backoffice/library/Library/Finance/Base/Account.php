<?php

namespace Library\Finance\Base;

use DDD\Dao\Finance\Transaction\TransactionAccounts;
use Library\Finance\Exception\MethodNotDefinedException;
use Library\Finance\Transaction\Transaction;
use Zend\ServiceManager\ServiceLocatorAwareTrait;
use Zend\ServiceManager\ServiceLocatorInterface;

abstract class Account implements IAccount
{
    use ServiceLocatorAwareTrait;

    const TYPE_CUSTOMER = 1;
    const TYPE_MONEY_ACCOUNT = 2;
    const TYPE_PARTNER = 3;
    const TYPE_SUPPLIER = 4;
    const TYPE_PEOPLE = 5;

    private static $accountNames = [
        self::TYPE_CUSTOMER => 'Customer',
        self::TYPE_MONEY_ACCOUNT => 'Money Account',
        self::TYPE_PARTNER => 'Partner',
        self::TYPE_SUPPLIER => 'Supplier',
        self::TYPE_PEOPLE => 'People',
    ];

    /**
     * @var \DDD\Dao\Finance\Transaction\TransactionAccounts $transactionAccountsDao
     */
    protected $transactionAccountsDao;

    /**
     * @var bool
     */
    protected $isForgetDirection = false;

    /**
     * @var int|null $transactionAccountId
     */
    private $transactionAccountId;

    /**
     * @var array|bool $account
     */
    protected $account;

    /**
     * @var int $accountId
     */
    protected $accountId;

    /**
     * @var double $amount
     */
    protected $amount;

    /**
     * Money direction. Two cases available - "from" and "to"
     *
     * @see \Library\Finance\Base\TransactionBase::DIRECTION_FROM
     * @see \Library\Finance\Base\TransactionBase::DIRECTION_TO
     *
     * @var string $direction
     */
    protected $direction;

    /**
     * @param ServiceLocatorInterface $serviceLocator
     * @throws \InvalidArgumentException
     */
    public function __construct(ServiceLocatorInterface $serviceLocator)
    {
        $this->setServiceLocator($serviceLocator);

        $this->transactionAccountsDao = new TransactionAccounts($this->getServiceLocator(), '\ArrayObject');
    }

    /**
     * @return int
     */
    public function getTransactionAccountId()
    {
        if (is_null($this->transactionAccountId)) {
            $transaction = $this->transactionAccountsDao->fetchOne([
                'type' => $this->getType(),
                'holder_id' => $this->getAccountId()
            ], ['id']);

            if (!$transaction) {
                throw new \RuntimeException('Cannot find transaction account by account type and holder id.');
            } else {
                $this->transactionAccountId = $transaction['id'];
            }
        }

        return $this->transactionAccountId;
    }

    /**
     * @return array|bool
     */
    public function getAccount()
    {
        return $this->account;
    }

    /**
     * @return bool
     */
    public function getCurrency()
    {
        return false;
    }

    /**
     * @return int
     */
    public function getAccountId()
    {
        return $this->accountId;
    }

    /**
     * @param int $accountId
     */
    public function setAccountId($accountId)
    {
        $this->accountId = $accountId;
    }

    /**
     * Depends on transaction direction the sign of the amount must be defined
     *
     * @return double
     */
    public function getAmount()
    {
        if (!$this->isForgetDirection && $this->getMoneyDirection() == TransactionBase::DIRECTION_FROM) {
            return -1 * $this->amount;
        }

        return $this->amount;
    }

    /**
     * Forget money direction when choosing amount sign (only in case of refund)
     *
     * @param bool|true $is
     */
    public function forgetDirection($is = true)
    {
        $this->isForgetDirection = $is;
    }

    /**
     * @param double $amount
     */
    public function setAmount($amount)
    {
        $this->amount = $amount;
    }

    /**
     * @return string
     */
    public function getMoneyDirection()
    {
        return $this->direction;
    }

    /**
     * @param string $direction
     */
    public function setMoneyDirection($direction)
    {
        $this->direction = $direction;
    }

    /**
     * @return int
     * @throws MethodNotDefinedException
     */
    public function getType()
    {
        throw new MethodNotDefinedException(__METHOD__ . '() method not defined.');
    }

    /**
     * It's an optional method for now. Tomorrow maybe we will be able to define in interface class.
     *
     * @throws MethodNotDefinedException
     */
    public function prepare()
    {
        throw new MethodNotDefinedException(__METHOD__ . '() method not defined.');
    }

    /**
     * @param int $accountId
     * @return string
     */
    public static function getAccountNameById($accountId)
    {
        if (array_key_exists($accountId, self::$accountNames)) {
            return self::$accountNames[$accountId];
        }

        return '';
    }
}
