<?php

namespace BackofficeTest\Backoffice\Controller;

use Library\UnitTesting\BaseTest;

class HousekeepingTasksControllerTest extends BaseTest
{
    /**
     * Test index action access
     */
    public function testIndexActionCanBeAccessed()
    {
        $this->dispatch('/housekeeping-tasks');
        $this->assertResponseStatusCode(200);
        $this->assertModuleName('backoffice');
        $this->assertControllerName('backoffice\controller\housekeepingtasks');
        $this->assertControllerClass('HousekeepingTasksController');
        $this->assertActionName('index');
        $this->assertMatchedRouteName('backoffice/default');
    }
}