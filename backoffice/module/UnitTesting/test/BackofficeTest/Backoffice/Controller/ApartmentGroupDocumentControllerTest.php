<?php

namespace BackofficeTest\Backoffice\Controller;

use Library\UnitTesting\BaseTest;

class ApartmentGroupDocumentControllerTest extends BaseTest
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
        $this->dispatch('/concierge/edit/'. $apartmentGroup->getId() .'/document');
        $this->assertResponseStatusCode(200);
        $this->assertModuleName('backoffice');
        $this->assertControllerName('backoffice\controller\apartmentgroupdocument');
        $this->assertControllerClass('ApartmentGroupDocumentController');
        $this->assertActionName('index');
        $this->assertMatchedRouteName('apartment-group/document');
    }
}