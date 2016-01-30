<?php

namespace BackofficeTest\Apartment\Controller;

use Library\UnitTesting\BaseTest;

class OccupancyStatisticsControllerTest extends BaseTest
{
    /**
     * Test statistics action access
     */
    public function testStatisticsActionCanBeAccessed()
    {
        $this->dispatch('/occupancy-statistics/statistics');
        $this->assertResponseStatusCode(200);

        $this->assertModuleName('apartment');
        $this->assertControllerName('controller_apartment_occupancy_statistics');
        $this->assertControllerClass('OccupancyStatistics');
        $this->assertActionName('statistics');
        $this->assertMatchedRouteName('occupancy_statistics');
    }
}