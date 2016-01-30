<?php
namespace BackofficeTest\Lock\Controller;

use Library\UnitTesting\BaseTest;

class GeneralControllerTest extends BaseTest
{
    /**
     * Test index action access
     */
    public function testIndexActionCanBeAccessed()
    {
        $this->dispatch('/lock');
        $this->assertResponseStatusCode(200);

        $this->assertModuleName('lock');
        $this->assertControllerName('controller_lock_general');
        $this->assertControllerClass('General');
        $this->assertActionName('index');
        $this->assertMatchedRouteName('lock');
    }
}