<?php

namespace BackofficeTest\Apartel\Controller;

use Library\UnitTesting\BaseTest;

class BaseControllerTest extends BaseTest
{
    /**
     * Test setter
     */
    public function testSetApartelId()
    {
        $generalDao = $this->getApplicationServiceLocator()->get('dao_apartel_general');

        $this->assertInstanceOf('\DDD\Dao\Apartel\General', $generalDao);
    }
}