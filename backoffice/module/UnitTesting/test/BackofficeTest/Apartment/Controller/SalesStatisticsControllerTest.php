<?php

namespace BackofficeTest\Apartment\Controller;

use Library\UnitTesting\BaseTest;

class SalesStatisticsControllerTest extends BaseTest
{
    /**
     * Test index action access
     */
    public function testIndexActionCanBeAccessed()
    {
        $this->dispatch('/sales-statistics');
        $this->assertResponseStatusCode(200);

        $this->assertModuleName('apartment');
        $this->assertControllerName('controller_apartment_sales_statistics');
        $this->assertControllerClass('SalesStatistics');
        $this->assertActionName('index');
        $this->assertMatchedRouteName('sales_statistics');
    }
}