<?php

namespace BackofficeTest\Backoffice\Controller;

use Library\UnitTesting\BaseTest;

class BlogControllerTest extends BaseTest
{
    /**
     * Test index access
     */
    public function testIndexActionCanBeAccessed()
    {
        $this->dispatch('/warehouse/asset');
        $this->assertResponseStatusCode(200);
    }

    /**
     * Test save process
     */
    public function testAddActionCanBeAccessed()
    {
        $postData = array(
            'title'  => 'post title',
            'date' => '10 Aug 2015',
            'body'     => 'lorem ipsum',
            'edit_id'     => 119,
            'img_post'     => '',
            'edit_title'     => 'post title',
        );
        $request = $this->getRequest();
        $headers = $request->getHeaders();
        $headers->addHeaders(array('X-Requested-With' =>'XMLHttpRequest'));
        $this->dispatch('/blog/ajax-save', 'POST', $postData);
        $this->assertResponseStatusCode(200);
        $response = json_decode($this->getResponse()->getContent(), true);
        $this->assertEquals($response['status'], 'success', 'Response status is not "success"');
    }

}