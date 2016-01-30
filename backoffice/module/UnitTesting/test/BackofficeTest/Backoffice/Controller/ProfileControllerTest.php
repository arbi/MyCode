<?php

namespace BackofficeTest\Backoffice\Controller;

use Library\UnitTesting\BaseTest;

class ProfileControllerTest extends BaseTest
{
    /**
     * Test index action access
     */
    public function testIndexActionCanBeAccessed()
    {
        $this->dispatch('/profile');
        $this->assertResponseStatusCode(200);
        $this->assertModuleName('backoffice');
        $this->assertControllerName('backoffice\controller\profile');
        $this->assertControllerClass('ProfileController');
        $this->assertActionName('index');
        $this->assertMatchedRouteName('profile');
    }
}