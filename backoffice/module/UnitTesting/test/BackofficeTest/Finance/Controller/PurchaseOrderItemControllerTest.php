<?php
namespace BackofficeTest\Finance\Controller;

use Library\UnitTesting\BaseTest;

class PurchaseOrderItemControllerTest extends BaseTest
{
    /**
     * Test index action access
     */
    public function testIndexActionCanBeAccessed()
    {
        $this->dispatch('/finance/item/search');
        $this->assertResponseStatusCode(200);

        $this->assertModuleName('finance');
        $this->assertControllerName('finance\controller\purchaseorderitem');
        $this->assertControllerClass('PurchaseOrderItemController');
        $this->assertActionName('index');
        $this->assertMatchedRouteName('finance/item/search');
    }
}