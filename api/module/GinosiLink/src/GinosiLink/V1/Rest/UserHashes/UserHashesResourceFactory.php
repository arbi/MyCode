<?php
namespace GinosiLink\V1\Rest\UserHashes;

class UserHashesResourceFactory
{
    public function __invoke($services)
    {
        return new UserHashesResource($services);
    }
}
