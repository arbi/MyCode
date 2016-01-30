<?php
namespace BackofficeTest\Finance\Controller;

use Library\UnitTesting\BaseTest;

class BudgetControllerTest extends BaseTest
{
    /**
     * Test index action access
     */
    public function testIndexActionCanBeAccessed()
    {
        $this->dispatch('/finance/budget');
        $this->assertResponseStatusCode(200);

        $this->assertModuleName('finance');
        $this->assertControllerName('finance\controller\budget');
        $this->assertControllerClass('BudgetController');
        $this->assertActionName('index');
        $this->assertMatchedRouteName('finance/budget');
    }
}