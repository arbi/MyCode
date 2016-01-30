<?php

namespace BackofficeTest\Backoffice\Controller;

use Library\UnitTesting\BaseTest;

class FrontierControllerTest extends BaseTest
{
    /**
     * Test cards action access
     */
    public function testCardsActionCanBeAccessed()
    {
        $this->dispatch('/frontier');
        $this->assertResponseStatusCode(200);
        $this->assertModuleName('backoffice');
        $this->assertControllerName('backoffice\controller\frontier');
        $this->assertControllerClass('FrontierController');
        $this->assertActionName('cards');
        $this->assertMatchedRouteName('frontier');
    }
}