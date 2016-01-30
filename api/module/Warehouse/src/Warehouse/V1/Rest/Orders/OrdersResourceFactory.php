<?php
namespace Warehouse\V1\Rest\Orders;

class OrdersResourceFactory
{
    public function __invoke($services)
    {
        return new OrdersResource();
    }
}
