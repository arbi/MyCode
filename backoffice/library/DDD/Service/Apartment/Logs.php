<?php

namespace DDD\Service\Apartment;

use DDD\Service\ServiceBase;

class Logs extends ServiceBase
{
    public function getApartmentLogs($apartmentId)
    {
        /**
         * @var \DDD\Dao\ActionLogs\ActionLogs $actionLogsDao
         */
        $actionLogsDao = $this->getServiceLocator()->get('dao_action_logs_action_logs');
        
        return $actionLogsDao->getByApartmentId($apartmentId);
    }
}
