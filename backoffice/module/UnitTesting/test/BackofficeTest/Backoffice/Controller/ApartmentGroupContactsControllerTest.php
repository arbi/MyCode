<?php

namespace BackofficeTest\Backoffice\Controller;

use Library\UnitTesting\BaseTest;

class ApartmentGroupContactsControllerTest extends BaseTest
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
        $this->dispatch('/concierge/edit/'. $apartmentGroup->getId() .'/contacts');
        $this->assertResponseStatusCode(200);
        $this->assertModuleName('backoffice');
        $this->assertControllerName('backoffice\controller\apartmentgroupcontacts');
        $this->assertControllerClass('ApartmentGroupContactsController');
        $this->assertActionName('index');
        $this->assertMatchedRouteName('apartment-group/contacts');
    }
}