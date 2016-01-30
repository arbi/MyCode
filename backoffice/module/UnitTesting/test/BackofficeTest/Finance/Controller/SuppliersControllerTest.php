<?php
namespace BackofficeTest\Finance\Controller;

use Library\UnitTesting\BaseTest;

class SuppliersControllerTest extends BaseTest
{
    /**
     * Test index action access
     */
    public function testIndexActionCanBeAccessed()
    {
        $this->dispatch('/finance/suppliers');
        $this->assertResponseStatusCode(200);

        $this->assertModuleName('finance');
        $this->assertControllerName('finance\controller\suppliers');
        $this->assertControllerClass('SuppliersController');
        $this->assertActionName('index');
        $this->assertMatchedRouteName('finance/suppliers');
    }
}