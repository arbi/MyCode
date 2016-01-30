<?php

namespace BackofficeTest\Apartment\Controller;

use Library\UnitTesting\BaseTest;

class ApartmentReviewCategoryControllerTest extends BaseTest
{
    /**
     * Test index action access
     */
    public function testIndexActionCanBeAccessed()
    {
        $this->dispatch('/apartment-review-category');
        $this->assertResponseStatusCode(200);

        $this->assertModuleName('apartment');
        $this->assertControllerName('controller_apartment_review_category');
        $this->assertControllerClass('ApartmentReviewCategoryController');
        $this->assertActionName('index');
        $this->assertMatchedRouteName('apartment_review_category');
    }
}