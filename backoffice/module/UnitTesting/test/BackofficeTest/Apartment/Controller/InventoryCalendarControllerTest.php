<?php

namespace BackofficeTest\Apartment\Controller;

use Library\Constants\Objects;
use Library\UnitTesting\BaseTest;
use Zend\Db\Sql\Select;

class InventoryCalendarControllerTest extends BaseTest
{
    /**
     * Test index action access for any apartment, date today
     */
    public function testIndexActionCanBeAccessed()
    {
        /**
         * @var \DDD\Dao\Apartment\General $apartmentDao
         */
        $apartmentDao = $this->getApplicationServiceLocator()->get('dao_apartment_general');
        $apartment    = $apartmentDao->fetchOne(function (Select $select) {
                            $select->where->notEqualTo('status', Objects::PRODUCT_STATUS_DISABLED);
                        });

        $this->assertNotNull($apartment);

        $this->dispatch('/apartment/'. $apartment['id'] .'/calendar/'. date('Y') .'/' . date('m'));
        $this->assertResponseStatusCode(200);
    }
}