<?php

namespace BackofficeTest\Backoffice\Controller;

use Library\UnitTesting\BaseTest;

class CompanyDirectoryControllerTest extends BaseTest
{
    /**
     * Test index action access
     */
    public function testIndexActionCanBeAccessed()
    {
        $this->dispatch('/company-directory');
        $this->assertResponseStatusCode(200);
        $this->assertModuleName('backoffice');
        $this->assertControllerName('backoffice\controller\companydirectory');
        $this->assertControllerClass('CompanyDirectoryController');
        $this->assertActionName('index');
        $this->assertMatchedRouteName('backoffice/default');
    }
}