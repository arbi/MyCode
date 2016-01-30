<?php

namespace BackofficeTest\Apartel\Controller;

use Library\UnitTesting\BaseTest;

class CalendarControllerTest extends BaseTest
{
    /**
     * Test index action access for any apartelType, date today
     */
    public function testIndexActionCanBeAccessed()
    {
        /**
         * @var \DDD\Dao\Apartel\Type $apartelTypeDao
         */
        $apartelTypeDao = $this->getApplicationServiceLocator()->get('dao_apartel_type');
        $apartelType    = $apartelTypeDao->fetchOne();

        $this->assertNotNull($apartelType);

        $this->dispatch('/apartel/'. $apartelType->getApartelId() .'/'. $apartelType->getId() .'/calendar/'. date('Y') .'/' . date('m'));
        $this->assertResponseStatusCode(200);
    }
}