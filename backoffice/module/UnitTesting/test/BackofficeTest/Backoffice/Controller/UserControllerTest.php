<?php

namespace BackofficeTest\Backoffice\Controller;

use Library\UnitTesting\BaseTest;
use Zend\Db\Sql\Select;

class UserControllerTest extends BaseTest
{
    /**
     * Test edit action access for any apartment group
     */
    public function testEditActionCanBeAccessed()
    {
        /**
         * @var \DDD\Dao\Venue\Venue $venueDao
         */
        $userDao = $this->getApplicationServiceLocator()->get('dao_user_user_manager');
        $user    = $userDao->fetchOne();

        $this->assertNotNull($user);
        $this->dispatch('/user/edit/' . $user->getId());
        $this->assertResponseStatusCode(200);
        $this->assertModuleName('backoffice');
        $this->assertControllerName('backoffice\controller\user');
        $this->assertControllerClass('UserController');
        $this->assertActionName('edit');
        $this->assertMatchedRouteName('backoffice/default');
    }
}