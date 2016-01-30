<?php

namespace BackofficeTest\Warehouse\Controller;

use Library\UnitTesting\BaseTest;

class AssetControllerTest extends BaseTest
{
    /**
     * Test index action access
     */
    public function testIndexActionCanBeAccessed()
    {
        $this->dispatch('/warehouse/asset');
        $this->assertResponseStatusCode(200);
        $this->assertModuleName('warehouse');
        $this->assertControllerName('controller_warehouse_asset');
        $this->assertControllerClass('Asset');
        $this->assertActionName('index');
        $this->assertMatchedRouteName('warehouse/asset');
    }
}