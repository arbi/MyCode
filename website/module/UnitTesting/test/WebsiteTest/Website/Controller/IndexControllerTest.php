<?php
namespace WebsiteTest\Website\Controller;

use Library\UnitTesting\WebsiteBaseTest;
use Zend\Db\Sql\Select;
use Zend\Db\Sql\Where;

class IndexControllerTest extends WebsiteBaseTest
{
    /**
     * Test index action access
     */
    public function testIndexActionCanBeAccessed()
    {
        $indexService = $this->getApplicationServiceLocator()->get('service_website_index');
        $this->assertInstanceOf('\DDD\Service\Website\Index', $indexService);
        $this->assertNotEmpty($indexService->getOptions());

        $this->dispatch('/');
        $this->assertResponseStatusCode(200);
        $this->assertModuleName('website');
        $this->assertControllerClass('IndexController');
        $this->assertActionName('index');
    }
}