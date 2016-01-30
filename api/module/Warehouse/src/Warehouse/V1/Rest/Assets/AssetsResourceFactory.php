<?php
namespace Warehouse\V1\Rest\Assets;

class AssetsResourceFactory
{
    public function __invoke($services)
    {
        return new AssetsResource($services);
    }
}
