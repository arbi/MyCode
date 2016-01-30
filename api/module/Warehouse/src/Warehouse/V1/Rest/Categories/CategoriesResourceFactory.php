<?php
namespace Warehouse\V1\Rest\Categories;

class CategoriesResourceFactory
{
    public function __invoke($services)
    {
        return new CategoriesResource($services);
    }
}
