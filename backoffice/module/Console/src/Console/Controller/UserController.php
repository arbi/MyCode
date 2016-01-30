<?php

namespace Console\Controller;

use Library\Controller\ConsoleBase;
use Zend\Db\Sql\Where;
use Zend\Text\Table\Table;
use Library\Constants\EmailAliases;
use Library\Utility\Helper;
use Library\Constants\DomainConstants;
use DDD\Service\User;

/**
 * Class UserController
 * @package Console\Controller
 */
class UserController extends ConsoleBase
{
    private $userId = FALSE;

    public function indexAction()
    {
        $this->initCommonParams($this->getRequest());

        $request = $this->getRequest();

        $action = $request->getParam('mode', 'show');

        if ($this->getRequest()->getParam('id')) {
            $this->userId = $this->getRequest()->getParam('id');
        }

        switch ($action) {
            case 'send-login-details':
                $this->sendLoginDetailsAction();
                break;
            case 'calculate-vacation-days':
                $this->calculateVacationDaysAction();
                break;
            case 'show':
                $this->showAction();
                break;
            case 'update-schedule-inventory':
                $this->updateScheduleInventoryAction();
                break;
            default :
                echo '- type a correct parameter ( send-login-details | calculate-vacation-days | show | update-schedule-inventory)'.PHP_EOL;
                return FALSE;
        }
    }

    public function showAction()
    {
        /**
         * @var \DDD\Service\User $userService
         */
        $userService = $this->getServiceLocator()->get('service_user');

        if($this->userId === 'all'){
            $usersObject = $userService->getUsersList();

            $table = new Table([
                'columnWidths' => array(4, 10, 16, 30, 12, 12, 20)
                ]);

            $table->appendRow(array(
                'id',
                'first name',
                'last name',
                'email',
                'business',
                'personal',
                'position',
            ));

            foreach ($usersObject as $row) {
                $table->appendRow(array(
                    $row->getId(),
                    $row->getFirstName(),
                    $row->getLastName(),
                    $row->getEmail(),
                    $row->getBusiness_phone(),
                    $row->getPersonal_phone(),
                    $row->getPosition()
                ));
            }
            echo $table;

        } elseif ($this->userId) {
            try {
                $userObject = $userService->getUsersById($this->userId);
            } catch (\Exception $e){
                echo '- user with this ID ('.$this->userId.') does NOT EXIST!'
                        .PHP_EOL;
                exit;
            }
            $userMain = $userObject->get('user_main');

            $table = new Table(['columnWidths' => array(16, 30)]);

            $table->appendRow(['id', $userMain->getId()]);
            $table->appendRow(['First name', $userMain->getFirstName()]);
            $table->appendRow(['Last name', $userMain->getLastName()]);
            $table->appendRow(['Email', $userMain->getEmail()]);
            $table->appendRow(['Business phone', $userMain->getBusiness_phone()]);
            $table->appendRow(['Personal phone', $userMain->getPersonal_phone()]);
            $table->appendRow(['Position', $userMain->getPosition()]);

            echo $table;
        } else {
            echo '- type true parameter ( show user (int)ID | show user "all" )'.PHP_EOL;
        }
    }

    public function sendLoginDetailsAction()
    {
        try {
            /**
             * @var \DDD\Service\User $userService
             */
            $userService = $this->getServiceLocator()->get('service_user');
            $mailer = $this->getServiceLocator()->get('Mailer\Email');

            $userData = $userService->getUsersDataForMail($this->userId);

            if (!$userData) {
                $msg = 'Invalid User id: ' . $this->userId . PHP_EOL;

                $this->gr2err('Cannot send login details', [
                    'user_id' => $this->userId,
                    'reason' => 'Invalid user id'
                ]);

                echo $msg;
                exit;
            }

            /**
             * @var \DDD\Service\Textline $textlineService
             */
            $textlineService = $this->getServiceLocator()->get('service_textline');

            $newPassword = $userService->generatePassword();
            $userService->setPassword($this->userId, $newPassword);

            $userName  = $userData['firstname'] . ' ' . $userData['lastname'];
            $userEmail = $userData['email'];

            $mailContent = Helper::evaluateTextline($textlineService->getUniversalTextline(1467), [
                '{{BO_USER_NAME}}' => $userName,
                '{{BO_USER_LOGIN}}' => $userEmail,
                '{{BO_USER_PASSWORD}}' => $newPassword,
            ]);

            $mailSubject = 'Welcome to Ginosi Backoffice';
            $mailSubject = preg_replace('/\s+/', ' ', trim($mailSubject));

            $mailer->send('user-welcome', [
                'to'            => $userEmail,
                'to_name'       => $userName,
                'from_address'  => EmailAliases::FROM_ALERT_MAIL,
                'from_name'     => 'Ginosi Apartments',
                'subject'       => $mailSubject,
                'title'         => 'Welcome to Ginosi Backoffice',
                'content'       => $mailContent,
                'bo_domain'     => DomainConstants::BO_DOMAIN_NAME,
                'textLine1478'  => $textlineService->getUniversalTextline(1478),
                'textLine1479'  => $textlineService->getUniversalTextline(1479)
            ]);

            $msg = 'User Welcome mail sent successfully to '.$mailContent
                    .', email '.$userEmail
                    .', id '.$this->userId;

            $this->gr2info('User Welcome mail sent successfully', [
                'user_id' => $this->userId,
                'user_name' => $userName
            ]);

            $this->outputMessage($msg);

            return TRUE;
        } catch (\Exception $e) {
            $msg = "[error]Error: User Welcome mail wasn't sent: id - " . $this->userId;

            $this->gr2logException($e, "User Welcome mail wasn't sent", [
                'user_id'   => ($this->userId) ? $this->userId : '',
                'user_name' => isset($userName) ? $userName : '',
            ]);

            $this->outputMessage($msg . ' ' . $e->getMessage());

            return FALSE;
        }
    }

    public function calculateVacationDaysAction()
    {
        try {
            /* @var  $vacationsService \DDD\Service\Vacations */
            $vacationsService = $this->getServiceLocator()->get('service_user_vacation');

            $daysCount = $vacationsService->getDiffDaysAfterLastCalculation();
            if ($daysCount <= 0) {
                $lastCalculationDate = $vacationsService->getLastCalculationDate();
                echo "There is no need to launch the process. Last run dates from: $lastCalculationDate".PHP_EOL;
                exit;
            }

            $daysInCurrentYear = date('L') ? 366 : 365;
            $currentDate = strtotime(date('Y-m-d'));

            /**
             * @var \DDD\Service\User $userService
             * @var \DDD\Dao\User\UserManager $userManagerDao
             */
            $userService = $this->getServiceLocator()->get('service_user');
            $userManagerDao = $this->getServiceLocator()->get('dao_user_user_manager');

            for ($i = 0; $i < $daysCount; $i++) {

                $usersObject = $userService->getUsersList(TRUE);

                /* @var $user \DDD\Domain\User\User */
                foreach ($usersObject as $user) {

                    // CHECK CONDITIONS
                    if ($user->getDisabled() == User::USER_DISABLED // disabled
                        || $user->getSystem() == User::USER_TYPE_SYSTEM // system
                        || $user->getVacation_days_per_year() <= 0 // 0 vacations days per yesr
                        || strtotime($user->getStart_date()) > $currentDate // start date > today
                        || ($user->getEndDate() != '0000-00-00' // have and date...
                            && strtotime($user->getEndDate()) < $currentDate) // ...and end date < today
                        ){
                        echo 'To that user there is no need to recalculate the days of vacations'
                            .' (id: ' . $user->getId() . ') '
                            .$user->getFirstName() . ' ' . $user->getLastName()
                            .PHP_EOL;

                        continue;
                    }

                    $calculatedVacationDaysCount = (float)$user->getVacation_days();
                    $accumulatedVacationDaysCount =  $user->getVacation_days_per_year() / $daysInCurrentYear;

                    $employment = $user->getEmployment() * 0.01;
                    if ($employment) {
                        $accumulatedVacationDaysCount *= $employment;
                    }
                    $newCalculatedVacationDaysCount = $calculatedVacationDaysCount + $accumulatedVacationDaysCount ;
                    $userManagerDao->save(
                        ['vacation_days' => $newCalculatedVacationDaysCount],
                        ['id' => $user->getId()]
                    );

                    echo 'Vacation days count calculated successfully for '
                        .' (id: ' . $user->getId() . ') '
                        .$user->getFirstName().' '.$user->getLastName()
                        .' and equal to '.$newCalculatedVacationDaysCount
                        .PHP_EOL;
                }
            }

            $vacationsService->setLastCalculationDate();

        } catch (\Exception $e) {
            if ($e->getPrevious()) {
                return $e->getPrevious()->getMessage();
            }
        }
    }

    public function updateScheduleInventoryAction()
    {
        /**
         * @var \DDD\Service\User\Schedule $scheduleService
         * @var \DDD\Dao\User\Schedule\Inventory $scheduleInventoryDao
         * @var \DDD\Service\User $userService
         * @var \DDD\Dao\User\VacationRequest $vacationsDao
         */
        $vacationsDao = $this->getServiceLocator()->get('dao_user_vacation_request');
        $scheduleInventoryDao = $this->getServiceLocator()->get('dao_user_schedule_inventory');
        $scheduleService = $this->getServiceLocator()->get('service_user_schedule');
        $userService = $this->getServiceLocator()->get('service_user');

        // Get all active human (non-system) users
        $users = $userService->getUsersList(true, false);

        $lastDates = $scheduleInventoryDao->getLastDates();

        $fillingStartDates = [];
        foreach ($lastDates as $row) {
            $fillingStartDates[$row->getUserId()] = date('Y-m-d', strtotime('+1 day', strtotime($row->getDate())));
        }

        echo PHP_EOL;
        foreach ($users as $user) {
            // We don't want this script to run for ages, but in same time we don't want it to be interrupted when we will have
            // lots of users
            set_time_limit(30);
            $this->outputMessage("\e[1;34mFilling inventory for user {$user->getFullName()}...  \e[0m");

            // We want to extend the inventory with this script, not add. Meaning if someone does not have work inventory
            // this is not the right function to add it. Instead it should be done when user's schedule is saved
            if (isset($fillingStartDates[$user->getId()])) {
                $scheduleService->fillInventory($user->getId(), $fillingStartDates[$user->getId()], $user->getReportingOfficeId());
                // Get vacations in filling period
                // We are going to fill inventory for up to 92 days + till the full cycle  of user ends so we take vacations for up to 122 days
                $vacations = $vacationsDao->getUsersApprovedVacationsInRange($user->getId(), $fillingStartDates[$user->getId()], date('Y-m-d', strtotime('+122 days')));

                foreach ($vacations as $vacation) {
                    $vacationWhere = new Where();
                    $vacationWhere
                        ->equalTo('user_id', $user->getId())
                        ->lessThanOrEqualTo('date', $vacation->getTo())
                        ->greaterThanOrEqualTo('date', $vacation->getFrom());

                    $availability = max(1 - $vacation->getTotal_number(), 0);

                    $scheduleInventoryDao->save(
                        [
                            'availability' => $availability,
                            'is_changed' => 1
                        ],
                        $vacationWhere
                    );
                }
            }

            $this->outputMessage("\e[1;32mDone!\e[0m");
        }

        $this->outputMessage(PHP_EOL .
            "\e[1;32mInventories were successfully filled for all active users.\e[0m");
    }
}
