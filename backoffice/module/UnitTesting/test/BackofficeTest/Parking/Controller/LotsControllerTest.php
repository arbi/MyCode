<?php
namespace BackofficeTest\Parking\Controller;

use Library\UnitTesting\BaseTest;

class LotsControllerTest extends BaseTest
{
    /**
     * Test index action access for any lot id, date today
     */
    public function testIndexActionCanBeAccessed()
    {
        $this->dispatch('/parking/lots');
        $this->assertResponseStatusCode(200);
        $this->assertModuleName('parking');
        $this->assertControllerName('controller_parking_lots');
        $this->assertControllerClass('Lots');
        $this->assertMatchedRouteName('parking_lots');
    }
}