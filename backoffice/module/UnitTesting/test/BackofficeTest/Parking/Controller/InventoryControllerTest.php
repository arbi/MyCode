<?php
namespace BackofficeTest\Parking\Controller;

use Library\UnitTesting\BaseTest;

class InventoryControllerTest extends BaseTest
{
    /**
     * Test index action access for any lot id, date today
     */
    public function testIndexActionCanBeAccessed()
    {
        $this->dispatch('/parking/inventory');
        $this->assertResponseStatusCode(200);
        $this->assertModuleName('parking');
        $this->assertControllerName('controller_parking_inventory');
        $this->assertControllerClass('Inventory');
        $this->assertMatchedRouteName('parking_inventory');
    }
}