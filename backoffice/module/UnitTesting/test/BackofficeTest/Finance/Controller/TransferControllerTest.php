<?php
namespace BackofficeTest\Finance\Controller;

use Library\UnitTesting\BaseTest;

class TransferControllerTest extends BaseTest
{
    /**
     * Test index action access
     */
    public function testIndexActionCanBeAccessed()
    {
        $this->dispatch('/finance/transfer');
        $this->assertResponseStatusCode(200);

        $this->assertModuleName('finance');
        $this->assertControllerName('finance\controller\transfer');
        $this->assertControllerClass('TransferController');
        $this->assertActionName('index');
        $this->assertMatchedRouteName('finance/transfer');
    }
}