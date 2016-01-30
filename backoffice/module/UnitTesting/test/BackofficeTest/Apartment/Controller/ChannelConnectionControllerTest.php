<?php

namespace BackofficeTest\Apartment\Controller;

use Library\UnitTesting\BaseTest;

class ChannelConnectionControllerTest extends BaseTest
{
    /**
     * Test index action access
     */
    public function testIndexActionCanBeAccessed()
    {
        $apartmentGeneralDao = $this->getApplicationServiceLocator()->get('dao_apartment_general');
        $apartment           = $apartmentGeneralDao->fetchOne();

        $this->dispatch('/apartment/' . $apartment['id'] . '/channel-connection');
        $this->assertResponseStatusCode(200);

        $this->assertModuleName('apartment');
        $this->assertControllerName('controller_apartment_channel_connection');
        $this->assertControllerClass('ChannelConnection');
        $this->assertActionName('index');
        $this->assertMatchedRouteName('apartment/channel-connection');
    }
}