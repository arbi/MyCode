<?php
namespace BackofficeTest\Reviews\Controller;

use Library\UnitTesting\BaseTest;

class GeneralControllerTest extends BaseTest
{
    /**
     * Test index action access for any lot id, date today
     */
    public function testIndexActionCanBeAccessed()
    {
        $this->dispatch('/reviews');
        $this->assertResponseStatusCode(200);
        $this->assertModuleName('reviews');
        $this->assertControllerName('controller_reviews_general');
        $this->assertControllerClass('General');
        $this->assertActionName('index');
        $this->assertMatchedRouteName('reviews');
    }
}