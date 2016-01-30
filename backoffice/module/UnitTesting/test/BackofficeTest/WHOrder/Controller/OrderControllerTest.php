<?php
namespace BackofficeTest\WHOrder\Controller;

use Library\UnitTesting\BaseTest;
use DDD\Service\Warehouse\Category as AssetsCategoryService;

class OrderControllerTest extends BaseTest
{
    /**
     * Test index action access
     */
    public function testIndexActionCanBeAccessed()
    {
        $this->dispatch('/orders');
        $this->assertResponseStatusCode(200);
        $this->assertModuleName('whorder');
        $this->assertControllerName('warehouse_order');
        $this->assertControllerClass('OrderController');
        $this->assertActionName('index');
        $this->assertMatchedRouteName('orders');
    }

    /**
     * Test getAllCategories for order filters
     */
    public function testAjaxGetOrderCategoriesAction()
    {
        // check routing and status
        $this->dispatch('/orders/ajax-get-order-categories', 'POST', [], true);
        $this->assertResponseStatusCode(200);
        $this->assertModuleName('whorder');
        $this->assertControllerName('warehouse_order');
        $this->assertControllerClass('OrderController');
        $this->assertActionName('ajax-get-order-categories');
        $this->assertMatchedRouteName('orders/ajax-get-order-categories');

        // check service
        $assetsCategoryService = $this->getApplicationServiceLocator()->get('service_warehouse_category');
        $this->assertInstanceOf('\DDD\Service\Warehouse\Category', $assetsCategoryService);

        $assetsCategoriesData = $assetsCategoryService->getCategories([
            AssetsCategoryService::CATEGORY_TYPE_CONSUMABLE,
            AssetsCategoryService::CATEGORY_TYPE_VALUABLE,
        ]);

        foreach ($assetsCategoriesData as $category) {
            $this->assertTrue(method_exists($category, 'getId'));
            $this->assertTrue(method_exists($category, 'getName'));
            $this->assertArrayHasKey($category->getType(), AssetsCategoryService::$categoryTypes);
            $this->assertTrue(method_exists($category, 'getId'));
        }

        $this->assertLessThan(sizeof($assetsCategoriesData), 1, 'Order category list is empty');
    }
}