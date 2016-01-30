<?php

namespace BackofficeTest\Backoffice\Controller;

use Library\UnitTesting\BaseTest;

class TaskControllerTest extends BaseTest
{
    /**
     * Test index action access
     */
    public function testIndexActionCanBeAccessed()
    {
        $this->dispatch('/task');
        $this->assertResponseStatusCode(200);
        $this->assertModuleName('backoffice');
        $this->assertControllerName('backoffice\controller\task');
        $this->assertControllerClass('TaskController');
        $this->assertActionName('index');
        $this->assertMatchedRouteName('backoffice/default');
    }
}