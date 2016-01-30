<?php
namespace BackofficeTest\Finance\Controller;

use Library\UnitTesting\BaseTest;

class EspmControllerTest extends BaseTest
{
    /**
     * Test index action access
     */
    public function testIndexActionCanBeAccessed()
    {
        $this->dispatch('/finance/espm');
        $this->assertResponseStatusCode(200);

        $this->assertModuleName('finance');
        $this->assertControllerName('finance\controller\espm');
        $this->assertControllerClass('EspmController');
        $this->assertActionName('index');
        $this->assertMatchedRouteName('finance/espm');
    }
}