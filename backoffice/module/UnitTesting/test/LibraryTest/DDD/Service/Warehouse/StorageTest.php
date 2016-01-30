<?php

namespace LibraryTest\DDD\Service\Warehouse;

use Library\UnitTesting\BaseTest;
use Zend\Db\Adapter\Adapter;

class StorageTest extends BaseTest
{
    /**
     * On storage creation task and team should be created automatically
     */
    public function testStorageAddTest()
    {
        /**
         * @var \DDD\Service\Warehouse\Storage $service
         * @var Adapter $adapter
         */
        $service = $this->getApplicationServiceLocator()->get('service_warehouse_storage');
        $adapter = $this->getApplicationServiceLocator()->get('dbadapter');
        $data = [
            'name'    => 'Test Storage',
            'city'    => 6,
            'address' => 'Address',
        ];

        $storageId = $service->saveStorage($data, false);

        $result = $adapter->createStatement('SELECT id, name, city_id FROM ga_wm_storages ORDER BY id DESC LIMIT 1;')->execute();
        $result = $result->current();

        // Storage Creation
        $this->assertEquals($storageId, $result['id']);
        $this->assertEquals($data['city'], $result['city_id']);
        $this->assertEquals($data['name'], $result['name']);

        // Task Creation
        $result = $adapter->createStatement('select name from ga_teams order by id DESC LIMIT 1;')->execute();
        $result = $result->current();

        $this->assertContains($data['name'], $result['name']);

        // Team Creation
        $result = $adapter->createStatement('select title from ga_task order by id DESC LIMIT 1;')->execute();
        $result = $result->current();

        $this->assertContains($data['name'], $result['title']);
    }
}
