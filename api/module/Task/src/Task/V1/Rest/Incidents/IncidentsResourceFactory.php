<?php
namespace Task\V1\Rest\Incidents;

class IncidentsResourceFactory
{
    public function __invoke($services)
    {
        return new IncidentsResource($services);
    }
}
