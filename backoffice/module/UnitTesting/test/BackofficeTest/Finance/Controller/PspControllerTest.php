<?php
namespace BackofficeTest\Finance\Controller;

use Library\UnitTesting\BaseTest;

class PspControllerTest extends BaseTest
{
    /**
     * Test index action access
     */
    public function testIndexActionCanBeAccessed()
    {
        $this->dispatch('/finance/psp');
        $this->assertResponseStatusCode(200);

        $this->assertModuleName('finance');
        $this->assertControllerName('finance\controller\psp');
        $this->assertControllerClass('PspController');
        $this->assertActionName('index');
        $this->assertMatchedRouteName('finance/psp');
    }

    /**
     * Test ajax list access
     */
    public function testAjaxPspListAction()
    {
        // check routing and status
        $this->dispatch('/finance/psp/ajax-psp-list?sEcho=1&iColumns=6&sColumns=status%2CshortName%2Cname%2Ccountry%2Cbatch%2Cactions&iDisplayStart=0&iDisplayLength=25&mDataProp_0=0&sSearch_0=&bRegex_0=false&bSearchable_0=true&bSortable_0=true&mDataProp_1=1&sSearch_1=&bRegex_1=false&bSearchable_1=true&bSortable_1=true&mDataProp_2=2&sSearch_2=&bRegex_2=false&bSearchable_2=true&bSortable_2=true&mDataProp_3=3&sSearch_3=&bRegex_3=false&bSearchable_3=true&bSortable_3=true&mDataProp_4=4&sSearch_4=&bRegex_4=false&bSearchable_4=true&bSortable_4=true&mDataProp_5=5&sSearch_5=&bRegex_5=false&bSearchable_5=false&bSortable_5=false&sSearch=&bRegex=false&iSortCol_0=1&sSortDir_0=asc&iSortingCols=1&_=1445925550425');
        $this->assertResponseStatusCode(200);
        $this->assertModuleName('finance');
        $this->assertControllerName('finance\controller\psp');
        $this->assertControllerClass('PspController');
        $this->assertActionName('ajax-psp-list');
        $this->assertMatchedRouteName('finance/psp');

        // check service
        $pspService = $this->getApplicationServiceLocator()->get('service_psp');
        $this->assertInstanceOf('\DDD\Service\Psp', $pspService);

        // check response by dummy data
        $iDisplayStart  = 0;
        $iDisplayLength = 25;
        $iSortCol_0     = 1;
        $sSortDir_0     = 0;
        $sSearch        = '';
        $all            = 1;

        $results = $pspService->pspList(
            $iDisplayStart,
            $iDisplayLength,
            $iSortCol_0,
            $sSortDir_0,
            $sSearch,
            $all
        );

        $this->assertInstanceOf('\Zend\Db\ResultSet\ResultSet', $results);

        $pspCount = $pspService->pspCount($sSearch, $all);
        $this->assertGreaterThan(0, $pspCount, 'Psp List is empty');
    }

    /**
     * Test edit page
     */
    public function testEditAction()
    {
        // check service
        $pspService = $this->getApplicationServiceLocator()->get('service_psp');
        $this->assertInstanceOf('\DDD\Service\Psp', $pspService);

        // get any result
        $pspDao = $this->getApplicationServiceLocator()->get('dao_psp_psp');
        $psp    = $pspDao->fetchOne();
        $this->assertNotNull($psp);

        // check routing and status
        $this->dispatch('/finance/psp/edit/' . $psp->getId());
        $this->assertResponseStatusCode(200);
        $this->assertModuleName('finance');
        $this->assertControllerName('finance\controller\psp');
        $this->assertControllerClass('PspController');
        $this->assertActionName('edit');
        $this->assertMatchedRouteName('finance/psp');

        // check form
        $this->assertQueryCount('form#psp', 1);
    }

    /**
     * Test add action submit [it's a same editAction without pspID]
     */
    public function testAddActionRedirectsAfterValidPost()
    {
        $bankDao          = $this->getApplicationServiceLocator()->get('dao_money_account_money_account');
        $moneyAccountList = $bankDao->getAllMoneyAccounts(1);
        $this->assertNotEmpty($moneyAccountList, 'Money Accounts List is Empty');

        $moneyAccount = $moneyAccountList->current();
        $this->assertInstanceOf('\DDD\Domain\MoneyAccount\MoneyAccount', $moneyAccount);

        $this->dispatch('/finance/psp/edit/', 'POST', [
            'name'             => 'Test PSP',
            'short_name'       => 'Test Name',
            'money_account_id' => $moneyAccount->getId(),
            'authorization'    => 0,
            'rrn'              => 0,
            'error_code'       => 0,
        ]);

        $this->assertResponseStatusCode(302);
    }

    /**
     * Test edit action submit
     */
    public function testEditActionRedirectsAfterValidPost()
    {
        // get any result
        $dbAdapter = $this->getApplicationServiceLocator()->get('dbAdapter');
        $psp       = $dbAdapter->createStatement('SELECT * FROM ga_psp')->execute()->current();
        $this->assertNotNull($psp);

        $bankDao          = $this->getApplicationServiceLocator()->get('dao_money_account_money_account');
        $moneyAccountList = $bankDao->getAllMoneyAccounts(1);
        $this->assertNotEmpty($moneyAccountList, 'Money Accounts List is Empty');

        $moneyAccount = $moneyAccountList->current();
        $this->assertInstanceOf('\DDD\Domain\MoneyAccount\MoneyAccount', $moneyAccount);

        $this->dispatch('/finance/psp/edit/' . $psp['id'], 'POST', [
            'name'             => $psp['name'],
            'short_name'       => $psp['short_name'],
            'money_account_id' => $psp['money_account_id'],
            'authorization'    => $psp['authorization'],
            'rrn'              => $psp['rrn'],
            'error_code'       => $psp['error_code'],
        ]);

        $this->assertResponseStatusCode(302);
        $this->assertRedirectTo('/finance/psp/edit/' . $psp['id']);
    }

    /**
     * Test change status action, it's activateAction() test
     */
    public function testChangeStatusAction()
    {
        // check service
        $pspService = $this->getApplicationServiceLocator()->get('service_psp');
        $this->assertInstanceOf('\DDD\Service\Psp', $pspService);

        // get any result
        $pspDao = $this->getApplicationServiceLocator()->get('dao_psp_psp');
        $psp    = $pspDao->fetchOne();
        $this->assertNotNull($psp);

        if ($psp->getStatus() == 0) {
            $status = 1;
        } else {
            $status = 0;
        }

        $result = $pspService->changeStatus($psp->getId(), $status);
        $this->assertNotFalse($result, 'PSP Change status problem in Activate action');
    }
}