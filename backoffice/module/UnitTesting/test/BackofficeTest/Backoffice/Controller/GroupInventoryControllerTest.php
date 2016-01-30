<?php

namespace BackofficeTest\Backoffice\Controller;

use Library\UnitTesting\BaseTest;

class GroupInventoryControllerTest extends BaseTest
{
    /**
     * Test index action access
     */
    public function testIndexActionCanBeAccessed()
    {
        $this->dispatch('/group-inventory');
        $this->assertResponseStatusCode(200);
        $this->assertModuleName('backoffice');
        $this->assertControllerName('backoffice\controller\groupinventory');
        $this->assertControllerClass('GroupInventoryController');
        $this->assertActionName('index');
        $this->assertMatchedRouteName('backoffice/default');
    }
}