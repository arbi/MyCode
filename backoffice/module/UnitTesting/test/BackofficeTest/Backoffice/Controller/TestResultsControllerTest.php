<?php

namespace BackofficeTest\Backoffice\Controller;

use Library\UnitTesting\BaseTest;

class TestResultsControllerTest extends BaseTest
{
    /**
     * Test index action access
     */
    public function testIndexActionCanBeAccessed()
    {
        $this->dispatch('/test-results');
        $this->assertResponseStatusCode(200);
        $this->assertModuleName('backoffice');
        $this->assertControllerName('backoffice\controller\testresults');
        $this->assertControllerClass('TestResultsController');
        $this->assertActionName('index');
        $this->assertMatchedRouteName('backoffice/default');
    }
}