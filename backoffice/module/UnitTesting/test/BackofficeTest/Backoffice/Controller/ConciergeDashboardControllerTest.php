<?php

namespace BackofficeTest\Backoffice\Controller;

use Library\UnitTesting\BaseTest;

class ConciergeDashboardControllerTest extends BaseTest
{
    /**
     * Test index action access
     */
    public function testIndexActionCanBeAccessed()
    {
        $this->dispatch('/concierge-dashboard');
        $this->assertResponseStatusCode(200);
        $this->assertModuleName('backoffice');
        $this->assertControllerName('backoffice\controller\conciergedashboard');
        $this->assertControllerClass('ConciergeDashboardController');
        $this->assertActionName('index');
        $this->assertMatchedRouteName('backoffice/default');
    }
}