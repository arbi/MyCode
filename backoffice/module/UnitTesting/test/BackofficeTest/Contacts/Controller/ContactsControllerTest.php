<?php
namespace BackofficeTest\Contacts\Controller;

use Library\UnitTesting\BaseTest;

class ContactsControllerTest extends BaseTest
{
    /**
     * Test search action access
     */
    public function testSearchActionCanBeAccessed()
    {
        $this->dispatch('/contacts');
        $this->assertResponseStatusCode(200);

        $this->assertModuleName('contacts');
        $this->assertControllerName('contacts');
        $this->assertControllerClass('Contacts');
        $this->assertActionName('search');
        $this->assertMatchedRouteName('contacts');
    }
}