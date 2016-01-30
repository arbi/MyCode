<?php
namespace Warehouse\V1\Rest\Histories;

class HistoriesResourceFactory
{
    public function __invoke($services)
    {
        $mapper = $services->get('Warehouse\V1\Rest\Histories\HistoriesMapper');
        return new HistoriesResource($services, $mapper);
    }
}
