<?php
namespace BackofficeTest\Finance\Controller;

use Library\UnitTesting\BaseTest;

class ChartControllerTest extends BaseTest
{
    /**
     * Test charge action access
     */
    public function testChargeActionCanBeAccessed()
    {
        $this->dispatch('/finance/chart/charge');
        $this->assertResponseStatusCode(200);

        $this->assertModuleName('finance');
        $this->assertControllerName('finance\controller\chart');
        $this->assertControllerClass('ChartController');
        $this->assertActionName('charge');
        $this->assertMatchedRouteName('finance/chart/charge');
    }
}