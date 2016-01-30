<?php

namespace BackofficeTest\Warehouse\Controller;

use Library\UnitTesting\BaseTest;

class CategoryControllerTest extends BaseTest
{
    /**
     * Test index action access
     */
    public function testIndexActionCanBeAccessed()
    {
        $this->dispatch('/warehouse/category');
        $this->assertResponseStatusCode(200);
        $this->assertModuleName('warehouse');
        $this->assertControllerName('controller_warehouse_category');
        $this->assertControllerClass('Category');
        $this->assertActionName('index');
        $this->assertMatchedRouteName('warehouse/category');
    }
}