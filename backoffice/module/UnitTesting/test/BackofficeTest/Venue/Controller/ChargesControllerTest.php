<?php

namespace BackofficeTest\Venue\Controller;

use DDD\Service\Venue\Venue;
use Library\UnitTesting\BaseTest;

class ChargesControllerTest extends BaseTest
{
    /**
     * Test add action access for any venue
     */
    public function testAddActionCanBeAccessed()
    {
        /**
         * @var \DDD\Dao\Venue\Venue $venueDao
         */
        $venueDao = new \DDD\Dao\Venue\Venue($this->getApplicationServiceLocator());
        $venue    = $venueDao->fetchOne();

        $this->assertNotFalse($venue, 'Venue List is Empty');
        $this->dispatch('/venue/charge/add/' . $venue->getId());
        $this->assertResponseStatusCode(200);
        $this->assertModuleName('venue');
        $this->assertControllerName('controller_venue_charges');
        $this->assertControllerClass('Charges');
        $this->assertActionName('add');
        $this->assertMatchedRouteName('venue-charges');
    }
}