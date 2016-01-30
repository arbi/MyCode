<?php

namespace BackofficeTest\Warehouse\Controller;

use Library\UnitTesting\BaseTest;

class StorageControllerTest extends BaseTest
{
    /**
     * Test index action access
     */
    public function testIndexActionCanBeAccessed()
    {
        $this->dispatch('/warehouse/storage');
        $this->assertResponseStatusCode(200);
        $this->assertModuleName('warehouse');
        $this->assertControllerName('controller_warehouse_storage');
        $this->assertControllerClass('Storage');
        $this->assertActionName('index');
        $this->assertMatchedRouteName('warehouse/storage');
    }

    /**
     * Test save process
     */
    public function testSaveAction()
    {
        $postData = [
            'name'    => 'New Storage' . rand(0, 1000),
            'city'    => 55,
            'address' => 'Ulneci street',
        ];
        $request  = $this->getRequest();
        $headers  = $request->getHeaders();
        $headers->addHeaders([
            'X-Requested-With' => 'XMLHttpRequest'
        ]);
        $this->dispatch('/warehouse/storage/edit', 'POST', $postData);

        $adapter         = $this->getApplicationServiceLocator()->get('dbadapter');
        $statement       = $adapter->createStatement('SELECT * FROM ga_wm_storages ORDER BY id DESC LIMIT 1');
        $insertedStorage = $statement->execute();
        $this->assertEquals($insertedStorage->count(), 1);
        $insertedStorage = $insertedStorage->current();

        $this->assertEquals($insertedStorage['name'], $postData['name']);
        $this->assertRedirectTo('/warehouse/storage/edit/' . $insertedStorage['id']);
    }
}