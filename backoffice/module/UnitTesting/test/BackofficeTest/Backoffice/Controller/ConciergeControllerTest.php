<?php

namespace BackofficeTest\Backoffice\Controller;

use Library\UnitTesting\BaseTest;

class ConciergeControllerTest extends BaseTest
{
    /**
     * Test item action access
     */
    public function testItemActionCanBeAccessed()
    {
        $apartmentGroupDao = $this->getApplicationServiceLocator()->get('dao_apartment_group_apartment_group');
        $apartmentGroup    = $apartmentGroupDao->fetchOne();

        $this->assertNotNull($apartmentGroup);
        $this->dispatch('/concierge/item/' . $apartmentGroup->getId());
        $this->assertResponseStatusCode(200);
        $this->assertModuleName('backoffice');
        $this->assertControllerName('backoffice\controller\concierge');
        $this->assertControllerClass('ConciergeController');
        $this->assertActionName('item');
        $this->assertMatchedRouteName('backoffice/default');
    }
}