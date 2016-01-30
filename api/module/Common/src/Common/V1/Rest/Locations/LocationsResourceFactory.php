<?php
namespace Common\V1\Rest\Locations;

class LocationsResourceFactory
{
    public function __invoke($services)
    {
        return new LocationsResource($services);
    }
}
