<?php
namespace BackofficeTest\CreditCard\Controller;

use Library\UnitTesting\BaseTest;

class TestControllerTest extends BaseTest
{
    /**
     * Test index action access
     */
    public function testIndexActionCanBeAccessed()
    {
        $this->dispatch('/cc-demo');
    }
}