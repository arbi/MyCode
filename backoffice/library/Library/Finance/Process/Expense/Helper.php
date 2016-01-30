<?php

namespace Library\Finance\Process\Expense;

use DDD\Dao\Finance\Expense\Expenses;
use DDD\Dao\Finance\Transaction\TransactionAccounts;
use DDD\Service\User;
use Library\Authentication\BackofficeAuthenticationService;
use Library\Finance\Exception\NotFoundException;
use Library\Finance\Finance;
use Zend\ServiceManager\ServiceLocatorAwareTrait;

class Helper
{
    use ServiceLocatorAwareTrait;

    const STATUS_CLOSED = 0;
    const STATUS_PENDING = 1;
    const STATUS_GRANTED = 2;
    const STATUS_DECLINED = 3;

    const FIN_STATUS_NEW = 1;
    const FIN_STATUS_READY = 3;
    const FIN_STATUS_SETTLED = 4;

    const TYPE_DECLARE_AN_EXPENSE = 0;
    const TYPE_REQUEST_AN_ADVANCE = 1;
    const TYPE_PAY_AN_INVOICE = 2;
    const TYPE_ORDER_EXPENSE = 3;

    const ITEM_STATUS_PENDING = 1;
    const ITEM_STATUS_APPROVED = 2;
    const ITEM_STATUS_REJECTED = 3;
    const ITEM_STATUS_COMPLETED = 4;

    CONST COST_CENTERS_LIMIT_IN_PO_ITEM_SEARCH = 10;

    public static $statuses = [
        self::STATUS_CLOSED => 'Closed',
        self::STATUS_PENDING => 'Pending',
        self::STATUS_GRANTED => 'Granted',
        self::STATUS_DECLINED => 'Declined',
    ];

    public static $itemStatuses = [
        self::ITEM_STATUS_PENDING => 'Pending',
        self::ITEM_STATUS_APPROVED => 'Approved',
        self::ITEM_STATUS_REJECTED => 'Rejected',
        self::ITEM_STATUS_COMPLETED => 'Completed',
    ];

    public static $financeStatuses = [
        self::STATUS_PENDING => 'New',
        self::FIN_STATUS_READY => 'Closed for Review',
        self::FIN_STATUS_SETTLED => 'Settled',
    ];

    public static $types = [
        self::TYPE_DECLARE_AN_EXPENSE => 'Declare an Expense',
        self::TYPE_REQUEST_AN_ADVANCE => 'Request an Advance',
        self::TYPE_PAY_AN_INVOICE => 'Pay an Invoice',
        self::TYPE_ORDER_EXPENSE => 'Order Expense',
    ];

    /**
     * @var Finance
     */
    protected $finance;

    /**
     * @var null|int
     */
    protected $expenseId = null;

    /**
     * Data requested from form
     *
     * @var array
     */
    protected $expenseTicketData;

    /**
     * Data exacly matched with DB
     *
     * @var array
     */
    protected $expenseTicketOrignalData = [];

    /**
     * @var Item[]|array $items
     */
    protected $items = [];

    /**
     * @var Transaction[]|array $transactions
     */
    protected $transactions = [];

    /**
     * @var Expenses $dao
     */
    protected $dao;

    /**
     * @var TransactionAccounts $dao
     */
    protected $transactionAccounDao;

    /**
     * @var array $tmp
     */
    protected $tmp = [];

    /**
     * @param Finance $finance
     * @param null|int $expenseId
     */
    public function __construct(Finance $finance, $expenseId)
    {
        $this->finance = $finance;
        $this->setServiceLocator($finance->getServiceLocator());

        $this->expenseId = $expenseId;

        $this->dao = new Expenses($this->getServiceLocator());
        $this->transactionAccounDao = new TransactionAccounts($this->getServiceLocator());
    }

    /**
     * @param array $expenseData
     * @return array
     */
    protected function applyExpenseTicketDataBinding($expenseData)
    {
        /**
         * @var BackofficeAuthenticationService $auth
         * @var User $userService
         */
        if ($this->expenseId) {
            unset($expenseData['id']);
        }

        if (empty($expenseData['managerId'])) {
            $userService = $this->getServiceLocator()->get('service_user');
            $auth = $this->getServiceLocator()->get('library_backoffice_auth');
            $expenseData['managerId'] = $userService->getBudgetHolderUserManagerId($auth->getIdentity()->id);
        }

        if (empty($expenseData['title'])) {
            $expenseData['title'] = '';
        }

        if (empty($expenseData['item'])) {
            $expenseData['item'] = null;
        }

        if (empty($expenseData['resubmission'])) {
            $expenseData['resubmission'] = null;
        }

        if (empty($expenseData['limit'])) {
            $expenseData['limit'] = null;
        }

        if (empty($expenseData['purpose'])) {
            $expenseData['purpose'] = '';
        }

        if (empty($expenseData['budget'])) {
            $expenseData['budget'] = null;
        }

        if (empty($expenseData['expectedCompletionDate'])) {
            $expenseData['expectedCompletionDateStart'] = $expenseData['expectedCompletionDateEnd'] = null;
        } else {
            $expectedCompletionDateArray = explode(' - ', $expenseData['expectedCompletionDate']);
            $expenseData['expectedCompletionDateStart'] = date('Y-m-d', strtotime($expectedCompletionDateArray[0]));
            $expenseData['expectedCompletionDateEnd'] = date('Y-m-d', strtotime($expectedCompletionDateArray[1]));
        }

        return $expenseData;
    }

    /**
     * @return bool
     */
    protected function validate()
    {
        return true;
    }

    /**
     * Check weather expense identified
     * @return bool
     */
    protected function identified()
    {
        return (bool)$this->expenseId;
    }

    /**
     * @return int|null
     */
    public function getExpenseId()
    {
        return $this->expenseId;
    }

    /**
     * @param int $expenseId
     */
    public function setExpenseId($expenseId)
    {
        $this->expenseId = $expenseId;
    }

    /**
     * @return array
     */
    public function getTicketData()
    {
        return $this->expenseTicketData;
    }

    /**
     * @param array $data
     */
    protected function setTicketOriginalData(array $data)
    {
        $this->expenseTicketOrignalData = $data;
    }

    /**
     * @param bool $force
     * @return array
     * @throws NotFoundException
     * @throws \RuntimeException
     */
    public function getTicketOriginalData()
    {
        if ($this->getExpenseId()) {
            $ticketDomain = $this->dao->getTicketSimpleData($this->getExpenseId());

            if ($ticketDomain) {
                $this->expenseTicketOrignalData = [
                    'id' => $ticketDomain->getId(),
                    'account_id' => $ticketDomain->getAccountId(),
                    'account_reference' => $ticketDomain->getAccountReference(),
                    'creator_id' => $ticketDomain->getCreatorId(),
                    'manager_id' => $ticketDomain->getManagerId(),
                    'currency_id' => $ticketDomain->getCurrencyId(),
                    'date_created' => $ticketDomain->getDateCreated(),
                    'purpose' => $ticketDomain->getDateCreated(),
                    'ticket_balance' => $ticketDomain->getTicketBalance(),
                    'deposit_balance' => $ticketDomain->getDepositBalance(),
                ];
            } else {
                throw new NotFoundException('Expense not found.');
            }
        } else {
            throw new \RuntimeException('Expense not identified.');
        }

        return $this->expenseTicketOrignalData;
    }

    /**
     * @return Finance
     */
    public function getFinance()
    {
        return $this->finance;
    }

    /**
     * @return Expenses
     */
    public function getDao()
    {
        return $this->dao;
    }

    /**
     * @return TransactionAccounts
     */
    public function getTransactionAccountDao()
    {
        return $this->transactionAccounDao;
    }

    /**
     * @return string
     */
    protected function getCreationDate()
    {
        if (!isset($this->tmp['creation_date'])) {
            $this->tmp['creation_date'] = date('Y-m-d H:i:s');
        }

        return $this->tmp['creation_date'];
    }

    /**
     * @param int $statusId
     * @return string
     */
    public static function getStatusById($statusId)
    {
        if (!isset(self::$statuses[$statusId])) {
            return '';
        }

        return self::$statuses[$statusId];
    }

    /**
     * @param int $statusId
     * @return string
     */
    public static function getItemStatusById($statusId)
    {
        if (!isset(self::$itemStatuses[$statusId])) {
            return '';
        }

        return self::$itemStatuses[$statusId];
    }

    /**
     * @param int $typeId
     * @return string
     */
    public static function getTypeById($typeId)
    {
        if (!isset(self::$types[$typeId])) {
            return '';
        }

        return self::$types[$typeId];
    }

    public function getItems()
    {
        return $this->items;
    }
}
