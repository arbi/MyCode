<?php
namespace GinosiTally\V1\Rest\VenueCharges;

class VenueChargesResourceFactory
{
    public function __invoke($services)
    {
        return new VenueChargesResource($services);
    }
}
