<?php

namespace Console\Controller;

use Library\Controller\ConsoleBase;
use DDD\Service\Task as TaskService;
use DDD\Service\Lock\General as LockService;
use DDD\Service\User\Main as UserMain;
use DDD\Service\User as UserService;

/**
 * Class TaskController
 * @package Console\Controller
 */
class TaskController extends ConsoleBase
{
    public function indexAction()
    {
        $this->initCommonParams($this->getRequest());

        $action = $this->getRequest()->getParam('mode', 'help');

        switch ($action) {
            case 'update-reservation-cleaning-tasks-for-2-days':
                $this->updateReservationCleaningTasksFor2Days();
                break;
            default :
                $this->helpAction();
        }
    }


   public function updateReservationCleaningTasksFor2Days()
   {

       /**
        * @var \DDD\Service\Booking $bookingService
        * @var \DDD\Service\Apartment\General $apartmentService
        * @var \DDD\Service\Task $taskService
        */
       $bookingService      = $this->getServiceLocator()->get('service_booking');
       $apartmentService    = $this->getServiceLocator()->get('service_apartment_general');
       $taskService         = $this->getServiceLocator()->get('service_task');

       $allActiveApartments = $apartmentService->getAllApartmentsIdsAndTimezones();

       foreach ($allActiveApartments as $activeApartment) {
           $datetime = new \DateTime('now');
           $datetime->setTimezone(new \DateTimeZone($activeApartment['timezone']));
           $dateToday             = $datetime->format('Y-m-d');
           $dateTimeToday         = $datetime->format('Y-m-d H:i:s');
           $dateTimeAfter2days    = new \DateTime('now +4days');
           $dateTimeAfter2days    = $dateTimeAfter2days->format('Y-m-d');
           $apartmentCheckOutHour =
               (is_null($activeApartment['check_out']) || !$activeApartment['check_out'])
                   ? '11:00:00' : $activeApartment['check_out'];

           $lastReservation = $bookingService->getLastReservationForApartment(
               $activeApartment['id'],
               $dateToday,
               $dateTimeToday
           );

           $preReservation = $bookingService->getPreviousReservationForApartment(
               $lastReservation['id'],
               $activeApartment['id'],
               $lastReservation['date_from']
           );

           if (!($lastReservation)) {
               continue;
           }
           echo $activeApartment['name'] . "\e[0m\n\r";
           // get future reservations which arrival date is in range of 10 days
           $nextReservations = $bookingService->getNextReservationsForApartment(
               $activeApartment['id'],
               $dateToday,
               $lastReservation,
               $dateTimeAfter2days
           );

           if (count($nextReservations) == 0) {
               //take one next reservation, no matter when it is
               $nextReservations = $bookingService->getNextReservationsForApartment(
                   $activeApartment['id'],
                   $dateToday,
                   $lastReservation
               );
               if (isset($nextReservations[0])) {
                   $lastReservationTask       = $taskService->getReservationAutoCreatedTask($lastReservation['id'], TaskService::TYPE_CLEANING, $activeApartment['id']);
                   $lastReservationDateTo     = $lastReservation['date_to'];
                   $dateTimeApartmentCheckOut = $lastReservationDateTo . ' ' . $apartmentCheckOutHour;

                   if ($dateTimeApartmentCheckOut < $dateTimeToday) {
                       $taskService->createExtraAutoTaskForKeyChange($lastReservation, $nextReservations[0]);
                   } else {
                       if ($lastReservationTask &&
                           ($lastReservationTask['task_status'] == TaskService::STATUS_DONE
                               || $lastReservationTask['task_status'] == TaskService::STATUS_VERIFIED)
                       ) {
                           //if the checkout time is in future, but the key change task is done
                           //also create an extra key fob task on next reservation's check-in day
                           $taskService->createExtraAutoTaskForKeyChange($lastReservation, $nextReservations[0]);
                       } else {
                           $taskService->refreshCleaningTaskSubtasks($preReservation, $lastReservation, $nextReservations[0]);
                       }
                       echo $lastReservation['res_number'] . ' ; ' . $lastReservation['pin'] . '---' . $nextReservations[0]['res_number'] . ',' . $nextReservations[0]['pin'] . "\e[0m\n\r";
                   }
               } else {
                   //there is no next reservation
                   $taskService->refreshCleaningTaskSubtasks($preReservation, $lastReservation, FALSE);
                   echo $lastReservation['res_number'] . ' ; ' . $lastReservation['pin'] . '---' . 'No next reservation' . "\e[0m\n\r";
               }

               continue;
           }
           $i = 0;
           foreach ($nextReservations as $nextReservation) {

               if ($i == 0) {

                   //if the last reservation has already ended
                   //check if last reservation's cleaning task's reservation != next reservation
                   //it means that there's new reservation in the middle
                   //or the old next reservation has been canceled or moved to another apartment
                   //in this case we create an extra KeyFob task to change the key of the door at the day of
                   //the check-in of newly next reservation
                   $lastReservationDateTo     = $lastReservation['date_to'];
                   $dateTimeApartmentCheckOut = $lastReservationDateTo . ' ' . $apartmentCheckOutHour;
                   $taskDao                   = $this->getServiceLocator()->get('dao_task_task');
                   $lastReservationTask       = $taskDao->getReservationAutoCreatedTask($lastReservation['id'], TaskService::TYPE_CLEANING, $activeApartment['id']);

                   if ($dateTimeApartmentCheckOut < $dateTimeToday) {
                       //also create an extra key fob task for next reservation's checkin day
                       $taskService->createExtraAutoTaskForKeyChange($lastReservation, $nextReservation);
                       if ($lastReservationTask
                           && $lastReservationTask['start_date'] > $dateTimeApartmentCheckOut
                           && $lastReservationTask['task_status'] != TaskService::STATUS_DONE
                           && $lastReservationTask['task_status'] != TaskService::STATUS_VERIFIED
                           && $lastReservationTask['priority'] != TaskService::TASK_PRIORITY_IMPORTANT
                       ) {
                           echo 'Last Day date decrease ' . $lastReservationTask['start_date'] . '   ' . $lastReservation['res_number'] . PHP_EOL;
                           //the reservation's has been checkout date has been decreased
                           $taskService->changeReservationsStartDatetoNow($lastReservationTask['id'], $dateTimeToday, date('Y-m-d H:i:s', strtotime($dateTimeToday) + 7200));
                       }
                   } else {
                       if ($lastReservationTask &&
                           ($lastReservationTask['task_status'] == TaskService::STATUS_DONE
                               || $lastReservationTask['task_status'] == TaskService::STATUS_VERIFIED)
                       ) {
                           //if the checkout time is in future, but the key change task is done
                           //also create an extra key fob task for next reservation's checkin day
                           $taskService->createExtraAutoTaskForKeyChange($lastReservation, $nextReservation);
                       } else {
                           $taskService->refreshCleaningTaskSubtasks($preReservation, $lastReservation, $nextReservation);
                       }
                   }
               } else {
                   $taskService->refreshCleaningTaskSubtasks($preReservation, $lastReservation, $nextReservation);
               }
               echo $lastReservation['res_number'] . ',' . $lastReservation['pin'] . '---' . $nextReservation['res_number'] . ',' . $nextReservation['pin'] . "\e[0m\n\r";
               $preReservation  = $lastReservation;
               $lastReservation = $nextReservation;
               $i++;
           }

           if (isset($lastReservation) && $lastReservation) {
               $nextReservations = $bookingService->getNextReservationsForApartment(
                   $activeApartment['id'],
                   $dateToday,
                   $lastReservation
               );
               if (isset($nextReservations[0])) {
                   $taskService->refreshCleaningTaskSubtasks($preReservation, $lastReservation, $nextReservations[0]);
                   echo $lastReservation['res_number'] . ' ;; ' . $lastReservation['pin'] . '---' . $nextReservations[0]['res_number'] . ',' . $nextReservations[0]['pin'] . "\e[0m\n\r";
               }
           }
       }

echo <<<USAGE

 \e[0;37m------------------------------------------------------------------------------------------------------\e[0m
 \e[2;37m                          Starting creation of extra inspection tasks for today                       \e[0m
 \e[0;37m------------------------------------------------------------------------------------------------------\e[0m

USAGE;

        $this->createExtraInspectionTask();
   }


    /**
     * Creates extra inspection tasks for apartments whose next reservation starts
     * today and whose last reservation has finished not today (i.e.: not same day check-in)
     */
    public function createExtraInspectionTask()
    {
        /**
         * @var \DDD\Service\Booking $bookingService
         * @var \DDD\Service\Apartment\General $apartmentService
         * @var \DDD\Service\Task $taskService
         */
        $bookingService      = $this->getServiceLocator()->get('service_booking');
        $apartmentService = $this->getServiceLocator()->get('service_apartment_general');
        $taskService = $this->getServiceLocator()->get('service_task');

        $allActiveApartmentsThatHaveExtraInspectionEnabled = $apartmentService->getAllApartmentsIdsAndTimezonesThatHaveExtraInspectionEnabled();

        foreach ($allActiveApartmentsThatHaveExtraInspectionEnabled as $activeApartment) {
            $datetime = new \DateTime('now');
            $datetime->setTimezone(new \DateTimeZone($activeApartment['timezone']));
            //getting today in particular apartment's timezone
            $dateToday             = $datetime->format('Y-m-d');
            $dateTimeToday         = $datetime->format('Y-m-d H:i:s');

            $lastReservation = $bookingService->getLastReservationForApartment(
                $activeApartment['id'],
                $dateToday,
                $dateTimeToday
            );

            if (!$lastReservation) {
                continue;
            }

            if ($lastReservation['date_to'] == $dateToday) {
               //there are two cases possible here:
               //1. The next reservation starts today - i.e.: this is same day  Same day Check-in
               //2. The next reservation does not start today, but we create this kind of tasks the same day,
               //so for both cases, we skip this iteration
                continue;
            }

            $lastReservationTask       = $taskService->getReservationAutoCreatedTask($lastReservation['id'], TaskService::TYPE_CLEANING, $activeApartment['id']);

            if (!$lastReservationTask) {
                //this should be already created by method above
                continue;
            }

            //getting next reservation for this particular apartment
            //which starts today
            $nextReservations = $bookingService->getNextReservationsForApartment(
                $activeApartment['id'],
                $dateToday,
                $lastReservation,
                $dateToday
            );

            if (!isset($nextReservations[0])) {
                //if there is no next reservation
                //which starts today, skip the iteration
                continue;
            }

            $nextReservation = $nextReservations[0];

            $taskService->createExtraInspectionTask($lastReservation, $nextReservation, $lastReservationTask);

            echo  $activeApartment['name'] . '----' .
                $nextReservation['res_number'] . '----------'.
                $nextReservation['date_from'] .
                "\e[0m\n\r";
        }
        die;
    }

    /**
     * Help
     */
    public function helpAction()
    {
echo <<<USAGE

 \e[0;37m----------------------------------------------------------\e[0m
 \e[2;37m          ✡  Ginosi Backoffice Console (GBC)  ✡          \e[0m
 \e[0;37m----------------------------------------------------------\e[0m

 \e[0;37mCleaning Tasks Automated Creation And Setting\e[0m

    \e[1;33mtask help\e[0m                                            \e[2;33m- show this help\e[0m

    \e[1;33mtask update-reservation-cleaning-tasks-for-2-days\e[0m    \e[2;33m- update cleaning tasks and set housekeeper entry and next guest keys\e[0m

USAGE;
    }
}
