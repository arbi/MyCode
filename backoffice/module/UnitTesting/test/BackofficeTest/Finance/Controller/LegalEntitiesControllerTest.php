<?php
namespace BackofficeTest\Finance\Controller;

use Library\UnitTesting\BaseTest;

class LegalEntitiesControllerTest extends BaseTest
{
    /**
     * Test index action access
     */
    public function testIndexActionCanBeAccessed()
    {
        $this->dispatch('/finance/legal-entities');
        $this->assertResponseStatusCode(200);

        $this->assertModuleName('finance');
        $this->assertControllerName('finance\controller\legalentities');
        $this->assertControllerClass('LegalEntitiesController');
        $this->assertActionName('index');
        $this->assertMatchedRouteName('finance/legal-entities');
    }
}