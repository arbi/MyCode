<?php
namespace GinosiTally\V1\Rest\UserPins;

class UserPinsResourceFactory
{
    public function __invoke($services)
    {
        return new UserPinsResource($services);
    }
}
