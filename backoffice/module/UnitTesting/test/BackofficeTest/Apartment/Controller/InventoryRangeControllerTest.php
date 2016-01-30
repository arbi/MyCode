<?php

namespace BackofficeTest\Apartment\Controller;

use Library\UnitTesting\BaseTest;

class InventoryRangeControllerTest extends BaseTest
{
    /**
     * Test index action access
     */
    public function testIndexActionCanBeAccessed()
    {
        $apartmentGeneralDao = $this->getApplicationServiceLocator()->get('dao_apartment_general');
        $apartment           = $apartmentGeneralDao->fetchOne();

        $this->dispatch('/apartment/' . $apartment['id'] . '/inventory-range');
        $this->assertResponseStatusCode(200);

        $this->assertModuleName('apartment');
        $this->assertControllerName('controller_apartment_inventory_range');
        $this->assertControllerClass('InventoryRange');
        $this->assertActionName('index');
        $this->assertMatchedRouteName('apartment/inventory-range');
    }
}