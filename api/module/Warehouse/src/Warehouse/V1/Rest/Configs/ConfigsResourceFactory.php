<?php
namespace Warehouse\V1\Rest\Configs;

class ConfigsResourceFactory
{
    public function __invoke($services)
    {
        return new ConfigsResource($services);
    }
}
