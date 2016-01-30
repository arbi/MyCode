<?php

namespace BackofficeTest\Apartel\Controller;

use Library\UnitTesting\BaseTest;

class InventoryControllerTest extends BaseTest
{
    /**
     * Test index action access for any apartelType
     */
    public function testIndexActionCanBeAccessed()
    {
        /**
         * @var \DDD\Dao\Apartel\Type $apartelTypeDao
         */
        $apartelTypeDao = $this->getApplicationServiceLocator()->get('dao_apartel_type');
        $apartelType    = $apartelTypeDao->fetchOne();

        $this->assertNotNull($apartelType);

        $this->dispatch('/apartel/'. $apartelType->getApartelId() .'/inventory/'. $apartelType->getId());
        $this->assertResponseStatusCode(200);
    }
}