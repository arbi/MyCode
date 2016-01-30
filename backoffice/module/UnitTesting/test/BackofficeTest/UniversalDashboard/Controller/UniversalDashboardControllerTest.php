<?php
namespace BackofficeTest\UniversalDashboard\Controller;

use Library\UnitTesting\BaseTest;

class UniversalDashboardControllerTest extends BaseTest
{
    /**
     * Test index action access for any lot id, date today
     */
    public function testIndexActionCanBeAccessed()
    {
        $this->dispatch('/ud/universal-dashboard');
        $this->assertResponseStatusCode(200);
        $this->assertModuleName('universalDashboard');
        $this->assertControllerName('UniversalDashboard\Controller\UniversalDashboard');
        $this->assertControllerClass('UniversalDashboardController');
        $this->assertActionName('index');
        $this->assertMatchedRouteName('universal-dashboard/default');
    }
}