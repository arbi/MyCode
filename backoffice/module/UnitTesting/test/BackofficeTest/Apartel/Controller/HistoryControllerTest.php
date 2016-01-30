<?php

namespace BackofficeTest\Apartel\Controller;

use Library\UnitTesting\BaseTest;

class HistoryControllerTest extends BaseTest
{
    /**
     * Test index action access
     */
    public function testIndexActionCanBeAccessed()
    {
        /**
         * @var \DDD\Dao\Apartel\General $apartelDao
         */
        $apartelTypeDao = $this->getApplicationServiceLocator()->get('dao_apartel_general');
        $apartel        = $apartelTypeDao->fetchOne();

        $this->assertNotNull($apartel);

        $this->dispatch('/apartel/'. $apartel->getId() .'/history');
        $this->assertResponseStatusCode(200);
    }
}