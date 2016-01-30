<?php
namespace LibraryTest\DDD\Service;

use Library\UnitTesting\BaseTest;

class BlogTest extends BaseTest
{
    /**
     * Testing getBlogById method
     */
    public function testGetBlogById()
    {
        $blogService = $this->getApplicationServiceLocator()->get('service_blog');
        $result = $blogService->getBlogById(119);
        $this->assertNotFalse($result);
    }
}