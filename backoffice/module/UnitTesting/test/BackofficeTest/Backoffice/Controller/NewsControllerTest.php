<?php

namespace BackofficeTest\Backoffice\Controller;

use Library\UnitTesting\BaseTest;

class NewsControllerTest extends BaseTest
{
    /**
     * Test index access
     */
    public function testIndexActionCanBeAccessed()
    {
        $this->dispatch('/news');
        $this->assertResponseStatusCode(200);
    }

    /**
     * test get json
     */
    public function testGetJsonAccessed()
    {
        $request = $this->getRequest();
        $headers = $request->getHeaders();
        $headers->addHeaders(array('X-Requested-With' =>'XMLHttpRequest'));
        $this->dispatch('/news/get-json', 'POST', []);
        $this->assertResponseStatusCode(200);
        $response = json_decode($this->getResponse()->getContent(), true);
        $this->assertArrayHasKey('aaData', $response, 'Response does not contain aaData');
    }

    /**
     * Test save process
     */
    public function testAjaxSaveAccessed()
    {
        $postData = array(
            'title'  => 'UnitTest news title',
            'date' => '10 Aug 2015',
            'body'     => 'UnitTest news body'
        );
        $request = $this->getRequest();
        $headers = $request->getHeaders();
        $headers->addHeaders(array('X-Requested-With' =>'XMLHttpRequest'));
        $this->dispatch('/news/ajaxsave', 'POST', $postData);
        $this->assertResponseStatusCode(200);
        $response = json_decode($this->getResponse()->getContent(), true);
        $this->assertEquals($response['status'], 'success', 'Response status is not "success"');
    }

    /**
     * @return array
     */
    public function additionProvider()
    {
        return array(
            array(0),
            array(27)
        );
    }

    /**
     * Test delete access
     */
    public function testAjaxDeleteAccessed()
    {
        $request = $this->getRequest();
        $headers = $request->getHeaders();
        $headers->addHeaders(array('X-Requested-With' =>'XMLHttpRequest'));
        $this->dispatch('/news/ajaxdelete', 'POST', [
            'id' => 25
        ]);
        $this->assertResponseStatusCode(200);
        $response = json_decode($this->getResponse()->getContent(), true);
        $this->assertEquals($response['status'], 'success', 'Response status is not "success"');
    }
}