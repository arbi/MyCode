<?php
namespace BackofficeTest\Parking\Controller;

use Library\UnitTesting\BaseTest;

class GeneralControllerTest extends BaseTest
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
        $this->dispatch('/parking/'. $lot->getId() .'/general');
        $this->assertResponseStatusCode(200);
        $this->assertModuleName('parking');
        $this->assertControllerName('controller_parking_general');
        $this->assertControllerClass('General');
        $this->assertMatchedRouteName('parking/general');
    }
}