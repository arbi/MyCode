<?php

namespace DDD\Service;

use DDD\Service\ServiceBase;
use Library\Constants\Objects;

class OTADistribution extends ServiceBase
{
    public function getIssueConnections()
    {
        $apartmentDao    = new \DDD\Dao\Apartment\OTADistribution($this->getServiceLocator(), '\ArrayObject');
        $apartelDao      = new \DDD\Dao\Apartel\OTADistribution($this->getServiceLocator(), '\ArrayObject');
        $apartmentResult = $apartmentDao->getIssueConnections();
        $apartelResult   = $apartelDao->getIssueConnections();

        $issues = [];
        foreach ($apartmentResult as $value) {
            array_push($issues, $value);
        }
        foreach ($apartelResult as $value) {
            array_push($issues, $value);
        }
        return $issues;
    }

    public function getIssueConnectionsCount()
    {
        $apartmentDao   = new \DDD\Dao\Apartment\OTADistribution($this->getServiceLocator(), '\ArrayObject');
        $apartelDao     = new \DDD\Dao\Apartel\OTADistribution($this->getServiceLocator(), '\ArrayObject');
        $apartmentCount = $apartmentDao->getIssueConnectionsCount();
        $apartelCount   = $apartelDao->getIssueConnectionsCount();
        return (int)$apartmentCount + (int)$apartelCount;
    }
}
