<?php

namespace DDD\Service\Finance\Expense;

use DDD\Dao\Finance\Expense\ExpenseAttachments;
use DDD\Dao\MoneyAccount\MoneyAccount;
use DDD\Service\Currency\CurrencyVault;
use DDD\Service\Finance\Budget;
use DDD\Service\Finance\Expense\ExpenseAttachments as ExpenseAttachmentsService;
use DDD\Dao\Finance\Expense\ExpenseCost;
use DDD\Dao\Finance\Expense\ExpenseItem;
use DDD\Dao\Finance\Expense\ExpenseItemAttachments;
use DDD\Dao\Finance\Expense\Expenses;
use DDD\Dao\Finance\Transaction\ExpenseTransactions;
use DDD\Dao\Finance\Transaction\Transactions;
use DDD\Service\ApartmentGroup;
use DDD\Service\Finance\Transaction\PurchaseOrderTransaction;
use DDD\Service\Finance\TransactionAccount;
use DDD\Service\Notifications;
use DDD\Service\ServiceBase;
use DDD\Service\Team\Team;
use DDD\Service\User;
use DDD\Service\User\Main;
use DDD\Service\WHOrder\Order;
use Library\ActionLogger\Logger;
use Library\Authentication\BackofficeAuthenticationService;
use Library\ChannelManager\Anonymous;
use Library\Constants\Constants;
use Library\Constants\DbTables;
use Library\Constants\Roles;
use Library\Finance\Base\Account;
use Library\Finance\Exception\ExpenseCustomException;
use Library\Finance\Exception\NotFoundException;
use Library\Finance\Finance;
use Library\Finance\Process\Expense\Helper;
use Library\Finance\Process\Expense\Ticket;
use Library\Finance\Transaction\Transactor\Expense;
use Zend\Db\Sql\Predicate\Expression;
use Zend\Db\Sql\Where;
use Zend\Stdlib\Parameters;
use Zend\Stdlib\ParametersInterface;
use Zend\Validator\File\Extension;
use Zend\View\Model\ViewModel;
use \DDD\Service\Currency\Currency as CurrencyService;
use Library\Finance\Base\TransactionBase;

class ExpenseTicket extends ServiceBase
{
    protected static $allowedExtensions = ['txt', 'pdf', 'doc', 'docx', 'csv', 'xls', 'xlsx', 'png', 'jpg', 'jpeg', 'gif', 'zip', 'tar', 'tar.gz', 'gz', 'rar', '7z'];
    private $currencies = [];

    /**
     * @return array
     */
    public static function getAllowedExtensions()
    {
        return self::$allowedExtensions;
    }

    /**
     * using HTML5 accept="..." attribute did not work well on all browsers
     */
    protected static $allowedMimeTypes = [
        'text/plain',
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/octet-stream',
        'text/csv',
        'application/excel',
        'application/vnd.ms-excel',
        'application/x-excel',
        'application/x-msexcel',
        'image/png',
        'image/jpeg',
        'image/pjpeg',
        'image/gif',
        'application/zip',
        'application/gzip',
        'application/x-rar'
    ];

    /**
     * @return array
     */
    public static function getAllowedMimeTypes()
    {
        return self::$allowedMimeTypes;
    }

    CONST ATTACHMENT_PREVIEW_TYPE_TICKET = 1;
    CONST ATTACHMENT_PREVIEW_TYPE_ITEM   = 2;

    CONST MAX_COST_CENTERS_PER_ITEM      = 6;

    CONST IS_NOT_REFUND = 0;
    CONST IS_REFUND     = 1;

    /**
     * @param array $params
     * @param array $permissions
     * @return array
     */

    public function getExpenseList(array $params, array $permissions)
    {
        $expenses = $this->getExpenses($params, $permissions);
        $count = $expenses['count'];
        $expenseList = [];

        if ($expenses['data']->count()) {
            foreach ($expenses['data'] as $expense) {
                $validity = '';
                if (!is_null($expense['expected_completion_date_start']) && !is_null($expense['expected_completion_date_end'])) {
                    $validity = date(Constants::GLOBAL_DATE_FORMAT, strtotime($expense['expected_completion_date_start'])) . ' - ' .
                        date(Constants::GLOBAL_DATE_FORMAT, strtotime($expense['expected_completion_date_end']));
                }

                array_push($expenseList, [
                    $expense['id'],
                    date(Constants::GLOBAL_DATE_FORMAT, strtotime($expense['date_created'])),
                    $validity,
                    Helper::$statuses[$expense['status']],
                    Helper::$financeStatuses[$expense['finance_status']],
                    $expense['ticket_balance'],
                    $expense['expense_limit'],
                    $expense['currency_code'],
                    '<div class="text-center"><span class="glyphicon ' .
                    'glyphicon-comment" data-toggle="popover" data-trigger="hover" ' .
                    'data-placement="top" data-content="' . $expense['purpose'] . '"></span></div>'
                ]);
            }
        }

        return [
            'iTotalRecords' => $count,
            'iTotalDisplayRecords' => $count,
            'iDisplayStart' => $params['iDisplayStart'],
            'iDisplayLength' => $params['iDisplayLength'],
            'aaData' => $expenseList,
        ];
    }

    /**
     * @param array $params
     * @param array $permissions
     * @return array
     */
    public function getExpenseListToDownload(array $params, array $permissions)
    {
        $expenses = $this->getExpensesToDownload($params, $permissions);
        $result = [[
            'PO Id',
            'Created',
            'Validity',
            'Approval',
            'Status',
            'Balance',
            'Limit',
            'CUR',
            'Purpose'
        ]];

        if ($expenses->count()) {
            foreach ($expenses as $expense) {
                $validity = '';
                if (!is_null($expense['expected_completion_date_start']) && !is_null($expense['expected_completion_date_end'])) {
                    $validity = date(Constants::GLOBAL_DATE_FORMAT, strtotime($expense['expected_completion_date_start'])) . ' - ' .
                        date(Constants::GLOBAL_DATE_FORMAT, strtotime($expense['expected_completion_date_end']));
                }
                array_push($result, [
                    $expense['id'],
                    date(Constants::GLOBAL_DATE_FORMAT, strtotime($expense['date_created'])),
                    $validity,
                    $expense['status'] ? Helper::$statuses[$expense['status']]: ' - ',
                    $expense['finance_status'] ? Helper::$financeStatuses[$expense['finance_status']]: ' - ',
                    $expense['ticket_balance'],
                    $expense['expense_limit'],
                    $expense['currency_code'],
                    $expense['purpose']
                ]);
            }
        }

        return $result;
    }

    /**
     * @param array $params
     * @param array $permissions
     * @return array
     */
    public function validateDownloadCsv(array $params, array $permissions)
    {
        $expenseItemDao = new ExpenseItem($this->getServiceLocator(), '\ArrayObject');
        $where = $this->prepareStatementForExpenseList($params, $permissions);
        $expenses = $expenseItemDao->getExpensesCountToDownload($where);
        return $expenses->count() <= Constants::MAX_ROW_COUNT;
    }

    /**
     * @param array $params
     * @param array $permissions
     * @return \ArrayObject[]|array
     */
    private function getExpenses(array $params, array $permissions)
    {
        $expenseDao = new Expenses($this->getServiceLocator(), '\ArrayObject');
        $where = $this->prepareStatementForExpenseList($params, $permissions);

        return [
            'data' => $expenseDao->getExpenses($where, $params),
            'count' => $expenseDao->getCount($where),
        ];
    }

    /**
     * @param array $params
     * @param array $permissions
     * @return \array[]|\DDD\Domain\Finance\Expense\Expenses[]|\Zend\Db\ResultSet\ResultSet
     */
    private function getExpensesToDownload(array $params, array $permissions)
    {
        $expenseItemDao = new ExpenseItem($this->getServiceLocator(), '\ArrayObject');
        $where = $this->prepareStatementForExpenseList($params, $permissions);
        return $expenseItemDao->getExpensesToDownload($where);
    }

    /**
     * @param array $params
     * @param array $permissions
     * @return array
     */
    private function prepareStatementForExpenseList(array $params, array $permissions)
    {
        $table = DbTables::TBL_EXPENSES;
        $where = [];

        $isFinance = $permissions['isFinance'];

        // General cases
        if (!empty($params['id'])) {
            array_push($where, $table . '.id = ' . $params['id']);
        }

        if (!empty($params['currency_id'])) {
            array_push($where, $table . '.currency_id = ' . $params['currency_id']);
        }

        if (!empty($params['manager_id']) && $isFinance) {
            array_push($where, $table . '.manager_id =' . $params['manager_id']);
        }

        if (!empty($params['status']) && $isFinance) {
            array_push($where, $table . '.status =' . $params['status']);
        }

        if (!empty($params['finance_status']) && $isFinance) {
            array_push($where, $table . '.finance_status =' . $params['finance_status']);
        }

        if (!empty($params['creator_id']) && $isFinance) {
            array_push($where, $table . '.creator_id =' . $params['creator_id']);
        }

        if (!empty($params['title']) && $isFinance) {
            array_push($where, $table . '.title like (\'%' . $params['title'] . '%\')');
        }

        if (!empty($params['creation_date'])) {
            list($dateFrom, $dateTo) = explode(' - ', $params['creation_date']);

            array_push($where, $table . '.date_created between "' . $dateFrom . '" and "' . date('Y-m-d', strtotime('+1 day', strtotime($dateTo))) . '"');
        }

        if (!empty($params['expected_completion_date'])) {
            list($dateFrom, $dateTo) = explode(' - ', $params['expected_completion_date']);
            $dateFrom = date('Y-m-d', strtotime($dateFrom));
            $dateTo = date('Y-m-d', strtotime($dateTo));
            array_push($where, 'DATE(' . $table . '.expected_completion_date_start) >= DATE("' . $dateFrom . '") AND DATE(' . $table . '.expected_completion_date_end) <= DATE("' . $dateTo . '") ');
        }

        return $where;
    }

    /**
     * @param int $ticketId
     * @return array
     * @throws NotFoundException
     */
    public function getTicketData($ticketId)
    {
        /**
         * @var Logger $logger
         */
        $expenseDao = new Expenses($this->getServiceLocator(), '\ArrayObject');
        $expenseItemDao = new ExpenseItem($this->getServiceLocator(), '\ArrayObject');
        $expenseTransactionDao = new ExpenseTransactions($this->getServiceLocator(), '\ArrayObject');
        $logger = $this->getServiceLocator()->get('ActionLogger');

        $expenseData = $expenseDao->getTicketData($ticketId);

        if (!$expenseData) {
            throw new NotFoundException('Purchase order not found.');
        }

        if (!empty($expenseData['expected_completion_date_start']) && !empty($expenseData['expected_completion_date_end'])) {
            $expectedCompletionDate = date(Constants::GLOBAL_DATE_FORMAT, strtotime($expenseData['expected_completion_date_start'])) . ' - ' . date(Constants::GLOBAL_DATE_FORMAT, strtotime($expenseData['expected_completion_date_end']));
        } else {
            $expectedCompletionDate = NULL;
        }

        $result = [];
        $logger->setOutputFormat(Logger::OUTPUT_HTML);

        if ($expenseData) {
            $result['ticket'] = [
                'id' => $expenseData['id'],
                'ticket_creator_id' => $expenseData['creator_id'],
                'ticket_creator' => $expenseData['ticket_creator'],
                'currency_id' => $expenseData['currency_id'],
                'currency' => $expenseData['currency'],
                'date_created' => $expenseData['date_created'],
                'title' => $expenseData['title'],
                'purpose' => $expenseData['purpose'],
                'ticket_manager' => $expenseData['manager_id'],
                'limit' => $expenseData['limit'],
                'expected_completion_date' => $expectedCompletionDate,
                'ticket_balance' => $expenseData['ticket_balance'],
                'deposit_balance' => $expenseData['deposit_balance'],
                'item_balance' => $expenseData['item_balance'],
                'transaction_balance' => $expenseData['transaction_balance'],
                'status' => $expenseData['status'],
                'finance_status' => $expenseData['finance_status'],
                'budget' => $expenseData['budget_id'],
                'comment' => $logger->get(Logger::MODULE_EXPENSE, $expenseData['id']),
            ];
        }

        $result['itemCount'] = $expenseItemDao->getCount(['expense_id' => $ticketId])['count'];
        $result['transactionCount'] = $expenseTransactionDao->getCount(['expense_id' => $ticketId, 'status' => Expense::STATUS_NORMAL])['count'];

        return $result;
    }

    /**
     * @param int $expenseId
     * @return \ArrayObject|bool
     * @throws \RuntimeException
     */
    public function getTicketBalance($expenseId)
    {
        $expenseDao = new Expenses($this->getServiceLocator(), '\ArrayObject');
        $result = $expenseDao->getTicketBalance($expenseId);

        if (!$result) {
            throw new \RuntimeException("Purchase order #{$expenseId} not found.");
        }

        if ($result['finance_status'] == Ticket::FIN_STATUS_SETTLED) {
            throw new \RuntimeException("The PO id that you have entered is already settled. Please either put another ID or unsettle the PO.");
        }

        return [
            'balance' => $result['ticket_balance'],
            'currency' => $result['currency'],
        ];
    }

    /**
     * Note! $itemIdList argument here to improve performance and do not walk over items twice.
     *
     * @param ExpenseCost $costCenterDao
     * @param \Zend\Db\ResultSet\ResultSet|array[] $items
     * @param array $itemIdList
     * @return array
     */
    public function getCostCenters($costCenterDao, $items, &$itemIdList = [])
    {
        $costCenterList = [];
        $localItemIdList = [];

        if (count($items)) {
            foreach ($items as $item) {
                array_push($localItemIdList, $item['id']);

                $itemCostCenters = $costCenterDao->getItemCostCenters($item['id']);
                $itemCostCenterList = [];

                if (count($itemCostCenters)) {
                    foreach ($itemCostCenters as $itemCostCenter) {
                        array_push($itemCostCenterList, [
                            'id' => $itemCostCenter['id'],
                            'type' => $itemCostCenter['type'],
                            'name' => $itemCostCenter['name'],
                            'label' => $itemCostCenter['label'],
                            'unique_id' => $itemCostCenter['type'] . '_' . $itemCostCenter['id'],
                            'currency_id' => $itemCostCenter['currency_id'],
                        ]);
                    }
                }

                $costCenterList[$item['id']] = $itemCostCenterList;
            }
        }

        $itemIdList = $localItemIdList;

        return $costCenterList;
    }

    /**
     * @param int $itemId
     * @return array
     */
    public function getItemCostCenters($itemId)
    {
        $costCenterDao = new ExpenseCost($this->getServiceLocator());
        $itemCostCenters = $costCenterDao->getItemCostCenters($itemId);
        $costCenterList = [];

        if ($itemCostCenters->count()) {
            foreach ($itemCostCenters as $itemCostCenter) {
                array_push($costCenterList, [
                    'id' => $itemCostCenter['id'],
                    'type' => $itemCostCenter['type'],
                    'name' => $itemCostCenter['name'],
                    'label' => $itemCostCenter['label'],
                    'unique_id' => $itemCostCenter['type'] . '_' . $itemCostCenter['id'],
                    'currency_id' => $itemCostCenter['currency_id'],
                ]);
            }
        }

        return $costCenterList;
    }

    /**
     * @param Ticket $expenseTicket
     * @param ParametersInterface $files
     * @return array
     */
    private function separateAttachments(Ticket $expenseTicket, ParametersInterface $files)
    {
        $ticketFiles = [];
        $itemFiles   = [];

        if ($files->count()) {
            $expenseItems = $expenseTicket->getItems();
            $files = $files->toArray();

            foreach ($files['files'] as $index => $file) {
                if (false === strpos($index, 'item_')) {
                    array_push($ticketFiles, $file);
                } else {
                    $fileOrder = substr($index, strlen('item_'));

                    foreach ($expenseItems as $item) {
                        $itemData = $item->getData();
                        $itemId = $item->getId();

                        if ($itemData['order'] == $fileOrder) {
                            $itemFiles[$itemId] = $file;
                            break;
                        }
                    }
                }
            }
        }

        return [$ticketFiles, $itemFiles];
    }

    public function removeRejectedItem($itemId)
    {
        $itemDao = new ExpenseItem($this->getServiceLocator(), '\ArrayObject');
        $itemCostDao = new ExpenseCost($this->getServiceLocator(), '\ArrayObject');

        $itemCostDao->delete(['expense_item_id' => $itemId]);
        $itemDao->delete(['id' => $itemId]);

        return true;
    }

    /**
     * @param int $itemId
     * @param array $extra
     * @return bool
     * @throws \Exception
     */
    public function removeExpenseItem($itemId, &$extra)
    {
        /**
         * @var CurrencyVault $currencyVaultService
         * @var ExpenseItemAttachments $expenseItemAttachmentsDao
         */
        $currencyVaultService = $this->getServiceLocator()->get('service_currency_currency_vault');
        $expenseItemAttachmentsDao = $this->getServiceLocator()->get('dao_finance_expense_expense_item_attachments');
        $expenseDao = new Expenses($this->getServiceLocator(), '\ArrayObject');
        $itemDao = new ExpenseItem($this->getServiceLocator(), '\ArrayObject');
        $itemCostDao = new ExpenseCost($this->getServiceLocator(), '\ArrayObject');

        $item = $itemDao->getItemDetails($itemId);
        $ticketBalance = (float)$item['ticket_balance'];
        $depositBalance = (float)$item['deposit_balance'];
        $itemBalance = (float)$item['item_balance'];

        if ($item['currency_id'] != $item['expense_currency_id']) {
            $item['amount'] = $currencyVaultService->convertCurrency($item['amount'], (int)$item['currency_id'], (int)$item['expense_currency_id'], $item['date_created']);
        }

        if ((int)$item['is_refund']) {
            $ticketBalance += (float)$item['amount'];
            $itemBalance += (float)$item['amount'];

            if ((int)$item['is_deposit']) {
                $depositBalance += (float)$item['amount'];
            }
        } else {
            $ticketBalance -= (float)$item['amount'];
            $itemBalance -= (float)$item['amount'];

            if ((int)$item['is_deposit']) {
                $depositBalance -= (float)$item['amount'];
            }
        }

        $extra = [
            'ticket_balance' => $ticketBalance,
            'deposit_balance' => $depositBalance,
            'item_balance' => $itemBalance,
        ];

        try {
            $itemDao->beginTransaction();

            // Remove Attachment
            if (!is_null($item['attachment_id'])) {
                $attachmentUrl = $this->getItemAttachmentPathById($item['attachment_id']);

                $expenseItemAttachmentsDao->delete(['id' => $item['attachment_id']]);
                @unlink($attachmentUrl);
            }

            // Remove Costs
            $itemCostDao->delete(['expense_item_id' => $itemId]);

            // Remove Item
            $itemDao->delete(['id' => $itemId]);

            // Update Balances
            $expenseDao->save([
                'ticket_balance' => $ticketBalance,
                'deposit_balance' => $depositBalance,
                'item_balance' => $itemBalance,
            ], ['id' => $item['expense_id']]);

            $itemDao->commitTransaction();
        } catch (\Exception $ex) {
            $itemDao->rollbackTransaction();

            throw $ex;
        }

        return true;
    }

    /**
     * @param array $data
     * @param ParametersInterface|bool $files
     * @param array $extra
     * @return bool
     * @throws \Exception
     */
    public function saveExpenseTicket($data, ParametersInterface $files, &$extra = [])
    {
        $data = $this->prepareData($data);

        if ($this->checkExpenseData($data)) {
            $finance = new Finance($this->getServiceLocator());
            $expenseTicket = $finance->getExpense($data['isEdit'] ? $data['ticket']['id'] : null);

            // Exact expense (ticket)
            $expenseTicket->prepare($data['ticket']);

            if (!empty($data['items']['add'])) {
                foreach ($data['items']['add'] as $item) {
                    $expenseTicket->addItem($item['data']);
                }
            }

            if (!empty($data['transactions']['add'])) {
                foreach ($data['transactions']['add'] as $item) {
                    $expenseTicket->addTransaction($item);
                }
            }

            $expenseTicket->save();

            list($ticketFiles, $itemFiles) = $this->separateAttachments($expenseTicket, $files);

            // Files
            $errorMessages = array_merge_recursive(
                $this->saveFiles($ticketFiles, $expenseTicket),
                $this->saveItemFiles($itemFiles, $expenseTicket),
                $this->removeFiles($data['attachments'])
            );

            // Passed by reference. Look up
            $extra = [
                'isEdit' => $data['isEdit'],
                'ticketId' => $expenseTicket->getExpenseId(),
                'errorMessages' => $errorMessages,
            ];

            return true;
        }

        return false;
    }

    /**
     * @param array $data
     * @param ParametersInterface|bool $files
     * @param array $extra
     * @return bool
     * @throws \Exception
     */
    public function saveItem($data, $files = false, &$extra)
    {
        /**
         * @var BackofficeAuthenticationService $auth
         * @var User $userService
         */
        $auth          = $this->getServiceLocator()->get('library_backoffice_auth');
        $userService   = $this->getServiceLocator()->get('service_user');
        $itemDao       = new ExpenseItem($this->getServiceLocator());
        $costCenterDao = new ExpenseCost($this->getServiceLocator());

        $data           = $this->prepareItemData($data);
        $isModification = false;
        $isManager      = false;
        $errorMessages  = [];
        $itemData       = [];
        $where          = [];

        if ($data['itemId']) {
            $where['id'] = $data['itemId'];
            $itemData = $itemDao->fetchOne(['id' => $data['itemId']], ['date_created', 'status', 'manager_id', 'expense_id']);

            if (!$itemData) {
                throw new \Exception('ERROR! Cannot find item.');
            }

            $isManager = $auth->getIdentity()->id == $itemData['manager_id'];
            $isModification = true;
        }

        try {
            $itemDao->beginTransaction();

            $itemDataArray = [
                'account_id'           => $data['accountId'],
                'account_reference'    => $data['accountReference'],
                'sub_category_id'      => $data['subCategoryId'],
                'currency_id'          => $data['currencyId'],
                'amount'               => $data['amount'],
                'type'                 => $data['type'],
                'period_from'          => $data['period']['from'],
                'period_to'            => $data['period']['to'],
                'comment'              => $data['comment'],
                'tmp_money_account_id' => $data['moneyAccount'],
                'tmp_transaction_date' => $data['transactionDate'],
            ];

            if (isset($data['isStartup'])) {
                $itemDataArray['is_startup'] = $data['isStartup'];
            }

            if (isset($data['isDeposit'])) {
                $itemDataArray['is_deposit'] = $data['isDeposit'];
            }

            if (isset($data['isRefund'])) {
                $itemDataArray['is_refund'] = $data['isRefund'];
            }

            if (!$isModification) {
                if (!isset($data['creatorId'])) {
                    $itemDataArray['creator_id']   = $auth->getIdentity()->id;
                } else {
                    $itemDataArray['creator_id'] = $data['creatorId'];
                }
                $itemDataArray['manager_id']   = $userService->getBudgetHolderUserManagerId($itemDataArray['creator_id']);
                $itemDataArray['date_created'] = date('Y-m-d H:i:s');
            } else {
                $data['dateCreated'] = $itemData['date_created'];

                if ($itemData['status'] == Helper::ITEM_STATUS_REJECTED) {
                    $itemDataArray['status'] = Helper::ITEM_STATUS_PENDING;
                }

                if (!empty($data['amount']) && !empty($data['currencyId'])) {
                    $this->recalculateTicketBalance($data['itemId'], $data['amount'], $data['currencyId']);
                }
            }

            $itemDao->save($itemDataArray, $where);

            $data['itemId'] = $isModification ? $data['itemId'] : $itemDao->lastInsertValue;

            if (count($data['costCenters'])) {
                $costCenterDao->delete(['expense_item_id' => $data['itemId']]);

                $costCenterAmount = $data['amount'] / count($data['costCenters']);

                if (!isset($data['isRefund'])) {
                    $itemStoredData = $itemDao->getItemDetails($data['itemId']);

                    $data['isRefund'] = $itemStoredData['is_refund'];
                }

                foreach ($data['costCenters'] as $costCenter) {
                    $costCenterDao->save([
                        'expense_item_id'  => $data['itemId'],
                        'cost_center_id'   => $costCenter['id'],
                        'cost_center_type' => $costCenter['type'],
                        'amount'           => $this->calculateCostAmount($costCenterAmount, $costCenter['currencyId'], $data['currencyId'], $data['isRefund']),
                    ]);
                }
            }

            $expenseData = [];

            if ($files !== false) {
                if (isset($itemData['expense_id']) && !is_null($itemData['expense_id'])) {
                    /**
                     * @var \DDD\Dao\Finance\Expense\Expenses $expenseDao
                     */
                    $expenseDao = $this->getServiceLocator()->get('dao_finance_expense_expenses');

                    $expenseData = $expenseDao->fetchOne(
                        ['id' => $itemData['expense_id']],
                        ['id', 'date_created']
                    );
                }

                $errorMessages = $this->saveItemFile($files, $data, $expenseData);

                if (count($errorMessages)) {
                    throw new \Exception('Cannot save item attachment. ' . print_r($errorMessages, true));
                }
            }

            if ($isModification) {
                if ((!is_null($data['poId']) && $isManager) && !in_array($itemData['status'], [Helper::ITEM_STATUS_APPROVED, Helper::ITEM_STATUS_COMPLETED])) {
                    // Approve Item
                    if (!$this->approveItem($data['itemId'], $data['poId'])) {
                        throw new \Exception('Cannot approve PO item.');
                    }

                    // Automatic transaction
                    if ($data['type'] == Helper::TYPE_DECLARE_AN_EXPENSE) {
                        $this->makeTransaction($data);
                    }
                }
            } else {
                if (!is_null($data['poId']) && isset($data['doAnExceptionForOrder']) && $data['doAnExceptionForOrder']) {
                    unset($data['doAnExceptionForOrder']);

                    // Approve Item
                    if (!$this->approveItem($data['itemId'], $data['poId'])) {
                        throw new \Exception('Cannot approve PO item.');
                    }
                }
            }

            $extra = [
                'itemId'        => $data['itemId'],
                'poId'          => $data['poId'],
                'errorMessages' => $errorMessages,
            ];

            $itemDao->commitTransaction();
        } catch (\Exception $ex) {
            $itemDao->rollbackTransaction();

            throw $ex;
        }

        return true;
    }

    /**
     * @param array $data
     * @param array $extra
     * @return bool
     * @throws \Exception
     */
    public function createRefundedOrder($data, &$extra)
    {
        /**
         * @var BackofficeAuthenticationService $auth
         * @var User $userService
         */
        $auth                  = $this->getServiceLocator()->get('library_backoffice_auth');
        $userService           = $this->getServiceLocator()->get('service_user');
        $itemDao               = new ExpenseItem($this->getServiceLocator());
        $costCenterDao         = new ExpenseCost($this->getServiceLocator());
        $expenseTransactionDao = new ExpenseTransactions($this->getServiceLocator(), '\ArrayObject');

        $data           = $this->prepareItemData($data);
        $errorMessages  = [];
        $where          = [];

        try {
            $itemDao->beginTransaction();

            $saveData = [
                'expense_id'           => $data['poId'],
                'account_id'           => $data['accountId'],
                'account_reference'    => $data['accountReference'],
                'sub_category_id'      => $data['subCategoryId'],
                'currency_id'          => $data['currencyId'],
                'amount'               => $data['orderAmount'],
                'type'                 => $data['type'],
                'period_from'          => $data['period']['from'],
                'period_to'            => $data['period']['to'],
                'comment'              => $data['comment'],
                'tmp_money_account_id' => $data['moneyAccount'],
                'tmp_transaction_date' => $data['transactionDate'],
                'is_refund'            => $data['isRefund'],
                'creator_id'           => $auth->getIdentity()->id,
                'manager_id'           => $userService->getBudgetHolderUserManagerId($auth->getIdentity()->id),
                'date_created'         => date('Y-m-d H:i:s')
            ];

            $itemDao->save($saveData, $where);
            $data['itemId'] = $itemDao->lastInsertValue;

            if (count($data['costCenters'])) {
                $costCenterDao->delete(['expense_item_id' => $data['itemId']]);

                $costCenterAmount = $data['orderAmount'] / count($data['costCenters']);

                foreach ($data['costCenters'] as $costCenter) {
                    $costCenterDao->save([
                        'expense_item_id'  => $data['itemId'],
                        'cost_center_id'   => $costCenter['id'],
                        'cost_center_type' => $costCenter['type'],
                        'amount'           => $this->calculateCostAmount($costCenterAmount, $data['currencyId'], $costCenter['currencyId'], $data['isRefund']),
                    ]);
                }
            }

            // Create refund transaction, If it already has.
            $hasTransaction = false;

            if ($data['transactionId']) {
                $expenseTransactionDetails = $expenseTransactionDao->getTransactionDetails($data['transactionId']);

                if ($expenseTransactionDetails) {
                    $transactionData = [
                        'poId'            => $data['poId'],
                        'moneyAccount'    => $expenseTransactionDetails['money_account_id'],
                        'accountId'       => $expenseTransactionDetails['account_to_id'],
                        'transactionDate' => date('Y-m-d H:i:s'),
                        'amount'          => $data['refundAmount'],
                        'itemId'          => $data['itemId'],
                        'isRefund'        => $data['isRefund']
                    ];

                    $this->makeTransaction($transactionData);
                    $hasTransaction = true;
                }
            }

            $extra = [
                'itemId'         => $data['itemId'],
                'poId'           => $data['poId'],
                'errorMessages'  => $errorMessages,
                'hasTransaction' => $hasTransaction,
            ];

            $itemDao->commitTransaction();
        } catch (\Exception $ex) {
            $itemDao->rollbackTransaction();

            throw $ex;
        }

        return true;
    }

    /**
     * @param float|string $sourceAmount
     * @param int $destinationCurrency
     * @param int $sourceCurrency
     *
     * @return float
     */
    public function calculateCostAmount($sourceAmount, $destinationCurrency, $sourceCurrency, $isRefund)
    {
        if (!count($this->currencies)) {
            foreach ($this->getServiceLocator()->get('CurrencyList') as $currency) {
                $this->currencies[$currency['id']] = $currency['value'];
            }
        }
        $isRefundFactor = ($isRefund == 1) ? -1 : 1;

        return $isRefundFactor * $sourceAmount * $this->currencies[$sourceCurrency] / $this->currencies[$destinationCurrency];
    }

    /**
     * @param string|array $data
     * @return array|bool
     * @throws \Exception
     */
    private function prepareItemData($data)
    {
        /**
         * @var ApartmentGroup $apartmentGroupService
         * @var Main $userService
         */
        $apartmentGroupService = $this->getServiceLocator()->get('service_apartment_group');
        $updatedCostCenterList = [];

        if (!is_array($data)) {
            $data = json_decode($data, true);
        }

        if (!$data) {
            throw new \Exception('Bad data provided to prepare Purchase Order item.');
        }

        if (empty($data['poId'])) {
            $data['poId'] = null;
        }

        if (empty($data['itemId'])) {
            $data['itemId'] = null;
        }

        if (empty($data['accountId'])) {
            $data['accountId'] = null;
        }

        if (empty($data['accountReference'])) {
            $data['accountReference'] = null;
        }

        if (empty($data['subCategoryId'])) {
            $data['subCategoryId'] = null;
        }

        if (empty($data['moneyAccount'])) {
            $data['moneyAccount'] = null;
        }

        if (empty($data['transactionDate'])) {
            $data['transactionDate'] = null;
        } else {
            $data['transactionDate'] = date('Y-m-d', strtotime($data['transactionDate']));
        }

        if (empty($data['type'])) {
            $data['type'] = Helper::TYPE_DECLARE_AN_EXPENSE;
        }

        if (empty($data['comment'])) {
            $data['comment'] = '';
        }

        // Configure scheduled period
        if (!empty($data['period'])) {
            list($periodFrom, $periodTo) = explode(' - ', $data['period']);

            $periodFrom = date('Y-m-d', strtotime($periodFrom));
            $periodTo = date('Y-m-d', strtotime($periodTo));

            $period = [
                'from' => $periodFrom,
                'to' => $periodTo,
            ];
        } else {
            $period = [
                'from' => null,
                'to' => null,
            ];
        }

        // Relocate cost centers
        if (empty($data['costCenters'])) {
            $data['costCenters'] = [];
        }

        if (count($data['costCenters'])) {
            $noneGroupApartmentList = [];

            // Collect all cost centers to prevent duplicate items
            foreach ($data['costCenters'] as $costCenter) {
                if ($costCenter['type'] == ExpenseCosts::TYPE_APARTMENT) {
                    array_push($noneGroupApartmentList, $costCenter['id']);
                }
            }

            foreach ($data['costCenters'] as $costCenter) {
                if ($costCenter['type'] == ExpenseCosts::TYPE_GROUP) {
                    $apartments = $apartmentGroupService->getApartmentsForExpenseByGroupId($costCenter['id']);

                    if (count($apartments)) {
                        foreach ($apartments as $apartment) {
                            // Prevent duplicates
                            if (in_array($apartment['id'], $noneGroupApartmentList)) {
                                continue;
                            }

                            array_push($updatedCostCenterList, [
                                'id' => $apartment['id'],
                                'type' => ExpenseCosts::TYPE_APARTMENT,
                                'currencyId' => $costCenter['currencyId'],
                            ]);
                        }
                    }
                } else {
                    array_push($updatedCostCenterList, [
                        'id' => $costCenter['id'],
                        'type' => $costCenter['type'],
                        'currencyId' => $costCenter['currencyId'],
                    ]);
                }
            }
        }

        $data['costCenters'] = $updatedCostCenterList;
        $data['period'] = $period;

        return $data;
    }

    /**
     * @param string $jsonString
     * @return array|bool
     * @throws \Exception
     */
    private function prepareData($jsonString)
    {
        /**
         * @var ApartmentGroup $apartmentGroupService
         * @var Main $userService
         */
        $apartmentGroupService = $this->getServiceLocator()->get('service_apartment_group');
        $data = json_decode($jsonString, true);

        if (!$data) {
            throw new \Exception('Bad data provided to prepare Purchase Order ticket.');
        }

        // Convert groups to cost centers in items
        foreach ($data['items'] as $collectionKey => $itemCollections) {
            if ($collectionKey == 'delete') {
                continue;
            }

            if (count($itemCollections)) {
                foreach ($itemCollections as $itemKey => $item) {
                    // Configure scheduled period
                    if (!empty($item['data']['period'])) {
                        list($periodFrom, $periodTo) = explode(' - ', $item['data']['period']);

                        $periodFrom = date('Y-m-d', strtotime($periodFrom));
                        $periodTo = date('Y-m-d', strtotime($periodTo));

                        $period = [
                            'from' => $periodFrom,
                            'to' => $periodTo,
                        ];
                    } else {
                        $period = [
                            'from' => null,
                            'to' => null,
                        ];
                    }

                    $data['items'][$collectionKey][$itemKey]['data']['period'] = $period;

                    // Relocate cost centers
                    if (count($item['data']['costCenters'])) {
                        $updatedCostCenterList = [];
                        $noneGroupApartmentList = [];

                        // Collect all cost centers to prevent duplicate items
                        foreach ($item['data']['costCenters'] as $costCenter) {
                            if ($costCenter['type'] == 'apartment') {
                                array_push($noneGroupApartmentList, $costCenter['id']);
                            }
                        }

                        foreach ($item['data']['costCenters'] as $costCenter) {
                            if ($costCenter['type'] == 'group') {
                                $apartments = $apartmentGroupService->getApartmentsForExpenseByGroupId($costCenter['id']);

                                if (count($apartments)) {
                                    foreach ($apartments as $apartment) {
                                        // Prevent duplicates
                                        if (in_array($apartment['id'], $noneGroupApartmentList)) {
                                            continue;
                                        }

                                        array_push($updatedCostCenterList, $apartment);
                                    }
                                }
                            } else {
                                array_push($updatedCostCenterList, $costCenter);
                            }
                        }

                        $data['items'][$collectionKey][$itemKey]['data']['costCenters'] = $updatedCostCenterList;
                    }
                }
            }
        }

        return $data;
    }

    /**
     * Validate submitted data. Have fun!
     *
     * @param array $data
     * @return bool
     */
    private function checkExpenseData($data)
    {
        return true;
    }

    /**
     * @param array|bool $files
     * @param Ticket $expenseTicket
     * @return array
     * @throws \Exception
     */
    private function saveFiles(array $files, Ticket $expenseTicket)
    {
        $errorMessages = [];

        if (count($files)) {
            $expenseAttachmentsDao = new ExpenseAttachments($this->getServiceLocator());
            $expenseId = $expenseTicket->getExpenseId();
            $expenseTicketData = $expenseTicket->getTicketOriginalData();
            $dateCreated = $expenseTicketData['date_created'];

            try {
                $fileReport = $this->saveFilesPhisically($files, $dateCreated, $expenseId);

                if (count($fileReport)) {
                    foreach ($fileReport as $report) {
                        if ($report['status'] == 'fail') {
                            array_push($errorMessages, $report['value']);
                        } else {
                            $expenseAttachmentsDao->save([
                                'expense_id' => $expenseId,
                                'filename' => basename($report['value']),
                            ]);
                        }
                    }
                }
            } catch (\Exception $ex) {
                array_push($errorMessages, $ex->getMessage());
            }
        }

        return $errorMessages;
    }

    /**
     * @param array $files
     * @param Ticket $expenseTicket
     * @return array
     * @throws \Exception
     */
    private function saveItemFiles(array $files, Ticket $expenseTicket)
    {
        $errorMessages = [];

        if (count($files)) {
            try {
                $expenseTicketData = $expenseTicket->getTicketOriginalData();
                $dateCreated = $expenseTicketData['date_created'];
                $fileReport = $this->saveItemFilesPhisically($files, $expenseTicket, $dateCreated);

                if (count($fileReport)) {
                    foreach ($fileReport as $report) {
                        if ($report['status'] == 'fail') {
                            array_push($errorMessages, $report['value']);
                        }
                    }
                }
            } catch (\Exception $ex) {
                array_push($errorMessages, $ex->getMessage());
            }
        }

        return $errorMessages;
    }

    /**
     * @param Parameters|bool $file
     * @param array $data
     * @param \DDD\Domain\Finance\Expense\Expenses $expenseData
     * @return array
     * @throws \Exception
     */
    public function saveItemFile(Parameters $file, $data, $expenseData)
    {
        $errorMessages = [];

        if ($file->count()) {
            try {
                $this->removeItemFile($data['itemId']);
                $fileReport = $this->saveItemFilePhisically($file, $data['itemId'], $expenseData);

                if (count($fileReport)) {
                    foreach ($fileReport as $report) {
                        if ($report['status'] == 'fail') {
                            array_push($errorMessages, $report['value']);
                        }
                    }
                }
            } catch (\Exception $ex) {
                array_push($errorMessages, $ex->getMessage());
            }
        }

        return $errorMessages;
    }

    /**
     * @param int $itemId
     */
    private function removeItemFile($itemId)
    {
        $path = "/ginosi/uploads/expense/items_tmp/{$itemId}";

        if (is_readable($path)) {
            \Library\Utility\Helper::deleteDirectory($path);
        }
    }

    /**
     * @param array $data
     * @param bool $dir Remove directory too
     * @return array
     */
    private function removeFiles(array $data, $dir = false)
    {
        $expenseAttachmentsDao = new ExpenseAttachments($this->getServiceLocator());
        $errorMessages = [];

        try {
            if (count($data)) {
                $expenseId = null;
                $dateCreated = null;

                foreach ($data as $attachmentId) {
                    $attachment = $expenseAttachmentsDao->getAttachmentsToRemove($attachmentId);
                    $expenseId = $attachment['expense_id'];
                    $dateCreated = $attachment['date_created'];

                    if ($attachment) {
                        $expenseAttachmentsDao->delete(['id' => $attachmentId]);

                        $result = $this->removeFilePhisically($attachment['expense_id'], $attachment['date_created'], $attachment['filename']);

                        if ($result['status'] == 'fail') {
                            array_push($errorMessages, $result['value']);
                        }
                    }
                }

                // Make sure all files has been deleted
                if (!count($errorMessages) && $dir) {
                    @rmdir($this->prepareDirectoryPath($expenseId, $dateCreated));
                }
            }
        } catch (\Exception $ex) {
            array_push($errorMessages, $ex->getMessage());
        }

        return $errorMessages;
    }


    /**
     * @param array $data
     * @return array
     */
    private function removeItemFiles(array $data)
    {
        /**
         * @var ExpenseItemAttachments $expenseItemAttachmentsDao
         * @var \DDD\Domain\Finance\Expense\ExpenseItemAttachments $attachment
         */
        $expenseItemAttachmentsDao = $this->getServiceLocator()->get('dao_finance_expense_expense_item_attachments');
        $errorMessages = [];

        try {
            if (count($data)) {
                foreach ($data as $item) {
                    $attachment = $expenseItemAttachmentsDao->getAttachmentsToRemove($item['item_id']);

                    if (false !== $attachment) {
                        $expenseItemAttachmentsDao->delete(['item_id' => $item['item_id']]);

                        $result = $this->removeItemFilePhisically($item['expense_id'], $item['item_id'], $attachment->getFilename(), $attachment->getDateCreatedNeededFormat());

                        if ($result['status'] == 'fail') {
                            array_push($errorMessages, $result['value']);
                        }

                        // remove the directory
                        if (!count($errorMessages) ) {
                            @rmdir($this->prepareItemDirectoryPath($item['expense_id'], $item['item_id'], $attachment->getDateCreatedNeededFormat()));
                        }
                    }
                }
            }
        } catch (\Exception $ex) {
            array_push($errorMessages, $ex->getMessage());
        }

        return $errorMessages;
    }

    /**
     * @param array $files
     * @param string $dateCreated
     * @param int $expenseId
     * @return array
     * @throws \Exception
     */
    private function saveFilesPhisically(array $files, $dateCreated, $expenseId)
    {
        $fileReport = [];

        if (count($files)) {
            // @todo: maybe we should validate date before separation?
            list($date,) = explode(' ', $dateCreated);
            list($y, $m, $d) = explode('-', $date);

            $path = "/ginosi/uploads/expense/{$y}/{$m}/{$d}/{$expenseId}/";

            if (!is_readable($path)) {
                if (!mkdir($path, 0755, true)) {
                    throw new \Exception('Cannot create directories');
                }
            }

            foreach ($files as $index => $file) {
                $fileName = $path . $this->generateFileName($file['name'], $dateCreated, $expenseId);

                if ($this->isValidFile($file)) {
                    if (move_uploaded_file($file['tmp_name'], $fileName)) {
                        array_push($fileReport, [
                            'id' => $index,
                            'value' => $fileName,
                            'status' => 'success',
                        ]);
                    } else {
                        array_push($fileReport, [
                            'id' => $index,
                            'value' => 'Cannot upload a file ' . $file['name'],
                            'status' => 'fail',
                        ]);
                    }
                } else {
                    array_push($fileReport, [
                        'id' => $index,
                        'value' => 'Unsupported file ' . $file['name'],
                        'status' => 'fail',
                    ]);
                }
            }
        }

        return $fileReport;
    }

    /**
     * @param array $files
     * @param Ticket $expenseTicket
     * @param string $dateCreated
     * @return array
     * @throws \Exception
     */
    private function saveItemFilesPhisically(array $files, Ticket $expenseTicket, $dateCreated)
    {
        $fileReport = [];

        if (count($files)) {
            $expenseItemAttachmentsDao = $this->getServiceLocator()->get('dao_finance_expense_expense_item_attachments');
            $expenseId = $expenseTicket->getExpenseId();
            $expenseItems = $expenseTicket->getItems();

            list($date,) = explode(' ', $dateCreated);
            list($y, $m, $d) = explode('-', $date);

            foreach ($expenseItems as $item) {
                $itemId = $item->getId();

                if (isset($files[$itemId])) {
                    $path = "/ginosi/uploads/expense/items/{$y}/{$m}/{$d}/{$expenseId}/{$itemId}/";

                    if (!is_readable($path)) {
                        if (!mkdir($path, 0755, true)) {
                            throw new \Exception('Cannot create directories');
                        }
                    }

                    $file = $files[$itemId];
                    $fileName =  $this->generateItemFileName($file['name'], $itemId, $expenseId);

                    if ($this->isValidFile($file)) {
                        if (move_uploaded_file($file['tmp_name'], $path . $fileName)) {
                            $expenseItemAttachmentsDao->save([
                                'expense_id' => $expenseId,
                                'item_id'    => $itemId,
                                'filename'   => $fileName
                            ]);
                            array_push($fileReport, [
                                'value' => $fileName,
                                'status' => 'success',
                            ]);
                        } else {
                            array_push($fileReport, [
                                'value' => 'Cannot upload a file ' . $file['name'],
                                'status' => 'fail',
                            ]);
                        }
                    } else {
                        array_push($fileReport, [
                            'value' => 'Unsupported file ' . $file['name'],
                            'status' => 'fail',
                        ]);
                    }
                }
            }
        }

        return $fileReport;
    }

    /**
     * @param Parameters|array|bool $file
     * @param int $itemId
     * @param \DDD\Domain\Finance\Expense\Expenses $expenseData
     * @return array
     * @throws \Exception
     */
    private function saveItemFilePhisically(Parameters $file, $itemId, $expenseData)
    {
        $fileReport = [];

        if ($file->count()) {
            $file = $file->get('file');
            $expenseItemDao = new ExpenseItem($this->getServiceLocator(), '\ArrayObject');
            $expenseId = $expenseItemDao->fetchOne(['id' => $itemId], ['expense_id'])['expense_id'];
            $expenseItemAttachmentsDao = $this->getServiceLocator()->get('dao_finance_expense_expense_item_attachments');

            $isTempItem = (empty($expenseData)) ? true : false;

            if ($isTempItem) {
                $path = "/ginosi/uploads/expense/items_tmp/{$itemId}/";
            } else {
                list($date,) = explode(' ', $expenseData->getDateCreated());
                list($y, $m, $d) = explode('-', $date);

                $path = "/ginosi/uploads/expense/items/{$y}/{$m}/{$d}/{$expenseData->getId()}/{$itemId}/";
            }

            if (!is_readable($path)) {
                if (!mkdir($path, 0755, true)) {
                    throw new \Exception('Cannot create directories.');
                }
            }

            $fileName = $this->generateItemFileName($file['name'], $itemId);
            $oldInfo = $expenseItemAttachmentsDao->fetchOne(['item_id' => $itemId],['id']);
            if ($this->isValidFile($file)) {
                if (!$isTempItem) {
                    array_map('unlink', glob($path . '*'));
                }

                if (move_uploaded_file($file['tmp_name'], $path . $fileName)) {
                    if ($oldInfo) {
                        $expenseItemAttachmentsDao->save([
                            'filename' => $fileName,
                            'expense_id' => $expenseId
                        ], ['item_id' => $itemId]);
                    } else {
                        $expenseItemAttachmentsDao->save([
                            'item_id' => $itemId,
                            'filename' => $fileName,
                            'expense_id' => $expenseId
                        ]);
                    }

                    array_push($fileReport, [
                        'value' => $fileName,
                        'status' => 'success',
                    ]);
                } else {
                    array_push($fileReport, [
                        'value' => 'Cannot upload a file ' . $file['name'],
                        'status' => 'fail',
                    ]);
                }
            } else {
                array_push($fileReport, [
                    'value' => 'Unsupported file ' . $file['name'],
                    'status' => 'fail',
                ]);
            }
        }

        return $fileReport;
    }

    /**
     * @param int $expenseId
     * @param string $dateCreated
     * @param string $filename
     * @return array
     */
    private function removeFilePhisically($expenseId, $dateCreated, $filename)
    {
        $dir = $this->prepareDirectoryPath($expenseId, $dateCreated);
        $path = "{$dir}/{$filename}";

        if (@unlink($path)) {
            $fileReport = [
                'value' => $filename . ' has been removed',
                'status' => 'success',
            ];
        } else {
            $fileReport = [
                'value' => 'Cannot remove file ' . $filename,
                'status' => 'fail',
            ];
        }

        return $fileReport;
    }

    /**
     * @param int $expenseId
     * @param int $itemId
     * @param string $filename
     * @return array
     */
    private function removeItemFilePhisically($expenseId, $itemId, $filename, $ymd)
    {
        $dir = $this->prepareItemDirectoryPath($expenseId, $itemId, $ymd);
        $path = "{$dir}/{$filename}";

        if (@unlink($path)) {
            $fileReport = [
                'value' => $filename . ' has been removed',
                'status' => 'success',
            ];
        } else {
            $fileReport = [
                'value' => 'Cannot remove file ' . $filename,
                'status' => 'fail',
            ];
        }

        return $fileReport;
    }


    /**
     * @param int $expenseId
     * @param string $dateCreated
     * @return string
     */
    private function prepareDirectoryPath($expenseId, $dateCreated)
    {
        // @todo: maybe we should validate date before separation?
        list($date,) = explode(' ', $dateCreated);
        list($y, $m, $d) = explode('-', $date);

        return "/ginosi/uploads/expense/{$y}/{$m}/{$d}/{$expenseId}";
    }

    /**
     * @param int $expenseId
     * @param int $itemId
     * @param string $ymd
     * @return string
     */
    private function prepareItemDirectoryPath($expenseId, $itemId, $ymd)
    {
        return "/ginosi/uploads/expense/items/{$ymd}/{$expenseId}/{$itemId}";
    }

    /**
     * @param array $file
     * @return bool
     */
    private function isValidFile(array $file)
    {
        $validator = new Extension(self::getAllowedExtensions());

        if (!$validator->isValid($file)) {
            return false;
        }

        // other validators if needed

        return true;
    }

    /**
     * @param string $name
     * @param string $date
     * @param int $expenseId
     * @return string
     */
    private function generateFileName($name, $date, $expenseId)
    {
        /**
         * @todo: Check date for datetime format, otherwise fatal error will occur
         */
        list($ymd,) = explode(' ', $date);
        $ymd = str_replace('-', '_', $ymd);

        $hashFunction = 'm' . 'd' . '5';
        $hash = $hashFunction(time() . $expenseId);
        $hash = substr($hash, 0, 6);
        $fileInfo = pathinfo($name);

        if (substr($name, -6) == 'tar.gz') {
            $fileInfo['extension'] = 'tar.gz';
        }

        $name = strtolower($fileInfo['filename']);
        $name = preg_replace('/[^a-z0-9]/', '_', $name);

        $extension = strtolower($fileInfo['extension']);

        return "{$ymd}_{$name}_{$hash}.{$extension}";
    }

    /**
     * @param string $name
     * @param int $expenseId
     * @param int $itemId
     * @return string
     */
    private function generateItemFileName($name, $itemId, $expenseId = 0)
    {
        $hash = md5(time() . $expenseId . $itemId);
        $hash = substr($hash, 0, 6);
        $fileInfo = pathinfo($name);

        if (substr($name, -6) == 'tar.gz') {
            $fileInfo['extension'] = 'tar.gz';
        }

        $name = strtolower($fileInfo['filename']);
        $name = preg_replace('/[^a-z0-9]/', '_', $name);

        $extension = strtolower($fileInfo['extension']);

        return "{$name}_{$hash}.{$extension}";
    }

    /**
     * @param int $ticketId
     * @return bool
     */
    public function deleteTicket($ticketId)
    {
        $expenseDao = new Expenses($this->getServiceLocator(), '\ArrayObject');
        $expenseItemDao = new ExpenseItem($this->getServiceLocator(), '\ArrayObject');
        $costCenterDao = new ExpenseCost($this->getServiceLocator(), '\ArrayObject');
        $expenseAttachmentDao = new ExpenseAttachments($this->getServiceLocator(), '\ArrayObject');
        $expenseItemAttachmentDao = $this->getServiceLocator()->get('dao_finance_expense_expense_item_attachments');

        try {
            $expenseDao->beginTransaction();

            $expense = $expenseDao->fetchOne(['id' => $ticketId]);

            if (!$expense) {
                throw new NotFoundException('Purchase order not found.');
            }

            $expenseItems = $expenseItemDao->fetchAll(['expense_id' => $ticketId], ['id']);
            $expenseAttachments = $expenseAttachmentDao->fetchAll(['expense_id' => $ticketId], ['id']);
            $expenseItemAttachments = $expenseItemAttachmentDao->fetchAll(['expense_id' => $ticketId], ['item_id']);

            // Delete items
            if ($expenseItems->count()) {
                $itemIdList = [];

                foreach ($expenseItems as $expenseItem) {
                    array_push($itemIdList, $expenseItem['id']);
                }

                $costCenterDao->deleteItemCosts($itemIdList);
            }

            $expenseItemDao->delete(['expense_id' => $ticketId]);

            $purchaseOrderTransactions = $this->getServiceLocator()->get('service_finance_transaction_po_transaction');
            $purchaseOrderTransactions->removePurchaseOrderAllTransactions($ticketId, false);

            // Delete Ticket attachments
            if ($expenseAttachments->count()) {
                $attachmentIdList = [];

                foreach ($expenseAttachments as $expenseAttachment) {
                    array_push($attachmentIdList, $expenseAttachment['id']);
                }

                $errorMessages = $this->removeFiles($attachmentIdList);

                if (count($errorMessages)) {
                    /**
                     * @todo: There are an error messages
                     */
                }
            }

            // Delete Item attachments
            if ($expenseItemAttachments->count()) {
                $attachmentItemIdList = [];

                foreach ($expenseItemAttachments as $expenseItemAttachment) {
                    array_push($attachmentItemIdList, [
                        'item_id' => $expenseItemAttachment->getItemId(),
                        'expense_id' => $ticketId
                    ]);
                }

                $this->removeItemFiles($attachmentItemIdList);
            }

            // Delete ticket
            $expenseDao->delete(['id' => $ticketId]);

            $expenseDao->commitTransaction();

            return true;
        } catch (\Exception $ex) {
            $expenseDao->rollbackTransaction();
        }

        return false;
    }

    /**
     * @param int $ticketId
     * @return int|bool
     * @throws \Exception
     */
    public function duplicateTicket($ticketId)
    {
        /**
         * @var BackofficeAuthenticationService $auth
         * @var Logger $logger
         */
        $expenseDao = new Expenses($this->getServiceLocator(), '\ArrayObject');
        $expenseItemDao = new ExpenseItem($this->getServiceLocator(), '\ArrayObject');
        $costCenterDao = new ExpenseCost($this->getServiceLocator(), '\ArrayObject');
        $logger = $this->getServiceLocator()->get('ActionLogger');
        $auth = $this->getServiceLocator()->get('library_backoffice_auth');

        try {
            $expenseDao->beginTransaction();

            // ------------- Expense
            $expense = $expenseDao->fetchOne(['id' => $ticketId]);
            $creatorId = $auth->getIdentity()->id;
            $creationDate = date('Y-m-d H:i:s');

            if (!$expense) {
                throw new NotFoundException('Purchase order not found.');
            }

            // Modify data
            unset($expense['id']);
            unset($expense['status']);
            unset($expense['finance_status']);
            $expense['date_created'] = $creationDate;
            $expense['creator_id'] = $creatorId;

            // Save
            $expenseDao->save(iterator_to_array($expense));
            $newTicketId = $expenseDao->lastInsertValue;

            // Logging
            $logger->save(Logger::MODULE_EXPENSE, $newTicketId, Logger::ACTION_COMMENT, "Duplicated from ticket #{$ticketId}");

            $expenseDao->commitTransaction();

            return $newTicketId;
        } catch (\Exception $ex) {
            $expenseDao->rollbackTransaction();

            throw new \Exception($ex->getMessage());
        }
    }

    /**
     * @param int $ticketId
     * @param float $limit
     * @return bool
     * @throws ExpenseCustomException
     * @throws \Exception
     */
    public function approveTicket($ticketId, $limit)
    {
        /**
         * @var BackofficeAuthenticationService $auth
         * @var Logger $logger
         * @var \DDD\Dao\Finance\Budget\Budget $budgetDao
         * @var \DDD\Domain\Finance\Expense\Expenses $currentExpense
         */
        $expenseDao = new Expenses($this->getServiceLocator(), '\ArrayObject');
        $budgetDao = $this->getServiceLocator()->get('dao_finance_budget_budget');
        $logger = $this->getServiceLocator()->get('ActionLogger');
        $auth = $this->getServiceLocator()->get('library_backoffice_auth');
        $currencyExchange = new \Library\Utility\Currency($this->getServiceLocator()->get('dao_currency_currency'));

        $currentExpense = $expenseDao->fetchOne([
            'id' => $ticketId,
            'status' => Ticket::STATUS_PENDING,
        ], ['budget_id', 'currency_id']);

        if (!$currentExpense) {
            throw new ExpenseCustomException("
                Impossible to approve ticket because of 2 reason\n
                1. Ticket not exists\n
                2. Ticket already approved or rejected\n
            ");
        } else {
            if (!$auth->hasRole(Roles::ROLE_PO_APPROVER)) {
                throw new ExpenseCustomException('You have no premissions to approve this purchase order.');
            }

            try {
                $expenseDao->beginTransaction();

                if ($currentExpense['budget_id']) {
                    $budgetBalanceReduction = $currencyExchange->convert($limit, intval($currentExpense['currency_id']), CurrencyService::DEFAULT_CURRENCY);

                    $budget = $budgetDao->fetchOne([
                        'id' => $currentExpense['budget_id']
                    ], ['balance']);

                    if (!$budget) {
                        throw new ExpenseCustomException('Unable to find the budget linked to this purchase order.');
                    } else if ($budget->getBalance() < $budgetBalanceReduction && $budget->getId() == Budget::BUDGET_NULL_ID) {
                        throw new ExpenseCustomException('Budget balance (' . $budget->getBalance() . ' EUR) is shorter than purchse order limit.');
                    }

                    $budgetDao->save([
                        'balance' => new Expression('balance - ' . $budgetBalanceReduction)
                    ], ['id' => $currentExpense['budget_id']]);
                }

                $expenseDao->save([
                    'status' => Ticket::STATUS_GRANTED,
                    'limit' => $limit,
                ], ['id' => $ticketId]);

                $logger->save(Logger::MODULE_EXPENSE, $ticketId, Logger::ACTION_APPROVED, Logger::POSITIVE);

                $expenseDao->commitTransaction();
            } catch (\Exception $ex) {
                $expenseDao->rollbackTransaction();

                throw $ex;
            }
        }

        return true;
    }

    /**
     * @param int $ticketId
     * @return bool
     * @throws ExpenseCustomException
     * @throws \Exception
     */
    public function rejectTicket($ticketId)
    {
        /**
         * @var BackofficeAuthenticationService $auth
         * @var Logger $logger
         */
        $expenseDao = new Expenses($this->getServiceLocator(), '\ArrayObject');
        $logger = $this->getServiceLocator()->get('ActionLogger');
        $auth = $this->getServiceLocator()->get('library_backoffice_auth');

        $expenseExist = $expenseDao->fetchOne([
            'id' => $ticketId,
            'status' => Ticket::STATUS_PENDING,
        ], ['id']);

        if (!$expenseExist) {
            throw new ExpenseCustomException("
                Impossible to reject ticket because of 2 reason\n
                1. Ticket not exists\n
                2. Ticket already approved or rejected\n
            ");
        } else {
            if (!$auth->hasRole(Roles::ROLE_PO_APPROVER)) {
                throw new ExpenseCustomException('You have no premissions to reject this purchase order.');
            }

            try {
                $expenseDao->beginTransaction();

                $expenseDao->save(['status' => Ticket::STATUS_DECLINED], ['id' => $ticketId]);
                $logger->save(Logger::MODULE_EXPENSE, $ticketId, Logger::ACTION_APPROVED, Logger::NEGATIVE);

                $expenseDao->commitTransaction();
            } catch (\Exception $ex) {
                $expenseDao->rollbackTransaction();
                throw new $ex;
            }
        }

        return true;
    }

    /**
     * @param int $ticketId
     * @return bool
     * @throws ExpenseCustomException
     * @throws \Exception
     */
    public function readyTicket($ticketId)
    {
        /**
         * @var BackofficeAuthenticationService $auth
         * @var Logger $logger
         */
        $expenseDao = new Expenses($this->getServiceLocator(), '\ArrayObject');
        $auth = $this->getServiceLocator()->get('library_backoffice_auth');
        $logger = $this->getServiceLocator()->get('ActionLogger');
        $expenseExist = $expenseDao->checkForCloseReview($ticketId);

        if (!$expenseExist) {
            throw new ExpenseCustomException('Impossible to set ready this ticket');
        } else {
            if ($expenseExist['manager_id'] != $auth->getIdentity()->id) {
                throw new ExpenseCustomException('You have no premissions to set as rendered this purchase order.');
            }

            try {
                $expenseDao->beginTransaction();

                $expenseDao->save(['finance_status' => Ticket::FIN_STATUS_READY], ['id' => $ticketId]);
                $logger->save(Logger::MODULE_EXPENSE, $ticketId, Logger::ACTION_SERVICE_IS_RENDERED);

                $expenseDao->commitTransaction();
            } catch (\Exception $ex) {
                $expenseDao->rollbackTransaction();

                throw new \Exception($ex);
            }
        }

        return true;
    }

    /**
     * @param int $ticketId
     * @return bool
     * @throws ExpenseCustomException
     * @throws \Exception
     */
    public function settleTicket($ticketId)
    {
        /**
         * @var BackofficeAuthenticationService $auth
         * @var Logger $logger
         */
        $expenseDao = new Expenses($this->getServiceLocator(), '\ArrayObject');
        $auth = $this->getServiceLocator()->get('library_backoffice_auth');
        $logger = $this->getServiceLocator()->get('ActionLogger');
        $expenseExist = $expenseDao->checkForSettle($ticketId);
        if (!$expenseExist) {
            throw new ExpenseCustomException('Impossible to settle ticket');
        } else {
            if (!$auth->hasRole(Roles::ROLE_PO_AND_TRANSFER_MANAGER_GLOBAL)) {
                throw new ExpenseCustomException('You have no premissions to settle this purchase order.');
            }

            try {
                $expenseDao->beginTransaction();

                $expenseDao->save(['finance_status' => Ticket::FIN_STATUS_SETTLED], ['id' => $ticketId]);
                $logger->save(Logger::MODULE_EXPENSE, $ticketId, Logger::ACTION_SETTLED);

                // increase budget if limit different item balance great zero
                if ($expenseExist['remaining'] > 0) {
                    $currencyExchange = new \Library\Utility\Currency($this->getServiceLocator()->get('dao_currency_currency'));
                    $budgetBalanceIncrease = $expenseExist['remaining'];
                    if ($expenseExist['currency_id'] != CurrencyService::DEFAULT_CURRENCY) {
                        $budgetBalanceIncrease = $currencyExchange->convert($budgetBalanceIncrease, intval($expenseExist['currency_id']), CurrencyService::DEFAULT_CURRENCY);
                    }

                    /** @var @var \DDD\Dao\Finance\Budget\Budget $budgetDao $budgetDao */
                    $budgetDao = $this->getServiceLocator()->get('dao_finance_budget_budget');
                    $budgetDao->save([
                        'balance' => new Expression('balance + ' . $budgetBalanceIncrease),
                    ], [
                        'id' => $expenseExist['budget_id'],
                    ]);
                }

                $expenseDao->commitTransaction();
            } catch (\Exception $ex) {
                $expenseDao->rollbackTransaction();

                throw new \Exception($ex);
            }
        }

        return true;
    }

    /**
     * @param int $ticketId
     * @return bool
     * @throws ExpenseCustomException
     * @throws \Exception
     */
    public function unsettleTicket($ticketId)
    {
        /**
         * @var BackofficeAuthenticationService $auth
         * @var Notifications $notificationService
         * @var Team $teamService
         * @var Logger $logger
         */
        $expenseDao = new Expenses($this->getServiceLocator(), '\ArrayObject');
        $notificationService = $this->getServiceLocator()->get('service_notifications');
        $auth = $this->getServiceLocator()->get('library_backoffice_auth');
        $teamService = $this->getServiceLocator()->get('service_team_team');
        $logger = $this->getServiceLocator()->get('ActionLogger');

        $expenseExist = $expenseDao->fetchOne([
            'id' => $ticketId,
            'status' => Ticket::STATUS_GRANTED,
            'finance_status' => Ticket::FIN_STATUS_SETTLED,
        ], ['id']);

        if (!$expenseExist) {
            throw new ExpenseCustomException('Impossible to unsettle ticket');
        } else {
            if (!$auth->hasRole(Roles::ROLE_EXPENSE_UNLOCKER)) {
                throw new ExpenseCustomException('You have no premissions to unsettle this purchase order.');
            }

            try {
                $userId = $auth->getIdentity()->id;

                $expenseDao->beginTransaction();

                $expenseDao->save(['finance_status' => Ticket::FIN_STATUS_READY], ['id' => $ticketId]);
                $logger->save(Logger::MODULE_EXPENSE, $ticketId, Logger::ACTION_SETTLED, Logger::NEGATIVE);
                $notificationData = [
                    'recipient' => $teamService->getTeamManagerAndOfficerList(Team::TEAM_FINANCE),
                    'user_id' => $userId,
                    'sender' => Notifications::$purchaseOrder,
                    'sender_id' => $userId,
                    'message' => 'Purchase order has been unsettled',
                    'url' => "/finance/purchase-order/ticket/{$ticketId}",
                ];

                if (!$notificationService->createNotification($notificationData)) {
                    throw new \Exception('Process interrupted. Cannot send notifications.');
                }

                $expenseDao->commitTransaction();
            } catch (\Exception $ex) {
                $expenseDao->rollbackTransaction();

                throw new \Exception($ex);
            }
        }

        return true;
    }

    /**
     * @param int $ticketId
     * @return bool
     * @throws ExpenseCustomException
     * @throws \Exception
     */
    public function revokeTicket($ticketId)
    {
        /**
         * @var BackofficeAuthenticationService $auth
         * @var Logger $logger
         * @var \DDD\Dao\Finance\Budget\Budget $budgetDao
         */
        $expenseDao = new Expenses($this->getServiceLocator(), '\ArrayObject');
        $budgetDao = $this->getServiceLocator()->get('dao_finance_budget_budget');
        $auth = $this->getServiceLocator()->get('library_backoffice_auth');
        $logger = $this->getServiceLocator()->get('ActionLogger');
        $currencyExchange = new \Library\Utility\Currency($this->getServiceLocator()->get('dao_currency_currency'));

        $expenseExist = $expenseDao->fetchOne([
            'id' => $ticketId,
            'status' => Ticket::STATUS_GRANTED,
        ], [
            'id',
            'budget_id',
            'currency_id',
            'manager_id',
            'limit'
        ]);

        if (!$expenseExist) {
            throw new ExpenseCustomException('Impossible to handle ticket');
        } else {
            if (!$auth->hasRole(Roles::ROLE_PO_APPROVER)) {
                throw new ExpenseCustomException('You have no premissions to revoke this purchase order.');
            }

            try {
                $expenseDao->beginTransaction();

                if ($expenseExist['budget_id']) {
                    $budgetBalanceIncrease = $currencyExchange->convert($expenseExist['limit'], intval($expenseExist['currency_id']), CurrencyService::DEFAULT_CURRENCY);

                    $budgetDao->save([
                        'balance' => new Expression('balance + ' . $budgetBalanceIncrease),
                    ], [
                        'id' => $expenseExist['budget_id'],
                    ]);
                }

                $expenseDao->save([
                    'status'            => Ticket::STATUS_PENDING,
                    'finance_status'    => Ticket::FIN_STATUS_NEW
                ], ['id' => $ticketId]);
                $logger->save(Logger::MODULE_EXPENSE, $ticketId, Logger::ACTION_APPROVED, Logger::NEGATIVE);

                $expenseDao->commitTransaction();
            } catch (\Exception $ex) {
                $expenseDao->rollbackTransaction();

                throw new \Exception($ex);
            }
        }

        return true;
    }

    /**
     * @param int $attachmentId
     * @return string
     * @throws \Exception
     */
    public function getAttachmentPathById($attachmentId)
    {
        $expenseAttachmentsDao = new ExpenseAttachments($this->getServiceLocator());
        $attachment = $expenseAttachmentsDao->getAttachmentForPreviewById($attachmentId);

        if (!$attachment) {
            throw new \Exception('Img does not exists');
        }

        list($date,) = explode(' ', $attachment['date_created']);
        $ymd = str_replace('-', '/', $date);

        return "/ginosi/uploads/expense/{$ymd}/{$attachment['expense_id']}/{$attachment['filename']}";
    }

    /**
     * @param int $attachmentId
     * @return string
     * @throws \Exception
     */
    public function getItemAttachmentPathById($attachmentId)
    {
        /**
         * @var ExpenseItemAttachments $expenseItemAttachmentsDao
         * @var \DDD\Domain\Finance\Expense\ExpenseItemAttachments $attachment
         */
        $expenseItemAttachmentsDao = $this->getServiceLocator()->get('dao_finance_expense_expense_item_attachments');
        $attachment = $expenseItemAttachmentsDao->getAttachmentForPreviewById($attachmentId);

        if (!$attachment) {
            throw new \Exception('Purchase order attachment does not exists');
        }

        if (is_null($attachment->getExpenseId())) {
            $path = "/ginosi/uploads/expense/items_tmp/{$attachment->getItemId()}/{$attachment->getFilename()}";
        } else {
            $path = "/ginosi/uploads/expense/items/{$attachment->getDateCreatedNeededFormat()}/{$attachment->getExpenseId()}/{$attachment->getItemId()}/{$attachment->getFilename()}";
        }

        return $path;
    }

    /**
     * @return Anonymous
     */
    public function periodTransformer()
    {
        return new Anonymous([
            'transform' => function($item) {
                if ($item['period_from'] && $item['period_to']) {
                    $period = date(Constants::GLOBAL_DATE_FORMAT, strtotime($item['period_from'])) . ' - ' . date(Constants::GLOBAL_DATE_FORMAT, strtotime($item['period_to']));
                } else {
                    $period = '';
                }

                return $period;
            },
        ]);
    }

    /**
     * @param array $params
     * @param bool $silent
     * @return string
     * @throws \Exception
     */
    public function getItems($params, $silent = false)
    {
        /**
         * @var ExpenseAttachmentsService $attachmentService
         *  @var CurrencyVault $currencyVaultService
         */
        $expenseItemDao = new ExpenseItem($this->getServiceLocator(), '\ArrayObject');
        $costCenterDao = new ExpenseCost($this->getServiceLocator(), '\ArrayObject');
        $attachmentService = $this->getServiceLocator()->get('service_finance_expense_expenses_attachments');
        $currencyVaultService = $this->getServiceLocator()->get('service_currency_currency_vault');
        $viewRender = $this->getServiceLocator()->get('ViewRenderer');
        /** @var BackofficeAuthenticationService $auth */
        $auth = $this->getServiceLocator()->get('library_backoffice_auth');
        $where = $this->constructWhereForItemSearch($params);

        $transactionIdList = [];
        $result = [
            'items' => '',
            'itemCount' => 0,
            'itemBalance' => 0,
            'transactions' => '',
            'transactionCount' => 0,
            'transactionBalance' => 0,
        ];

        $items = $expenseItemDao->getItems($where);
        $costCenters = $this->getCostCenters($costCenterDao, $items, $itemIdList);
        $itemAttachmentData = $attachmentService->getItemAttachmentListForPreview($itemIdList);
//        var_dump($itemAttachmentData); die;
        $items = iterator_to_array($items);

        if (count($items)) {
            foreach ($items as $item) {
                if ($item['po_currency_id'] != $item['currency_id']) {
                    $amount = $currencyVaultService->convertCurrency(
                        $item['amount'], (int)$item['currency_id'], (int)$item['po_currency_id'], $item['date_created']
                    );
                } else {
                    $amount = $item['amount'];
                }

                $result['itemBalance'] += $amount;
            }
        }

        if (!$silent) {
            if (count($items)) {
                $result['itemCount'] = count($items);

                foreach ($items as $item) {

                    if (!empty($item['transaction_id'])) {
                        array_push($transactionIdList, $item['transaction_id']);
                    }
                }
            }

            if (count($transactionIdList)) {
                $transactions = $this->getTransactions(['transactionIdList' => $transactionIdList], false, $count);
                $result['transactions'] = $transactions['transactions'];
                $result['transactionCount'] = $count;
                $result['transactionBalance'] = $transactions['transactionBalance'];
            }
        }

        $view = (new ViewModel([
            'items' => $items,
            'costCenters' => $costCenters,
            'period' => $this->periodTransformer(),
            'itemAttachments' => $itemAttachmentData,
            'auth' => $auth
        ]))->setTemplate('finance/partial/item');

        $result['items'] .= $viewRender->render($view);
        $result['itemBalance'] = number_format($result['itemBalance'], 2, '.', ',');
        return $result;
    }

    /**
     * @param $queryParams
     * @return bool
     * @throws \Exception
     */
    public function validateDownloadCsvItem($queryParams)
    {
        $expenseItemDao = new ExpenseItem($this->getServiceLocator(), '\ArrayObject');
        $where = $this->constructWhereForItemSearchForDatatable($queryParams);
        $result = $expenseItemDao->getItemsCountToDownload($where);
        return $result->count() <= Constants::MAX_ROW_COUNT;
    }

    /**
     * @param $iDisplayStart
     * @param $iDisplayLength
     * @param $queryParams
     * @param $sortCol
     * @param $sortDir
     * @return array
     * @throws \Exception
     */
    public function getItemsForDataTable(
        $iDisplayStart,
        $iDisplayLength,
        $queryParams,
        $sortCol,
        $sortDir
    )
    {

        $expenseItemDao = new ExpenseItem($this->getServiceLocator(), '\ArrayObject');
        $costCenterDao = new ExpenseCost($this->getServiceLocator(), '\ArrayObject');
        $where = $this->constructWhereForItemSearchForDatatable($queryParams);
        $itemIdList = [];
        $result = ($expenseItemDao->getItemsForDataTable(
            $iDisplayStart,
            $iDisplayLength,
            $where,
            $sortCol,
            $sortDir));
        $items = iterator_to_array($result['result']);
        $finalArray = [];
        $costCenters = $this->getCostCenters($costCenterDao, $items, $itemIdList);
        foreach ($items as &$item) {
            $costCenterArray = [];
            $i = 0;
            $dots = '';
            if (isset($costCenters[$item['id']])) {
                foreach($costCenters[$item['id']] as $costCenter) {
                    if ($i++ == Helper::COST_CENTERS_LIMIT_IN_PO_ITEM_SEARCH) {
                        if (count($costCenters[$item['id']]) > Helper::COST_CENTERS_LIMIT_IN_PO_ITEM_SEARCH) {
                            $dots = '...';
                        }
                        break;
                    }
                    array_push($costCenterArray, $costCenter['name']);
                }
            }

            $expenseUrl = (!is_null($item['expense_id'])) ?
                '<a href="/finance/purchase-order/ticket/' . $item['expense_id'] . '" target="_blank" class="btn btn-xs  btn-primary"><span class="glyphicon glyphicon-chevron-right"></span></a>'
                : '';
            $supplier = $item['account_name'];
            $supplierRefference = $item['account_reference'];
            $dateCreated = date(Constants::GLOBAL_DATE_FORMAT, strtotime($item['date_created']));
            $periodFrom = (!is_null($item['period_from'])) ? date(Constants::GLOBAL_DATE_FORMAT, strtotime($item['period_from'])) : '';
            $periodTo = (!is_null($item['period_to'])) ? date(Constants::GLOBAL_DATE_FORMAT, strtotime($item['period_to'])) : '';
            $period = $periodFrom . ' - ' . $periodTo;
            $comment = ($item['comment']) ?
                '<span class="glyphicon glyphicon-comment" data-toggle="popover" data-trigger="hover" data-placement="top" data-content="' . $item['comment'] . '" data-original-title="" title="" aria-describedby="popover555121"></span>'
                : '';
            $category = $item['category_name'];
            $subCategory = $item['sub_category_name'];
            $amount = $item['amount'];
            $currency = $item['currency'];
            $type = $item['type'];

            array_push(
                $finalArray,
                [
                    $dateCreated,
                    $period,
                    $supplier,
                    $supplierRefference,
                    implode(',', $costCenterArray) . $dots,
                    $category,
                    $subCategory,
                    $amount,
                    $currency,
                    $comment,
                    Helper::$types[$type],
                    $expenseUrl
                ]
            );
        }

        return [
            'result' => $finalArray,
            'count'  => $result['total']
        ];
    }

    /**
     * @param $queryParams
     * @return array
     * @throws \Exception
     */
    public function getCsvArrayForDownload($queryParams)
    {

        $expenseItemDao = new ExpenseItem($this->getServiceLocator(), '\ArrayObject');
        $costCenterDao = new ExpenseCost($this->getServiceLocator(), '\ArrayObject');
        $where = $this->constructWhereForItemSearchForDatatable($queryParams);
        $itemIdList = [];
        $result = $expenseItemDao->getItemsCsvForDownload($where);
        $items = iterator_to_array($result);
        $finalArray = [[
            'Created Date',
            'Period',
            'Supplier',
            'Reference',
            'Cost Centers',
            'Category',
            'Subcategory',
            'Amount',
            'Comment',
            'Type'
        ]];
        $costCenters = $this->getCostCenters($costCenterDao, $items, $itemIdList);
        foreach ($items as &$item) {
            $costCenterArray = [];
            if (isset($costCenters[$item['id']])) {
                foreach($costCenters[$item['id']] as $costCenter) {
                     array_push($costCenterArray, $costCenter['name'] . ' (' . $costCenter['label'] . ')  ');
                }
            }


            $supplier = $item['account_name'] . ' (' .  TransactionBase::getAccountTypeById($item['account_type']) . ')';;
            $supplierRefference = $item['account_reference'];
            $dateCreated = date(Constants::GLOBAL_DATE_FORMAT, strtotime($item['date_created']));
            $periodFrom = (!is_null($item['period_from'])) ? date(Constants::GLOBAL_DATE_FORMAT, strtotime($item['period_from'])) : '';
            $periodTo = (!is_null($item['period_to'])) ? date(Constants::GLOBAL_DATE_FORMAT, strtotime($item['period_to'])) : '';
            $period = $periodFrom . ' - ' . $periodTo;
            $comment = $item['comment'];
            $category = $item['category_name'];
            $subCategory = $item['sub_category_name'];
            $amount = $item['amount'] . $item['currency'];
            $type = $item['type'];
            array_push(
                $finalArray,
                [
                    $dateCreated,
                    $period,
                    $supplier,
                    $supplierRefference,
                    implode(',', $costCenterArray),
                    $category,
                    $subCategory,
                    $amount,
                    $comment,
                    Helper::$types[$type]
                ]
            );
        }
        return $finalArray;
    }

    /**
     * @param array $params
     * @param bool $silent
     * @param int $count
     *
     * @return string
     * @throws \Exception
     */
    public function getTransactions($params, $silent = false, &$count = 0)
    {


        /**
         *  @var CurrencyVault $currencyVaultService
         */
        $currencyVaultService = $this->getServiceLocator()->get('service_currency_currency_vault');
        $expenseTransactionDao = new ExpenseTransactions($this->getServiceLocator(), '\ArrayObject');
        $viewRender = $this->getServiceLocator()->get('ViewRenderer');
        $where = $this->constructWhereForTransactionSearch($params);
        $itemIdList = [];
        $result = [
            'items' => '',
            'itemCount' => 0,
            'itemBalance' => 0,
            'transactions' => '',
            'transactionCount' => 0,
            'transactionBalance' => 0,
        ];

        $transactions = iterator_to_array($expenseTransactionDao->getTransactions($where));

        if (count($transactions)) {
            foreach ($transactions as $transaction) {
                if ($transaction['status'] != Expense::STATUS_VOID) {
                    if (!$silent) {
                        $result['transactionCount']++;
                    }

                    $transaction['expense_transactions_count_with_same_money_transaction_id'] =
                        $expenseTransactionDao->getCountOfExpenseTransactionWithSameMoneyTransactionId($transaction['money_transaction_id']);

                    if ($transaction['po_currency_id'] != $transaction['transaction_currency_id']) {
                        $amount = $currencyVaultService->convertCurrency(
                            $transaction['amount'], (int)$transaction['transaction_currency_id'], (int)$transaction['po_currency_id'], $transaction['creation_date']
                        );
                    } else {
                        $amount = $transaction['amount'];
                    }
                    $result['transactionBalance'] += $amount;
                }
            }
        }

        $count = $result['transactionCount'];
        if (!$silent) {
            if (count($transactions)) {
                foreach ($transactions as &$transaction) {
                    $transaction['expense_transactions_count_with_same_money_transaction_id'] =
                        $expenseTransactionDao->getCountOfExpenseTransactionWithSameMoneyTransactionId($transaction['money_transaction_id']);

                    if (!empty($transaction['items'])) {
                        foreach (explode(',', $transaction['items']) as $item) {
                            array_push($itemIdList, $item);
                        }
                    }
                }
            }

            $itemIdList = array_unique($itemIdList);
            $result['itemCount'] = count($itemIdList);

            if (count($itemIdList)) {
                $items = $this->getItems(['itemIdList' => $itemIdList], true);
                $result['items'] = $items['items'];
                $result['itemBalance'] = $items['itemBalance'];
            }
        }

        $view = (new ViewModel([
            'transactions' => $transactions,
        ]))->setTemplate('finance/partial/transaction');

        $result['transactions'] .= $viewRender->render($view);
        $result['transactionBalance'] = number_format($result['transactionBalance'], 2, '.', ',');

        return $result;
    }

    /**
     * @param array $params
     * @return Where
     * @throws \Exception
     */
    private function constructWhereForItemSearch($params)
    {
        $where = new Where();
        $itemTable = DbTables::TBL_EXPENSE_ITEM;

        if (isset($params['item_id']) && $params['item_id']) {
            $where->equalTo("{$itemTable}.id", $params['item_id']);
        } elseif (empty($params['itemIdList'])) {
            if (empty($params['poId'])) {
                throw new \Exception('Purchase order id is invalid.');
            }

            $where->equalTo("{$itemTable}.expense_id", $params['poId']);

            if (!empty($params['supplier'])) {
                $where->equalTo("{$itemTable}.account_id", $params['supplier']);
            }

            if (!empty($params['creationDate'])) {
                $where->expression("date({$itemTable}.date_created) = ?", [date('Y-m-d', strtotime($params['creationDate']))]);
            }

            if (!empty($params['reference'])) {
                $where->like("{$itemTable}.account_reference", "%{$params['reference']}%");
            }

            if (!empty($params['amount'])) {
                $where->equalTo("{$itemTable}.amount", $params['amount']);
            }

            if (!empty($params['period'])) {
                list($periodFrom, $periodTo) = explode(' - ', $params['period']);
                $periodFrom = date('Y-m-d', strtotime($periodFrom));
                $periodTo = date('Y-m-d', strtotime($periodTo));
                $whereP = new Where();
                $whereP->between("{$itemTable}.period_from", $periodFrom, $periodTo);
                $whereP->or;
                $whereP->between("{$itemTable}.period_to", $periodFrom, $periodTo);

                $where->andPredicate($whereP);
            }

            if (!empty($params['category'])) {
                list($categoryId, $categoryType) = explode('_', $params['category']);

                // Conventional: 1 - category, 2 - sub category
                if ($categoryType == 1) {
                    $where->equalTo("sub_category.category_id", $categoryId);
                    $where->equalTo("sub_category.category_id", $categoryId);
                } elseif ($categoryType == 2) {
                    $where->equalTo("{$itemTable}.sub_category_id", $categoryId);
                } else {
                    throw new \Exception('Invalid category type.');
                }
            }

            if (!empty($params['costCenter'])) {
                list($costCenterId, $costCenterType) = explode('_', $params['costCenter']);

                // Conventional: 1 - apartment, 2 - office section
                if (in_array($costCenterType, [1, 2])) {
                    $where->equalTo("cost.cost_center_id", $costCenterId);
                    $where->equalTo("cost.cost_center_type", $costCenterType);
                } else {
                    throw new \Exception('Invalid cost center type.');
                }
            }
        } else {
            $where->in("{$itemTable}.id", $params['itemIdList']);
        }

        return $where;
    }

    /**
     * @param $params
     * @return Where
     * @throws \Exception
     */
    private function constructWhereForItemSearchForDatatable($params)
    {
        $where = new Where();
        $itemTable = DbTables::TBL_EXPENSE_ITEM;

        if (!empty($params['item-search-supplier'])) {
            $where->equalTo("{$itemTable}.account_id", $params['item-search-supplier']);
        }

        if (!empty($params['creator_id'])) {
            $where->equalTo("{$itemTable}.creator_id", $params['creator_id']);
        }

        if (!empty($params['item-search-creation-date'])) {
            list($creationFrom, $creationTo) = explode(' - ', $params['item-search-creation-date']);
            $creationFrom = date(Constants::DATABASE_DATE_FORMAT, strtotime($creationFrom));
            $creationTo = date(Constants::DATABASE_DATE_FORMAT, strtotime($creationTo));

            $where->expression("date({$itemTable}.date_created) BETWEEN ? AND ?", [$creationFrom, $creationTo]);
        }

        if (!empty($params['item-search-reference'])) {
            $where->like("{$itemTable}.account_reference", "%{$params['item-search-reference']}%");
        }

        if ($params['item-search-amount'] !== '') {
            $where->equalTo("{$itemTable}.amount", $params['item-search-amount']);
        }

        if (!empty($params['item-search-period'])) {
            list($periodFrom, $periodTo) = explode(' - ', $params['item-search-period']);
            $periodFrom = date(Constants::DATABASE_DATE_FORMAT, strtotime($periodFrom));
            $periodTo = date(Constants::DATABASE_DATE_FORMAT, strtotime($periodTo));

            $whereP = new Where();
            $whereP->between("{$itemTable}.period_from", $periodFrom, $periodTo);
            $whereP->or;
            $whereP->between("{$itemTable}.period_to", $periodFrom, $periodTo);
            $where->andPredicate($whereP);
        }

        if (!empty($params['item-search-category'])) {
            list($categoryId, $categoryType) = explode('_', $params['item-search-category']);

            // Conventional: 1 - category, 2 - sub category
            if ($categoryType == 1) {
                $where->equalTo("sub_category.category_id", $categoryId);
                $where->equalTo("sub_category.category_id", $categoryId);
            } elseif ($categoryType == 2) {
                $where->equalTo("{$itemTable}.sub_category_id", $categoryId);
            } else {
                throw new \Exception('Invalid category type.');
            }
        }

        if (!empty($params['item-search-cost-center'])) {
            list($costCenterType, $costCenterId) = explode('_', $params['item-search-cost-center']);

            // Conventional: 1 - apartment, 2 - office section
            if (in_array($costCenterType, [1, 2])) {
                $where->equalTo("cost.cost_center_id", $costCenterId);
                $where->equalTo("cost.cost_center_type", $costCenterType);
            } else {
                throw new \Exception('Invalid cost center type.');
            }
        }

        return $where;
    }
    /**
     * @param array $params
     * @return Where
     * @throws \Exception
     */
    private function constructWhereForTransactionSearch($params)
    {
        $where = new Where();
        $transactionTable = DbTables::TBL_EXPENSE_TRANSACTIONS;

        if (empty($params['transactionIdList'])) {
            if (empty($params['poId'])) {
                throw new \Exception('Purchase order id is invalid.');
            }

            $where->equalTo("{$transactionTable}.expense_id", $params['poId']);

            if (!empty($params['transactionId'])) {
                $where->equalTo("{$transactionTable}.id", $params['transactionId']);
            }

            if (!empty($params['accountFrom'])) {
                $where->equalTo("{$transactionTable}.money_account_id", $params['accountFrom']);
            }

            if (!empty($params['accountTo'])) {
                $where->equalTo("{$transactionTable}.account_to_id", $params['accountTo']);
            }

            if (!empty($params['transactionDate'])) {
                $where->equalTo("{$transactionTable}.transaction_date", date('Y-m-d', strtotime($params['transactionDate'])));
            }

            if (!empty($params['creationDate'])) {
                $where->expression("date({$transactionTable}.creation_date) = ?", [date('Y-m-d', strtotime($params['creationDate']))]);
            }

            if (!empty($params['amount'])) {
                $where->expression("abs({$transactionTable}.amount) = ?", $params['amount']);
            }
        } else {
            $where->in("{$transactionTable}.id", $params['transactionIdList']);
        }

        return $where;
    }

    /**
     * @param int $expenseTransactionId
     * @param array $extra
     * @return bool
     * @throws \Exception
     */
    public function voidTransaction($expenseTransactionId, &$extra)
    {
        /**
         * @var CurrencyVault $currencyVaultService
         * @var ExpenseItemAttachments $expenseItemAttachmentsDao
         * @var BackofficeAuthenticationService $auth
         */
        $auth = $this->getServiceLocator()->get('library_backoffice_auth');
        $currencyVaultService = $this->getServiceLocator()->get('service_currency_currency_vault');
        $expenseDao = new Expenses($this->getServiceLocator(), '\ArrayObject');
        $expenseItemDao = new ExpenseItem($this->getServiceLocator(), '\ArrayObject');
        $expenseTransactionDao = new ExpenseTransactions($this->getServiceLocator(), '\ArrayObject');
        $transactionDao = new Transactions($this->getServiceLocator(), '\ArrayObject');

        $transactionDetails = $expenseTransactionDao->getTransactionDetails($expenseTransactionId);

        if ($transactionDetails) {
            if ($transactionDetails['currency_id'] != $transactionDetails['expense_currency_id']) {
                $transactionDetails['amount'] = $currencyVaultService->convertCurrency(
                    $transactionDetails['amount'], (int)$transactionDetails['currency_id'], (int)$transactionDetails['expense_currency_id'], $transactionDetails['creation_date']
                );
            }

            try {
                $expenseTransactionDao->beginTransaction();

                $transactionCount = $expenseTransactionDao->getActiveTransactionCount($transactionDetails['expense_id']);
                $siblingCount = 0;
                $expenseTransactionData = [
                    'status' => Expense::STATUS_VOID,
                    'verifier_id' => $auth->getIdentity()->id,
                ];

                if (!is_null($transactionDetails['money_transaction_id'])) {
                    $siblingCount = $transactionDao->getSiblingTransactionsCount($transactionDetails['money_transaction_id']);

                    if ($siblingCount == 1) {
                        $expenseTransactionData['money_transaction_id'] = null;
                    }
                }

                // Detach items from transaction
                $expenseItemDao->update(['transaction_id' => null], ['transaction_id' => $expenseTransactionId]);

                // Update expense transaction data
                $expenseTransactionDao->save($expenseTransactionData, ['id' => $expenseTransactionId]);

                // If one to one connected transactions then remove money transaction
                if (!is_null($transactionDetails['money_transaction_id']) && $siblingCount == 1) {
                    $transactionDao->delete(['id' => $transactionDetails['money_transaction_id']]);
                }

                $expenseTransactionAmount = abs($transactionDetails['amount']);

                if ($transactionDetails['is_refund']) {
                    $transactionDetails['ticket_balance'] -= $expenseTransactionAmount;
                    $transactionDetails['transaction_balance'] -= $expenseTransactionAmount;
                } else {
                    if ($transactionCount == 1) {
                        $transactionDetails['transaction_balance'] = 0;
                    } else {
                        $transactionDetails['transaction_balance'] += $expenseTransactionAmount;
                    }

                    $transactionDetails['ticket_balance'] += $expenseTransactionAmount;
                }

                $extra = [
                    'ticket_balance' => $transactionDetails['ticket_balance'],
                    'transaction_balance' => $transactionDetails['transaction_balance'],
                ];

                // Update expense ticket balance
                $expenseDao->save([
                    'ticket_balance' => $transactionDetails['ticket_balance'],
                    'transaction_balance' => $transactionDetails['transaction_balance'],
                ], ['id' => $transactionDetails['expense_id']]);

                $expenseTransactionDao->commitTransaction();

                return true;
            } catch (\Exception $ex) {
                $expenseTransactionDao->rollbackTransaction();
            }
        }

        return false;
    }

    /**
     * @param int $transactionId
     * @param int $itemId
     * @return bool
     * @throws \Exception
     */
    public function attachItem($transactionId, $itemId)
    {
        try {
            $expenseItemDao = new ExpenseItem($this->getServiceLocator(), '\ArrayObject');
            $expenseTransactionDao = new ExpenseTransactions($this->getServiceLocator(), '\ArrayObject');
            $expenseDao = new Expenses($this->getServiceLocator(), '\ArrayObject');

            $item = $expenseItemDao->fetchOne(['id' => $itemId], ['transaction_id', 'date_created']);
            $transactionPoInfo = $expenseTransactionDao->getTransactionInfoWithAdditionalInfo($transactionId);
            if ($item && is_null($item['transaction_id']) && $transactionPoInfo) {
                $expenseItemDao->save(['transaction_id' => $transactionId], ['id' => $itemId]);
                $transactionDateArray = explode(' ', $transactionPoInfo['creation_date']);
                $transactionDateCreated = $transactionDateArray[0];
                $itemDateArray = explode(' ', $item['date_created']);
                $itemDateCreated = $itemDateArray[0];
                if (
                    $transactionDateCreated != $itemDateCreated
                    ||
                    $transactionPoInfo['currency_code_transaction'] != $transactionPoInfo['currency_code_expense']
                ) {
                    //recalculate balances with currency conversion rate of item creation date
                    /**
                     * @var \DDD\Service\Currency\CurrencyVault $currencyVaultService
                     */
                    $currencyVaultService = $this->getServiceLocator()->get('service_currency_currency_vault');
                    $transactionAmountInPOCurrencyByTransactionCreationDateConversionRate =
                        $currencyVaultService->convertCurrency($transactionPoInfo['amount'], $transactionPoInfo['currency_code_transaction'], $transactionPoInfo['currency_code_expense'], $transactionDateCreated);
                    $transactionAmountInPOCurrencyByTransactionCreationDateConversionRate =
                        number_format((float)abs($transactionAmountInPOCurrencyByTransactionCreationDateConversionRate), 2, '.', '');
                    $transactionAmountInPOCurrencyByItemCreationDateConversionRate =
                        $currencyVaultService->convertCurrency($transactionPoInfo['amount'], $transactionPoInfo['currency_code_transaction'], $transactionPoInfo['currency_code_expense'], $itemDateCreated);
                    $transactionAmountInPOCurrencyByItemCreationDateConversionRate =
                        number_format((float)abs($transactionAmountInPOCurrencyByItemCreationDateConversionRate), 2, '.', '');
                    if ($transactionAmountInPOCurrencyByTransactionCreationDateConversionRate != $transactionAmountInPOCurrencyByItemCreationDateConversionRate) {
                        $refundFactor = ($transactionPoInfo['is_refund']) ? -1 : 1;
                        $newPOTransactionBalance = (float)$transactionPoInfo['expense_transaction_balance'] + $transactionAmountInPOCurrencyByTransactionCreationDateConversionRate * $refundFactor - $transactionAmountInPOCurrencyByItemCreationDateConversionRate * $refundFactor;
                        $newPOTicketBalance = (float)$transactionPoInfo['expense_ticket_balance'] + $transactionAmountInPOCurrencyByTransactionCreationDateConversionRate * $refundFactor - $transactionAmountInPOCurrencyByItemCreationDateConversionRate * $refundFactor;
                        $expenseDao->save(
                            ['ticket_balance' => $newPOTicketBalance, 'transaction_balance' => $newPOTransactionBalance],
                            ['id' => $transactionPoInfo['expense_id']]);
                    }
                }
            } else {
                throw new \Exception('You shall not pass');
            }
        } catch (\Exception $ex) {
            return false;
        }

        return true;
    }

    /**
     * @param int $itemId
     * @return bool
     * @throws \Exception
     */
    public function detachItem($itemId)
    {
        try {
            $expenseItemDao = new ExpenseItem($this->getServiceLocator(), '\ArrayObject');
            $expenseItemDao->save(['transaction_id' => null], ['id' => $itemId]);
        } catch (\Exception $ex) {
            return false;
        }

        return true;
    }

    /**
     * @param int $itemId
     * @return array|bool
     */
    public function getItemDetails($itemId)
    {
        $itemDao = new ExpenseItem($this->getServiceLocator());
        return $itemDao->getItemDetails($itemId);
    }

    /**
     * @param $managerId
     * @return array
     */
    public function getManagersPOListJson($managerId)
    {
        $expenseDao = new Expenses($this->getServiceLocator(), '\ArrayObject');
        $pos = $expenseDao->getManagerPOs($managerId);
        $poList= [];
        if ($pos->count()) {
            foreach ($pos as $po) {
                $validity = '';
                if (!is_null($po['expected_completion_date_start']) && !is_null($po['expected_completion_date_end'])) {
                    $validity = date(Constants::GLOBAL_DATE_FORMAT, strtotime($po['expected_completion_date_start'])) . ' - ' .
                        date(Constants::GLOBAL_DATE_FORMAT, strtotime($po['expected_completion_date_end']));
                }
                $po['title'] .= $validity;
                unset($po['expected_completion_date_start']);
                unset($po['expected_completion_date_end']);
                array_push($poList, $po);
            }
        }

        return json_encode($poList);
    }

    /**
     * @param int $itemId
     * @return bool
     * @throws \RuntimeException
     */
    public function rejectItem($itemId)
    {
        /** @var \DDD\Dao\WHOrder\Order $orderDao */
        $itemDao = new ExpenseItem($this->getServiceLocator());
        $orderDao = $this->getServiceLocator()->get('dao_wh_order_order');
        $item = $itemDao->fetchOne(['id' => $itemId], ['expense_id', 'status']);

        if ($item) {
            if (is_null($item['expense_id']) && $item['status'] == Helper::ITEM_STATUS_PENDING) {
                // Reject Item
                $itemDao->save(['status' => Helper::ITEM_STATUS_REJECTED], ['id' => $itemId]);

                // Reject Order
                $orderDao->save(['status' => Order::STATUS_ORDER_REJECTED], ['po_item_id' => $itemId]);
            } else {
                throw new \RuntimeException("Unable to reject item #{$itemId}.");
            }
        } else {
            throw new \RuntimeException("PO Item #{$itemId} not found.");
        }

        return true;
    }

    /**
     * @param int $itemId
     * @return bool
     * @throws \RuntimeException
     */
    public function completeItem($itemId)
    {
        $itemDao = new ExpenseItem($this->getServiceLocator());
        $item = $itemDao->fetchOne(['id' => $itemId], ['expense_id', 'status']);

        if ($item) {
            if (is_null($item['expense_id']) && $item['status'] == Helper::ITEM_STATUS_APPROVED) {
                $itemDao->save(['status' => Helper::ITEM_STATUS_COMPLETED], ['id' => $itemId]);
            } else {
                throw new \RuntimeException("Unable to complete the item #{$itemId}.");
            }
        } else {
            throw new \RuntimeException("PO Item #{$itemId} not found.");
        }

        return true;
    }

    /**
     * @param int $itemId
     * @param int $newManagerId
     * @return bool
     * @throws \RuntimeException
     */
    public function changeManager($itemId, $newManagerId)
    {
        $itemDao = new ExpenseItem($this->getServiceLocator());
        return $itemDao->save(['manager_id' => $newManagerId], ['id' => $itemId]);
    }
    /**
     * @param int $itemId
     * @param int|null $poId
     * @return bool
     * @throws \RuntimeException
     */
    public function approveItem($itemId, $poId = null)
    {
        /**
         * @var CurrencyVault $currencyVaultService
         * @var ExpenseItemAttachments $expenseItemAttachmentsDao
         * @var \DDD\Dao\WHOrder\Order $orderDao
         */
        $currencyVaultService = $this->getServiceLocator()->get('service_currency_currency_vault');
        $poItemAttachmentsDao = $this->getServiceLocator()->get('dao_finance_expense_expense_item_attachments');
        $poDao = new Expenses($this->getServiceLocator(), '\ArrayObject');
        $itemDao = new ExpenseItem($this->getServiceLocator(), '\ArrayObject');
        $orderDao = $this->getServiceLocator()->get('dao_wh_order_order');
        $item = $itemDao->fetchOne(['id' => $itemId], ['expense_id', 'currency_id', 'amount', 'date_created', 'status']);
        $itemAttachment = $poItemAttachmentsDao->fetchOne(['item_id' => $itemId], ['filename']);
        $po = true;

        if (!is_null($poId)) {
            $po = $poDao->fetchOne(['id' => $poId], ['date_created', 'currency_id', 'ticket_balance', 'item_balance', 'finance_status']);
        }

        if ($item && $po) {
            if (is_null($item['expense_id']) && $item['status'] == Helper::ITEM_STATUS_PENDING) {
                $generalApproval = !is_null($poId);
                $itemData = ['status' => Helper::ITEM_STATUS_APPROVED];

                if ($generalApproval) {
                    if ($item['currency_id'] != $po['currency_id']) {
                        $item['amount'] = $currencyVaultService->convertCurrency($item['amount'], (int)$item['currency_id'], (int)$po['currency_id'], $item['date_created']);
                    }

                    // Save PO
                    $poDao->save([
                        'ticket_balance' => $po['ticket_balance'] + $item['amount'],
                        'item_balance' => $po['item_balance'] + $item['amount'],
                        'finance_status' => Helper::FIN_STATUS_NEW,
                    ], ['id' => $poId]);

                    $itemData['expense_id'] = $poId;
                }

                // Save Item
                $itemDao->save($itemData, ['id' => $itemId]);

                // Approve Order
                $orderDao->save(['status' => Order::STATUS_ORDER_APPROVED], ['po_item_id' => $itemId]);

                if ($generalApproval) {
                    // Save Attachment
                    $poItemAttachmentsDao->save(['expense_id' => $poId], ['item_id' => $itemId]);

                    // Move Attachment
                    if ($itemAttachment) {
                        $this->moveTmpItem($poId, $itemId, $po['date_created'], $itemAttachment->getFilename());
                    }
                }
            } else {
                throw new \RuntimeException("Unable to approve item #{$itemId}.");
            }
        } else {
            throw new \RuntimeException('PO or PO Item not found.');
        }

        return true;
    }

    /**
     * IMPORTANT: To Be Called BEFORE item amount and currency save
     * @param $itemId
     * @param $newAmount
     * @param $newCurrencyId
     * @return bool
     */
    public function recalculateTicketBalance($itemId, $newAmount, $newCurrencyId)
    {
        /**
         * @var CurrencyVault $currencyVaultService
         */
        $currencyVaultService = $this->getServiceLocator()->get('service_currency_currency_vault');
        $poDao = new Expenses($this->getServiceLocator(), '\ArrayObject');
        $itemDao = new ExpenseItem($this->getServiceLocator(), '\ArrayObject');
        $oldItemData = $itemDao->getItemAmountAndCurrencyIdById($itemId);
        if (is_null($oldItemData['expense_id'])) {
            return false;
        }

        if ($oldItemData['amount'] == $newAmount && $oldItemData['currency_id'] == $newCurrencyId) {
            return false;
        }
        $poData = $poDao->getDataForRecalculation($oldItemData['expense_id']);
        $oldItemAmountInPoCurrency = $currencyVaultService->convertCurrency($oldItemData['amount'], (int)$oldItemData['currency_id'], (int)$poData['currency_id'], $oldItemData['date_created']);
        $newItemAmountInPoCurrency = $currencyVaultService->convertCurrency($newAmount, (int)$newCurrencyId, (int)$poData['currency_id'], $oldItemData['date_created']);
        $recalculatedPoTicketBalance = $poData['ticket_balance'] - $oldItemAmountInPoCurrency + $newItemAmountInPoCurrency;
        $recalculatedPoItemBalance = $poData['item_balance'] - $oldItemAmountInPoCurrency + $newItemAmountInPoCurrency;
        $poDao->save(
            ['ticket_balance' => $recalculatedPoTicketBalance, 'item_balance' => $recalculatedPoItemBalance],
            ['id' => $oldItemData['expense_id']]
        );
        $newItemAmountInOldItemCurrency = $currencyVaultService->convertCurrency($newAmount, (int)$newCurrencyId, (int)$oldItemData['currency_id'], $oldItemData['date_created']);
        return $newItemAmountInOldItemCurrency;
    }

    /**
     * @param array $data
     * @return bool
     * @throws \Exception
     * @throws \RuntimeException
     */
    public function makeTransaction($data)
    {
        /**
         * @var BackofficeAuthenticationService $auth
         * @var MoneyAccount $moneyAccountDao
         * @var \DDD\Domain\Finance\Expense\Expenses $po
         * @var \DDD\Domain\MoneyAccount\MoneyAccount $moneyAccount
         * @var TransactionAccount $transactionAccountService
         */
        $transactionAccountService = $this->getServiceLocator()->get('service_finance_transaction_account');
        $moneyAccountDao       = $this->getServiceLocator()->get('dao_money_account_money_account');
        $expenseTransactionDao = new ExpenseTransactions($this->getServiceLocator());
        $auth                  = $this->getServiceLocator()->get('library_backoffice_auth');
        $transactionDao        = new Transactions($this->getServiceLocator());
        $expenseDao            = new Expenses($this->getServiceLocator());
        $expenseItemDao        = new ExpenseItem($this->getServiceLocator());
        $currencyService       = new CurrencyVault();
        $currencyService->setServiceLocator($this->getServiceLocator());

        if (   empty($data['poId'])
            || empty($data['itemId'])
            || empty($data['accountId'])
            || empty($data['moneyAccount'])
            || empty($data['transactionDate'])
            || empty($data['amount'])
        ) {
            throw new \RuntimeException('Invalid data provided.');
        }

        $isRefund = 0;

        if (isset($data['isRefund']) && $data['isRefund']) {
            $isRefund = 1;
        }

        $amount = $isRefund ? $data['amount'] : -1 * $data['amount'];

        $moneyAccount = $moneyAccountDao->fetchOne(['id' => $data['moneyAccount']], ['currency_id', 'balance']);
        $po           = $expenseDao->fetchOne(['id' => $data['poId']], ['ticket_balance', 'transaction_balance', 'currency_id']);
        $accountId    = $transactionAccountService->getTransactionAccountIdByIdentity($data['moneyAccount'], Account::TYPE_MONEY_ACCOUNT);

        if (!$moneyAccount || !$po || !$accountId) {
            throw new \RuntimeException('Problem with PO and Money Account.');
        }

        $transactionDao->save([
            'account_id'  => $accountId,
            'currency_id' => $moneyAccount->getCurrencyId(),
            'date'        => $data['transactionDate'],
            'description' => '',
            'amount'      => $amount,
        ]);

        $expenseTransactionDao->save([
            'money_transaction_id' => $transactionDao->lastInsertValue,
            'expense_id'           => $data['poId'],
            'creator_id'           => $auth->getIdentity()->id,
            'money_account_id'     => $data['moneyAccount'],
            'account_to_id'        => $data['accountId'],
            'transaction_date'     => $data['transactionDate'],
            'creation_date'        => date('Y-m-d H:i:s'),
            'amount'               => $amount,
            'is_refund'            => $isRefund,
        ]);

        $expenseItemDao->save(['transaction_id' => $expenseTransactionDao->lastInsertValue], ['id' => $data['itemId']]);

        $moneyAccountDao->save(
            ['balance' => $moneyAccount->getBalance() + $amount],
            ['id' => $data['moneyAccount']]
        );

        if ($po->getCurrencyId() != $moneyAccount->getCurrencyId()) {
            $amount = $currencyService->convertCurrency($amount, (int)$moneyAccount->getCurrencyId(), (int)$po->getCurrencyId());
        }

        $expenseDao->save([
            'ticket_balance'      => $po->getTicketBalance() + $amount,
            'transaction_balance' => $po->getTransactionBalance() + $amount,
        ], ['id' => $data['poId']]);
    }

    /**
     * @param int $poId
     * @param int $itemId
     * @param string $filename
     * @param string $dateCreated
     *
     * @return bool
     * @throws \Exception
     */
    public function moveTmpItem($poId, $itemId, $dateCreated, $filename)
    {
        list($date,) = explode(' ', $dateCreated);
        list($y, $m, $d) = explode('-', $date);

        $inititalDir = "/ginosi/uploads/expense/items_tmp/{$itemId}/";
        $inititalPath = "{$inititalDir}{$filename}";

        $destinationDir = "/ginosi/uploads/expense/items/{$y}/{$m}/{$d}/{$poId}/{$itemId}/";
        $destinationPath = "{$destinationDir}{$filename}";
        if (is_readable($inititalPath)) {
            if (!is_readable($destinationDir)) {
                if (!mkdir($destinationDir, 0755, true)) {
                    throw new \Exception('Cannot create directory for item attachment.');
                }
            }

            if (!@rename($inititalPath, $destinationPath)) {
                throw new \Exception('Cannot move PO Item from temp directory.');
            }

            @unlink($inititalDir);
        }

        return true;
    }

    /**
     * @param $ticketId
     * @param $price
     * @param $currencyId
     * @return array
     */
    public function checkPoForOrderItem($ticketId, $price, $currencyId)
    {
        $expenseDao = new Expenses($this->getServiceLocator());
        $basicInfo  = $expenseDao->getBasicInfoForOrderManagement($ticketId);

        if (!$basicInfo) {
            return [
                'status' => 'error',
                'msg'  => 'PO with mentioned ID does not exist',
            ];
        }

        if ($basicInfo['status'] != Helper::STATUS_GRANTED) {
            return [
                'status' => 'error',
                'msg'    => 'Purchase order is not approved',
            ];
        }

        $PoCurrency    = $basicInfo['currency_id'];
        $PoItemBalance = $basicInfo['item_balance'];
        $PoLimit       = $basicInfo['limit'];
        $currencyExchange = new \Library\Utility\Currency($this->getServiceLocator()->get('dao_currency_currency'));
        $priceInTicketCurrency = $currencyExchange->convert($price, intval($currencyId), intval($PoCurrency));

        if ($priceInTicketCurrency + $PoItemBalance > $PoLimit) {
            return [
                'status' => 'error',
                'msg'    => 'Can not exceed the limit',
            ];
        }

        return [
            'status' => 'success',
        ];
    }

    /**
     * @return int
     */
    public function getUnpaidInvoicesCount()
    {
        /**
         * @var \DDD\Dao\Finance\Expense\ExpenseItem $expenseItemDao
         */
        $expenseItemDao = $this->getServiceLocator()->get('dao_finance_expense_expense_item');
        return $expenseItemDao->getUnpaidInvoicesCount();
    }

    /**
     * @return \array[]|\Zend\Db\ResultSet\ResultSet
     */
    public function getUnpaidInvoices()
    {
        /**
         * @var \DDD\Dao\Finance\Expense\ExpenseItem $expenseItemDao
         */
        $expenseItemDao = $this->getServiceLocator()->get('dao_finance_expense_expense_item');
        return $expenseItemDao->getUnpaidInvoices();
    }

    public function resolveUnpaidItem($expenseItemId)
    {
        /**
         * @var \DDD\Dao\Finance\Expense\ExpenseItem $expenseItemDao
         */
        $expenseItemDao = $this->getServiceLocator()->get('dao_finance_expense_expense_item');
        return $expenseItemDao->update(['is_paid' => 1], ['id' => $expenseItemId]);
    }

    /**
     * @param $budgetId
     * @return \Zend\Db\ResultSet\ResultSet
     */
    public function getPOsByBudgetId($budgetId)
    {
        $expenseDao = new Expenses($this->getServiceLocator());
        return $expenseDao->getPOsByBudgetId($budgetId);
    }

    /**
     * @param $itemId
     * @return bool
     */
    public function getItemAttachment($itemId)
    {
        /**
         * @var ExpenseItemAttachments $expenseItemAttachmentsDao
         */
        $poItemAttachmentsDao = $this->getServiceLocator()->get('dao_finance_expense_expense_item_attachments');
        return $poItemAttachmentsDao->getAttachmentBasicInfoByItemId($itemId);
    }

}
