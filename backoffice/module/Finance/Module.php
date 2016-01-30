<?php

namespace Finance;

use Zend\ModuleManager\Feature\AutoloaderProviderInterface;
use Zend\Mvc\ModuleRouteListener;
use Zend\Mvc\MvcEvent;
use Zend\Session\Container;
use Zend\View\Model\ViewModel;
use Zend\View\Renderer\RendererInterface;
use Zend\Validator\Db\RecordExists;

class Module implements AutoloaderProviderInterface
{
    /**
     * @return array
     */
	public function getAutoloaderConfig()
    {
		return [
            'Zend\Loader\StandardAutoloader' => [
                'namespaces' => [
                    __NAMESPACE__ => __DIR__ . '/src/' . str_replace ('\\', '/', __NAMESPACE__),
                    'Library' => __DIR__ . '/../../library/Library',
                    'DDD' => __DIR__ . '/../../library/DDD'
                ]
            ]
		];
	}

    /**
     * @return array
     */
	public function getViewHelperConfig()
    {
		return [];
	}

    /**
     * @return mixed
     */
	public function getConfig()
    {
		return include __DIR__ . '/config/module.config.php';
	}

    /**
     * @return array
     */
    public function getServiceConfig()
    {
        return [
            'invokables' => [
                'service_finance_suppliers'                        => 'DDD\Service\Finance\Suppliers',
                'service_finance_legal_entities'                   => 'DDD\Service\Finance\LegalEntities',
                'service_finance_expense_expense_ticket'           => 'DDD\Service\Finance\Expense\ExpenseTicket',
                'service_finance_expense_expenses_attachments'     => 'DDD\Service\Finance\Expense\ExpenseAttachments',
                'service_finance_expense_expenses_item_categories' => 'DDD\Service\Finance\Expense\ExpenseItemCategories',
                'service_finance_expense_expenses_cost'            => 'DDD\Service\Finance\Expense\ExpenseCosts',
                'service_finance_transaction_bank_transaction'     => 'DDD\Service\Finance\Transaction\BankTransaction',
                'service_finance_transaction_po_transaction'       => 'DDD\Service\Finance\Transaction\PurchaseOrderTransaction',
                'service_finance_transfer'                         => 'DDD\Service\Finance\Transfer',
                'service_finance_espm'                             => 'DDD\Service\Finance\Espm',
            ],
            'factories' => [
                'DDD\Dao\Finance\Expense\Expenses' =>  function($sm) {
                    return new \DDD\Dao\Finance\Expense\Expenses($sm);
                },
                'DDD\Dao\Finance\Expense\ExpenseItem' =>  function($sm) {
                    return new \DDD\Dao\Finance\Expense\ExpenseItem($sm);
                },
                'DDD\Dao\Finance\Expense\ExpenseItemAttachments' =>  function($sm) {
                    return new \DDD\Dao\Finance\Expense\ExpenseItemAttachments($sm);
                },
                'DDD\Dao\Finance\Expense\ExpenseItemCategories' =>  function($sm) {
                    return new \DDD\Dao\Finance\Expense\ExpenseItemCategories($sm);
                },
                'DDD\Dao\Finance\Expense\ExpenseItemSubCategories' =>  function($sm) {
                    return new \DDD\Dao\Finance\Expense\ExpenseItemSubCategories($sm);
                },
                'DDD\Dao\Finance\Expense\ExpenseCost' => function($sm) {
                    return new \DDD\Dao\Finance\Expense\ExpenseCost($sm);
                },
                'DDD\Dao\Finance\Transaction\Transactions' => function($sm) {
                    return new \DDD\Dao\Finance\Transaction\Transactions($sm);
                },
                'DDD\Dao\Finance\Transaction\ExpenseTransactions' => function($sm) {
                    return new \DDD\Dao\Finance\Transaction\ExpenseTransactions($sm);
                },
                'DDD\Dao\Finance\Transaction\TransactionTypes' => function($sm) {
                    return new \DDD\Dao\Finance\Transaction\TransactionTypes($sm);
                },
                'DDD\Dao\Finance\Transaction\TransactionAccounts' => function($sm) {
                    return new \DDD\Dao\Finance\Transaction\TransactionAccounts($sm);
                },
                'DDD\Dao\Finance\Transaction\TransferTransactions' => function($sm) {
                    return new \DDD\Dao\Finance\Transaction\TransferTransactions($sm);
                },
                'DDD\Dao\Finance\Transaction\PendingTransfer' => function($sm) {
                    return new \DDD\Dao\Finance\Transaction\PendingTransfer($sm);
                },
                'DDD\Dao\Finance\LegalEntities' => function($sm) {
                    return new \DDD\Dao\Finance\LegalEntities($sm);
                },
                'DDD\Dao\Finance\Bank' => function($sm) {
                    return new \DDD\Dao\Finance\Bank($sm);
                },
                'DDD\Dao\Finance\Espm\Espm' => function($sm) {
                    return new \DDD\Dao\Finance\Espm\Espm($sm);
                },
            ],
            'aliases' => [
                'dao_finance_expense_expenses'                    => 'DDD\Dao\Finance\Expense\Expenses',
                'dao_finance_expense_expense_item'               => 'DDD\Dao\Finance\Expense\ExpenseItem',
                'dao_finance_expense_expense_item_attachments'    => 'DDD\Dao\Finance\Expense\ExpenseItemAttachments',
                'dao_finance_expense_expense_item_categories'     => 'DDD\Dao\Finance\Expense\ExpenseItemCategories',
                'dao_finance_expense_expense_item_sub_categories' => 'DDD\Dao\Finance\Expense\ExpenseItemSubCategories',
                'dao_finance_expense_expense_cost'                => 'DDD\Dao\Finance\Expense\ExpenseCost',
                'dao_finance_transaction_expense_transactions'    => 'DDD\Dao\Finance\Transaction\ExpenseTransactions',
                'dao_finance_transaction_transactions'            => 'DDD\Dao\Finance\Transaction\Transactions',
                'dao_finance_transaction_transaction_accounts'    => 'DDD\Dao\Finance\Transaction\TransactionAccounts',
                'dao_finance_transaction_transfer_transactions'   => 'DDD\Dao\Finance\Transaction\TransferTransactions',
                'dao_finance_transaction_pending_transfer'        => 'DDD\Dao\Finance\Transaction\PendingTransfer',
                'dao_finance_legal_entities' 		              => 'DDD\Dao\Finance\LegalEntities',
                'dao_finance_bank'                                => 'DDD\Dao\Finance\Bank',
                'dao_finance_espm'                                => 'DDD\Dao\Finance\Espm\Espm',
            ],
        ];
    }
}
