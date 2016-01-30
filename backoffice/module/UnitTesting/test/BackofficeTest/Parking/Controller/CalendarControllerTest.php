<?php
namespace BackofficeTest\Parking\Controller;

use Library\UnitTesting\BaseTest;

class CalendarControllerTest extends BaseTest
{
    /**
     * Test index action access for any lot id, date today
     */
    public function testIndexActionCanBeAccessed()
    {
        /**
         * @var \DDD\Dao\Parking\General $lotsDao
         */
        $lotsDao = $this->getApplicationServiceLocator()->get('dao_parking_general');
        $lot     = $lotsDao->fetchOne();

        $this->assertNotNull($lot);
        $this->dispatch('/parking/'. $lot->getId() .'/calendar/'. date('Y') .'/' . date('m'));
        $this->assertResponseStatusCode(200);
        $this->assertModuleName('parking');
        $this->assertControllerName('controller_parking_inventory_calendar');
        $this->assertControllerClass('Calendar');
        $this->assertMatchedRouteName('parking/calendar');
    }
}