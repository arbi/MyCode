<?php

namespace BackofficeTest\Backoffice\Controller;

use Library\UnitTesting\BaseTest;

class LocationControllerTest extends BaseTest
{
    /**
     * Test index action access
     */
    public function testIndexActionCanBeAccessed()
    {
        $this->dispatch('/location');
        $this->assertResponseStatusCode(200);
        $this->assertModuleName('backoffice');
        $this->assertControllerName('backoffice\controller\location');
        $this->assertControllerClass('LocationController');
        $this->assertActionName('index');
        $this->assertMatchedRouteName('backoffice/default');
    }
}