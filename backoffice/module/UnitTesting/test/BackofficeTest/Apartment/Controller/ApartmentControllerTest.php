<?php

namespace BackofficeTest\Apartment\Controller;

use Apartment\Form\SearchApartmentForm;
use Library\UnitTesting\BaseTest;

class ApartmentControllerTest extends BaseTest
{
    /**
     * Test search action access
     */
    public function testSearchActionCanBeAccessed()
    {
        $this->dispatch('/apartments/search');
        $this->assertResponseStatusCode(200);

        $this->assertModuleName('apartment');
        $this->assertControllerName('controller_apartment_apartment');
        $this->assertControllerClass('ApartmentController');
        $this->assertActionName('search');
        $this->assertMatchedRouteName('apartments');
    }
}