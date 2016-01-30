<?php

namespace BackofficeTest\Backoffice\Controller;

use Library\UnitTesting\BaseTest;

class ApartmentGroupGeneralControllerTest extends BaseTest
{
    /**
     * Test index action access for any apartment group
     */
    public function testIndexActionCanBeAccessed()
    {
        /**
         * @var \DDD\Dao\Venue\Venue $venueDao
         */
        $apartmentGroupDao = $this->getApplicationServiceLocator()->get('dao_apartment_group_apartment_group');
        $apartmentGroup    = $apartmentGroupDao->fetchOne();

        $this->assertNotNull($apartmentGroup);
        $this->dispatch('/concierge/edit/'. $apartmentGroup->getId());
        $this->assertResponseStatusCode(200);
        $this->assertModuleName('backoffice');
        $this->assertControllerName('backoffice\controller\apartmentgroupgeneral');
        $this->assertControllerClass('ApartmentGroupGeneralController');
        $this->assertActionName('index');
        $this->assertMatchedRouteName('apartment-group');
    }
}