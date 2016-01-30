<?php

namespace BackofficeTest\Apartel\Controller;

use Library\UnitTesting\BaseTest;

class GeneralControllerTest extends BaseTest
{
    /**
     * Test index action access
     */
    public function testIndexActionCanBeAccessed()
    {
        /**
         * @var \DDD\Dao\Apartel\General $apartelDao
         */
        $apartelDao = $this->getApplicationServiceLocator()->get('dao_apartel_general');
        $result     = $apartelDao->fetchOne();

        $this->assertNotNull($result);

        $this->dispatch('/apartel/' . $result->getId());
        $this->assertResponseStatusCode(200);

        $this->assertModuleName('apartel');
        $this->assertControllerName('controller_apartel_general');
        $this->assertControllerClass('General');
        $this->assertMatchedRouteName('apartel');
    }
}