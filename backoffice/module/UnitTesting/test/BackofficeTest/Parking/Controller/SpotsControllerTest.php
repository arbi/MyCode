<?php
namespace BackofficeTest\Parking\Controller;

use Library\UnitTesting\BaseTest;

class SpotsControllerTest extends BaseTest
{
    /**
     * Test index action access for any lot id
     */
    public function testIndexActionCanBeAccessed()
    {
        /**
         * @var \DDD\Dao\Parking\General $lotsDao
         */
        $lotsDao = $this->getApplicationServiceLocator()->get('dao_parking_general');
        $lot     = $lotsDao->fetchOne();

        $this->assertNotNull($lot);
        $this->dispatch('/parking/'. $lot->getId() .'/spots');
        $this->assertResponseStatusCode(200);
        $this->assertModuleName('parking');
        $this->assertControllerName('controller_parking_spots');
        $this->assertControllerClass('Spots');
        $this->assertMatchedRouteName('parking/spots');
    }
}