<?php
namespace LibraryTest\DDD\Dao\Lock;

use Library\UnitTesting\BaseTest;

class LocksTest extends BaseTest
{
    /**
     * Testing saveNewLock method
     */
    public function testSaveNewLock()
    {
        $lockDao = $this->getApplicationServiceLocator()->get('dao_lock_locks');
        $time = time();
        $data = [
            'type_id' => 3,
            'description' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. ',
            'is_physical' => 1,
            'name' => 'test lock' . $time,
            'additional_settings' => ''
        ];
        $lastInsertedId = $lockDao->saveNewLock($data);
        $adapter = $this->getApplicationServiceLocator()->get('dbadapter');
        $statement = $adapter->createStatement('SELECT * FROM ga_locks ORDER BY id DESC LIMIT 1');
        $result = $statement->execute();
        $this->assertEquals($result->count(), 1);
        foreach($result as $row) {
            $this->assertEquals($lastInsertedId, $row['id']);
        }
    }
}