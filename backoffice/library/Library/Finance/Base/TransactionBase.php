<?php

namespace Library\Finance\Base;

use DDD\Dao\Finance\Transaction;
use DDD\Dao\Finance\MoneyAccount;
use Library\Authentication\BackofficeAuthenticationService;
use Library\DbManager\TableGatewayManager;
use Library\Finance\Exception\NotFoundException;
use Library\Finance\Exception\NotSupportedOperationException;
use Zend\ServiceManager\ServiceLocatorAwareTrait;

abstract class TransactionBase
{
    use ServiceLocatorAwareTrait;

    const ACCOUNT_MONEY_ACCOUNT = 'MoneyAccount';
    const ACCOUNT_SUPPLIER = 'Supplier';
    const ACCOUNT_CUSTOMER = 'Customer';
    const ACCOUNT_PARTNER = 'Partner';
    const ACCOUNT_PEOPLE = 'People';

    const MODE_ADD = 1;
    const MODE_EDIT = 2;
    const MODE_DELETE = 3;

    const STATUS_OK = 1;

    const IS_VERIFIED = 1;
    const IS_UNVERIFIED = 0;

    const TYPE_NORMAL = 1;
    const TYPE_REFUND = 2;

    const DIRECTION_FROM = 'from';
    const DIRECTION_TO = 'to';

    const TRANSACTOR_TYPE_TRANSFER = 1;
    const TRANSACTOR_TYPE_EXPENSE = 2;
    const TRANSACTOR_TYPE_RESERVATION = 3;
    const TRANSACTOR_TYPE_PARTNER_COLLECTION = 4;
    const TRANSACTOR_TYPE_PARTNER_PAYMENT = 5;
    const TRANSACTOR_TYPE_DEBIT = 6;
    const TRANSACTOR_TYPE_CUSTOMER_COLLECTION = 7;

    /**
     * @var array
     */
    private static $accountBindings = [
        Account::TYPE_CUSTOMER => self::ACCOUNT_CUSTOMER,
        Account::TYPE_MONEY_ACCOUNT => self::ACCOUNT_MONEY_ACCOUNT,
        Account::TYPE_PARTNER => self::ACCOUNT_PARTNER,
        Account::TYPE_SUPPLIER => self::ACCOUNT_SUPPLIER,
        Account::TYPE_PEOPLE => self::ACCOUNT_PEOPLE,
    ];

    /**
     * @var array $availableTransactors
     */
    private $availableTransactors = [
        'Transfer',
        'Expense',
        'Reservation',
        'PartnerCollection',
        'PartnerPayment',
        'Debit',
        'CustomerCollection',
    ];

    /**
     * @var int $mode Transaction Mode
     */
    private $mode = self::MODE_ADD;

    /**
     * @var string $accountFromType
     */
    protected $accountFromType;

    /**
     * @var string $accountToType
     */
    protected $accountToType;

    /**
     * @var Account $accountFrom
     */
    protected $accountFrom;

    /**
     * @var Account $accountTo
     */
    protected $accountTo;

    /**
     * @var int $transactorId
     */
    protected $transactorId;

    /**
     * @var int $minorTransferId
     */
    protected $minorTransferId;

    /**
     * @var int
     */
    protected $transactionType = self::TYPE_NORMAL;

    /**
     * @var int
     */
    protected $transactorType;

    /**
     * @var \Datetime $transactionDateFrom
     */
    protected $transactionDateFrom;

    /**
     * @var \Datetime $transactionDateTo
     */
    protected $transactionDateTo;

    /**
     * @var TableGatewayManager $dao
     */
    protected $dao;

    /**
     * @var TableGatewayManager $transactionsDao
     */
    protected $transactionsDao;

    /**
     * @var TableGatewayManager $transactionAccountsDao
     */
    protected $transactionAccountsDao;

    /**
     * @var TableGatewayManager $moneyAccountsDao
     */
    protected $moneyAccountsDao;

    /**
     * @var BackofficeAuthenticationService
     */
    protected $auth;

    /**
     * The date when record added to database.
     *
     * @var \DateTime
     */
    protected $timestamp;

    /**
     * @var int $generalCurrency
     */
    protected $generalCurrency;

    /**
     * @var bool $isRefund
     */
    protected $isRefund = false;

    /**
     * @var int $isVerified
     */
    protected $isVerified = 0;

    /**
     * @var string $description
     */
    protected $description;

    /**
     * @param string $accountFrom
     * @param string $accountTo
     */
    public function __construct($accountFrom, $accountTo)
    {
        $this->accountFromType = $accountFrom;
        $this->accountToType = $accountTo;

        $this->detectTransactor();
    }

    /**
     * @throws \RuntimeException
     */
    private function detectTransactor()
    {
        $caller = explode('\\', get_called_class());
        $caller = array_pop($caller);

        if (in_array($caller, $this->availableTransactors)) {
            switch ($caller) {
                case 'Transfer':
                    $this->setTransactorType(self::TRANSACTOR_TYPE_TRANSFER);

                    break;
                case 'Expense':
                    $this->setTransactorType(self::TRANSACTOR_TYPE_EXPENSE);

                    break;
                case 'Reservation':
                    $this->setTransactorType(self::TRANSACTOR_TYPE_RESERVATION);

                    break;
                case 'PartnerCollection':
                    $this->setTransactorType(self::TRANSACTOR_TYPE_PARTNER_COLLECTION);

                    break;
                case 'PartnerPayment':
                    $this->setTransactorType(self::TRANSACTOR_TYPE_PARTNER_PAYMENT);

                    break;
                case 'Debit':
                    $this->setTransactorType(self::TRANSACTOR_TYPE_DEBIT);

                    break;
                case 'CustomerCollection':
                    $this->setTransactorType(self::TRANSACTOR_TYPE_CUSTOMER_COLLECTION);

                    break;
            }
        } else {
            throw new \RuntimeException("Caller not defined. You should register {$caller} as a Caller.");
        }
    }

    /**
     * @throws NotSupportedOperationException
     * @throws NotFoundException
     */
    public function prepare()
    {
        // Prepare resources
        $caller = explode('\\', get_called_class());
        $caller = array_pop($caller);

        if (in_array($caller, $this->availableTransactors)) {
            $class = "\\DDD\\Dao\\Finance\\Transaction\\{$caller}Transactions";

            if (class_exists($class)) {
                $this->dao = new $class($this->getServiceLocator(), '\ArrayObject');
            } else {
                throw new NotFoundException("Class {$class} not found.");
            }
        } else {
            throw new NotSupportedOperationException('Not supported operation.');
        }

        $this->transactionsDao = new Transaction\Transactions($this->getServiceLocator(), '\ArrayObject');
        $this->transactionAccountsDao = new Transaction\TransactionAccounts($this->getServiceLocator(), '\ArrayObject');
        $this->moneyAccountsDao = new MoneyAccount($this->getServiceLocator(), '\ArrayObject');

        $this->auth = $this->getServiceLocator()->get('library_backoffice_auth');
        $this->timestamp = new \DateTime('now');

        // Prepare refund
        if ($this->getIsRefund()) {
            $neutral = is_null($this->accountFrom) ? null : clone $this->accountFrom;
            $this->accountFrom = $this->accountTo;
            $this->accountTo = $neutral;
        }
    }

    /**
     * @param string $direction
     * @param string $accountType
     * @param int $accountId
     *
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     */
    protected function setAccount($direction, $accountType, $accountId)
    {
        /**
         * @var Account|null $account
         */
        $accountWho = 'account' . ucfirst($direction);

        if (in_array($accountType, self::getAvailableAccounts())) {
            $accountType = "\\Library\\Finance\\Account\\{$accountType}";

            if (class_exists($accountType)) {
                $account = new $accountType($this->getServiceLocator());
                $account->setAccountId($accountId);
                $account->setMoneyDirection($this->detectDirection($direction));
                $account->prepare();

                $this->$accountWho = $account;
            } else {
                throw new \RuntimeException(ucfirst($direction) . ' Account class not found.');
            }
        } else {
            if (is_null($accountType)) {
                $this->$accountWho = null;
            } else {
                throw new \InvalidArgumentException(ucfirst($direction) . ' Account is in bad format.');
            }
        }
    }

    /**
     * Method will return dao of called transaction. That should be generated
     * dynamically depends on caller object
     *
     * @return TableGatewayManager
     */
    protected function getDao()
    {
        return $this->dao;
    }

    /**
     * @return Transaction\TransactionAccounts
     */
    protected function getTransactionAccountDao()
    {
        return $this->transactionAccountsDao;
    }

    /**
     * @return Transaction\Transactions
     */
    protected function getTransactionsDao()
    {
        return $this->transactionsDao;
    }

    /**
     * @return MoneyAccount
     */
    protected function getMoneyAccountsDao()
    {
        return $this->moneyAccountsDao;
    }

    /**
     * @return Account
     */
    public function getAccountFrom()
    {
        return $this->accountFrom;
    }

    /**
     * @return Account
     */
    public function getAccountTo()
    {
        return $this->accountTo;
    }

    /**
     * @param string $date
     * @throws \InvalidArgumentException
     */
    public function setTransactionDate($date)
    {
        try {
            $this->transactionDateFrom = new \DateTime($date);
            $this->transactionDateTo = new \DateTime($date);
        } catch (\Exception $ex) {
            throw new \InvalidArgumentException('Date from or date to is in bad format.');
        }
    }

    /**
     * @param double $amount
     */
    public function setAmountFrom($amount)
    {
        if (!is_null($this->accountFrom)) {
            $this->accountFrom->setAmount($amount);
        }
    }

    /**
     * @param double $amount
     */
    public function setAmountTo($amount)
    {
        if (!is_null($this->accountTo)) {
            $this->accountTo->setAmount($amount);
        }
    }

    /**
     * @return int
     * @throws \Exception
     */
    protected function getCurrency()
    {
        if (is_null($this->generalCurrency)) {
            throw new \Exception('Currency not defined.');
        }

        return $this->generalCurrency;
    }

    /**
     * @param int $currencyId
     */
    protected function setCurrency($currencyId)
    {
        $this->generalCurrency = $currencyId;
    }

    /**
     * @return int
     * @throws \Exception
     */
    public function getIsVerified()
    {
        return $this->isVerified;
    }

    /**
     * @param bool $isVerified
     */
    public function setIsVerified($isVerified = true)
    {
        $this->isVerified = (int)$isVerified;
    }

    /**
     * @param string $date
     * @throws \InvalidArgumentException
     */
    public function setTransactionDateFrom($date)
    {
        try {
            $this->transactionDateFrom = new \DateTime($date);
        } catch (\Exception $ex) {
            throw new \InvalidArgumentException('Date from is in bad format.');
        }
    }

    /**
     * @param string $date
     * @throws \InvalidArgumentException
     */
    public function setTransactionDateTo($date)
    {
        try {
            $this->transactionDateTo = new \DateTime($date);
        } catch (\Exception $ex) {
            throw new \InvalidArgumentException('Date to is in bad format.');
        }
    }

    /**
     * @return string
     * @throws \RuntimeException
     */
    public function getTransactionDate()
    {
        if (!($this->transactionDateFrom instanceof \DateTime)) {
            throw new \RuntimeException('Transaction date not set yet.');
        }

        return $this->transactionDateFrom->format('Y-m-d H:i:s');
    }

    /**
     * @return string
     * @throws \RuntimeException
     */
    public function getTransactionDateFrom()
    {
        if (!($this->transactionDateFrom instanceof \DateTime)) {
            throw new \RuntimeException('Transaction date_from not set yet.');
        }

        return $this->transactionDateFrom->format('Y-m-d H:i:s');
    }

    /**
     * @return string
     * @throws \RuntimeException
     */
    public function getTransactionDateTo()
    {
        if (!($this->transactionDateTo instanceof \DateTime)) {
            throw new \RuntimeException('Transaction date_to not set yet.');
        }

        return $this->transactionDateTo->format('Y-m-d H:i:s');
    }

    /**
     * @return string
     * @throws \RuntimeException
     */
    public function getCreationDate()
    {
        if (!($this->timestamp instanceof \DateTime)) {
            throw new \RuntimeException('Transaction creation date not set yet.');
        }

        return $this->timestamp->format('Y-m-d H:i:s');
    }

    /**
     * @return int
     */
    protected function getUserId()
    {
        return $this->auth->getIdentity()->id;
    }

    /**
     * @return int
     */
    protected function getType()
    {
        return $this->transactionType;
    }

    /**
     * @param int $type
     * @throws \Exception
     */
    protected function setType($type)
    {
        if (!in_array($type, [self::TYPE_NORMAL, self::TYPE_REFUND])) {
            throw new \Exception('Undefined transaction type.');
        }

        $this->transactionType = $type;
    }

    /**
     * @return int
     */
    protected function getTransactorType()
    {
        return $this->transactorType;
    }

    /**
     * @param int $type
     * @throws \Exception
     */
    protected function setTransactorType($type)
    {
        $this->transactorType = $type;
    }

    /**
     * @return int
     */
    protected function getTransactorId()
    {
        return $this->transactorId;
    }

    /**
     * @param int $transactorId
     */
    protected function setTransactorId($transactorId)
    {
        $this->transactorId = $transactorId;
    }

    /**
     * @return int
     */
    public function getMinorTransferId()
    {
        return $this->minorTransferId;
    }

    /**
     * @param int $minorTransferId
     */
    public function setMinorTransferId($minorTransferId)
    {
        $this->minorTransferId = $minorTransferId;
    }

    /**
     * @return int
     */
    public function getMode()
    {
        return $this->mode;
    }

    /**
     * @param int $mode
     */
    public function setMode($mode)
    {
        $this->mode = $mode;
    }

    /**
     * @param bool|int $isRefund
     * @throws \RuntimeException
     */
    public function setIsRefund($isRefund)
    {
        if (is_null($this->accountFrom) && is_null($this->accountTo)) {
            throw new \RuntimeException('Call setIsRefund() before setAccountIdentity() or do not call overall.');
        }

        $this->isRefund = $isRefund;
    }

    /**
     * @return int
     */
    public function getIsRefund()
    {
        return $this->isRefund ? 1 : 0;
    }

    /**
     * @param string $description
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }

    /**
     * @return string
     */
    protected function getDescription()
    {
        return $this->description;
    }

    /**
     *        Maybe it is not a good idea to get constants via Reflections :/
     * @todo: Think about
     *
     * @return array
     */
    public static function getAvailableAccounts()
    {
        $reflection = new \ReflectionClass(__CLASS__);
        $constants = $reflection->getConstants();
        $constantsFiltered = [];

        foreach ($constants as $k => $v) {
            if (strpos($k, 'ACCOUNT_') !== false) {
                array_push($constantsFiltered, $v);
            }
        }

        return $constantsFiltered;
    }

    /**
     * @param int $accountTypeId
     * @return bool
     * @throws \Exception
     */
    public static function getAccountTypeById($accountTypeId)
    {
        if ($accountTypeId) {
            if (isset(self::$accountBindings[$accountTypeId])) {
                return self::$accountBindings[$accountTypeId];
            }
        } else {
            return '';
        }

        throw new \Exception('Bad account type id.');
    }

    /**
     * To truly detect transaction money direction we need to take into account
     * refundability of transaction
     *
     * @param string $direction
     * @return string
     */
    private function detectDirection($direction)
    {
        if ($this->getIsRefund()) {
            if (TransactionBase::DIRECTION_FROM == $direction) {
                $direction = TransactionBase::DIRECTION_TO;
            } else {
                $direction = TransactionBase::DIRECTION_FROM;
            }
        }

        return $direction;
    }
}
