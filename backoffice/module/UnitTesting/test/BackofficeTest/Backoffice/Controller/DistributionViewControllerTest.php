<?php

namespace BackofficeTest\Backoffice\Controller;

use Library\UnitTesting\BaseTest;

class DistributionViewControllerTest extends BaseTest
{
    /**
     * Test index action access
     */
    public function testIndexActionCanBeAccessed()
    {
        $this->dispatch('/distribution-view');
        $this->assertResponseStatusCode(200);
        $this->assertModuleName('backoffice');
        $this->assertControllerName('backoffice\controller\distributionview');
        $this->assertControllerClass('DistributionViewController');
        $this->assertActionName('index');
        $this->assertMatchedRouteName('backoffice/default');
    }
}