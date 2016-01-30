<?php

namespace DDD\Service\UniversalDashboard\Widget;
use DDD\Dao\User\VacationRequest as VacationRequestDAO;
use DDD\Service\ServiceBase;
use DDD\Service\User\Vacation as VacationService;

/**
 * Class TimeOffRequests
 * Methods to work with "Time-off requests" widget
 *
 * @package DDD\Service\UniversalDashboard\Widget
 *
 * @author Tigran Petrosyan
 */
final class TimeOffRequests extends ServiceBase
{
    /**
     * @param $loggedInUserId
     * @return \DDD\Domain\User\VacationRequest[]
     */
    public function getTimeOffRequests($loggedInUserId)
    {
        /**
         * @var VacationRequestDAO $vacationRequestsDao
         */
        $vacationRequestsDao = $this->getServiceLocator()->get('dao_user_vacation_request');

        $timeOffRequests = $vacationRequestsDao->getTimeOffRequests($loggedInUserId);

        return $timeOffRequests;
    }

    /**
     * @param $loggedInUserId
     * @return int
     */
    public function getTimeOffRequestsCount($loggedInUserId)
    {
        /**
         * @var VacationRequestDAO $vacationRequestsDao
         */
        $vacationRequestsDao = $this->getServiceLocator()->get('dao_user_vacation_request');

        $timeOffRequestCount = $vacationRequestsDao->getTimeOffRequestsCount($loggedInUserId);

        return $timeOffRequestCount;
    }

    /**
     * @param $vacationRequestId
     * @param $vacationRequestStatus
     * @return bool
     */
    public function vacationRequestUpdate($vacationRequestId, $vacationRequestStatus)
    {
        /** @var VacationService $vacationService */
        $vacationService = $this->getServiceLocator()->get('service_user_vacation');

        $response = $vacationService->changeVacationRequestStatus($vacationRequestId, $vacationRequestStatus);

        return $response;
    }
}
