<?php

namespace BackofficeTest\Backoffice\Controller;

use Library\UnitTesting\BaseTest;

class ApartmentGroupBuildingControllerTest extends BaseTest
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
        $apartmentGroup    = $apartmentGroupDao->fetchOne([
                                'usage_building' => 1
                             ]);

        $this->assertNotNull($apartmentGroup);
        $this->dispatch('/concierge/edit/'. $apartmentGroup->getId() .'/building');
        $this->assertResponseStatusCode(200);
        $this->assertModuleName('backoffice');
        $this->assertControllerName('backoffice\controller\apartmentgroupbuilding');
        $this->assertControllerClass('ApartmentGroupBuildingController');
        $this->assertActionName('index');
        $this->assertMatchedRouteName('apartment-group/building');
    }
}