<?php

namespace BackofficeTest\Recruitment\Controller;

use Library\UnitTesting\BaseTest;
use DDD\Service\User\Main as UserMain;
use Library\Constants\DomainConstants;

class JobsControllerTest extends BaseTest
{
    /**
     * Test index action access for any lot id, date today
     */
    public function testIndexActionCanBeAccessed()
    {
        $this->dispatch('/recruitment/jobs');
        $this->assertResponseStatusCode(200);
        $this->assertModuleName('recruitment');
        $this->assertControllerName('Recruitment\Controller\Jobs');
        $this->assertControllerClass('JobsController');
        $this->assertActionName('index');
        $this->assertMatchedRouteName('recruitment/jobs');
    }

    /**
     * Test save process
     */
    public function testAjaxSave()
    {
        $request = $this->getRequest();
        $headers = $request->getHeaders();
        $headers->addHeaders(array('X-Requested-With' =>'XMLHttpRequest'));
        $title = 'test' . time();
        $this->dispatch('/recruitment/jobs/ajaxsave', 'POST', [
            'userId' => UserMain::UNIT_TESTER_USER_ID,
            'job_id' => 0,
            'title'  => $title,
            'subtitle'  => 'test subtitle',
            'status'  => 2,
            'hiring_manager_id'  => 117,
            'hiring_team_id'  => 0,
            'department_id'  => 14,
            'meta_description'  => 'lorem ipsum',
            'cv_required'  => 0,
            'notify_manager'  => 0,
            'start_date'  => date('j M Y'),
            'country_id'  => 2,
            'province_id'  => 3,
            'city_id'  => 6,
            'description'  => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit.',
            'requirements'  => '',
            'city' => 'Yerevan'
        ]);
        $this->assertResponseStatusCode(200);
        $response = json_decode($this->getResponse()->getContent(), true);
        $this->assertEquals($response['status'], 'success');
        $host = 'https://' . DomainConstants::WS_DOMAIN_NAME . '/jobs/armenia--yerevan/' . $title;

        if (strpos(DomainConstants::WS_DOMAIN_NAME, 'ginosialpha') != false
            ||
            strpos(DomainConstants::WS_DOMAIN_NAME, 'ginosibeta') != false) {
            $process = curl_init($host);
            curl_setopt($process, CURLOPT_USERPWD, 'ararat' . ":" . 'havanagila');
            curl_setopt($process, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($process, CURLOPT_TIMEOUT, 30);
            $webSitePageContent = curl_exec($process);
            curl_close($process);
        } else {
            $webSitePageContent = file_get_contents($host);
        }

        $positionCareersAtGinosi = strpos($webSitePageContent, 'Careers at Ginosi');
        $positionJobTitle = strpos($webSitePageContent,$title);
        $this->assertNotFalse($positionCareersAtGinosi);
        $this->assertNotFalse($positionJobTitle);
    }
}