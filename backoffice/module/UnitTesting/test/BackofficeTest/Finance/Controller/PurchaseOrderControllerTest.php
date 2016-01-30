<?php
namespace BackofficeTest\Finance\Controller;

use DDD\Service\Currency\CurrencyVault;
use Library\UnitTesting\BaseTest;
use Library\Constants\DbTables;
use Library\Constants\Constants;

class PurchaseOrderControllerTest extends BaseTest
{
    CONST AHMAND_S_CDC_MONEY_ACCOUNT_ID = 44;
    CONST TRANSACTION_ACCOUNT_AMAZON = 16453;
    CONST COST_CENTER_YEREVAN_OFFICE_GENERAL_ID = 1;
    CONST COST_CENTER_APARTMENT_YEREVAN_DREAM_ID = 608;
    CONST SUBCATEGORY_LAUNDRY_CLEANING = 49;

    /**
     * Test index action access
     */
    public function testIndexActionCanBeAccessed()
    {
        $this->dispatch('/finance/purchase-order');
        $this->assertResponseStatusCode(200);

        $this->assertModuleName('finance');
        $this->assertControllerName('finance\controller\purchaseorder');
        $this->assertControllerClass('PurchaseOrderController');
        $this->assertActionName('index');
        $this->assertMatchedRouteName('finance/purchase-order');
    }

    /**
     * Test item can be created
     */

    public function testItemCanBeCreatedApprovedAssignedRemoved()
    {
        $dbAdapter = $this->getApplicationServiceLocator()->get('dbAdapter');
        //giving permission to add transactions for a particular money account
        $sqlSelectMoneyAccountUser = "SELECT id FROM " . DbTables::TBL_MONEY_ACCOUNT_USERS . " WHERE user_id=? AND money_account_id=? AND operation_type=?";
        $sqlInsertIntoMoneyAccountUsers = "INSERT INTO " . DbTables::TBL_MONEY_ACCOUNT_USERS . " (money_account_id, user_id, operation_type) VALUES(?,?,?) ";
        $sqlGetLastId = "SELECT MAX(id) AS max_id FROM " . DbTables::TBL_EXPENSE_ITEM;
        $sqlGetPO = "SELECT * FROM " . DbTables::TBL_EXPENSES . " WHERE currency_id=? AND status=? ORDER BY id DESC";
        $sqlGetPoById = "SELECT * FROM " . DbTables::TBL_EXPENSES . " WHERE id=?";
        $sqlExpenseTransaction = "SELECT * FROM " . DbTables::TBL_EXPENSE_TRANSACTIONS . "  ORDER BY id DESC";
        $sqlUpdatePoItem = "UPDATE " . DbTables::TBL_EXPENSE_ITEM . " SET manager_id=? WHERE id=?";
        $sqlUpdatePo = "UPDATE " . DbTables::TBL_EXPENSES . " SET `limit`=`limit`+100000, finance_status=?, manager_id=? WHERE id=?";
        $maxIdBeforeCreation = $dbAdapter->createStatement($sqlGetLastId)->execute()->current()['max_id'];
        $moneyAccountResult = $dbAdapter->createStatement($sqlSelectMoneyAccountUser)->execute([self::UNIT_TESTER_USER_ID, self::AHMAND_S_CDC_MONEY_ACCOUNT_ID,2]);
        if (!$moneyAccountResult->count()) {
            $dbAdapter->createStatement($sqlInsertIntoMoneyAccountUsers)->execute([self::AHMAND_S_CDC_MONEY_ACCOUNT_ID, self::UNIT_TESTER_USER_ID, 2]);
        }

        $currencyId = 50;//usd
        $dateToday = date('Y-m-d');
        $dateTomorrow = date('Y-m-d', strtotime('+1 day'));
        $dateTodayInOtherFormat = date(Constants::GLOBAL_DATE_FORMAT);
        $costCenters = [
            [
                'id' => self::COST_CENTER_YEREVAN_OFFICE_GENERAL_ID,
                'type' => 2, //office
                'currencyId' => 14//amd
            ],
            [
                'id' => self::COST_CENTER_APARTMENT_YEREVAN_DREAM_ID,
                'type' => 1, //apartment
                'currencyId' => 14//amd
            ],
            ];
        $data = [
            "itemId" => 0,
            "accountId" => self::TRANSACTION_ACCOUNT_AMAZON,
            "accountReference" => 'Test Supplier Reference',
            "comment" => 'Lorem ipsum',
            "costCenters" => $costCenters,
            "amount" => 10,
            "currencyId" => $currencyId,
            "subCategoryId" => self::SUBCATEGORY_LAUNDRY_CLEANING,
            "type" => 0,// Declare an expense
            "period" => $dateToday . ' - ' . $dateTomorrow,// Declare an expense
            "moneyAccount" => self::AHMAND_S_CDC_MONEY_ACCOUNT_ID,
            "transactionDate" => $dateTodayInOtherFormat
        ];

        $request = $this->getRequest();
        $headers = $request->getHeaders();
        $headers->addHeaders(array('X-Requested-With' =>'XMLHttpRequest'));
        $this->dispatch('/finance/item/save', 'POST', ['data' => json_encode($data)]);
        $this->assertResponseStatusCode(200);
        $response = json_decode($this->getResponse()->getContent(), true);
        $this->assertEquals($response['status'], 'success', 'Response status is not "success", but is "' . $response['status'] . '"');

        $maxIdAfterCreation = $dbAdapter->createStatement($sqlGetLastId)->execute()->current()['max_id'];
        $this->assertGreaterThan($maxIdBeforeCreation, $maxIdAfterCreation);
        $this->dispatch('/finance/item/' . $maxIdAfterCreation);
        $this->assertResponseStatusCode(200);


        //approve item and attach it to a PO


        $po = $dbAdapter->createStatement($sqlGetPO)->execute([51,2])->current();//Euro, Granted

        //increasing limit and changing finance status to "New" and changing manager to Engineering department manager
        $dbAdapter->createStatement($sqlUpdatePo)->execute([1, self::UNIT_TESTER_USER_ID, $po['id']]);


        $dbAdapter->createStatement($sqlUpdatePoItem)->execute([self::UNIT_TESTER_USER_ID, $maxIdAfterCreation]);



        //changing po item manager to be th unit tester
        /**
         * @var CurrencyVault $currencyVaultService
         */
        $currencyVaultService = $this->getApplicationServiceLocator()->get('service_currency_currency_vault');
        $itemAmountInPoCurrency = $currencyVaultService->convertCurrency($data['amount'], (int)$data['currencyId'], (int)$po['currency_id'], $dateToday);

        $data = [
            "poId" => $po['id'],
            "itemId" => $maxIdAfterCreation,
            "accountId" => self::TRANSACTION_ACCOUNT_AMAZON,
            "accountReference" => 'Test Supplier Reference',
            "comment" => 'Lorem ipsum',
            "costCenters" => $costCenters,
            "amount" => 10,
            "currencyId" => $currencyId,
            "subCategoryId" => self::SUBCATEGORY_LAUNDRY_CLEANING,
            "type" => 0,// Declare an expense
            "period" => $dateToday . ' - ' . $dateTomorrow,// Declare an expense
            "moneyAccount" => self::AHMAND_S_CDC_MONEY_ACCOUNT_ID,
            "transactionDate" => $dateTodayInOtherFormat
        ];

        $headers = $request->getHeaders();
        $headers->addHeaders(array('X-Requested-With' =>'XMLHttpRequest'));
        $this->dispatch('/finance/item/' . $maxIdAfterCreation . '/save', 'POST', ['data' => json_encode($data)]);
        $this->assertResponseStatusCode(200);
        $response = json_decode($this->getResponse()->getContent(), true);
        $this->assertEquals($response['status'], 'success', 'Response status is not "success", but is "' . $response['status'] . '"');

        $this->dispatch('/finance/purchase-order/ticket/' . $po['id']);
        $this->assertResponseStatusCode(200);

        $poUpdatedInfo = $dbAdapter->createStatement($sqlGetPoById)->execute([$po['id']])->current();

        //check balance recalculations
        $this->assertEquals($poUpdatedInfo['item_balance'], number_format($po['item_balance'] + $itemAmountInPoCurrency, 2, '.', ''));
        $this->assertEquals(abs($poUpdatedInfo['transaction_balance']), number_format(abs($po['transaction_balance']) + abs($itemAmountInPoCurrency), 2, '.', ''));

        //checking if the transaction has been done
        $lastTransaction =  $dbAdapter->createStatement($sqlExpenseTransaction)->execute()->current();
        $this->assertEquals($lastTransaction['expense_id'], $po['id']);

        //testing unbinding item from transaction
        $transactionId = $lastTransaction['id'];
        $this->dispatch('/finance/purchase-order/item/' . $transactionId . '/detach-transaction', 'POST', ['transactionId' => $transactionId]);
        $this->assertResponseStatusCode(200);
        $response = json_decode($this->getResponse()->getContent(), true);
        $this->assertEquals($response['status'], 'success', 'Response status is not "success", but is "' . $response['status'] . '"');


        //testing remove of item
        $headers->addHeaders(array('X-Requested-With' =>'XMLHttpRequest'));
        $this->dispatch('/finance/purchase-order/item/' . $maxIdAfterCreation . '/remove', 'POST', ['id' => $maxIdAfterCreation]);
        $this->assertResponseStatusCode(200);
        $response = json_decode($this->getResponse()->getContent(), true);
        $this->assertEquals($response['status'], 'success', 'Response status is not "success", but is "' . $response['status'] . '"');

        //checking balance recalculations
        $poUpdatedInfo2 = $dbAdapter->createStatement($sqlGetPoById)->execute([$po['id']])->current();
        $this->assertEquals($poUpdatedInfo2['ticket_balance'], number_format($poUpdatedInfo['ticket_balance'] - $itemAmountInPoCurrency, 2, '.', ''));
        $this->assertEquals($poUpdatedInfo2['item_balance'], number_format($poUpdatedInfo['item_balance'] - $itemAmountInPoCurrency, 2, '.', ''));

    }
}