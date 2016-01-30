<?php

namespace BackofficeTest\Apartment\Controller;

use Library\UnitTesting\BaseTest;

class LocationControllerTest extends BaseTest
{
    /**
     * Test index action access
     */
    public function testIndexActionCanBeAccessed()
    {
        $apartmentGeneralDao = $this->getApplicationServiceLocator()->get('dao_apartment_general');
        $apartment           = $apartmentGeneralDao->fetchOne();

        $this->dispatch('/apartment/' . $apartment['id'] . '/location');
        $this->assertResponseStatusCode(200);

        $this->assertModuleName('apartment');
        $this->assertControllerName('controller_apartment_location');
        $this->assertControllerClass('Location');
        $this->assertActionName('index');
        $this->assertMatchedRouteName('apartment/location');
    }
}