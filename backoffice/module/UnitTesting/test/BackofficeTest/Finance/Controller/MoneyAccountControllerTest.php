<?php
namespace BackofficeTest\Finance\Controller;

use Library\UnitTesting\BaseTest;
use Zend\Db\Sql\Expression;
use Zend\Db\Sql\Select;

class MoneyAccountControllerTest extends BaseTest
{
    /**
     * Test index action access
     */
    public function testIndexActionCanBeAccessed()
    {
        $this->dispatch('/finance/money-account');
        $this->assertResponseStatusCode(200);

        $this->assertModuleName('finance');
        $this->assertControllerName('finance\controller\moneyaccount');
        $this->assertControllerClass('MoneyAccountController');
        $this->assertActionName('index');
        $this->assertMatchedRouteName('finance/money-account');
    }

    /**
     * Test money account list action
     */
    public function testAjaxMoneyAccountListAction()
    {
        // check routing and status
        $this->dispatch('/finance/money-account/ajax-money-account-list?sEcho=1&iColumns=7&sColumns=status%2Cname%2Ctype%2Cbalance%2Ccurrency%2Cactions%2Ctransactions&iDisplayStart=0&iDisplayLength=25&mDataProp_0=0&sSearch_0=&bRegex_0=false&bSearchable_0=true&bSortable_0=true&mDataProp_1=1&sSearch_1=&bRegex_1=false&bSearchable_1=true&bSortable_1=true&mDataProp_2=2&sSearch_2=&bRegex_2=false&bSearchable_2=true&bSortable_2=true&mDataProp_3=3&sSearch_3=&bRegex_3=false&bSearchable_3=true&bSortable_3=true&mDataProp_4=4&sSearch_4=&bRegex_4=false&bSearchable_4=true&bSortable_4=true&mDataProp_5=5&sSearch_5=&bRegex_5=false&bSearchable_5=false&bSortable_5=false&mDataProp_6=6&sSearch_6=&bRegex_6=false&bSearchable_6=false&bSortable_6=false&sSearch=&bRegex=false&iSortCol_0=3&sSortDir_0=desc&iSortingCols=1&_=1445945095866');
        $this->assertResponseStatusCode(200);
        $this->assertModuleName('finance');
        $this->assertControllerName('finance\controller\moneyaccount');
        $this->assertControllerClass('MoneyAccountController');
        $this->assertActionName('ajax-money-account-list');
        $this->assertMatchedRouteName('finance/money-account');

        // check service
        $auth                = $this->getApplicationServiceLocator()->get('library_backoffice_auth');
        $moneyAccountService = $this->getApplicationServiceLocator()->get('service_money_account');
        $this->assertInstanceOf('\DDD\Service\MoneyAccount', $moneyAccountService);

        // check response by dummy data
        $iDisplayStart  = 0;
        $iDisplayLength = 25;
        $iSortCol_0     = 1;
        $sSortDir_0     = 0;
        $sSearch        = '';
        $all            = 1;

        $results = $moneyAccountService->moneyAccountList(
            $iDisplayStart,
            $iDisplayLength,
            $iSortCol_0,
            $sSortDir_0,
            $sSearch,
            $all,
            $auth->getIdentity()->id
        );

        $this->assertInstanceOf('\Zend\Db\ResultSet\ResultSet', $results);

        $moneyAccountCount = $moneyAccountService->moneyAccountCount($sSearch, $all);
        $this->assertGreaterThan(0, $moneyAccountCount, 'Money Account List is empty');
    }

    /**
     * Test edit action
     */
    public function testEditAction()
    {
        // check services
        $moneyAccountService    = $this->getApplicationServiceLocator()->get('service_money_account');
        $moneyAccountDocService = $this->getApplicationServiceLocator()->get('service_money_account_attachment');
        $bankDao                = $this->getApplicationServiceLocator()->get('dao_finance_bank');
        $pspDao                 = $this->getApplicationServiceLocator()->get('dao_psp_psp');

        $this->assertInstanceOf('\DDD\Service\MoneyAccount', $moneyAccountService);
        $this->assertInstanceOf('\DDD\Service\MoneyAccountAttachment', $moneyAccountDocService);
        $this->assertInstanceOf('\DDD\Dao\Finance\Bank', $bankDao);
        $this->assertInstanceOf('\DDD\Dao\Psp\Psp', $pspDao);

        // get any result
        $moneyAccountDao = $this->getApplicationServiceLocator()->get('dao_money_account_money_account');
        $moneyAccount    = $moneyAccountDao->fetchOne();
        $this->assertNotNull($moneyAccount);

        // check bank account data
        $bankAccountData = $moneyAccountService->getMoneyAccountData($moneyAccount->getId());
        $this->assertNotFalse($bankAccountData);

        // check routing and status
        $this->dispatch('/finance/money-account/edit/' . $moneyAccount->getId());
        $this->assertResponseStatusCode(200);
        $this->assertModuleName('finance');
        $this->assertControllerName('finance\controller\moneyaccount');
        $this->assertControllerClass('MoneyAccountController');
        $this->assertActionName('edit');
        $this->assertMatchedRouteName('finance/money-account');

        // check form
        $this->assertQueryCount('form#bank-account', 1);
    }

    /**
     * Test add action submit [it's a same editAction without moneyAccountID]
     */
    public function testAddActionRedirectsAfterValidPost()
    {
        // get any user
        $userManagerDao = $this->getApplicationServiceLocator()->get('dao_user_user_manager');
        $user           = $userManagerDao->fetchOne();
        $this->assertNotNull($user);

        // get any bank
        $bankDao = $this->getApplicationServiceLocator()->get('dao_finance_bank');
        $bank    = $bankDao->fetchOne();
        $this->assertNotNull($bank);

        // get any legal entity
        $legalDao = $this->getApplicationServiceLocator()->get('dao_finance_legal_entities');
        $legal    = $legalDao->fetchOne();
        $this->assertNotNull($legal);

        // get any currency
        $currencyDao = $this->getApplicationServiceLocator()->get('dao_currency_currency');
        $currency    = $currencyDao->fetchOne();
        $this->assertNotNull($currency);

        $this->dispatch('/finance/money-account/edit/', 'POST', [
            'name'                  => 'Test Money Account',
            'responsible_person_id' => $user->getId(),
            'legal_entity_id'       => $legal->getId(),
            'is_searchable'         => 1,
            'bank_account_number'   => 11111111,
            'account_ending'        => 111111,
            'currency_id'           => $currency->getId(),
            'bank_id'               => $bank->getId(),
            'description'           => 'Test Money Account Description',
            'card_holder_id'        => 0,
        ]);

        $this->assertResponseStatusCode(302);
    }

    /**
     * Test edit action submit [it's a same editAction]
     */
    public function testEditActionRedirectsAfterValidPost()
    {
        // get any money account
        $moneyAccountDao = $this->getApplicationServiceLocator()->get('dao_money_account_money_account');
        $moneyAccount    = $moneyAccountDao->fetchOne();
        $this->assertNotNull($moneyAccount);
        $this->assertInstanceOf('\DDD\Domain\MoneyAccount\MoneyAccount', $moneyAccount);

        // get money account users
        $moneyAccountUserDao = $this->getApplicationServiceLocator()->get('dao_money_account_money_account_users');
        $moneyAccountUsers   = $moneyAccountUserDao->fetchAll([
            'money_account_id' => $moneyAccount->getId()
        ]);

        $viewUsers = $addUsers = $manageUsers = [];
        foreach ($moneyAccountUsers as $moneyAccountUser) {
            if ($moneyAccountUser['operation_type'] == \DDD\Service\MoneyAccount::OPERATION_VIEW_TRANSACTION) {
                $viewUsers[] = $moneyAccountUser['user_id'];
            } elseif ($moneyAccountUser['operation_type'] == \DDD\Service\MoneyAccount::OPERATION_ADD_TRANSACTION) {
                $addUsers[] = $moneyAccountUser['user_id'];
            } elseif ($moneyAccountUser['operation_type'] == \DDD\Service\MoneyAccount::OPERATION_MANAGE_TRANSACTION) {
                $manageUsers[] = $moneyAccountUser['user_id'];
            }
        }

        $this->dispatch('/finance/money-account/edit/' . $moneyAccount->getId(), 'POST', [
            'name'                  => 'Test Money Account',
            'responsible_person_id' => $moneyAccount->getResponsiblePersonId(),
            'legal_entity_id'       => $moneyAccount->getLegalEntityId(),
            'is_searchable'         => 1,
            'bank_account_number'   => 11111111,
            'bank_id'               => $moneyAccount->getBankId(),
            'description'           => 'Test Money Account Description',
            'card_holder_id'        => 0,
            'account_ending'        => 111111,
            'view_transactions'     => $viewUsers,
            'add_transactions'      => $addUsers,
            'manage_transactions'   => $manageUsers
        ]);

        $this->assertResponseStatusCode(302);
        $this->assertRedirectTo('/finance/money-account/edit/' . $moneyAccount->getId());
    }

    /**
     * Test activateAction()
     */
    public function testActivateAction()
    {
        // check service
        $moneyAccountService = $this->getApplicationServiceLocator()->get('service_money_account');
        $this->assertInstanceOf('\DDD\Service\MoneyAccount', $moneyAccountService);

        // get any result
        $moneyAccountDao = $this->getApplicationServiceLocator()->get('dao_money_account_money_account');
        $moneyAccount    = $moneyAccountDao->fetchOne();
        $this->assertNotNull($moneyAccount);

        if ($moneyAccount->getActive() == 0) {
            $status = 1;
        } else {
            $status = 0;
        }

        $result = $moneyAccountService->activateMoneyAccount($moneyAccount->getId(), $status);
        $this->assertNotFalse($result, 'Money Account Activate or Deactivate problem in activateAction()');

        $this->dispatch('/finance/money-account/activate');
        $this->assertResponseStatusCode(200);

        $this->assertModuleName('finance');
        $this->assertControllerName('finance\controller\moneyaccount');
        $this->assertControllerClass('MoneyAccountController');
        $this->assertActionName('activate');
        $this->assertMatchedRouteName('finance/money-account');
    }

    /**
     * Transaction list action test
     */
    public function testTransactionsActionCanBeAccessed()
    {
        // get any result
        $moneyAccountDao = $this->getApplicationServiceLocator()->get('dao_money_account_money_account');
        $moneyAccount    = $moneyAccountDao->fetchOne();
        $this->assertNotNull($moneyAccount);

        $transactionService = $this->getApplicationServiceLocator()->get('service_finance_transaction_bank_transaction');
        $this->assertInstanceOf('\DDD\Service\Finance\Transaction\BankTransaction', $transactionService);

        $this->dispatch('/finance/money-account/transactions/' . $moneyAccount->getId());
        $this->assertResponseStatusCode(200);

        $this->assertModuleName('finance');
        $this->assertControllerName('finance\controller\moneyaccount');
        $this->assertControllerClass('MoneyAccountController');
        $this->assertActionName('transactions');
        $this->assertMatchedRouteName('finance/money-account');
    }

    /**
     * Transaction details action test
     */
    public function testTransactionDetailsActionCanBeAccessed()
    {
        // get any result
        $transactionDao = $this->getApplicationServiceLocator()->get('dao_finance_transaction_transactions');
        $transaction    = $transactionDao->fetchOne();
        $this->assertNotNull($transaction);

        $transactionService = $this->getApplicationServiceLocator()->get('service_finance_transaction_bank_transaction');
        $this->assertInstanceOf('\DDD\Service\Finance\Transaction\BankTransaction', $transactionService);

        $transactions = $transactionService->getMoneyAccountTransactionDetails($transaction['id']);
        $this->assertNotEmpty($transactions);

        $this->dispatch('/finance/money-account/transaction-details/' . $transaction['id']);
        $this->assertResponseStatusCode(200);

        $this->assertModuleName('finance');
        $this->assertControllerName('finance\controller\moneyaccount');
        $this->assertControllerClass('MoneyAccountController');
        $this->assertActionName('transaction-details');
        $this->assertMatchedRouteName('finance/money-account');
    }

    /**
     * Test ajaxGetFullAddress action
     */
    public function testAjaxGetFullAddressAction()
    {
        // get any user
        $userManagerDao = $this->getApplicationServiceLocator()->get('dao_user_user_manager');
        $user           = $userManagerDao->fetchOne();
        $this->assertNotNull($user);

        $this->dispatch('/finance/money-account/ajax-get-full-address/', 'POST', [
            'id'     => $user->getId(),
        ], true);

        $response = $this->getResponse()->getContent();
        $response = json_decode($response);

        $this->assertObjectHasAttribute('status', $response);
        $this->assertObjectHasAttribute('full_address', $response);

        $this->assertEquals($response->status, 'success');

        $this->assertResponseStatusCode(200);
        $this->assertModuleName('finance');
        $this->assertControllerName('finance\controller\moneyaccount');
        $this->assertControllerClass('MoneyAccountController');
        $this->assertActionName('ajax-get-full-address');
        $this->assertMatchedRouteName('finance/money-account');
    }

    /**
     * Test compine transactions action
     */
    public function testCombineTransactionsAction()
    {
        // get any result
        $transactionDao = $this->getApplicationServiceLocator()->get('dao_finance_transaction_transactions');
        $transactions   = $transactionDao->fetchAll(function(Select $select) {
            $select->where->equalTo('is_verified', 1);
            $select->order('id DESC');
        });

        $this->assertNotEmpty($transactions);

        $transactionsIDsByAccountID = [];
        $transactionIDs             = [];
        foreach ($transactions as $transaction) {
            if (isset($transactionsIDsByAccountID[$transaction['account_id']])) {
                $transactionsIDsByAccountID[$transaction['account_id']][] = $transaction['id'];
                $transactionIDs = $transactionsIDsByAccountID[$transaction['account_id']];
                break;
            } else {
                $transactionsIDsByAccountID[$transaction['account_id']][] = $transaction['id'];
            }
        }

        $this->dispatch('/finance/money-account/combine-transactions/', 'POST', [
            'money_transaction_ids' => $transactionIDs
        ], true);

        $response = $this->getResponse()->getContent();
        $response = json_decode($response);

        $this->assertObjectHasAttribute('status', $response);
        $this->assertObjectHasAttribute('msg', $response);

        $this->assertEquals($response->status, 'success');

        $this->assertResponseStatusCode(200);
        $this->assertModuleName('finance');
        $this->assertControllerName('finance\controller\moneyaccount');
        $this->assertControllerClass('MoneyAccountController');
        $this->assertActionName('combine-transactions');
        $this->assertMatchedRouteName('finance/money-account');
    }

    /**
     * Test change verify status action
     */
    public function testChangeVerifyStatusAction()
    {
        // get any result
        $transactionDao = $this->getApplicationServiceLocator()->get('dao_finance_transaction_transactions');
        $transaction    = $transactionDao->fetchOne();

        $this->assertNotNull($transaction);

        if ($transaction['is_verified'] == 0) {
            $status = 1;
        } else {
            $status = 0;
        }

        $this->dispatch('/finance/money-account/change-verify-status/', 'POST', [
            'id'     => $transaction['id'],
            'status' => $status
        ], true);

        $response = $this->getResponse()->getContent();
        $response = json_decode($response);

        $this->assertObjectHasAttribute('status', $response);
        $this->assertObjectHasAttribute('msg', $response);

        $this->assertEquals($response->status, 'success');

        $this->assertResponseStatusCode(200);
        $this->assertModuleName('finance');
        $this->assertControllerName('finance\controller\moneyaccount');
        $this->assertControllerClass('MoneyAccountController');
        $this->assertActionName('change-verify-status');
        $this->assertMatchedRouteName('finance/money-account-verify-status');
    }
}