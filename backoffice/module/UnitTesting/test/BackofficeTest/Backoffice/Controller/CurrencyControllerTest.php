<?php

namespace BackofficeTest\Backoffice\Controller;

use Library\UnitTesting\BaseTest;

class CurrencyControllerTest extends BaseTest
{
    /**
     * Test index action access
     */
    public function testIndexActionCanBeAccessed()
    {
        $this->dispatch('/currency');
        $this->assertResponseStatusCode(200);
        $this->assertModuleName('backoffice');
        $this->assertControllerName('backoffice\controller\currency');
        $this->assertControllerClass('CurrencyController');
        $this->assertActionName('index');
        $this->assertMatchedRouteName('backoffice/default');
    }
}