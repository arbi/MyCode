<?php
namespace BackofficeTest\Recruitment\Controller;

use Library\UnitTesting\BaseTest;

class ApplicantsControllerTest extends BaseTest
{
    /**
     * Test index action access for any lot id, date today
     */
    public function testIndexActionCanBeAccessed()
    {
        $this->dispatch('/recruitment/applicants');
        $this->assertResponseStatusCode(200);
        $this->assertModuleName('recruitment');
        $this->assertControllerName('Recruitment\Controller\Applicants');
        $this->assertControllerClass('ApplicantsController');
        $this->assertActionName('index');
        $this->assertMatchedRouteName('recruitment/applicants');
    }
}