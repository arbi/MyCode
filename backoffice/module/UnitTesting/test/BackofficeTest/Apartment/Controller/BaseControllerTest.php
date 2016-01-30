<?php

namespace BackofficeTest\Apartment\Controller;

use Library\UnitTesting\BaseTest;

class BaseControllerTest extends BaseTest
{
    /**
     * Test setter
     */
    public function testSetApartmentId()
    {
        $apartmentGeneralDao = $this->getApplicationServiceLocator()->get('dao_apartment_general');
        $apartment           = $apartmentGeneralDao->fetchOne();
        $apartmentStatus     = $apartmentGeneralDao->getStatusID($apartment['id']);

        $this->assertArrayHasKey('status', $apartmentStatus);
    }
}