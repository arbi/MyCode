<?php

namespace DDD\Service\User;

use DDD\Dao\User\VacationRequest as VacationRequestDAO;
use DDD\Service\Notifications as NotificationsService;
use DDD\Service\ServiceBase;
use DDD\Dao\Settings\Vacations as VacationsDao;
use DDD\Service\Task as TaskService;
use DDD\Service\User as UserService;
use Library\Constants\Roles;


use Library\Authentication\BackofficeAuthenticationService;
use Library\Constants\TextConstants;
use Zend\Db\Sql\Where;

/**
 * Class Vacation
 * @package DDD\Service\User
 */
class Vacation extends ServiceBase
{
    const VACATION_REQUEST_STATUS_CANCELLED = 0;
    const VACATION_REQUEST_STATUS_APPROVED = 1;
    const VACATION_REQUEST_STATUS_PENDING = 2;
    const VACATION_REQUEST_STATUS_REJECTED = 3;

    const VACATION_TYPE_VACATION = 1;
    const VACATION_TYPE_PERSONAL = 2;
    const VACATION_TYPE_SICK = 3;
    const VACATION_TYPE_UNPAID = 4;

    /**
     * @return array
     */
    public static function getVacationTypeOptions()
    {
        return [
            self::VACATION_TYPE_VACATION => 'Vacation',
            self::VACATION_TYPE_PERSONAL => 'Personal',
            self::VACATION_TYPE_SICK => 'Sick',
            self::VACATION_TYPE_UNPAID => 'Unpaid Leave'
        ];
    }

    /**
     * @param $vacationRequestId
     * @param $status
     * @return bool
     */
    public function changeVacationRequestStatus($vacationRequestId, $status)
    {
        /** @var BackofficeAuthenticationService $authenticationService */
        $authenticationService = $this->getServiceLocator()->get('library_backoffice_auth');
        $loggedInUserId = $authenticationService->getIdentity()->id;

        /** @var VacationRequestDAO $vacationRequestsDao */
        $vacationRequestsDao = $this->getServiceLocator()->get('dao_user_vacation_request');
        $vacationRequest = $vacationRequestsDao->getVacationById($vacationRequestId);

        /** @var \DDD\Dao\User\Schedule\Inventory $scheduleInventoryDao */
        $scheduleInventoryDao = $this->getServiceLocator()->get('dao_user_schedule_inventory');

        if ($vacationRequest &&
            (
                $loggedInUserId == $vacationRequest->getUser_id()
                ||
                $loggedInUserId == $vacationRequest->getManager_id()
                ||
                $authenticationService->hasRole(Roles::ROLE_HR_VACATION_EDITOR)
            )
        ) {
            $vacationRequestsDao->save(
                ['is_approved' => $status],
                ['id' => $vacationRequestId]
            );

            if ($status == 1) {
                $scheduleInventoryDao->applyVacation($vacationRequest);
            } else {
                $scheduleInventoryDao->applyVacationCancellation($vacationRequest);
            }

            $userService = $this->getServiceLocator()->get('service_user');

            $userId = $vacationRequest->getUser_id();

            /** @var UserManager */
            $userManagerDao = $this->getServiceLocator()->get('dao_user_user_manager');
            $user = $userManagerDao->fetchOne(['id' => $userId]);


            //Set Vacation Response & reminder Notifications
            if ($status == 1) {
                $this->setVacationReminder($vacationRequestId);
            }

            //  if (Requested vacation's type is vacation or personal)
            //  AND
            //  (
            //      vacation is approved
            //          OR
            //      Canceling a vacation, which was approved before
            //  )
            if (
                $vacationRequest
                &&
                in_array($vacationRequest->getType(), [self::VACATION_TYPE_VACATION, self::VACATION_TYPE_PERSONAL])
                &&
                (
                    $status == self::VACATION_REQUEST_STATUS_APPROVED
                    ||
                    (
                        $status == self::VACATION_REQUEST_STATUS_CANCELLED
                        &&
                        $vacationRequest->getIs_approved() == self::VACATION_REQUEST_STATUS_APPROVED
                    )
                )
            ) {
                if ($status ==  self::VACATION_REQUEST_STATUS_APPROVED) {
                    $calculatedVacationDays = round (($user->getVacation_days() - $vacationRequest->getTotal_number()), 2);
                } else {
                    $calculatedVacationDays = round (($user->getVacation_days() + $vacationRequest->getTotal_number()),2);
                }

                $userManagerDao->save(['vacation_days' => $calculatedVacationDays], ['id' => $userId]);
            }

            $vacationRequestType = $vacationRequest->getType();

            $this->notifyTimeOffRequestResponse($vacationRequestId);

            /**
             * @var TaskService $taskService
             */
            $taskService = $this->getServiceLocator()->get('service_task');

            switch ($vacationRequestType) {
                case self::VACATION_TYPE_SICK:

                    /**
                     * @todo this is a old dao and it needs to be removed
                     */
                    $vacationDao = $this->getServiceLocator()->get('dao_user_vacation_days');
                    $userRegistry = $userService->getUsersById($userId);

                    $takenSickDays = 0;
                    $totalSickDays = $userRegistry->get('user_main')->getSickDays();
                    $sickDays = $vacationDao->getSickDays($userId);
                    if ($sickDays) {
                        foreach ($sickDays as $sickDay) {
                            $takenSickDays += abs($sickDay['total_number']);
                        }
                    }

                    if ($totalSickDays  != UserService::UNLIMITED_SICK_DAYS) {
                        $userFullName = $vacationRequest->getFirstName() . ' ' . $vacationRequest->getLastName();
                        $vacationStartDate = $vacationRequest->getFrom();
                        $vacationEndDate = $vacationRequest->getTo();
                        $vacationDaysCount = $vacationRequest->getTotal_number();

                        $taskService->createSickDayPayOutAutoTask($userFullName, $vacationStartDate, $vacationEndDate, $vacationDaysCount, $totalSickDays, $takenSickDays);
                    }

                    break;
                case self::VACATION_TYPE_UNPAID:
                    $userFullName = $vacationRequest->getFirstName() . ' ' . $vacationRequest->getLastName();
                    $vacationStartDate = $vacationRequest->getFrom();
                    $vacationEndDate = $vacationRequest->getTo();
                    $vacationDaysCount = $vacationRequest->getTotal_number();

                    $taskService->createApprovedUnpaidVacationAutoTask($userFullName, $vacationStartDate, $vacationEndDate, $vacationDaysCount);

                    break;
            }

        }

        return true;
    }

    public function notifyTimeOffRequestResponse($vacationRequestId)
    {
        try {
            /**
             * @var NotificationsService $notificationService
             * @var VacationRequestDAO $vacationRequestDao
             */
            $notificationService = $this->getServiceLocator()->get('service_notifications');
            $vacationRequestDao = $this->getServiceLocator()->get('dao_user_vacation_request');

            $timeOffRequest = $vacationRequestDao->getVacationById($vacationRequestId);

            $sender = NotificationsService::$vacation;
            $now = date('Y-m-d');

            $message = sprintf(
                TextConstants::VACATION_RESPONSE_UD,
                $timeOffRequest->getUser_id(),
                $timeOffRequest->getFirstName() . ' ' . $timeOffRequest->getLastName(),
                (isset($this->getVacationTypeOptions()[$timeOffRequest->getType()]) ?
                    $this->getVacationTypeOptions()[$timeOffRequest->getType()] :
                    'None'
                ),
                date('d M Y', strtotime($timeOffRequest->getFrom())),
                date('d M Y', strtotime($timeOffRequest->getTo()))
            );

            if (self::VACATION_REQUEST_STATUS_APPROVED == $timeOffRequest->getIs_approved()) {
                $message .= ' Approved';
            } else if (self::VACATION_REQUEST_STATUS_REJECTED == $timeOffRequest->getIs_approved()) {
                $message .= ' Rejected';
            } else {
                $message .= ' Canceled';
            }

            $senderId = $timeOffRequest->getManager_id();
            $userId   = $timeOffRequest->getUser_id();
            $url = '/profile/index/' . $timeOffRequest->getUser_id();

            $notificationData = [
                'recipient' => $userId,
                'sender'    => $sender,
                'sender_id' => $senderId,
                'message'   => $message,
                'show_date' => $now,
                'url'       => $url,
            ];

            return $notificationService->createNotification($notificationData);
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }

    /**
     * @param $vacationRequestId
     * @return bool|string
     */
    public function setVacationReminder($vacationRequestId)
    {
        try {
            /**
             * @var NotificationsService $notificationService
             * @var VacationRequestDAO $vacationRequestDao
             */
            $notificationService = $this->getServiceLocator()->get('service_notifications');
            $vacationRequestDao = $this->getServiceLocator()->get('dao_user_vacation_request');

            $timeOffRequest = $vacationRequestDao->getVacationById($vacationRequestId);

            $sender = NotificationsService::$vacationReminder;
            $now = date('Y-m-d');

            $message = sprintf(
                TextConstants::VACATION_REMINDER_UD,
                $timeOffRequest->getUser_id(),
                $timeOffRequest->getFirstName() . ' ' .$timeOffRequest->getLastName(),
                (isset($this->getVacationTypeOptions()[$timeOffRequest->getType()]) ?
                    $this->getVacationTypeOptions()[$timeOffRequest->getType()] :
                    'None'
                ),
                date('d M Y', strtotime($timeOffRequest->getFrom())),
                date('d M Y', strtotime($timeOffRequest->getTo()))
            );

            $senderId = $timeOffRequest->getManager_id();

            $url = '/profile/index/' . $timeOffRequest->getUser_id();

            $timeDiff = floor((
                    strtotime($timeOffRequest->getFrom()) - strtotime($now)
                ) / 86400
            );

            if ($timeDiff > 5) {
                $showDate = date(
                    'Y-m-d',
                    strtotime("-5 day", strtotime($timeOffRequest->getFrom()))
                );
                $notificationData = [
                    'recipient' => $senderId,
                    'sender'    => $sender,
                    'sender_id' => $senderId,
                    'message'   => $message,
                    'url'       => $url,
                    'show_date' => $showDate
                ];

                return $notificationService->createNotification($notificationData);
            }
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }

    /**
     * @return bool
     */
    public function getLastCalculationDate()
    {
        /* @var $vacationsDao \DDD\Dao\Settings\Vacations */
        $vacationsDao = new VacationsDao($this->getServiceLocator());

        $cronLastRunDate = $vacationsDao->getCronLastRunDate();

        if ($cronLastRunDate) {
            return $cronLastRunDate['vacation_last_run_date'];
        }

        return FALSE;
    }

    /**
     *
     * @return string
     */
    public function getDiffDaysAfterLastCalculation()
    {
        /* @var $vacationsDao \DDD\Dao\Settings\Vacations */
        $vacationsDao = new VacationsDao($this->getServiceLocator());

        $diffDays = $vacationsDao->getDiffDaysBeforeToday();

        return $diffDays;
    }

    /**
     * @param bool $date
     * @return bool|int
     */
    public function setLastCalculationDate($date = FALSE)
    {
        if (!$date) {
            $date = date('Y-m-d');
        }

        /* @var $vacationsDao \DDD\Dao\Settings\Vacations */
        $vacationsDao = new VacationsDao($this->getServiceLocator());

        return $vacationsDao->updateCronLastRunDate($date);
    }
}
