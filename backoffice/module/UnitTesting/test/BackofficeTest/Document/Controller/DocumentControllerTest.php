<?php
namespace BackofficeTest\Document\Controller;

use Library\UnitTesting\BaseTest;

class DocumentControllerTest extends BaseTest
{
    /**
     * Test search action access
     */
    public function testSearchActionCanBeAccessed()
    {
        $this->dispatch('/documents');
        $this->assertResponseStatusCode(200);

        $this->assertModuleName('document');
        $this->assertControllerName('controller_document');
        $this->assertControllerClass('Document');
        $this->assertActionName('search');
        $this->assertMatchedRouteName('documents');
    }
}