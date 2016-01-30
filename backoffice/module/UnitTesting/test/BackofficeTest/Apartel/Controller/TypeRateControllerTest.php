<?php

namespace BackofficeTest\Apartel\Controller;

use Library\UnitTesting\BaseTest;

class TypeRateControllerTest extends BaseTest
{
    /**
     * Test home action access
     */
    public function testHomeActionCanBeAccessed()
    {
        /**
         * @var \DDD\Dao\Apartel\General $apartelDao
         */
        $apartelTypeDao = $this->getApplicationServiceLocator()->get('dao_apartel_general');
        $apartel        = $apartelTypeDao->fetchOne();

        $this->assertNotNull($apartel);

        $this->dispatch('/apartel/'. $apartel->getId() .'/content');
        $this->assertResponseStatusCode(200);
    }
}